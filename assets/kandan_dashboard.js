(() => {
  const cfg = window.KANDAN_CONFIG || {};
  const state = { inverters: {}, connected: false, lastUpdate: null };
  let ws;
  let saveTimer = null;

  const num = value => {
    const parsed = parseFloat(value);
    return Number.isFinite(parsed) ? parsed : 0;
  };

  const canon = name => {
    const match = String(name || '').match(/\d+/);
    return match ? `INV-${parseInt(match[0], 10)}` : String(name || 'INV').toUpperCase();
  };

  const read = (values, patterns) => {
    for (const [key, value] of Object.entries(values || {})) {
      if (patterns.some(pattern => pattern.test(key.toLowerCase()))) return num(value);
    }
    return 0;
  };

  const parseStrings = values => {
    const strings = [];
    for (const [key, value] of Object.entries(values || {})) {
      const match = key.match(/(?:string|str|mppt)\s*[-_ ]?(\d+).*?(?:current|curr|amp)/i);
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
    if (!inverter || !inverter.name) return;
    state.inverters[inverter.name] = { ...(state.inverters[inverter.name] || {}), ...inverter };
    state.lastUpdate = new Date();
    render();
    if (persist) scheduleSave();
  }

  const sorted = () => Object.values(state.inverters).sort((a, b) =>
    (parseInt(a.name.replace(/\D/g, ''), 10) || 0) - (parseInt(b.name.replace(/\D/g, ''), 10) || 0)
  );

  const expected = () => Number(cfg.inverterCount) || 10;
  const stringCount = () => Number(cfg.stringCount) || 24;

  function statusUI() {
    document.querySelectorAll('[data-ws-status]').forEach(element => {
      element.textContent = state.connected ? 'LIVE' : (sorted().length ? 'CACHED' : 'OFFLINE');
    });
    document.querySelectorAll('[data-live-dot]').forEach(element => {
      element.style.background = state.connected ? '#2bd66f' : (sorted().length ? '#f59e0b' : '#ef3340');
    });
  }

  function overview() {
    const tbody = document.getElementById('overviewRows');
    if (!tbody) return;
    const rows = sorted();
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="10" class="empty">Waiting for Kandan plant telemetry...</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map((inverter, index) => {
      const strings = Array.isArray(inverter.strings) ? inverter.strings : [];
      const active = strings.filter(string => num(string.current) > 0.5).length;
      const totalStrings = Math.max(strings.length, stringCount());
      const online = inverter.lastSeen && Date.now() - inverter.lastSeen < 120000;
      const fault = String(inverter.fault || '');
      return `<tr><td><b>${index + 1}</b></td><td><b>${inverter.name}</b></td><td><span class="status ${online ? 'online' : 'offline'}">${online ? 'Online' : 'Cached'}</span></td><td class="metric-green">${num(inverter.power).toFixed(2)} kW</td><td class="metric-blue">${num(inverter.dcPower).toFixed(2)} kW</td><td class="metric-orange">${active}/${totalStrings}${active < totalStrings ? ' ▲' : ''}</td><td class="metric-green">${num(inverter.daily).toFixed(2)} kWh</td><td class="metric-blue">${(num(inverter.total) / 1000).toFixed(2)} MWh</td><td class="metric-red">${num(inverter.temp).toFixed(1)} °C</td><td class="${fault && fault !== '0' ? 'metric-red' : 'metric-green'}">${fault && fault !== '0' ? `Fault ${fault}` : 'No Error'}</td></tr>`;
    }).join('');
  }

  function matrix() {
    const grid = document.getElementById('stringMatrix');
    if (!grid) return;
    const rows = sorted();
    const columns = stringCount();
    const cells = ['<div class="matrix-cell matrix-head"></div>', ...Array.from({ length: columns }, (_, index) => `<div class="matrix-cell matrix-head">${index + 1}</div>`)];
    rows.forEach(inverter => {
      const map = new Map((inverter.strings || []).map(string => [Number(string.n), num(string.current)]));
      cells.push(`<div class="matrix-cell matrix-label">${inverter.name}</div>`);
      for (let number = 1; number <= columns; number++) {
        const value = map.has(number) ? map.get(number) : 0;
        cells.push(`<div class="matrix-cell ${value <= 0.5 ? 'matrix-bad' : ''}">${value.toFixed(1)}</div>`);
      }
    });
    if (!rows.length) {
      grid.innerHTML = '<div class="empty">Waiting for string-current telemetry...</div>';
      return;
    }
    grid.style.gridTemplateColumns = `110px repeat(${columns}, minmax(38px, 1fr))`;
    grid.innerHTML = cells.join('');
  }

  function availability() {
    const list = document.getElementById('availabilityList');
    if (!list) return;
    const rows = sorted();
    const all = rows.length ? rows : Array.from({ length: expected() }, (_, index) => ({ name: `INV-${index + 1}`, lastSeen: 0, power: 0, fault: 0 }));
    list.innerHTML = all.map(inverter => {
      const live = inverter.lastSeen && Date.now() - inverter.lastSeen < 120000;
      const running = live && num(inverter.power) > 0.5 && !num(inverter.fault);
      const cssClass = running ? 'seg-on' : live ? 'seg-off' : 'seg-none';
      const label = running ? 'ONLINE' : live ? 'OFFLINE' : 'NO COMMUNICATION';
      return `<div class="card timeline-card"><div class="timeline-name"><span>${inverter.name}</span><span class="timeline-meta">${label}</span></div><div class="timeline-meta">${running ? '100.0' : '0.0'}% uptime</div><div class="timeline-bar" style="margin-top:10px"><div class="${cssClass}" style="width:100%"></div></div></div>`;
    }).join('');
  }

  function render() {
    statusUI();
    overview();
    matrix();
    availability();
  }

  function snapshotPayload() {
    return {
      unit_id: cfg.unitId,
      updated_at: new Date().toISOString(),
      inverters: sorted().map(inverter => ({
        name: inverter.name,
        snapshotAt: inverter.snapshotAt || new Date().toISOString(),
        power: num(inverter.power),
        dcPower: num(inverter.dcPower),
        daily: num(inverter.daily),
        total: num(inverter.total),
        temp: num(inverter.temp),
        fault: inverter.fault || '',
        strings: Array.isArray(inverter.strings) ? inverter.strings : []
      }))
    };
  }

  function scheduleSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
      if (!cfg.cacheUrl || !sorted().length) return;
      fetch(cfg.cacheUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(snapshotPayload()),
        keepalive: true
      }).catch(error => console.warn('[Kandan cache save]', error));
    }, 1500);
  }

  async function loadCache() {
    if (!cfg.cacheUrl) return;
    try {
      const response = await fetch(cfg.cacheUrl, { cache: 'no-store' });
      const snapshot = await response.json();
      (snapshot.inverters || []).forEach(inverter => {
        merge({
          ...inverter,
          name: canon(inverter.name),
          strings: Array.isArray(inverter.strings) ? inverter.strings : [],
          lastSeen: 0
        }, false);
      });
    } catch (error) {
      console.warn('[Kandan cache load]', error);
    }
  }

  function handle(message) {
    if (message.unit_id && cfg.unitId && message.unit_id !== cfg.unitId) return;
    if (message.type === 'device_list') {
      (message.devices || []).forEach(device => {
        const name = String(device.name || device.device || '');
        if (/inv/i.test(name)) {
          const key = canon(name);
          state.inverters[key] ||= { name: key, power: 0, dcPower: 0, daily: 0, total: 0, temp: 0, fault: 0, strings: [], lastSeen: 0 };
        }
      });
      render();
      return;
    }
    if (message.type === 'daily_data_result') {
      const last = Array.isArray(message.data) ? message.data.at(-1) : null;
      if (last?.values && /inv/i.test(message.deviceName || message.device || last.device || '')) {
        merge(inverterFrom({ device: message.deviceName || message.device || last.device, values: last.values, time: last.time || last.timestamp }));
      }
      return;
    }
    const name = String(message.device || message.deviceName || '');
    if (/inv/i.test(name) || /inverter/i.test(String(message.task || ''))) merge(inverterFrom(message));
  }

  function connect() {
    if (!cfg.wsUrl) return;
    ws = new WebSocket(cfg.wsUrl);
    ws.onopen = () => {
      state.connected = true;
      statusUI();
      ws.send(JSON.stringify({ type: 'subscribe', unit_id: cfg.unitId }));
      ws.send(JSON.stringify({ type: 'get_devices', unit_id: cfg.unitId }));
    };
    ws.onmessage = event => {
      try {
        handle(JSON.parse(event.data));
      } catch (error) {
        console.error('[Kandan WebSocket]', error);
      }
    };
    ws.onclose = () => {
      state.connected = false;
      statusUI();
      setTimeout(connect, 2000);
    };
    ws.onerror = () => {
      state.connected = false;
      statusUI();
    };
  }

  window.addEventListener('DOMContentLoaded', async () => {
    render();
    await loadCache();
    connect();
    document.getElementById('mobileMenu')?.addEventListener('click', () => document.querySelector('.sidebar')?.classList.toggle('open'));
  });
})();
