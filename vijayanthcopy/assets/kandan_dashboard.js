(() => {
  const cfg = window.KANDAN_CONFIG || {};
  const state = { inverters: {}, connected: false, lastUpdate: null };
  let ws;

  const num = v => { const n = parseFloat(v); return Number.isFinite(n) ? n : 0; };
  const canon = name => { const m = String(name || '').match(/\d+/); return m ? `INV-${parseInt(m[0],10)}` : String(name || 'INV').toUpperCase(); };
  const read = (values, patterns) => {
    for (const [key, value] of Object.entries(values || {})) {
      if (patterns.some(rx => rx.test(key.toLowerCase()))) return num(value);
    }
    return 0;
  };
  const parseStrings = values => {
    const out = [];
    for (const [key, value] of Object.entries(values || {})) {
      const m = key.match(/(?:string|str|mppt)\s*[-_ ]?(\d+).*?(?:current|curr|amp)/i);
      if (m) out.push({ n: parseInt(m[1],10), current: num(value) });
    }
    out.sort((a,b)=>a.n-b.n);
    return out;
  };
  const inverterFrom = (message) => {
    const v = message.values || {};
    const strings = parseStrings(v);
    const power = read(v, [/total active power/, /active.*power/, /ac.*power/]);
    const dcPower = read(v, [/total dc power/, /dc.*power/]);
    const daily = read(v, [/daily power yields/, /today.*gen/, /day.*energy/]);
    const total = read(v, [/total power yields precise/, /total power yields/, /life.*energy/, /total.*gen/]);
    const temp = read(v, [/internal temperature/, /ambient.*temp/, /temperature/]);
    const fault = read(v, [/fault\s*code/, /error\s*code/]);
    return { name: canon(message.device || message.deviceName), power, dcPower, daily, total, temp, fault, strings, lastSeen: Date.now() };
  };
  const merge = inv => { state.inverters[inv.name] = { ...(state.inverters[inv.name] || {}), ...inv }; state.lastUpdate = new Date(); render(); };
  const sorted = () => Object.values(state.inverters).sort((a,b)=>(parseInt(a.name.replace(/\D/g,''))||0)-(parseInt(b.name.replace(/\D/g,''))||0));
  const expected = () => cfg.inverterCount || 10;

  function statusUI() {
    document.querySelectorAll('[data-ws-status]').forEach(el => el.textContent = state.connected ? 'LIVE' : 'OFFLINE');
    document.querySelectorAll('[data-live-dot]').forEach(el => el.style.background = state.connected ? '#2bd66f' : '#ef3340');
  }

  function overview() {
    const tbody = document.getElementById('overviewRows'); if (!tbody) return;
    const rows = sorted();
    if (!rows.length) { tbody.innerHTML = '<tr><td colspan="10" class="empty">Waiting for Kandan plant telemetry...</td></tr>'; return; }
    tbody.innerHTML = rows.map((inv,i) => {
      const active = inv.strings.filter(s=>s.current>0.5).length;
      const totalStrings = Math.max(inv.strings.length, 24);
      const online = Date.now()-inv.lastSeen < 120000;
      return `<tr><td><b>${i+1}</b></td><td><b>${inv.name}</b></td><td><span class="status ${online?'online':'offline'}">${online?'Online':'Offline'}</span></td><td class="metric-green">${inv.power.toFixed(2)} kW</td><td class="metric-blue">${inv.dcPower.toFixed(2)} kW</td><td class="metric-orange">${active}/${totalStrings}${active<totalStrings?' ▲':''}</td><td class="metric-green">${inv.daily.toFixed(2)} kWh</td><td class="metric-blue">${(inv.total/1000).toFixed(2)} MWh</td><td class="metric-red">${inv.temp.toFixed(1)} °C</td><td class="${inv.fault?'metric-red':'metric-green'}">${inv.fault ? `Fault ${inv.fault}` : 'No Error'}</td></tr>`;
    }).join('');
  }

  function matrix() {
    const grid = document.getElementById('stringMatrix'); if (!grid) return;
    const rows = sorted(); const cols = 24;
    const cells = [`<div class="matrix-cell matrix-head"></div>`, ...Array.from({length:cols},(_,i)=>`<div class="matrix-cell matrix-head">${i+1}</div>`)]
    rows.forEach(inv => {
      const map = new Map(inv.strings.map(s=>[s.n,s.current]));
      cells.push(`<div class="matrix-cell matrix-label">${inv.name}</div>`);
      for(let n=1;n<=cols;n++){ const value=map.has(n)?map.get(n):0; cells.push(`<div class="matrix-cell ${value<=0.5?'matrix-bad':''}">${value.toFixed(1)}</div>`); }
    });
    grid.style.gridTemplateColumns = `110px repeat(${cols}, minmax(38px,1fr))`; grid.innerHTML = cells.join('');
  }

  function availability() {
    const list = document.getElementById('availabilityList'); if (!list) return;
    const rows = sorted();
    const all = rows.length ? rows : Array.from({length:expected()},(_,i)=>({name:`INV-${i+1}`,lastSeen:0,power:0,fault:0}));
    list.innerHTML = all.map(inv => {
      const online = inv.lastSeen && Date.now()-inv.lastSeen<120000;
      const running = online && inv.power>0.5 && !inv.fault;
      const cls = running?'seg-on':online?'seg-off':'seg-none';
      const uptime = running?'100.0':online?'0.0':'0.0';
      return `<div class="card timeline-card"><div class="timeline-name"><span>${inv.name}</span><span class="timeline-meta">${running?'ONLINE':online?'OFFLINE':'NO DATA'}</span></div><div class="timeline-meta">${uptime}% uptime</div><div class="timeline-bar" style="margin-top:10px"><div class="${cls}" style="width:${running?'100':online?'18':'0'}%"></div>${online&&!running?'<div class="seg-on" style="width:82%"></div>':''}</div></div>`;
    }).join('');
  }

  function render(){ statusUI(); overview(); matrix(); availability(); }

  function handle(message){
    if (message.unit_id && cfg.unitId && message.unit_id !== cfg.unitId) return;
    if (message.type === 'device_list') {
      (message.devices || []).forEach(d => { const name=String(d.name||d.device||''); if(/inv/i.test(name)){ const key=canon(name); state.inverters[key] ||= {name:key,power:0,dcPower:0,daily:0,total:0,temp:0,fault:0,strings:[],lastSeen:0}; }}); render(); return;
    }
    if (message.type === 'daily_data_result') {
      const last = Array.isArray(message.data) ? message.data.at(-1) : null;
      if (last?.values && /inv/i.test(message.deviceName||message.device||last.device||'')) merge(inverterFrom({device:message.deviceName||message.device||last.device,values:last.values}));
      return;
    }
    const name = String(message.device || message.deviceName || '');
    if (/inv/i.test(name) || /inverter/i.test(String(message.task||''))) merge(inverterFrom(message));
  }

  function connect(){
    if (!cfg.wsUrl) return;
    ws = new WebSocket(cfg.wsUrl);
    ws.onopen = () => { state.connected=true; statusUI(); ws.send(JSON.stringify({type:'subscribe',unit_id:cfg.unitId})); ws.send(JSON.stringify({type:'get_devices',unit_id:cfg.unitId})); };
    ws.onmessage = e => { try { handle(JSON.parse(e.data)); } catch(err){ console.error('[Kandan]',err); } };
    ws.onclose = () => { state.connected=false; statusUI(); setTimeout(connect,2000); };
    ws.onerror = () => { state.connected=false; statusUI(); };
  }
  window.addEventListener('DOMContentLoaded',()=>{ render(); connect(); document.getElementById('mobileMenu')?.addEventListener('click',()=>document.querySelector('.sidebar')?.classList.toggle('open')); });
})();