<?php require 'check_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title id="pageTitle">Solar Plant - Analytics</title>
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
                    <div><h2 class="text-xl font-black text-slate-800 tracking-tight">Plant Analytics</h2></div>
                </div>
                <div class="flex items-center gap-3 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                    <div id="refreshPulse" class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                    <span class="text-xs font-bold text-slate-600 tracking-widest hidden sm:inline" id="clockDisplay">--:--:--</span>
                </div>
            </header>
            <div class="p-4 sm:p-6 w-full flex flex-col gap-6 max-w-[1600px] mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full blur-xl -z-10 group-hover:bg-blue-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Performance</h3>
                        <p class="font-black text-slate-800 text-3xl" id="perf_val">-- <span class="text-sm font-bold text-blue-600">%</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Capacity factor</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-50 rounded-full blur-xl -z-10 group-hover:bg-purple-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Yield</h3>
                        <p class="font-black text-slate-800 text-3xl" id="yield_val">-- <span class="text-sm font-bold text-purple-600">kWh</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Daily energy</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full blur-xl -z-10 group-hover:bg-emerald-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Availability</h3>
                        <p class="font-black text-slate-800 text-3xl" id="avail_val">-- <span class="text-sm font-bold text-emerald-600">%</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Inverter uptime</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-5">
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Daily Generation</p><h2 class="text-xl font-bold text-slate-900">Output Trend</h2></div>
                    </div>
                    <div style="height:300px; min-height:300px;"><canvas id="analyticsChart"></canvas></div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest">Alerts & Recommendations</h3>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 shrink-0" id="recBadge">0 items</span>
                    </div>
                    <div id="recContainer" class="space-y-3">
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                            <p class="font-black text-slate-800 text-sm">No Alerts</p>
                            <p class="mt-2 text-sm text-slate-600">All systems operating within normal parameters. No action required.</p>
                        </div>
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
        document.getElementById('pageTitle').textContent = (plantNames[currentPlant] || currentPlant) + ' - Analytics';
        setInterval(() => { document.getElementById('clockDisplay').innerText = new Date().toLocaleTimeString('en-IN', {hour12: false}); }, 1000);
        fetch('sidebar.html', { cache: 'no-store' }).then(r => r.text()).then(html => {
            document.getElementById('sidebar-container').innerHTML = html;
            document.getElementById('sidebar-container').querySelectorAll('script').forEach(s => { const ns = document.createElement('script'); ns.textContent = s.textContent; s.replaceWith(ns); });
            const overlay = document.getElementById('overlay'), sidebar = document.getElementById('sidebar');
            document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar?.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); });
            document.getElementById('closeSidebarBtn')?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay?.classList.add('hidden'); });
            overlay?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
        });
        let analyticsChart;
        function initAnalyticsChart() {
            const ctx = document.getElementById('analyticsChart').getContext('2d');
            analyticsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0') + ':00'),
                    datasets: [{ label: 'Power (kW)', data: new Array(24).fill(0), backgroundColor: '#2563eb', borderRadius: 8, barThickness: 18 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { color: '#475569' } },
                        x: { grid: { display: false }, ticks: { color: '#475569', autoSkip: true, maxTicksLimit: 8, maxRotation: 45, minRotation: 0, font: { size: 10 } } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
        initAnalyticsChart();
        const cfg = plantConfig[currentPlant] || { capacity: 1.0, inverter_count: 0 };
        const aState = { inverters: {}, inverterPowerByHour: {} };
        const storedHourlyPower = new Array(24).fill(0);
        function readNumber(values, names) {
            for (const name of names) {
                if (values && values[name] !== undefined && values[name] !== null && values[name] !== '') {
                    const n = parseFloat(values[name]);
                    if (Number.isFinite(n)) return n;
                }
            }
            return 0;
        }
        function canonicalDeviceName(device) {
            const name = (device || '').toString().trim();
            const m = name.match(/(?:inv(?:erter)?)[-\s_]*(\d+)/i);
            if (m) return 'Inverter' + parseInt(m[1], 10);
            return name || 'Inverter';
        }
        function hourFromTime(sourceTime) {
            const raw = (sourceTime || '').toString();
            const match = raw.match(/\b(\d{1,2}):\d{2}/);
            if (match) return Math.max(0, Math.min(23, parseInt(match[1], 10)));
            return new Date().getHours();
        }
        function ensureInverter(device) {
            const key = canonicalDeviceName(device);
            if (!aState.inverters[key]) aState.inverters[key] = { power: 0, energy: 0, online: false };
            if (!aState.inverterPowerByHour[key]) aState.inverterPowerByHour[key] = new Array(24).fill(0);
            return key;
        }
        function applyInverterAnalytics(device, values, sourceTime) {
            const key = ensureInverter(device);
            const power = readNumber(values, ['Total active power', 'a.c. active power', 'AC Power', 'active_power', 'power']);
            const energy = readNumber(values, ['Daily power yields', 'daily generation', 'daily_generation', 'Day Energy', 'today_energy']);
            const workState = (values && (values['Work state'] || values['work_state'] || values['Status'] || values['status']) || '').toString().toLowerCase();
            const online = power > 0.1 || !/offline|fault|stop|standby|disconnect/.test(workState);
            aState.inverters[key] = { power, energy, online };
            aState.inverterPowerByHour[key][hourFromTime(sourceTime)] = power;
        }
        function renderAnalyticsChart() {
            const hourly = storedHourlyPower.slice();
            Object.values(aState.inverterPowerByHour).forEach(series => {
                series.forEach((value, hour) => { hourly[hour] += value || 0; });
            });
            analyticsChart.data.datasets[0].data = hourly.map(v => Number(v.toFixed(2)));
            analyticsChart.update('none');
        }
        function loadStoredHourlyAnalytics() {
            fetch(`api.php?action=get_overview_hourly&plant_id=${encodeURIComponent(currentPlant)}`, { cache: 'no-store' })
                .then(res => res.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data) return;
                    const power = Array.isArray(res.data.power) ? res.data.power : [];
                    for (let i = 0; i < 24; i++) storedHourlyPower[i] = Number(power[i] || 0);
                    renderAnalyticsChart();
                })
                .catch(() => {});
        }
        function updateAnalyticsCards() {
            const inverterRows = Object.values(aState.inverters);
            const totalInv = Math.max(cfg.inverter_count || 0, inverterRows.length);
            const activeInv = inverterRows.filter(row => row.online && row.power > 0.1).length;
            const livePower = inverterRows.reduce((sum, row) => sum + (row.power || 0), 0);
            const liveEnergy = inverterRows.reduce((sum, row) => sum + (row.energy || 0), 0);
            aState.power = livePower;
            aState.energy = liveEnergy;
            const perf = cfg.capacity > 0 ? ((aState.power / (cfg.capacity * 1000)) * 100) : 0;
            document.getElementById('perf_val').innerHTML = perf.toFixed(1) + ' <span class="text-sm font-bold text-blue-600">%</span>';
            document.getElementById('yield_val').innerHTML = aState.energy.toFixed(2) + ' <span class="text-sm font-bold text-purple-600">kWh</span>';
            const avail = totalInv > 0 ? ((activeInv / totalInv) * 100) : 0;
            document.getElementById('avail_val').innerHTML = avail.toFixed(1) + ' <span class="text-sm font-bold text-emerald-600">%</span>';
        }
        function dbInverterValues(row) {
            return {
                "Total active power": row.power_kw,
                "Daily power yields": row.daily_gen_kwh,
                "Work state": row.work_state || row.status_text || ''
            };
        }
        function loadLatestSnapshot() {
            window.LiveWsStore.fastSnapshot(currentPlant)
                .then(res => res.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data) return;
                    (res.data.inverters || []).forEach(row => {
                        applyInverterAnalytics(row.inverter_name, dbInverterValues(row), row.snapshot_at || '');
                    });
                    updateAnalyticsCards();
                    renderAnalyticsChart();
                })
                .catch(() => {});
        }
        function isInverterData(message) {
            const device = (message.device || message.deviceName || message.task || '').toString();
            if (/inv/i.test(device)) return true;
            const values = message.values || {};
            return values['Total active power'] !== undefined || values['Daily power yields'] !== undefined;
        }
        function connectWSAnalytics() {
            const wsUrl = "<?php echo getPlantWsUrl($currentPlant); ?>";
            if (!wsUrl) return;
            const ws = new WebSocket(wsUrl);
            ws.onopen = function() { ws.send(JSON.stringify({ type: "subscribe", unit_id: wsUnitId }));
                ws.send(JSON.stringify({ type: "get_devices", unit_id: wsUnitId })); };
            ws.onmessage = function(e) {
                try {
                    let d = JSON.parse(e.data);
                    if (d.unit_id && d.unit_id !== wsUnitId) return;
                    window.LiveWsStore?.storeMessage(d, currentPlant);
                    if (d.type === 'device_list') {
                        (d.devices || []).forEach(device => {
                            const name = device.name || device.device || '';
                            if (/inv/i.test(name)) ensureInverter(name);
                        });
                        window.LiveWsStore?.requestTodayForDevices(ws, wsUnitId, d.devices);
                        updateAnalyticsCards();
                        renderAnalyticsChart();
                        return;
                    }
                    if (d.type === 'daily_data_result') {
                        if (d.unit_id !== wsUnitId) return;
                        (d.data || []).forEach(row => {
                            const rowDevice = row.device || d.deviceName || d.device || '';
                            if (row.values && isInverterData({ device: rowDevice, values: row.values })) applyInverterAnalytics(rowDevice, row.values, row.time || row.timestamp || '');
                        });
                        updateAnalyticsCards();
                        renderAnalyticsChart();
                        return;
                    }
                    if (d.unit_id !== wsUnitId) return;
                    if (d.values && isInverterData(d)) {
                        applyInverterAnalytics(d.device || d.deviceName || 'Inverter', d.values, d.time || d.timestamp || '');
                        updateAnalyticsCards();
                        renderAnalyticsChart();
                    }
                } catch(err) {}
            };
            ws.onclose = function() { setTimeout(connectWSAnalytics, 2000); };
        }
        connectWSAnalytics();
    </script>
</body>
</html>
