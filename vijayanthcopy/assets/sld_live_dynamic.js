(function () {
    if (!/sld\.php$/i.test(window.location.pathname)) return;

    const plantId = window.SIGNED_PLANT_ID || new URLSearchParams(window.location.search).get('plant') || '';
    const config = (window.SIGNED_PLANT_CONFIG && window.SIGNED_PLANT_CONFIG[plantId]) || {};
    const wsUnitId = config.ws_unit_id || plantId;
    const wsUrl = config.ws_url || 'wss://vinobasolar.scadahub.in:5001';
    const state = {
        devicesFromWs: false,
        inverters: new Map(),
        gridKw: null,
        connected: false
    };

    function canonicalName(name) {
        const raw = String(name || '');
        const match = raw.match(/\d+/);
        return match ? `INVERTER${parseInt(match[0], 10)}` : raw.toUpperCase().replace(/\s+/g, '');
    }

    function labelName(name) {
        const match = String(name || '').match(/\d+/);
        return match ? `INV-${String(parseInt(match[0], 10)).padStart(2, '0')}` : String(name || 'INV');
    }

    function sortedInverters() {
        return Array.from(state.inverters.values()).sort((a, b) => {
            const na = parseInt(a.key.replace(/\D+/g, ''), 10) || 0;
            const nb = parseInt(b.key.replace(/\D+/g, ''), 10) || 0;
            return na - nb;
        });
    }

    function readNumber(value) {
        if (value === null || value === undefined || value === '') return null;
        const n = parseFloat(value);
        return Number.isFinite(n) ? n : null;
    }

    function firstNumber(values, patterns, reject = []) {
        for (const key in values || {}) {
            const kl = key.toLowerCase();
            if (reject.some(rx => rx.test(kl))) continue;
            if (!patterns.some(rx => rx.test(kl))) continue;
            const n = readNumber(values[key]);
            if (n !== null) return n;
        }
        return null;
    }

    function setFallbackInverters() {
        const count = parseInt(config.inverter_count || 0, 10) || 0;
        for (let i = 1; i <= count; i += 1) {
            const key = `INVERTER${i}`;
            if (!state.inverters.has(key)) {
                state.inverters.set(key, { key, name: key, power: 0, dailyGen: 0, strings: null, live: false });
            }
        }
    }

    function applyDeviceList(devices) {
        const inverterDevices = (Array.isArray(devices) ? devices : [])
            .map(device => device.name || device.device || device)
            .filter(name => /inv|inverter/i.test(String(name || '')));

        if (!inverterDevices.length) return;
        state.devicesFromWs = true;
        const next = new Map();
        inverterDevices.forEach(name => {
            const key = canonicalName(name);
            next.set(key, state.inverters.get(key) || { key, name, power: 0, dailyGen: 0, strings: null, live: false });
            next.get(key).name = name;
        });
        state.inverters = next;
        render();
    }

    function updateInverter(name, values) {
        const key = canonicalName(name);
        if (!key) return;
        if (!state.inverters.has(key)) state.inverters.set(key, { key, name, power: 0, dailyGen: 0, strings: null, live: false });
        const row = state.inverters.get(key);
        row.name = name || row.name;
        const power = firstNumber(values, [/^total active power$/, /active.*power/, /ac.*power/], [/reactive/, /apparent/, /nominal/, /3.phase/]);
        const dailyGen = firstNumber(values, [/^daily power yields$/, /daily.*yield/, /day.*energy/]);
        let activeStrings = 0;
        let totalStrings = 0;
        Object.keys(values || {}).forEach(k => {
            if (!/^string\s*\d+\s*current$/i.test(k)) return;
            totalStrings += 1;
            if ((parseFloat(values[k]) || 0) > 0.5) activeStrings += 1;
        });
        if (power !== null) row.power = Math.abs(power) > 10000 ? power / 1000 : power;
        if (dailyGen !== null) row.dailyGen = dailyGen;
        row.strings = totalStrings ? `${activeStrings}/${totalStrings}` : row.strings;
        row.live = true;
        render();
    }

    function updateGrid(values) {
        const p = firstNumber(values, [/3\.phase\.active\.power/, /three\.phase\.active\.power/, /^active power$/]);
        if (p !== null) state.gridKw = Math.abs(p) > 10000 ? p / 1000 : p;
        render();
    }

    function ensurePanel() {
        let host = document.getElementById('liveSldHost');
        if (host) return host;
        const staticCanvas = document.querySelector('.sld-svg')?.closest('.bg-white.border');
        const parentCard = staticCanvas?.closest('.bg-white.rounded-xl');
        if (!parentCard) return null;
        const oldCanvas = staticCanvas;
        host = document.createElement('div');
        host.id = 'liveSldHost';
        oldCanvas?.replaceWith(host);
        return host;
    }

    function injectStyle() {
        if (document.getElementById('liveSldStyle')) return;
        const style = document.createElement('style');
        style.id = 'liveSldStyle';
        style.textContent = `
            .live-sld-shell{border:1px solid #cbd5e1;border-radius:16px;background:#f8fafc;padding:18px;overflow:auto;}
            .live-sld-stage{min-width:980px;background:#fff;border:1px solid #dbe3ec;border-radius:14px;padding:18px;box-shadow:inset 0 1px 0 rgba(255,255,255,.8);}
            .live-sld-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;}
            .live-sld-title h4{font-size:14px;font-weight:900;color:#111827;letter-spacing:.04em;text-transform:uppercase;}
            .live-sld-pill{font-size:11px;font-weight:800;border-radius:999px;padding:5px 10px;background:#e0f2fe;color:#075985;border:1px solid #bae6fd;}
            .live-sld-flow{display:grid;grid-template-columns:170px 96px 180px 1fr 150px;align-items:center;gap:14px;}
            .live-sld-node{border:1px solid #cbd5e1;border-radius:14px;background:#fff;padding:14px;text-align:center;min-height:92px;display:flex;flex-direction:column;justify-content:center;box-shadow:0 3px 10px rgba(15,23,42,.05);}
            .live-sld-node strong{font-size:13px;color:#111827;}
            .live-sld-node span{font-size:11px;color:#475569;font-weight:700;margin-top:5px;}
            .live-sld-line{height:4px;background:#111827;border-radius:999px;position:relative;}
            .live-sld-line:after{content:'';position:absolute;right:-2px;top:50%;transform:translateY(-50%);border-left:9px solid #111827;border-top:6px solid transparent;border-bottom:6px solid transparent;}
            .live-sld-bus{border:2px solid #111827;border-radius:14px;padding:14px;background:#f8fafc;}
            .live-sld-bus-title{text-align:center;font-size:11px;font-weight:900;color:#334155;letter-spacing:.08em;margin-bottom:12px;text-transform:uppercase;}
            .live-sld-inverters{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;}
            .live-sld-inv{border:1px solid #cbd5e1;border-radius:12px;background:#fff;padding:12px;text-align:center;position:relative;}
            .live-sld-inv.live{border-color:#16a34a;background:#f0fdf4;}
            .live-sld-inv.off{border-color:#ef4444;background:#fef2f2;}
            .live-sld-inv:before{content:'';position:absolute;top:-16px;left:50%;width:2px;height:16px;background:#111827;}
            .live-sld-inv b{display:block;font-size:12px;color:#111827;}
            .live-sld-inv .kw{font-size:16px;font-weight:900;color:#111827;margin-top:6px;}
            .live-sld-inv .meta{font-size:10px;font-weight:800;color:#64748b;margin-top:4px;}
            @media(max-width:900px){.live-sld-stage{min-width:760px}.live-sld-flow{grid-template-columns:150px 70px 150px 1fr 120px;gap:10px}.live-sld-node{padding:10px}}
        `;
        document.head.appendChild(style);
    }

    function render() {
        injectStyle();
        const host = ensurePanel();
        if (!host) return;
        if (!state.inverters.size) setFallbackInverters();
        const inverters = sortedInverters();
        const totalInvPower = inverters.reduce((sum, inv) => sum + (inv.power || 0), 0);
        const totalInvGen = inverters.reduce((sum, inv) => sum + (inv.dailyGen || 0), 0);
        const sourceLabel = state.devicesFromWs ? 'Live WS device list' : 'Config fallback until WS devices arrive';
        const invHtml = inverters.map(inv => {
            const on = (inv.power || 0) > 0.5;
            return `<div class="live-sld-inv ${on ? 'live' : 'off'}">
                <b>${labelName(inv.name || inv.key)}</b>
                <div class="kw">${Number(inv.power || 0).toFixed(1)} kW</div>
                <div class="meta">${Number(inv.dailyGen || 0).toFixed(1)} kWh${inv.strings ? ` · ${inv.strings} STR` : ''}</div>
            </div>`;
        }).join('');

        host.innerHTML = `<div class="live-sld-shell">
            <div class="live-sld-stage">
                <div class="live-sld-title">
                    <h4>${config.name || 'Solar Plant'} · Live Single Line Diagram</h4>
                    <span class="live-sld-pill">${sourceLabel} · ${inverters.length} inverter${inverters.length === 1 ? '' : 's'}</span>
                </div>
                <div class="live-sld-flow">
                    <div class="live-sld-node"><strong>EB Line</strong><span>33kV / 50Hz</span></div>
                    <div class="live-sld-line"></div>
                    <div class="live-sld-node"><strong>HT VCB + Transformer</strong><span>${config.capacity || '--'} MW plant</span></div>
                    <div class="live-sld-bus">
                        <div class="live-sld-bus-title">800V LT Bus · Inverters from WebSocket</div>
                        <div class="live-sld-inverters">${invHtml || '<div class="live-sld-inv"><b>No inverter devices</b><div class="meta">Waiting for device_list</div></div>'}</div>
                    </div>
                    <div class="live-sld-node"><strong>Grid Export</strong><span>${Number(state.gridKw !== null ? state.gridKw : totalInvPower).toFixed(1)} kW</span><span>${Number(totalInvGen || 0).toFixed(1)} kWh today</span></div>
                </div>
            </div>
        </div>`;
    }

    function connect() {
        render();
        if (!wsUrl || !wsUnitId) return;
        try {
            const ws = new WebSocket(wsUrl);
            ws.onopen = () => {
                state.connected = true;
                ws.send(JSON.stringify({ type: 'subscribe', unit_id: wsUnitId }));
                ws.send(JSON.stringify({ type: 'get_devices', unit_id: wsUnitId }));
                render();
            };
            ws.onmessage = event => {
                try {
                    let data = JSON.parse(event.data);
                    if (data.unit_id && data.unit_id !== wsUnitId) return;
                    if (data.type === 'device_list') {
                        applyDeviceList(data.devices || []);
                        if (window.LiveWsStore && data.devices) window.LiveWsStore.requestTodayForDevices(ws, wsUnitId, data.devices);
                        return;
                    }
                    if (data.type === 'daily_data_result') {
                        const latest = Array.isArray(data.data) && data.data.length ? data.data[data.data.length - 1] : null;
                        if (!latest || !latest.values) return;
                        const deviceName = data.deviceName || data.device || latest.device || '';
                        if (/inv/i.test(deviceName)) updateInverter(deviceName, latest.values);
                        else if (/vcb/i.test(deviceName)) updateGrid(latest.values);
                        return;
                    }
                    const task = String(data.task || '').toLowerCase();
                    const device = String(data.device || '').toLowerCase();
                    if (task.includes('vcb') || device.includes('vcb')) updateGrid(data.values || {});
                    else if (task.includes('inv') || device.includes('inv')) updateInverter(data.device || '', data.values || {});
                } catch (e) {}
            };
            ws.onclose = () => setTimeout(connect, 3000);
        } catch (e) {}
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', connect, { once: true });
    else connect();
})();
