<?php require 'check_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title id="pageTitle">Solar Plant - Transformer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/live_ws_store.js"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800 font-sans">
    <div class="min-h-screen flex relative">
        <div id="overlay" class="fixed inset-0 bg-slate-900 bg-opacity-40 hidden z-30 md:hidden transition-opacity"></div>
        <div id="sidebar-container"></div>
        <main class="flex-1 flex flex-col w-full md:ml-64 overflow-x-hidden">
            <header class="bg-white p-4 sm:px-6 flex justify-between items-center sticky top-0 z-20 border-b border-slate-200 shadow-sm">
                <div class="flex items-center gap-3">
                    <button id="menuBtn" class="md:hidden text-emerald-600 text-2xl focus:outline-none">&#9776;</button>
                    <div><h2 class="text-xl font-black text-slate-800 tracking-tight">Transformer Monitoring</h2></div>
                </div>
                <div class="flex items-center gap-3 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                    <div id="refreshPulse" class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                    <span class="text-xs font-bold text-slate-600 tracking-widest hidden sm:inline" id="clockDisplay">--:--:--</span>
                </div>
            </header>
            <div class="p-4 sm:p-6 w-full flex flex-col gap-6 max-w-[1600px] mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-orange-50 rounded-full blur-xl -z-10 group-hover:bg-orange-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Oil Temperature</h3>
                        <p class="font-black text-slate-800 text-3xl" id="oil_temp">-- <span class="text-sm font-bold text-orange-600">degC</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1" id="oil_device">Transformer-oil</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-50 rounded-full blur-xl -z-10 group-hover:bg-amber-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Winding Temp</h3>
                        <p class="font-black text-slate-800 text-3xl" id="wind_temp">-- <span class="text-sm font-bold text-amber-600">degC</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1" id="wind_device">Transformer-winding</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full blur-xl -z-10 group-hover:bg-emerald-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Status</h3>
                        <p class="font-black text-slate-400 text-3xl" id="trafo_status">Connecting</p>
                        <p class="text-xs text-slate-500 font-medium mt-1" id="trafo_source">WebSocket loading</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</p><h2 class="text-lg sm:text-xl font-bold text-slate-900">Transformer Health</h2></div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 shrink-0" id="trafo_badge">Connecting</span>
                    </div>
                    <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-500" id="trafo_message">
                        Checking WebSocket devices for transformer telemetry...
                    </div>
                    <div class="grid gap-4 md:grid-cols-3 mb-4">
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Oil Temp</p>
                            <p class="mt-3 text-2xl font-black text-slate-800" id="oil_detail">-- <span class="text-sm font-bold text-orange-600">degC</span></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Winding Temp</p>
                            <p class="mt-3 text-2xl font-black text-slate-800" id="wind_detail">-- <span class="text-sm font-bold text-amber-600">degC</span></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Last Update</p>
                            <p class="mt-3 text-2xl font-black text-slate-800" id="last_update">--</p>
                        </div>
                    </div>
                    <div style="height:280px;">
                        <canvas id="trafoChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        const currentPlant = '<?php echo addslashes($currentPlant); ?>';
        const wsUnitId = "<?php echo getPlantWsUnitId($currentPlant); ?>";
        const plantConfig = <?php echo getPlantPublicConfigJson(); ?>;
        const plantNames = Object.fromEntries(Object.entries(plantConfig).map(([id, cfg]) => [id, cfg.name]));
        document.getElementById('pageTitle').textContent = (plantNames[currentPlant] || currentPlant) + ' - Transformer';
        setInterval(() => { document.getElementById('clockDisplay').innerText = new Date().toLocaleTimeString('en-IN', {hour12: false}); }, 1000);
        fetch('sidebar.html', { cache: 'no-store' }).then(r => r.text()).then(html => {
            document.getElementById('sidebar-container').innerHTML = html;
            document.getElementById('sidebar-container').querySelectorAll('script').forEach(s => { const ns = document.createElement('script'); ns.textContent = s.textContent; s.replaceWith(ns); });
            const overlay = document.getElementById('overlay'), sidebar = document.getElementById('sidebar');
            document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar?.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); });
            document.getElementById('closeSidebarBtn')?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay?.classList.add('hidden'); });
            overlay?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
        });
        const tState = { oil: null, winding: null, lastUpdate: '', hasDevice: false, hasData: false };
        let trafoChart;
        function readNumber(value) {
            if (value === null || value === undefined || value === '') return null;
            const n = parseFloat(value);
            return Number.isFinite(n) ? n : null;
        }
        function findNumber(values, patterns) {
            for (const key in values || {}) {
                const kl = key.toLowerCase();
                if (!patterns.some(rx => rx.test(kl))) continue;
                const n = readNumber(values[key]);
                if (n !== null) return n;
            }
            return null;
        }
        function formatTemp(value) {
            return value === null ? '--' : value.toFixed(1);
        }
        function initTrafoChart() {
            const ctx = document.getElementById('trafoChart').getContext('2d');
            Chart.defaults.color = '#64748b';
            trafoChart = new Chart(ctx, {
                type: 'line',
                data: { labels: [], datasets: [
                    { label: 'Oil Temp (degC)', borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,0.1)', borderWidth: 2, fill: true, tension: 0, pointRadius: 0, data: [] },
                    { label: 'Winding Temp (degC)', borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', borderWidth: 2, fill: true, tension: 0, pointRadius: 0, data: [] }
                ]},
                options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 12, usePointStyle: true, font: { weight: '600' } } } },
                    scales: { x: { grid: { color: '#f1f5f9', drawBorder: false }, ticks: { maxTicksLimit: 12 } },
                        y: { beginAtZero: false, grid: { color: '#f1f5f9', drawBorder: false } } }
                }
            });
        }
        initTrafoChart();
        let lastTrafoPush = 0;
        let trafoHasData = false;
        function pushTrafoPoint() {
            if (tState.oil === null && tState.winding === null) return;
            const now = Date.now();
            if (trafoHasData && now - lastTrafoPush < 10000) return;
            lastTrafoPush = now; trafoHasData = true;
            const timeStr = new Date().toLocaleTimeString('en-IN', {hour:'2-digit', minute:'2-digit', hour12:false});
            trafoChart.data.labels.push(timeStr);
            trafoChart.data.datasets[0].data.push(tState.oil);
            trafoChart.data.datasets[1].data.push(tState.winding);
            if (trafoChart.data.labels.length > 50) {
                trafoChart.data.labels.shift();
                trafoChart.data.datasets.forEach(ds => ds.data.shift());
            }
            trafoChart.update('none');
        }
        function updateTrafoStatus() {
            const hasData = tState.oil !== null || tState.winding !== null;
            const warn = (tState.oil !== null && tState.oil > 80) || (tState.winding !== null && tState.winding > 100);
            const statusEl = document.getElementById('trafo_status');
            const badgeEl = document.getElementById('trafo_badge');
            const sourceEl = document.getElementById('trafo_source');
            const msgEl = document.getElementById('trafo_message');
            if (statusEl) {
                statusEl.textContent = hasData ? (warn ? 'Warning' : 'Normal') : (tState.hasDevice ? 'No Data' : 'Not Found');
                statusEl.className = 'font-black text-3xl ' + (!hasData ? 'text-slate-400' : (warn ? 'text-amber-600' : 'text-emerald-700'));
            }
            if (badgeEl) {
                badgeEl.textContent = hasData ? (warn ? 'Check' : 'Normal') : (tState.hasDevice ? 'Waiting' : 'No Device');
                badgeEl.className = 'rounded-full px-3 py-1 text-xs font-bold ' + (!hasData ? 'bg-slate-100 text-slate-500' : (warn ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'));
            }
            if (sourceEl) sourceEl.textContent = hasData ? 'Live WebSocket telemetry' : `WS unit: ${wsUnitId}`;
            if (msgEl) {
                if (hasData) {
                    msgEl.textContent = 'Live transformer telemetry received from WebSocket.';
                    msgEl.className = 'mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700';
                } else if (tState.hasDevice) {
                    msgEl.textContent = `Transformer device exists in WebSocket for ${wsUnitId}, waiting for oil/winding temperature values.`;
                    msgEl.className = 'mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-700';
                } else {
                    msgEl.textContent = `No transformer device is returned by WebSocket get_devices for ${wsUnitId}. ws-dash style check returned only VCB/Inverter devices.`;
                    msgEl.className = 'mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-500';
                }
            }
        }
        function renderTrafoPoint(lastUpdate = '') {
            document.getElementById('oil_temp').innerHTML = formatTemp(tState.oil) + ' <span class="text-sm font-bold text-orange-600">degC</span>';
            document.getElementById('oil_detail').innerHTML = formatTemp(tState.oil) + ' <span class="text-sm font-bold text-orange-600">degC</span>';
            document.getElementById('wind_temp').innerHTML = formatTemp(tState.winding) + ' <span class="text-sm font-bold text-amber-600">degC</span>';
            document.getElementById('wind_detail').innerHTML = formatTemp(tState.winding) + ' <span class="text-sm font-bold text-amber-600">degC</span>';
            const updateText = lastUpdate || tState.lastUpdate;
            document.getElementById('last_update').textContent = updateText ? (updateText.includes(' ') ? updateText.split(' ').pop() : updateText) : '--:--:--';
            updateTrafoStatus();
            pushTrafoPoint();
        }
        function applyTransformerValues(deviceName, values, sourceTime = '') {
            const device = (deviceName || '').toLowerCase();
            const oil = findNumber(values, [/oil.*temp/, /temp.*oil/, /^oil-temp$/]);
            const winding = findNumber(values, [/winding.*temp/, /temp.*winding/, /^winding-temp$/]);
            if (oil !== null || (device.includes('oil') && findNumber(values, [/temp/, /temperature/]) !== null)) {
                tState.oil = oil !== null ? oil : findNumber(values, [/temp/, /temperature/]);
            }
            if (winding !== null || (device.includes('winding') && findNumber(values, [/temp/, /temperature/]) !== null)) {
                tState.winding = winding !== null ? winding : findNumber(values, [/temp/, /temperature/]);
            }
            if (tState.oil !== null || tState.winding !== null) {
                tState.hasData = true;
                tState.lastUpdate = sourceTime || new Date().toLocaleTimeString('en-IN', {hour12:false});
                renderTrafoPoint(tState.lastUpdate);
            }
        }
        function latestWithTransformerValues(rows) {
            if (!Array.isArray(rows)) return null;
            for (let i = rows.length - 1; i >= 0; i--) {
                const row = rows[i];
                if (!row || !row.values) continue;
                const oil = findNumber(row.values, [/oil.*temp/, /temp.*oil/, /^oil-temp$/]);
                const winding = findNumber(row.values, [/winding.*temp/, /temp.*winding/, /^winding-temp$/]);
                if (oil !== null || winding !== null) return row;
            }
            return null;
        }

        function loadLatestSnapshot() {
            window.LiveWsStore.fastSnapshot(currentPlant)
                .then(res => res.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data) return;
                    const row = (res.data.transformers || [])[0];
                    if (!row) return;
                    tState.hasDevice = true;
                    tState.oil = parseFloat(row.oil_temp_c);
                    tState.winding = parseFloat(row.winding_temp_c);
                    if (!Number.isFinite(tState.oil)) tState.oil = null;
                    if (!Number.isFinite(tState.winding)) tState.winding = null;
                    tState.hasData = tState.oil !== null || tState.winding !== null;
                    tState.lastUpdate = row.snapshot_at || '';
                    renderTrafoPoint(tState.lastUpdate);
                })
                .catch(() => {});
        }
        
        function connectWS() {
            const wsUrl = "wss://vinobasolar.scadahub.in:5001";
            if (!wsUrl) return;
            const ws = new WebSocket(wsUrl);
            ws.onopen = function() {
                document.getElementById('refreshPulse').className = 'w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]';
                ws.send(JSON.stringify({ type: "subscribe", unit_id: wsUnitId }));
                ws.send(JSON.stringify({ type: "get_devices", unit_id: wsUnitId }));
            };
            ws.onmessage = function(e) {
                try {
                    let d = JSON.parse(e.data);
                    if (d.unit_id && d.unit_id !== wsUnitId) return;
                    window.LiveWsStore?.storeMessage(d, currentPlant);
                    if (d.type === 'device_list') {
                        let foundTransformer = false;
                        (d.devices || []).forEach(device => {
                            const name = device.name || device.device || '';
                            if (/transformer|oil|winding/i.test(name)) {
                                foundTransformer = true;
                            }
                        });
                        window.LiveWsStore?.requestTodayForDevices(ws, wsUnitId, d.devices);
                        tState.hasDevice = foundTransformer;
                        if (!foundTransformer) renderTrafoPoint('');
                        return;
                    }
                    if (d.type === 'daily_data_result') {
                        const latest = latestWithTransformerValues(d.data);
                        if (!latest || !latest.values) return;
                        d = { type: 'data', unit_id: d.unit_id, task: 'Transformer', device: latest.device || d.deviceName, time: latest.time || '', values: latest.values };
                    }
                    if (d.unit_id !== wsUnitId) return;
                    if (!d.values) return;
                    const task = (d.task || '').toString().toLowerCase();
                    const device = (d.device || '').toString().toLowerCase();
                    const hasTransformerKeys = findNumber(d.values, [/oil.*temp/, /temp.*oil/, /^oil-temp$/, /winding.*temp/, /temp.*winding/, /^winding-temp$/]) !== null;
                    if (!task.includes('transformer') && !/transformer|oil|winding/.test(device) && !hasTransformerKeys) return;
                    applyTransformerValues(d.device || '', d.values, d.time || '');
                } catch(err) {}
            };
            ws.onclose = function() { document.getElementById('refreshPulse').className = 'w-2.5 h-2.5 bg-red-500 rounded-full'; setTimeout(connectWS, 2000); };
        }
        connectWS();
    </script>
</body>
</html>
