(function () {
    if (!/admin\.php$/i.test(window.location.pathname)) return;

    const config = window.SIGNED_PLANT_CONFIG || {};
    const plantEntries = Object.entries(config).filter(([, plant]) => plant && plant.ws_unit_id);
    if (!plantEntries.length) return;

    const state = {};
    plantEntries.forEach(([plantId, plant]) => {
        state[plant.ws_unit_id] = {
            plantId,
            devices: new Set(),
            samplesByDevice: {},
            liveByDevice: {},
            peakKw: 0,
            peakTime: ''
        };
    });

    function numberValue(value) {
        if (value && typeof value === 'object') value = value.value ?? value.val ?? value.reading ?? value.data ?? value.result;
        const n = parseFloat(String(value ?? '').replace(/,/g, ''));
        return Number.isFinite(n) ? n : null;
    }

    function powerKw(value) {
        const n = numberValue(value);
        if (n === null) return null;
        return Math.abs(n) > 10000 ? n / 1000 : n;
    }

    function canonicalDevice(name) {
        const match = String(name || '').match(/(?:inverter|inv|solar)\s*[-_#:]?\s*0*(\d+)\b/i);
        return match ? `INVERTER${parseInt(match[1], 10)}` : '';
    }

    function extractPower(values) {
        if (!values || typeof values !== 'object') return null;
        const preferred = [/^total active power/i, /^active power/i, /^ac active power/i, /^ac power/i, /^output power/i, /^power output/i, /^pac$/i, /^p ac$/i];
        const rejected = /reactive|apparent|nominal|rated|capacity|maximum|max power|dc power|string|mppt|yield|energy|factor|3[ ._-]*phase/i;
        for (const [key, raw] of Object.entries(values)) {
            if (rejected.test(key) || !preferred.some(rx => rx.test(key.trim()))) continue;
            const value = powerKw(raw);
            if (value !== null) return value;
        }
        for (const [key, raw] of Object.entries(values)) {
            if (!/power/i.test(key) || rejected.test(key)) continue;
            const value = powerKw(raw);
            if (value !== null) return value;
        }
        return null;
    }

    function minuteKey(value) {
        const text = String(value || '').trim();
        const direct = text.match(/(?:T|\s|^)(\d{1,2}):(\d{2})/);
        if (direct) return `${String(parseInt(direct[1], 10)).padStart(2, '0')}:${direct[2]}`;
        const date = new Date(text);
        if (Number.isNaN(date.getTime())) return '';
        return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
    }

    function ensurePeakUi(plantId) {
        const card = document.getElementById(`card-${plantId}`);
        if (!card) return null;
        let box = document.getElementById(`peak-${plantId}`);
        if (box) return box;

        const metricsGrid = card.querySelector('.p-4.grid.grid-cols-2');
        if (!metricsGrid) return null;

        box = document.createElement('div');
        box.id = `peak-${plantId}`;
        box.className = 'col-span-2 bg-slate-50 rounded-lg px-3 py-2.5 border border-slate-100 flex items-center justify-between gap-3 min-w-0 overflow-hidden';
        box.innerHTML = `
            <div class="min-w-0">
                <div class="text-slate-400 text-[9px] sm:text-[10px] font-bold uppercase tracking-wide leading-tight">
                    <i class="fa-solid fa-arrow-trend-up text-blue-500 mr-1"></i>Today Peak Power
                </div>
                <div class="text-[9px] font-semibold text-slate-400 mt-1 truncate" data-peak-time>Waiting for today data</div>
            </div>
            <div class="text-sm sm:text-base font-black text-slate-800 whitespace-nowrap shrink-0" data-peak-value>-- kW</div>`;
        metricsGrid.appendChild(box);
        return box;
    }

    function renderPeak(unitId) {
        const st = state[unitId];
        if (!st) return;

        const totalsByMinute = {};
        Object.values(st.samplesByDevice).forEach(samples => {
            Object.entries(samples || {}).forEach(([minute, value]) => {
                totalsByMinute[minute] = (totalsByMinute[minute] || 0) + (Number(value) || 0);
            });
        });

        Object.entries(st.liveByDevice).forEach(([device, sample]) => {
            if (!sample || !sample.minute || sample.power === null) return;
            if (st.samplesByDevice[device]?.[sample.minute] === undefined) {
                totalsByMinute[sample.minute] = (totalsByMinute[sample.minute] || 0) + sample.power;
            }
        });

        let peakKw = 0;
        let peakTime = '';
        Object.entries(totalsByMinute).forEach(([minute, total]) => {
            if (total > peakKw) {
                peakKw = total;
                peakTime = minute;
            }
        });

        st.peakKw = peakKw;
        st.peakTime = peakTime;
        const box = ensurePeakUi(st.plantId);
        if (!box) return;
        const valueEl = box.querySelector('[data-peak-value]');
        const timeEl = box.querySelector('[data-peak-time]');
        if (valueEl) valueEl.textContent = peakTime ? `${peakKw.toFixed(1)} kW` : '-- kW';
        if (timeEl) timeEl.textContent = peakTime ? `Highest combined reading at ${peakTime}` : 'No inverter history today';
    }

    function processDailyResult(message) {
        const st = state[message.unit_id];
        if (!st) return;
        const rawName = message.deviceName || message.device || message.request?.device || '';
        const device = canonicalDevice(rawName);
        if (!device) return;

        const rows = Array.isArray(message.data) ? message.data : Array.isArray(message.result) ? message.result : Array.isArray(message.results) ? message.results : [];
        if (!rows.length) return;

        const samples = {};
        rows.forEach(row => {
            const values = row?.values || row?.data || {};
            const power = extractPower(values);
            const minute = minuteKey(row?.time || row?.timestamp || row?.dateTime || row?.datetime || row?.bucket);
            if (power === null || !minute || power < 0) return;
            samples[minute] = Math.max(samples[minute] || 0, power);
        });
        st.samplesByDevice[device] = samples;
        renderPeak(message.unit_id);
    }

    function processLive(message) {
        const st = state[message.unit_id];
        if (!st || !message.values) return;
        const device = canonicalDevice(message.device || message.deviceName || message.task || '');
        if (!device) return;
        const power = extractPower(message.values);
        if (power === null || power < 0) return;
        const now = new Date();
        const minute = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
        st.liveByDevice[device] = { minute, power };
        renderPeak(message.unit_id);
    }

    let socket = null;
    let reconnectTimer = null;
    let refreshTimer = null;

    function requestToday(unitId, deviceName) {
        if (!socket || socket.readyState !== WebSocket.OPEN) return;
        const d = new Date();
        const date = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        socket.send(JSON.stringify({ type: 'get_daily_data', unit_id: unitId, device: deviceName, date }));
    }

    function refreshHistory() {
        Object.entries(state).forEach(([unitId, st]) => st.devices.forEach(deviceName => requestToday(unitId, deviceName)));
    }

    function connect() {
        clearTimeout(reconnectTimer);
        clearInterval(refreshTimer);
        socket = new WebSocket('wss://vinobasolar.scadahub.in:5001');
        socket.addEventListener('open', () => {
            plantEntries.forEach(([, plant]) => {
                socket.send(JSON.stringify({ type: 'subscribe', unit_id: plant.ws_unit_id }));
                socket.send(JSON.stringify({ type: 'get_devices', unit_id: plant.ws_unit_id }));
            });
            refreshTimer = setInterval(refreshHistory, 300000);
        });
        socket.addEventListener('message', event => {
            try {
                const message = JSON.parse(event.data);
                if (!state[message.unit_id]) return;
                if (message.type === 'device_list') {
                    const devices = Array.isArray(message.devices) ? message.devices : [];
                    devices.forEach(item => {
                        const name = typeof item === 'string' ? item : (item.name || item.device || item.deviceName || '');
                        if (!canonicalDevice(name)) return;
                        state[message.unit_id].devices.add(name);
                        requestToday(message.unit_id, name);
                    });
                    return;
                }
                if (message.type === 'daily_data_result') processDailyResult(message);
                else processLive(message);
            } catch (error) {
                console.warn('[AdminTodayPeak] Unable to parse telemetry', error);
            }
        });
        socket.addEventListener('close', () => {
            clearInterval(refreshTimer);
            reconnectTimer = setTimeout(connect, 4000);
        });
        socket.addEventListener('error', () => socket.close());
    }

    function initUi() {
        plantEntries.forEach(([plantId]) => ensurePeakUi(plantId));
        connect();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initUi, { once: true });
    else initUi();
})();