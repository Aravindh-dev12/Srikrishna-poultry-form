(() => {
  const cfg = window.KANDAN_CONFIG || {};
  const state = { inverters: {}, connected: false, lastUpdate: null, history: {} };
  let ws;
  let saveTimer;

  const num = value => {
    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed : 0;
  };

  const canon = name => {
    const match = String(name || '').match(/\d+/);
    return match ? `INV-${Number.parseInt(match[0], 10)}` : String(name || 'INV').toUpperCase();
  };

  const read = (values, patterns) => {
    for (const [key, value] of Object.entries(values || {})) {
      const normalized = key.toLowerCase();
      if (patterns.some(pattern => pattern.test(normalized))) return num(value);
    }
    return 0;
  };

  const parseStrings = values => {
    const strings = [];
    for (const [key, value] of Object.entries(values || {})) {
      const match = key.match(/(?:string|str|mppt)\s*[-_ ]?(\d+).*?(?:current|curr|amp)/i);
      if (match) strings.push({ n: Number.parseInt(match[1], 10), current: num(value) });
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

  const expected = () => Number(cfg.inverterCount) || 10;
  const stringCount = () => Number(cfg.stringCount) || 24;
  const inverterKey = index => `INV-${index}`;

  const allInverters = () => Array.from({ length: expected() }, (_, offset) => {
    const index = offset + 1;
    const key = inverterKey(index);
    return state.inverters[key] || {
      name: key,
      power: 0,
      dcPower: 0,
      daily: 0,
      total: 0,
      temp: 0,
      fault: 0,
      strings: [],
      lastSeen: 0,
      snapshotAt: null
    };
  });

  const persistedInverters = () => Object.values(state.inverters).sort((a, b) => {
    const aNumber = Number.parseInt(a.name.replace(/\D/g, ''), 10) || 0;
    const bNumber = Number.parseInt(b.name.replace(/\D/g, ''), 10) || 0;
    return aNumber - bNumber;
  });

  function merge(inverter, persist = true) {
    if (!inverter?.name) return;
    const key = canon(inverter.name);
    state.inverters[key] = { ...(state.inverters[key] || {}), ...inverter, name: key };

    const stamp = new Date(inverter.snapshotAt || Date.now());
    if (!Number.isNaN(stamp.getTime())) {
      const hour = stamp.getHours();
      state.history[key] ||= new Array(24).fill(null);
      state.history[key][hour] = num(inverter.fault) === 0 && num(inverter.power) > 0.5;
    }

    state.lastUpdate = new Date();
    render();
    if (persist) scheduleSave();
  }

  function statusUI() {
    const hasData = persistedInverters().length > 0;
    const text = state.connected ? 'LIVE' : (hasData ? 'CACHED' : 'OFFLINE');
    const color = state.connected ? '#22c55e' : (hasData ? '#f59e0b' : '#ef3340');

    document.querySelectorAll('[data-ws-status]').forEach(element => { element.textContent = text; });
    document.querySelectorAll('[data-live-dot]').forEach(element => {
      element.style.background = color;
      element.style.boxShadow = `0 0 8px ${color}99`;
    });
  }

  function overview() {
    const tbody = document.getElementById('overviewRows');
    if (!tbody) return;

    tbody.innerHTML = allInverters().map((inverter, index) => {
      const strings = Array.isArray(inverter.strings) ? inverter.strings : [];
      const activeStrings = strings.filter(item => num(item.current) > 0.5).length;
      const fresh = Boolean(inverter.lastSeen) && Date.now() - inverter.lastSeen < 120000;
      const hasData = Boolean(inverter.snapshotAt) || strings.length > 0 || num(inverter.power) !== 0 || num(inverter.total) !== 0;
      const statusClass = fresh ? 'status-online' : (hasData ? 'status-cached' : 'status-offline');
      const statusText = fresh ? 'Online' : (hasData ? 'Cached' : 'No data');
      const fault = String(inverter.fault || '');
      const noError = !fault || fault === '0';

      return `
        <tr>
          <td class="row-number">${index + 1}</td>
          <td class="inverter-name">${inverter.name}</td>
          <td><span class="status-pill ${statusClass}">${statusText}</span></td>
          <td class="metric-green">${num(inverter.power).toFixed(2)} kW</td>
          <td class="metric-blue">${num(inverter.dcPower).toFixed(2)} kW</td>
          <td class="metric-orange">${activeStrings}/${stringCount()}${activeStrings < stringCount() ? ' ▲' : ''}</td>
          <td class="metric-green">${num(inverter.daily).toFixed(2)} kWh</td>
          <td class="metric-blue">${(num(inverter.total) / 1000).toFixed(2)} MWh</td>
          <td class="metric-red">${num(inverter.temp).toFixed(2)} °C</td>
          <td class="error-ok">${noError ? '0 · No Error' : `${fault} · Fault`}</td>
        </tr>`;
    }).join('');
  }

  function matrix() {
    const grid = document.getElementById('stringMatrix');
    if (!grid) return;

    const columns = stringCount();
    const cells = [
      '<div class="matrix-cell matrix-head matrix-corner"></div>',
      ...Array.from({ length: columns }, (_, index) => `<div class="matrix-cell matrix-head">${index + 1}</div>`)
    ];

    allInverters().forEach(inverter => {
      const values = new Map((inverter.strings || []).map(item => [Number(item.n), num(item.current)]));
      cells.push(`<div class="matrix-cell matrix-label">${inverter.name}</div>`);
      for (let number = 1; number <= columns; number += 1) {
        const value = values.has(number) ? values.get(number) : 0;
        const stateClass = value <= 0.5 ? 'matrix-bad' : (value < 5 ? 'matrix-warn' : '');
        cells.push(`<div class="matrix-cell ${stateClass}">${value.toFixed(1)}</div>`);
      }
    });

    grid.style.gridTemplateColumns = `82px repeat(${columns}, minmax(38px, 1fr))`;
    grid.innerHTML = cells.join('');
  }

  function availability() {
    const list = document.getElementById('availabilityList');
    if (!list) return;

    const visibleHours = Array.from({ length: 14 }, (_, index) => index + 5);
    const now = new Date();
    const nowHour = now.getHours();

    list.innerHTML = allInverters().map((inverter, index) => {
      const fresh = Boolean(inverter.lastSeen) && Date.now() - inverter.lastSeen < 120000;
      const running = fresh && num(inverter.power) > 0.5 && num(inverter.fault) === 0;
      const history = state.history[inverter.name] || new Array(24).fill(null);

      if (visibleHours.includes(nowHour) && fresh) history[nowHour] = running;

      const known = visibleHours.map(hour => history[hour]).filter(value => value !== null && value !== undefined);
      const trueCount = known.filter(Boolean).length;
      const falseCount = known.filter(value => value === false).length;
      const uptime = known.length ? (trueCount / known.length) * 100 : 0;
      const label = running ? 'ONLINE' : 'OFFLINE';
      const uptimeClass = running ? 'is-online' : (fresh ? 'is-offline' : '');
      const segments = visibleHours.map(hour => {
        const value = history[hour];
        const cssClass = value === true ? 'seg-on' : (value === false ? 'seg-off' : 'seg-none');
        return `<span class="${cssClass}" style="width:${100 / visibleHours.length}%" title="${String(hour).padStart(2, '0')}:00"></span>`;
      }).join('');

      return `
        <article class="timeline-card">
          <div class="timeline-top">
            <div class="timeline-name-wrap">
              <span class="timeline-name">INVERTER ${index + 1}</span>
              <span class="timeline-state">${label}</span>
            </div>
            <div class="timeline-summary">
              <span><strong>TRUE</strong><em>${trueCount} · ${trueCount.toFixed(1)}h</em></span>
              <span><strong>FALSE</strong><em>${falseCount} · ${falseCount.toFixed(1)}h</em></span>
            </div>
          </div>
          <div class="timeline-uptime ${uptimeClass}">${uptime.toFixed(1)}% uptime</div>
          <div class="timeline-bar">${segments}</div>
          <div class="timeline-axis"><span>05:00</span><span>12:00</span><span>19:00</span></div>
        </article>`;
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
      plant_id: cfg.plantId || 'kandan',
      unit_id: cfg.unitId,
      updated_at: new Date().toISOString(),
      inverters: persistedInverters().map(inverter => ({
        name: inverter.name,
        snapshotAt: inverter.snapshotAt || new Date().toISOString(),
        power: num(inverter.power),
        dcPower: num(inverter.dcPower),
        daily: num(inverter.daily),
        total: num(inverter.total),
        temp: num(inverter.temp),
        fault: inverter.fault || '',
        strings: Array.isArray(inverter.strings) ? inverter.strings : [],
        history: state.history[inverter.name] || []
      }))
    };
  }

  function scheduleSave() {
    window.clearTimeout(saveTimer);
    saveTimer = window.setTimeout(() => {
      if (!cfg.cacheUrl || !persistedInverters().length) return;
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
      if (snapshot.plant_id && cfg.plantId && snapshot.plant_id !== cfg.plantId) return;
      (snapshot.inverters || []).forEach(inverter => {
        const key = canon(inverter.name);
        if (Array.isArray(inverter.history)) state.history[key] = inverter.history.slice(0, 24);
        merge({
          ...inverter,
          name: key,
          strings: Array.isArray(inverter.strings) ? inverter.strings : [],
          lastSeen: 0
        }, false);
      });
    } catch (error) {
      console.warn('[Kandan cache load]', error);
    }
  }

  function matchesPlant(message) {
    if (message.plant_id && cfg.plantId && message.plant_id !== cfg.plantId) return false;
    if (message.unit_id && cfg.unitId && message.unit_id !== cfg.unitId) return false;
    return true;
  }

  function handle(message) {
    if (!matchesPlant(message)) return;

    if (message.type === 'device_list') {
      (message.devices || []).forEach(device => {
        const name = String(device.name || device.device || '');
        if (/inv/i.test(name)) {
          const key = canon(name);
          state.inverters[key] ||= {
            name: key,
            power: 0,
            dcPower: 0,
            daily: 0,
            total: 0,
            temp: 0,
            fault: 0,
            strings: [],
            lastSeen: 0,
            snapshotAt: null
          };
        }
      });
      render();
      window.setTimeout(requestTodayHistory, 500);
      return;
    }

    if (message.type === 'daily_data_result') {
      const device = message.deviceName || message.device || '';
      if (/inv/i.test(device) && Array.isArray(message.data)) {
        message.data.forEach(row => {
          if (row?.values) merge(inverterFrom({
            device,
            values: row.values,
            time: row.time || row.timestamp
          }), false);
        });
        scheduleSave();
      }
      return;
    }

    const name = String(message.device || message.deviceName || '');
    if (/inv/i.test(name) || /inverter/i.test(String(message.task || ''))) merge(inverterFrom(message));
  }

  function selectorPayload(type) {
    return {
      type,
      plant_id: cfg.plantId || 'kandan',
      unit_id: cfg.unitId
    };
  }

  function requestTodayHistory() {
    if (!ws || ws.readyState !== WebSocket.OPEN) return;
    const dateObject = new Date();
    const date = `${dateObject.getFullYear()}-${String(dateObject.getMonth() + 1).padStart(2, '0')}-${String(dateObject.getDate()).padStart(2, '0')}`;
    allInverters().forEach(inverter => {
      ws.send(JSON.stringify({ ...selectorPayload('get_daily_data'), device: inverter.name, date }));
    });
  }

  function connect() {
    if (!cfg.wsUrl) return;
    ws = new WebSocket(cfg.wsUrl);
    ws.onopen = () => {
      state.connected = true;
      statusUI();
      ws.send(JSON.stringify(selectorPayload('subscribe')));
      ws.send(JSON.stringify(selectorPayload('get_devices')));
      window.setTimeout(requestTodayHistory, 1000);
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
      window.setTimeout(connect, 2500);
    };
    ws.onerror = () => {
      state.connected = false;
      statusUI();
    };
  }

  function initMobileNavigation() {
    const menu = document.getElementById('mobileMenu');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const close = () => {
      sidebar?.classList.remove('open');
      backdrop?.classList.remove('open');
    };

    menu?.addEventListener('click', () => {
      sidebar?.classList.toggle('open');
      backdrop?.classList.toggle('open');
    });
    backdrop?.addEventListener('click', close);
    document.querySelectorAll('.nav-link').forEach(link => link.addEventListener('click', close));
  }

  window.addEventListener('DOMContentLoaded', async () => {
    render();
    initMobileNavigation();
    await loadCache();
    connect();
  });
})();
