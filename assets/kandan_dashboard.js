(() => {
  const cfg = window.KANDAN_CONFIG || {};
  const state = { inverters: {}, connected: false, lastUpdate: null, history: {} };
  let ws;
  let saveTimer = null;

  const num = value => { const parsed = parseFloat(value); return Number.isFinite(parsed) ? parsed : 0; };
  const canon = name => { const match = String(name || '').match(/\d+/); return match ? `INV-${parseInt(match[0], 10)}` : String(name || 'INV').toUpperCase(); };
  const read = (values, patterns) => {
    for (const [key, value] of Object.entries(values || {})) {
      if (patterns.some(pattern => pattern.test(key.toLowerCase()))) return num(value);
    }
    return 0;
  };
  const parseStrings = values => {
    const strings = [];
    for (const [key, value] of Object.entries(values || {})) {
      const match = key.match(/(?:string|str)\s*[-_ ]?(\d+).*?(?:current|curr|amp)/i);
      if (match) strings.push({ n: parseInt(match[1], 10), current: num(value) });
    }
    strings.sort((a, b) => a.n - b.n);
    return strings;
  };
  const inverterFrom = message => {
    const values = message.values || {};
    return {
      name: canon(message.device || message.deviceName),
      power: read(values, [/total active power/, /active.*power/, /ac.*power/]),
      dcPower: read(values, [/total dc power/, /dc.*power/]),
      daily: read(values, [/daily power yields/, /today.*gen/, /day.*energy/]),
      total: read(values, [/total power yields precise/, /total power yields/, /life.*energy/, /total.*gen/]),
      temp: read(values, [/internal temperature/, /ambient.*temp/, /temperature/]),
      fault: read(values, [/fault\s*code/, /error\s*code/]),
      strings: parseStrings(values),
      lastSeen: Date.now(),
      snapshotAt: message.time || message.timestamp || new Date().toISOString()
    };
  };

  function merge(inverter, persist = true) {
    if (!inverter?.name) return;
    state.inverters[inverter.name] = { ...(state.inverters[inverter.name] || {}), ...inverter };
    const hour = new Date(inverter.snapshotAt || Date.now()).getHours();
    state.history[inverter.name] ||= new Array(24).fill(null);
    state.history[inverter.name][hour] = num(inverter.fault) === 0 && num(inverter.power) > .5;
    state.lastUpdate = new Date();
    render();
    if (persist) scheduleSave();
  }

  const sorted = () => Object.values(state.inverters).sort((a, b) => (parseInt(a.name.replace(/\D/g, ''), 10) || 0) - (parseInt(b.name.replace(/\D/g, ''), 10) || 0));
  const expected = () => Number(cfg.inverterCount) || 10;
  const stringCount = () => Number(cfg.stringCount) || 24;

  function statusUI() {
    document.querySelectorAll('[data-ws-status]').forEach(el => { el.textContent = state.connected ? 'LIVE' : (sorted().length ? 'CACHED' : 'OFFLINE'); });
    document.querySelectorAll('[data-live-dot]').forEach(el => { el.style.background = state.connected ? '#22c55e' : (sorted().length ? '#f59e0b' : '#ef3340'); });
  }

  function overview() {
    const tbody = document.getElementById('overviewRows'); if (!tbody) return;
    const rows = sorted();
    if (!rows.length) { tbody.innerHTML = '<tr><td colspan="10" class="empty">Waiting for Kandan plant telemetry...</td></tr>'; return; }
    tbody.innerHTML = rows.map((inverter, index) => {
      const strings = Array.isArray(inverter.strings) ? inverter.strings : [];
      const active = strings.filter(s => num(s.current) > .5).length;
      const totalStrings = Math.max(strings.length, stringCount());
      const fresh = inverter.lastSeen && Date.now() - inverter.lastSeen < 120000;
      const statusClass = fresh ? 'online' : 'cached';
      const statusText = fresh ? 'Online' : 'Cached';
      const fault = String(inverter.fault || '');
      return `<tr><td><b>${index + 1}</b></td><td><b>${inverter.name}</b></td><td><span class="status ${statusClass}">${statusText}</span></td><td class="metric-green">${num(inverter.power).toFixed(2)} kW</td><td class="metric-blue">${num(inverter.dcPower).toFixed(2)} kW</td><td class="metric-orange">${active}/${totalStrings}${active < totalStrings ? ' ▲' : ''}</td><td class="metric-green">${num(inverter.daily).toFixed(2)} kWh</td><td class="metric-blue">${(num(inverter.total) / 1000).toFixed(2)} MWh</td><td class="metric-red">${num(inverter.temp).toFixed(1)} °C</td><td class="${fault && fault !== '0' ? 'metric-red' : 'metric-green'}">${fault && fault !== '0' ? `Fault ${fault}` : 'No Error'}</td></tr>`;
    }).join('');
  }

  function matrix() {
    const grid = document.getElementById('stringMatrix'); if (!grid) return;
    const rows = sorted(); const columns = stringCount();
    if (!rows.length) { grid.innerHTML = '<div class="empty">Waiting for string-current telemetry...</div>'; return; }
    const cells = ['<div class="matrix-cell matrix-head"></div>', ...Array.from({ length: columns }, (_, i) => `<div class="matrix-cell matrix-head">${i + 1}</div>`)];
    rows.forEach(inverter => {
      const map = new Map((inverter.strings || []).map(s => [Number(s.n), num(s.current)]));
      cells.push(`<div class="matrix-cell matrix-label">${inverter.name}</div>`);
      for (let n = 1; n <= columns; n++) {
        const value = map.has(n) ? map.get(n) : 0;
        const cls = value <= .5 ? 'matrix-bad' : value < 5 ? 'matrix-warn' : '';
        cells.push(`<div class="matrix-cell ${cls}">${value.toFixed(1)}</div>`);
      }
    });
    grid.style.gridTemplateColumns = `105px repeat(${columns}, minmax(37px,1fr))`;
    grid.innerHTML = cells.join('');
  }

  function availability() {
    const list = document.getElementById('availabilityList'); if (!list) return;
    const rows = sorted();
    const all = rows.length ? rows : Array.from({ length: expected() }, (_, i) => ({ name: `INV-${i + 1}`, lastSeen: 0, power: 0, fault: 0 }));
    const nowHour = new Date().getHours();
    list.innerHTML = all.map(inverter => {
      const fresh = inverter.lastSeen && Date.now() - inverter.lastSeen < 120000;
      const running = fresh && num(inverter.power) > .5 && !num(inverter.fault);
      const hist = state.history[inverter.name] || new Array(24).fill(null);
      const known = hist.slice(0, nowHour + 1).filter(v => v !== null);
      const onlineHours = known.filter(Boolean).length;
      const uptime = known.length ? (onlineHours / known.length * 100) : (running ? 100 : 0);
      const segments = Array.from({ length: 24 }, (_, hour) => {
        const value = hist[hour];
        const cls = value === true ? 'seg-on' : value === false ? 'seg-off' : 'seg-none';
        return `<span class="${cls}" style="width:${100 / 24}%" title="${String(hour).padStart(2,'0')}:00"></span>`;
      }).join('');
      const label = running ? 'ONLINE' : fresh ? 'OFFLINE' : 'NO COMMUNICATION';
      return `<article class="card timeline-card"><div class="timeline-name"><span>${inverter.name}</span><span class="timeline-meta">${label}</span></div><div class="timeline-meta">${uptime.toFixed(1)}% uptime</div><div class="timeline-bar">${segments}</div><div class="timeline-stats"><span>00:00</span><span>12:00</span><span>23:00</span></div></article>`;
    }).join('');
    const date = document.getElementById('availabilityDate');
    if (date) date.textContent = `${new Date().toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})} · plant_id: ${cfg.plantId || 'kandan'}`;
  }

  function render() { statusUI(); overview(); matrix(); availability(); }

  function snapshotPayload() {
    return {
      plant_id: cfg.plantId || 'kandan', unit_id: cfg.unitId, updated_at: new Date().toISOString(),
      inverters: sorted().map(i => ({ name:i.name, snapshotAt:i.snapshotAt || new Date().toISOString(), power:num(i.power), dcPower:num(i.dcPower), daily:num(i.daily), total:num(i.total), temp:num(i.temp), fault:i.fault || '', strings:Array.isArray(i.strings)?i.strings:[], history:state.history[i.name] || [] }))
    };
  }
  function scheduleSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
      if (!cfg.cacheUrl || !sorted().length) return;
      fetch(cfg.cacheUrl, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(snapshotPayload()), keepalive:true }).catch(err => console.warn('[Kandan cache save]', err));
    }, 1500);
  }
  async function loadCache() {
    if (!cfg.cacheUrl) return;
    try {
      const response = await fetch(cfg.cacheUrl, { cache:'no-store' });
      const snapshot = await response.json();
      if (snapshot.plant_id && cfg.plantId && snapshot.plant_id !== cfg.plantId) return;
      (snapshot.inverters || []).forEach(i => { if (Array.isArray(i.history)) state.history[canon(i.name)] = i.history.slice(0,24); merge({ ...i, name:canon(i.name), strings:Array.isArray(i.strings)?i.strings:[], lastSeen:0 }, false); });
    } catch (err) { console.warn('[Kandan cache load]', err); }
  }

  function handle(message) {
    if (message.unit_id && cfg.unitId && message.unit_id !== cfg.unitId) return;
    if (message.type === 'device_list') {
      (message.devices || []).forEach(device => { const name=String(device.name || device.device || ''); if (/inv/i.test(name)) { const key=canon(name); state.inverters[key] ||= {name:key,power:0,dcPower:0,daily:0,total:0,temp:0,fault:0,strings:[],lastSeen:0}; } });
      render(); return;
    }
    if (message.type === 'daily_data_result') {
      const device = message.deviceName || message.device || '';
      if (/inv/i.test(device) && Array.isArray(message.data)) {
        message.data.forEach(row => { if (row?.values) merge(inverterFrom({device,values:row.values,time:row.time || row.timestamp}), false); });
        scheduleSave();
      }
      return;
    }
    const name = String(message.device || message.deviceName || '');
    if (/inv/i.test(name) || /inverter/i.test(String(message.task || ''))) merge(inverterFrom(message));
  }

  function requestTodayHistory() {
    if (!ws || ws.readyState !== WebSocket.OPEN) return;
    const d = new Date(); const date = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    sorted().forEach(i => ws.send(JSON.stringify({type:'get_daily_data',unit_id:cfg.unitId,device:i.name,date})));
  }
  function connect() {
    if (!cfg.wsUrl) return;
    ws = new WebSocket(cfg.wsUrl);
    ws.onopen = () => { state.connected=true; statusUI(); ws.send(JSON.stringify({type:'subscribe',unit_id:cfg.unitId})); ws.send(JSON.stringify({type:'get_devices',unit_id:cfg.unitId})); setTimeout(requestTodayHistory,800); };
    ws.onmessage = event => { try { handle(JSON.parse(event.data)); } catch (err) { console.error('[Kandan WebSocket]', err); } };
    ws.onclose = () => { state.connected=false; statusUI(); setTimeout(connect,2000); };
    ws.onerror = () => { state.connected=false; statusUI(); };
  }

  function initMobileNav() {
    const menu = document.getElementById('mobileMenu'); const sidebar=document.querySelector('.sidebar'); const backdrop=document.getElementById('sidebarBackdrop');
    const close = () => { sidebar?.classList.remove('open'); backdrop?.classList.remove('open'); };
    menu?.addEventListener('click', () => { sidebar?.classList.toggle('open'); backdrop?.classList.toggle('open'); });
    backdrop?.addEventListener('click', close);
    document.querySelectorAll('.nav-link').forEach(link => link.addEventListener('click', close));
  }

  window.addEventListener('DOMContentLoaded', async () => { render(); initMobileNav(); await loadCache(); connect(); });
})();
