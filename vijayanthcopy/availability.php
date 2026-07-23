<?php require 'check_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title id="pageTitle">Solar Plant – Availability</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/live_ws_store.js"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .status-box { display: flex; align-items: center; justify-content: center; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .status-on { background: #dcfce7; color: #166534; }
        .status-off { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800 font-sans">
    <div class="min-h-screen flex relative">
        <div id="overlay" class="fixed inset-0 bg-slate-900 bg-opacity-40 hidden z-30 md:hidden transition-opacity"></div>
        <div id="sidebar-container"></div>
        <main class="flex-1 flex flex-col w-full md:ml-64 overflow-x-hidden">
            <header class="bg-white p-3 sm:px-6 flex flex-col gap-2 sticky top-0 z-20 border-b border-slate-200 shadow-sm">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <button id="menuBtn" class="md:hidden text-emerald-600 text-2xl focus:outline-none">&#9776;</button>
                        <div><h2 class="text-lg font-black text-slate-800 tracking-tight">Availability</h2></div>
                    </div>
                    <div class="flex items-center gap-3 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                        <div id="refreshPulse" class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                        <span class="text-[10px] font-bold text-slate-500" id="wsStatusText">Connecting...</span>
                        <span class="text-xs font-bold text-slate-600 tracking-widest hidden sm:inline" id="clockDisplay">--:--:--</span>
                    </div>
                </div>
            </header>

            <div class="p-3 sm:p-4 w-full flex flex-col gap-4 max-w-[1600px] mx-auto">

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-3 flex flex-col items-center justify-center text-center">
                        <div class="text-[10px] font-black text-slate-500 uppercase mb-1">Total Inverters</div>
                        <div class="text-3xl font-black text-slate-800" id="totalInvCount">--</div>
                        <i class="fas fa-desktop text-slate-400 text-lg mt-1"></i>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-3 flex flex-col items-center justify-center text-center">
                        <div class="text-[10px] font-black text-emerald-600 uppercase mb-1">Running Now</div>
                        <div class="text-3xl font-black text-emerald-600" id="runningInvCount">--</div>
                        <div class="text-xs font-bold text-slate-500" id="runningInvPct">--</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-3 flex flex-col items-center justify-center text-center">
                        <div class="text-[10px] font-black text-red-600 uppercase mb-1">Offline Now</div>
                        <div class="text-3xl font-black text-red-600" id="offlineInvCount">--</div>
                        <div class="text-xs font-bold text-slate-500" id="offlineInvPct">--</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-3 flex flex-col items-center justify-center text-center">
                        <div class="text-[10px] font-black text-amber-600 uppercase mb-1">Today's Downtime</div>
                        <div class="text-3xl font-black text-amber-600"><span id="todayDowntime">--</span> <span class="text-sm">hrs</span></div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-3 flex flex-col items-center justify-center text-center">
                        <div class="text-[10px] font-black text-purple-600 uppercase mb-1">Availability Today</div>
                        <div class="text-3xl font-black text-purple-600" id="todayAvailability">--</div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                    <!-- Integrated Availability Query Panel -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-xs font-black text-slate-700 uppercase tracking-wider mb-1">24-Hour Inverter Availability Timeline</h3>
                            <div class="text-[11px] font-bold text-slate-400" id="timelineDate">--</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <div class="flex flex-col gap-1">
                                <span class="font-bold text-slate-400 text-[10px] uppercase">Availability Date</span>
                                <input id="histDate" type="date" class="px-2.5 py-1.5 border border-slate-200 rounded-lg text-slate-700 focus:outline-none focus:border-emerald-500 bg-slate-50 text-[11px]">
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="min-w-[1000px]">
                        <div id="timelineTicks" class="flex text-[9px] font-bold text-slate-500 mb-2 pl-20">
                            <div class="flex-1 text-center">00:00</div>
                            <div class="flex-1 text-center">01:00</div>
                            <div class="flex-1 text-center">02:00</div>
                            <div class="flex-1 text-center">03:00</div>
                            <div class="flex-1 text-center">04:00</div>
                            <div class="flex-1 text-center">05:00</div>
                            <div class="flex-1 text-center">06:00</div>
                            <div class="flex-1 text-center">07:00</div>
                            <div class="flex-1 text-center">08:00</div>
                            <div class="flex-1 text-center">09:00</div>
                            <div class="flex-1 text-center">10:00</div>
                            <div class="flex-1 text-center">11:00</div>
                            <div class="flex-1 text-center">12:00</div>
                            <div class="flex-1 text-center">13:00</div>
                            <div class="flex-1 text-center">14:00</div>
                            <div class="flex-1 text-center">15:00</div>
                            <div class="flex-1 text-center">16:00</div>
                            <div class="flex-1 text-center">17:00</div>
                            <div class="flex-1 text-center">18:00</div>
                            <div class="flex-1 text-center">19:00</div>
                            <div class="flex-1 text-center">20:00</div>
                            <div class="flex-1 text-center">21:00</div>
                            <div class="flex-1 text-center">22:00</div>
                            <div class="flex-1 text-center">23:00</div>
                        </div>
                        <div id="invTimeline"></div>
                        <div class="flex flex-wrap gap-4 mt-3 text-[10px] font-bold text-slate-600">
                            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-emerald-500 rounded-sm"></div> Running / Available</div>
                            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-red-500 rounded-sm"></div> Offline / Fault</div>
                            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-slate-400 rounded-sm"></div> No Communication</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                    <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Current Status - All Inverters</h3>
                    <div class="h-[220px] max-w-md mx-auto relative"><canvas id="currentStatusChart"></canvas></div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col h-[300px]">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Grid Availability (24h)</h3>
                        <div class="flex-grow relative"><canvas id="gridAvailChart"></canvas></div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col h-[300px]">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Plant Availability (24h)</h3>
                        <div class="flex-grow relative"><canvas id="plantAvailChart"></canvas></div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        const currentPlant = '<?php echo addslashes($currentPlant); ?>';
        const wsUnitId = "<?php echo getPlantWsUnitId($currentPlant); ?>";
        const plantConfig = <?php echo getPlantPublicConfigJson(); ?>;
        document.getElementById('pageTitle').textContent = 'Availability';
        setInterval(() => { document.getElementById('clockDisplay').innerText = new Date().toLocaleTimeString('en-IN', {hour12: false}); }, 1000);
        fetch('sidebar.html', { cache: 'no-store' }).then(r => r.text()).then(html => {
            document.getElementById('sidebar-container').innerHTML = html;
            document.getElementById('sidebar-container').querySelectorAll('script').forEach(s => { const ns = document.createElement('script'); ns.textContent = s.textContent; s.replaceWith(ns); });
            const overlay = document.getElementById('overlay');
            const sidebar = document.getElementById('sidebar');
            document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar?.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); });
            document.getElementById('closeSidebarBtn')?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay?.classList.add('hidden'); });
            overlay?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
        });
        let gridAvailChart, plantAvailChart, currentStatusChart;
        let isHistoricalMode = false;
        let historicalData = null;
        let plantInverterKeys = new Set();
        let availabilityWs = null;
        let latestDeviceList = [];
        let pendingWsHistory = null;
        let defaultWsHistoryLoaded = false;
        const wsDailyRows = {};
        const wsDateQueueByDevice = {};
        let analyticsRowsByDevice = {};
        let analyticsExpectedDevices = [];
        let analyticsReceivedDevices = new Set();
        let analyticsRequestToken = 0;
        let analyticsFinishTimer = null;
        const hourLabels = Array.from({length: 25}, (_, i) => String(i).padStart(2, '0') + ':00');
        const pointColor = (ctx) => {
            const v = ctx.raw;
            return v === null ? 'transparent' : (v > 0 ? '#22c55e' : '#ef4444');
        };

        function initAvailabilityCharts() {
            Chart.defaults.color = '#64748b';
            Chart.defaults.font.size = 10;
            const baseOptions = {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true, font: { size: 10 } } } },
                scales: {
                    x: { grid: { display: false }, ticks: { autoSkip: false, maxTicksLimit: 25, maxRotation: 45, minRotation: 45, font: { size: 9 } } },
                    y: { min: 0, max: 105, grid: { color: '#f1f5f9' }, ticks: { stepSize: 25 }, title: { display: true, text: 'Availability (%)' } }
                }
            };
            const onOffLineDataset = (label, color) => ({
                type: 'line', label, borderColor: color, borderWidth: 3, pointRadius: 5, pointHoverRadius: 8,
                pointBackgroundColor: pointColor, pointBorderColor: pointColor,
                pointBorderWidth: 2, spanGaps: true, stepped: true,
                tension: 0, fill: false, backgroundColor: color + '20', data: new Array(25).fill(null)
            });
            gridAvailChart = new Chart(document.getElementById('gridAvailChart'), {
                type: 'line',
                data: { labels: hourLabels, datasets: [
                    onOffLineDataset('Grid Availability', '#3b82f6')
                ]},
                options: baseOptions
            });
            plantAvailChart = new Chart(document.getElementById('plantAvailChart'), {
                type: 'line',
                data: { labels: hourLabels, datasets: [
                    onOffLineDataset('Plant Availability', '#f59e0b')
                ]},
                options: baseOptions
            });
            currentStatusChart = new Chart(document.getElementById('currentStatusChart'), {
                type: 'doughnut',
                data: { labels: ['Running', 'Offline', 'No Communication'], datasets: [{
                    data: [0, 0, 0], backgroundColor: ['#22c55e', '#ef4444', '#94a3b8'], borderWidth: 0, hoverOffset: 4
                }]},
                options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
            });
        }
        function updateAvailabilityCharts() {
            if (!gridAvailChart || !plantAvailChart) return;
            const withLineAnchor = (data) => {
                const points = data.reduce((acc, value, index) => value === null ? acc : acc.concat(index), []);
                if (points.length !== 1) return data;
                const index = points[0];
                const anchor = index > 0 ? index - 1 : 1;
                const next = data.slice();
                next[anchor] = data[index];
                return next;
            };
            const gridData = withLineAnchor(state.availabilityHistory.grid.map(v => v === true ? 100 : v === false ? 0 : null));
            const plantData = withLineAnchor(state.availabilityHistory.plant.map(v => v === true ? 100 : v === false ? 0 : null));
            gridAvailChart.data.datasets[0].data = gridData;
            plantAvailChart.data.datasets[0].data = plantData;
            gridAvailChart.update('none');
            plantAvailChart.update('none');
            updateAvailabilityDashboard();
        }
        const state = {
            vcbPower: 0, dailyEnergy: 0, inverters: {}, peakPower: 0, expectedEnergy: 0, vcbOnline: false,
            availabilityHistory: { grid: new Array(25).fill(null), plant: new Array(25).fill(null), inverters: {} },
            minuteSamples: { grid: [], plant: [], inverters: {} },
            invDowntime: {}
        };
        const STATUS = { RUNNING: 'running', OFFLINE: 'offline', NOCOMM: 'no_comm' };
        const STATUS_COLORS = { running: 'bg-emerald-500', offline: 'bg-red-500', no_comm: 'bg-slate-400' };
        initAvailabilityCharts();
        updateAvailabilityCharts();
        updateAvailabilityDashboard();

        function canonicalInverterName(name) {
            const match = (name || '').toString().match(/\d+/);
            return match ? `INVERTER${parseInt(match[0], 10)}` : (name || 'INVERTER').toString().toUpperCase().replace(/\s+/g, '');
        }

        function ensureAvailabilityInverter(name, lastSeen = 0) {
            const key = canonicalInverterName(name);
            if (!state.inverters[key]) {
                state.inverters[key] = {
                    active: 0,
                    total: 0,
                    power: 0,
                    dcPower: 0,
                    acCurrent: 0,
                    acVoltage: 0,
                    dailyGen: 0,
                    totalGen: 0,
                    strings: [],
                    faultCode: null,
                    hasFault: false,
                    lastSeen,
                    hasData: lastSeen > 0
                };
            }
            if (!state.availabilityHistory.inverters[key]) state.availabilityHistory.inverters[key] = new Array(24).fill(null);
            if (!state.minuteSamples.inverters[key]) state.minuteSamples.inverters[key] = [];
            if (!state.invDowntime[key]) state.invDowntime[key] = { totalHours: 0, faultHours: 0, noCommHours: 0, events: 0, lastStatus: STATUS.NOCOMM, lastChange: Date.now() };
            return key;
        }

        function sortInverterKeys(keys) {
            return Array.from(keys).sort((a,b) => (parseInt(a.replace(/\D/g,''))||0) - (parseInt(b.replace(/\D/g,''))||0));
        }

        function historicalInverterKeys() {
            const keys = new Set(plantInverterKeys);
            (historicalData || []).forEach(bucket => {
                Object.keys(bucket.devices || {}).forEach(dev => keys.add(dev));
            });
            return sortInverterKeys(keys);
        }

        function bucketHasInverterData(bucket) {
            return Object.keys(bucket?.devices || {}).length > 0;
        }

        function bucketHasActiveInverter(bucket) {
            return Object.values(bucket?.devices || {}).some(dev => dev && dev.available);
        }

        function bucketHasAvailabilityData(bucket) {
            return !!bucket && (
                (bucket.vcb && bucket.vcb.available !== null && bucket.vcb.available !== undefined) ||
                bucketHasInverterData(bucket)
            );
        }

        function latestHistoricalBucketWithData() {
            for (let i = (historicalData || []).length - 1; i >= 0; i--) {
                if (bucketHasAvailabilityData(historicalData[i])) return historicalData[i];
            }
            return null;
        }

        function bucketGridAvailable(bucket) {
            if (bucket?.vcb && bucket.vcb.available !== null && bucket.vcb.available !== undefined) return !!bucket.vcb.available;
            if (bucketHasInverterData(bucket)) return bucketHasActiveInverter(bucket);
            return null;
        }

        function bucketPlantAvailable(bucket) {
            if (!bucket) return null;
            if (bucketHasActiveInverter(bucket)) return true;
            if (bucket?.vcb && bucket.vcb.available !== null && bucket.vcb.available !== undefined) return !!bucket.vcb.available;
            if (bucketHasInverterData(bucket)) return false;
            return null;
        }

        function applyDeviceList(devices) {
            if (!Array.isArray(devices)) return;
            latestDeviceList = devices;
            const nextKeys = new Set();
            devices.forEach(device => {
                const name = (device.name || device.device || '').toString();
                if (/inv/i.test(name)) {
                    const key = ensureAvailabilityInverter(name);
                    nextKeys.add(key);
                }
            });
            if (nextKeys.size) plantInverterKeys = nextKeys;
            updateAvailabilityDashboard();
        }

        function snapshotTimeToMs(snapshotAt) {
            if (!snapshotAt) return Date.now();
            const parsed = Date.parse(snapshotAt.replace(' ', 'T'));
            return Number.isNaN(parsed) ? Date.now() : parsed;
        }

        function hasAnyNumericValue(values) {
            return Object.values(values || {}).some(value => value !== null && value !== '' && Number.isFinite(parseFloat(value)));
        }

        function readFaultCode(values) {
            for (const key in values || {}) {
                if (/fault\s*code|faultcode/i.test(key)) {
                    const raw = values[key];
                    if (raw === null || raw === undefined || raw === '') return null;
                    const n = parseFloat(raw);
                    return Number.isFinite(n) ? n : raw.toString();
                }
            }
            return 0;
        }

        function inverterAvailableFromValues(values) {
            if (!values || !Object.keys(values).length) return null;
            const faultCode = readFaultCode(values);
            if (faultCode === null) return false;
            if (Number.isFinite(parseFloat(faultCode))) return parseFloat(faultCode) === 0;
            let pwr = null;
            if (values["Total active power"] !== undefined) pwr = parseFloat(values["Total active power"]);
            if (pwr === null || !Number.isFinite(pwr)) {
                for (const pk in values) {
                    const pkl = pk.toLowerCase();
                    if (/active.*power|ac.*power/i.test(pkl) && !/reactive|apparent|nominal|3.phase/i.test(pkl)) {
                        pwr = parseFloat(values[pk]);
                        break;
                    }
                }
            }
            if (Number.isFinite(pwr)) return pwr > 0.5;
            return hasAnyNumericValue(values);
        }

        function localDateString(date = new Date()) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function dateRange(start, end) {
            const dates = [];
            const cur = new Date(start + 'T00:00:00');
            const last = new Date(end + 'T00:00:00');
            while (cur <= last) {
                dates.push(localDateString(cur));
                cur.setDate(cur.getDate() + 1);
            }
            return dates;
        }

        function parseWsRowTime(row, dateHint) {
            const raw = (row.time || row.timestamp || row.ts || '').toString();
            if (/^\d{4}-\d{2}-\d{2}/.test(raw)) return new Date(raw.replace(' ', 'T'));
            if (/^\d{1,2}:\d{2}/.test(raw)) return new Date(`${dateHint}T${raw}`);
            const parsed = new Date(raw);
            return Number.isNaN(parsed.getTime()) ? null : parsed;
        }

        function cacheDailyRows(message) {
            const deviceRaw = message.deviceName || message.device || '';
            const device = canonicalInverterName(deviceRaw);
            if (!device || !/inv/i.test(deviceRaw)) return;
            const dateHint = (wsDateQueueByDevice[device] && wsDateQueueByDevice[device].shift()) || pendingWsHistory?.dates?.[0] || localDateString();
            (message.data || []).forEach(row => {
                if (!row || !row.values) return;
                const at = parseWsRowTime(row, dateHint);
                const dateKey = at ? localDateString(at) : dateHint;
                const key = `${device}|${dateKey}`;
                if (!wsDailyRows[key]) wsDailyRows[key] = [];
                wsDailyRows[key].push({ device, at, values: row.values });
            });
        }

        function selectedBucketAvailability(rows, method) {
            if (!rows.length) return null;
            rows.sort((a, b) => a.at - b.at);
            if (method === 'first') return inverterAvailableFromValues(rows[0].values);
            if (method === 'max') return rows.some(r => inverterAvailableFromValues(r.values) === true);
            if (method === 'min') return rows.every(r => inverterAvailableFromValues(r.values) === true);
            return inverterAvailableFromValues(rows[rows.length - 1].values);
        }

        function buildWsAvailabilityHistory(start, end, startTime, endTime, period, method) {
            const buckets = [];
            const startDt = new Date(`${start}T${startTime || '00:00'}:00`);
            const endDt = new Date(`${end}T${endTime || '23:59'}:59`);
            for (let t = new Date(startDt); t <= endDt; t = new Date(t.getTime() + period * 60000)) {
                const next = new Date(t.getTime() + period * 60000);
                const label = `${localDateString(t)} ${String(t.getHours()).padStart(2, '0')}:${String(t.getMinutes()).padStart(2, '0')}`;
                const bucket = { time: label, devices: {}, vcb: { available: null } };
                sortInverterKeys(Array.from(plantInverterKeys)).forEach(inv => {
                    const rows = [];
                    dateRange(start, end).forEach(dateKey => {
                        (wsDailyRows[`${inv}|${dateKey}`] || []).forEach(row => {
                            if (row.at && row.at >= t && row.at < next) rows.push(row);
                        });
                    });
                    const available = selectedBucketAvailability(rows, method);
                    if (available !== null) bucket.devices[inv] = { available: available === true, fault: available === false };
                });
                if (Object.keys(bucket.devices).length) bucket.vcb.available = bucketHasActiveInverter(bucket);
                buckets.push(bucket);
            }
            return buckets;
        }

        function getInvStatus(k) {
            const inv = state.inverters[k];
            if (!inv) return STATUS.NOCOMM;
            if (!inv.hasData) return STATUS.NOCOMM;
            if (inv.faultCode === null) return STATUS.OFFLINE;
            if (Number.isFinite(parseFloat(inv.faultCode))) return parseFloat(inv.faultCode) === 0 ? STATUS.RUNNING : STATUS.OFFLINE;
            if (inv.hasFault) return STATUS.OFFLINE;
            return (inv.power || 0) > 0 ? STATUS.RUNNING : STATUS.OFFLINE;
        }
        function initEmptyData() {
            state.availabilityHistory.grid = new Array(25).fill(null);
            state.availabilityHistory.plant = new Array(25).fill(null);
            state.availabilityHistory.inverters = {};
        }
        initEmptyData();
        updateAvailabilityHistory();
        updateAvailabilityCharts();
        updateAvailabilityDashboard();

        function recalcDowntimeFromHistory() {
            const nowH = new Date().getHours();
            Object.keys(state.availabilityHistory.inverters).forEach(k => {
                const hist = state.availabilityHistory.inverters[k] || [];
                let offlineHours = 0, events = 0, inEvent = false;
                for (let h = 0; h <= nowH; h++) {
                    const on = hist[h] === true;
                    if (!on) offlineHours += 1;
                    if (!on && !inEvent) { events += 1; inEvent = true; }
                    if (on && inEvent) inEvent = false;
                }
                if (!state.invDowntime[k]) state.invDowntime[k] = { totalHours: 0, faultHours: 0, noCommHours: 0, events: 0, lastStatus: STATUS.NOCOMM, lastChange: Date.now() };
                state.invDowntime[k].totalHours = offlineHours;
                state.invDowntime[k].faultHours = offlineHours;
                state.invDowntime[k].noCommHours = 0;
                state.invDowntime[k].events = events;
            });
        }
        function trackInvStatus(k) {
            if (!state.invDowntime[k]) state.invDowntime[k] = { totalHours: 0, faultHours: 0, noCommHours: 0, events: 0, lastStatus: STATUS.NOCOMM, lastChange: Date.now() };
            const dd = state.invDowntime[k];
            const newStatus = getInvStatus(k);
            if (newStatus !== STATUS.RUNNING) {
                dd.totalHours += 1 / 60;
                if (newStatus === STATUS.OFFLINE) dd.faultHours += 1 / 60;
                else if (newStatus === STATUS.NOCOMM) dd.noCommHours += 1 / 60;
            }
            if (dd.lastStatus === STATUS.RUNNING && (newStatus === STATUS.OFFLINE || newStatus === STATUS.NOCOMM)) {
                dd.events += 1;
            }
            dd.lastStatus = newStatus;
            dd.lastChange = Date.now();
        }
        function recordAvailability() {
            const now = new Date();
            const h = now.getHours();
            const m = now.getMinutes();
            const anyInvOn = Object.values(state.inverters).some(i => (i.power || 0) > 0);
            const hasAnyLiveData = state.vcbOnline || Object.values(state.inverters).some(i => i.hasData);
            const gridOn = state.vcbOnline ? state.vcbPower > 0 : null;
            const plantOn = hasAnyLiveData ? ((state.vcbPower > 0) || anyInvOn) : null;
            state.availabilityHistory.grid[h] = gridOn;
            state.availabilityHistory.plant[h] = plantOn;
            if (gridOn !== null) state.minuteSamples.grid.push({ h, m, on: gridOn });
            if (plantOn !== null) state.minuteSamples.plant.push({ h, m, on: plantOn });
            Object.keys(state.inverters).forEach(k => {
                if (!state.availabilityHistory.inverters[k]) state.availabilityHistory.inverters[k] = new Array(24).fill(null);
                if (!state.minuteSamples.inverters[k]) state.minuteSamples.inverters[k] = [];
                const inv = state.inverters[k];
                const hasLiveData = !!(inv && inv.hasData);
                const invOn = hasLiveData ? getInvStatus(k) === STATUS.RUNNING : null;
                state.availabilityHistory.inverters[k][h] = invOn;
                if (hasLiveData) {
                    state.minuteSamples.inverters[k].push({ h, m, on: invOn });
                    trackInvStatus(k);
                }
            });
            updateAvailabilityHistory();
            updateAvailabilityCharts();
            updateAvailabilityDashboard();
        }
        setInterval(recordAvailability, 60000);

        function updateAvailabilityHistory() {
            const nowH = new Date().getHours();
            const anyInvOn = Object.values(state.inverters).some(i => (i.power || 0) > 0);
            const hasAnyLiveData = state.vcbOnline || Object.values(state.inverters).some(i => i.hasData);
            state.availabilityHistory.grid[nowH] = state.vcbOnline ? state.vcbPower > 0 : null;
            state.availabilityHistory.plant[nowH] = hasAnyLiveData ? (state.vcbPower > 0 || anyInvOn) : null;
            Object.keys(state.inverters).forEach(k => {
                if (!state.availabilityHistory.inverters[k]) state.availabilityHistory.inverters[k] = new Array(24).fill(null);
                state.availabilityHistory.inverters[k][nowH] = state.inverters[k].hasData ? getInvStatus(k) === STATUS.RUNNING : null;
            });
        }
        function updateInvAvailability() {
            updateAvailabilityHistory();
            if (gridAvailChart && plantAvailChart) updateAvailabilityCharts();
            updateAvailabilityDashboard();
        }
        function updateDash() {
            updateInvAvailability();
        }

        function updateAvailabilityDashboard() {
            if (isHistoricalMode && historicalData) {
                const keys = historicalInverterKeys();
                const total = keys.length || (plantConfig[currentPlant] ? plantConfig[currentPlant].inverter_count : 18);

                const statusBucket = latestHistoricalBucketWithData();
                let running = 0, offline = 0, noComm = 0;

                keys.forEach(k => {
                    const devData = statusBucket ? statusBucket.devices[k] : null;
                    if (devData) {
                        if (devData.available) running++;
                        else offline++;
                    } else {
                        noComm++;
                    }
                });

                const timePeriod = 5; // Fixed 5-minute period for WebSocket data
                let activeHours = 0;
                let offlineHours = 0;

                keys.forEach(inv => {
                    historicalData.forEach(bucket => {
                        const devData = bucket.devices[inv];
                        if (devData) {
                            if (devData.available) {
                                activeHours += (timePeriod / 60);
                            } else {
                                offlineHours += (timePeriod / 60);
                            }
                        }
                    });
                });

                const totalDowntimeHours = offlineHours;
                const runningPct = total ? ((running / total) * 100).toFixed(2) : 0;
                const offlinePct = total ? ((offline / total) * 100).toFixed(2) : 0;
                const availability = (activeHours + totalDowntimeHours) > 0
                    ? ((activeHours / (activeHours + totalDowntimeHours)) * 100).toFixed(2)
                    : "0.00";

                document.getElementById('totalInvCount').textContent = total || '--';
                document.getElementById('runningInvCount').textContent = running;
                document.getElementById('runningInvPct').textContent = runningPct + '%';
                document.getElementById('offlineInvCount').textContent = offline;
                document.getElementById('offlineInvPct').textContent = offlinePct + '%';
                document.getElementById('todayDowntime').textContent = totalDowntimeHours.toFixed(2);
                document.getElementById('todayAvailability').textContent = availability + '%';

                updateInvTimeline();
                updateTimelineTicks();
                updateHistoricalCharts();

                if (currentStatusChart) {
                    currentStatusChart.data.datasets[0].data = [running, offline, noComm];
                    currentStatusChart.update('none');
                }
                return;
            }

            const keys = Object.keys(state.inverters).sort((a,b) => (parseInt(a.replace(/\D/g,''))||0) - (parseInt(b.replace(/\D/g,''))||0));
            const total = keys.length;
            let running = 0, offline = 0, noComm = 0;
            let totalDowntimeHours = 0;
            keys.forEach(k => {
                const st = getInvStatus(k);
                if (st === STATUS.RUNNING) running++;
                else if (st === STATUS.OFFLINE) offline++;
                else noComm++;
                const dd = state.invDowntime[k] || { totalHours: 0, events: 0 };
                totalDowntimeHours += dd.totalHours;
            });
            const runningPct = total ? ((running / total) * 100).toFixed(2) : 0;
            const offlinePct = total ? ((offline / total) * 100).toFixed(2) : 0;
            const liveCount = keys.filter(k => state.inverters[k] && state.inverters[k].hasData).length;
            const availability = total ? ((running / total) * 100).toFixed(2) : 0;
            document.getElementById('totalInvCount').textContent = total || '--';
            document.getElementById('runningInvCount').textContent = total ? running : '--';
            document.getElementById('runningInvPct').textContent = total ? runningPct + '%' : '--';
            document.getElementById('offlineInvCount').textContent = total ? offline : '--';
            document.getElementById('offlineInvPct').textContent = total ? offlinePct + '%' : '--';
            document.getElementById('todayDowntime').textContent = liveCount ? totalDowntimeHours.toFixed(2) : '--';
            document.getElementById('todayAvailability').textContent = total ? availability + '%' : '--';
            document.getElementById('timelineDate').textContent = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            updateInvTimeline();
            updateTimelineTicks();
            if (currentStatusChart) {
                currentStatusChart.data.datasets[0].data = [running, offline, noComm];
                currentStatusChart.update('none');
            }
        }
        function getStatusBlock(hist, h) {
            const status = hist[h];
            let start = h, end = h;
            while (start > 0 && hist[start - 1] === status) start--;
            while (end < 23 && hist[end + 1] === status) end++;
            return { start, end, status };
        }
        function updateInvTimeline() {
            const container = document.getElementById('invTimeline');
            if (!container) return;

            let keys = [];
            if (isHistoricalMode && historicalData) {
                keys = historicalInverterKeys();
            } else {
                keys = sortInverterKeys(Object.keys(state.availabilityHistory.inverters));
            }

            if (!keys.length) {
                container.innerHTML = '<div class="text-xs text-slate-400 italic">Waiting for inverter data...</div>';
                return;
            }

            if (isHistoricalMode && historicalData) {
                container.innerHTML = keys.map(k => {
                    const num = (k.match(/\d+/) || ['0'])[0];
                    const cells = historicalData.map((bucket, idx) => {
                        const devData = bucket.devices[k];
                        let color = 'bg-slate-200';
                        let statusText = 'No data';
                        if (devData) {
                            if (devData.available) {
                                color = 'bg-emerald-500';
                                statusText = 'ON';
                            } else if (devData.fault) {
                                color = 'bg-red-500';
                                statusText = 'Fault';
                            } else {
                                color = 'bg-red-500';
                                statusText = 'OFF';
                            }
                        }
                        const timeLabel = bucket.time;
                        return `<div class="flex-1 ${color} ${idx === 0 ? 'rounded-l-md' : ''} ${idx === historicalData.length - 1 ? 'rounded-r-md' : ''} hover:opacity-80 transition-opacity" title="${statusText} at ${timeLabel}"></div>`;
                    }).join('');

                    const statusBucket = latestHistoricalBucketWithData();
                    const lastDevData = statusBucket ? statusBucket.devices[k] : null;
                    const status = lastDevData ? (lastDevData.available ? STATUS.RUNNING : STATUS.OFFLINE) : STATUS.NOCOMM;

                    return `<div class="flex items-center gap-3">
                        <div class="w-20 flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full ${status === STATUS.RUNNING ? 'bg-emerald-500 animate-pulse' : status === STATUS.OFFLINE ? 'bg-red-500' : 'bg-slate-400'}"></div>
                            <span class="text-[10px] font-bold text-slate-600">Inv-${String(num).padStart(2,'0')}</span>
                        </div>
                        <div class="flex-1 flex h-7 border border-slate-200 rounded-md overflow-hidden">${cells}</div>
                    </div>`;
                }).join('');
            } else {
                const nowH = new Date().getHours();
                container.innerHTML = keys.map(k => {
                    const hist = state.availabilityHistory.inverters[k] || new Array(24).fill(null);
                    const num = (k.match(/\d+/) || ['0'])[0];
                    const status = getInvStatus(k);
                    const cells = hist.map((v, h) => {
                        let color = 'bg-slate-200';
                        if (h <= nowH) {
                            if (v === true) color = 'bg-emerald-500';
                            else if (v === false) color = 'bg-red-500';
                            else color = 'bg-slate-300';
                        }
                        const block = getStatusBlock(hist, h);
                        const startLabel = String(block.start).padStart(2, '0') + ':00';
                        const endLabel = String(block.end + 1).padStart(2, '0') + ':00';
                        const statusText = v === true ? 'ON' : v === false ? 'OFF' : 'No data';
                        return `<div class="flex-1 ${color} ${h === 0 ? 'rounded-l-md' : ''} ${h === 23 ? 'rounded-r-md' : ''} hover:opacity-80 transition-opacity" title="${statusText} ${startLabel} - ${endLabel}"></div>`;
                    }).join('');
                    return `<div class="flex items-center gap-3">
                        <div class="w-20 flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full ${status === STATUS.RUNNING ? 'bg-emerald-500 animate-pulse' : status === STATUS.OFFLINE ? 'bg-red-500' : 'bg-slate-400'}"></div>
                            <span class="text-[10px] font-bold text-slate-600">Inv-${String(num).padStart(2,'0')}</span>
                        </div>
                        <div class="flex-1 flex h-7 border border-slate-200 rounded-md overflow-hidden">${cells}</div>
                    </div>`;
                }).join('');
            }
        }
        let liveInvDataReceived = false;
        function ensureLiveInvHistory(devName) {
            if (!state.availabilityHistory.inverters[devName]) {
                state.availabilityHistory.inverters[devName] = new Array(24).fill(null);
            }
        }
        function connectWS() {
            const wsUrl = "wss://vinobasolar.scadahub.in:5001";
            if (!wsUrl) {
                const pulse = document.getElementById('refreshPulse');
                if (pulse) pulse.className = 'w-2.5 h-2.5 bg-amber-500 rounded-full';
                const status = document.getElementById('wsStatusText');
                if (status) { status.textContent = 'No WS URL'; status.className = 'text-[10px] font-bold text-amber-600'; }
                return;
            }
            const ws = new WebSocket(wsUrl);
            availabilityWs = ws;
            ws.onopen = function() {
                document.getElementById('refreshPulse').className = 'w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]';
                const status = document.getElementById('wsStatusText');
                if (status) { status.textContent = 'Live'; status.className = 'text-[10px] font-bold text-emerald-600'; }
                ws.send(JSON.stringify({ type: "subscribe", unit_id: wsUnitId }));
                ws.send(JSON.stringify({ type: "get_devices", unit_id: wsUnitId }));
                if (!defaultWsHistoryLoaded) {
                    defaultWsHistoryLoaded = true;
                    setTimeout(fetchHistoricalAvailability, 500);
                }
            };
            ws.onmessage = function(e) {
                try {
                    let d = JSON.parse(e.data);
                    if (d.unit_id && d.unit_id !== wsUnitId) return;
                    window.LiveWsStore?.storeMessage(d, currentPlant);
                    if (d.type === 'analytics_data_result') {
                        handleAvailabilityAnalyticsResult(d);
                        return;
                    }
                    if (d.type === 'daily_data_result') {
                        cacheDailyRows(d);
                        const devName = d.deviceName || d.device || '';
                        if (devName && Array.isArray(d.data)) {
                            const isVCB = /vcb/i.test(devName);
                            if (isVCB) {
                                d.data.forEach(row => {
                                    if (!row || !row.values) return;
                                    const timeStr = row.time || row.timestamp || '';
                                    if (!timeStr) return;
                                    const timeParts = timeStr.split(' ');
                                    const timeOnly = timeParts.length > 1 ? timeParts[1] : timeParts[0];
                                    const h = parseInt(timeOnly.split(':')[0], 10);
                                    if (Number.isInteger(h) && h >= 0 && h < 24) {
                                        const pwr = parseFloat(row.values["3 Phase Active Power"]) || 0;
                                        state.availabilityHistory.grid[h] = (pwr > 0);
                                    }
                                });
                            } else {
                                const canon = canonicalInverterName(devName);
                                ensureAvailabilityInverter(canon);
                                ensureLiveInvHistory(canon);
                                d.data.forEach(row => {
                                    if (!row || !row.values) return;
                                    const timeStr = row.time || row.timestamp || '';
                                    if (!timeStr) return;
                                    const timeParts = timeStr.split(' ');
                                    const timeOnly = timeParts.length > 1 ? timeParts[1] : timeParts[0];
                                    const h = parseInt(timeOnly.split(':')[0], 10);
                                    if (Number.isInteger(h) && h >= 0 && h < 24) {
                                        const available = inverterAvailableFromValues(row.values);
                                        state.availabilityHistory.inverters[canon][h] = available === null ? null : available;
                                    }
                                });
                            }

                            // Recalculate plant status for each hour
                            for (let h = 0; h < 24; h++) {
                                const gridOn = state.availabilityHistory.grid[h];
                                const anyInvOn = Object.keys(state.availabilityHistory.inverters).some(k => {
                                    const hist = state.availabilityHistory.inverters[k];
                                    return hist && hist[h] === true;
                                });
                                if (gridOn !== null || anyInvOn) {
                                    state.availabilityHistory.plant[h] = (gridOn || anyInvOn);
                                }
                            }
                            recalcDowntimeFromHistory();
                        }
                        if (pendingWsHistory) {
                            pendingWsHistory.remaining = Math.max(0, pendingWsHistory.remaining - 1);
                            if (pendingWsHistory.remaining === 0) renderPendingWsHistory();
                        }

                        const latest = Array.isArray(d.data) && d.data.length ? d.data[d.data.length - 1] : null;
                        if (!latest || !latest.values) return;
                        d = { type: 'data', unit_id: d.unit_id, task: /vcb/i.test(d.deviceName || latest.device || '') ? 'VCB' : 'Inverter', device: latest.device || d.deviceName, time: latest.time || '', values: latest.values };
                    }
                    if (d.type === 'device_list') {
                        applyDeviceList(d.devices);
                        return;
                    }
                    if (d.unit_id !== wsUnitId) return;
                    if (d.values && d.values["3 Phase Active Power"] !== undefined) {
                        state.vcbPower = parseFloat(d.values["3 Phase Active Power"]) || 0;
                        state.vcbOnline = true;
                    }
                    if (d.virtualTags && d.virtualTags["vcb-today"] !== undefined) state.dailyEnergy = parseFloat(d.virtualTags["vcb-today"].value) || 0;
                    else if (d.values && d.values["Active Total Export"] !== undefined) {
                    }
                    const keys = d.values ? Object.keys(d.values) : [];
                    const taskStr = d.task ? d.task.toString().toLowerCase() : '';
                    const deviceStr = d.device ? d.device.toString().toLowerCase() : '';
                    if (taskStr === 'vcb' || deviceStr.includes('vcb')) {
                        updateDash();
                        return;
                    }

                    const hasInvPower = keys.some(pk => {
                        const pkl = pk.toLowerCase();
                        return (/power/.test(pkl) && /active|ac/.test(pkl) && !/reactive|apparent/.test(pkl));
                    });
                    const hasNumberedCurrents = keys.some(k => /\d/.test(k) && /curr|current|amp/i.test(k) && !/phase|3.phase|reactive|apparent|freq|temp/i.test(k.toLowerCase()));
                    if (hasInvPower || hasNumberedCurrents || taskStr === 'inverter') {
                        const usedKeys = new Set();
                        const byNum = {};
                        for (const k of keys) {
                            const num = k.match(/(\d+)/);
                            if (!num) continue;
                            const n = parseInt(num[1]);
                            if (!byNum[n]) byNum[n] = [];
                            byNum[n].push(k);
                        }
                        const strings = [];
                        let a = 0, t = 0;
                        for (const n in byNum) {
                            const group = byNum[n];
                            let currKey = '', voltKey = '';
                            for (const k of group) {
                                const kl = k.toLowerCase();
                                if (usedKeys.has(k)) continue;
                                if (/phase|phasa|ph_|r.phase|y.phase|b.phase|a.phase|c.phase|3.phase|three.phase/i.test(kl)) continue;
                                if (/inverter.*curr|inv.*curr|total.*curr|grid.*curr|load.*curr|reactive.*curr|mppt.*curr|dc.*curr/i.test(kl)) continue;
                                if (/freq|temperature|temp|ambient|cosphi|pf.*_/i.test(kl)) continue;
                                if (!currKey && /\b(curr|current|amp|i)\b/i.test(kl) && !/\b(volt|voltage|temp|freq)\b/i.test(kl)) {
                                    currKey = k;
                                }
                                if (!voltKey && /\b(volt|voltage|v)\b/i.test(kl) && !/\b(curr|current|amp|i)\b/i.test(kl)) {
                                    voltKey = k;
                                }
                            }
                            if (currKey) {
                                usedKeys.add(currKey);
                                if (voltKey) usedKeys.add(voltKey);
                                const curr = parseFloat(d.values[currKey]) || 0;
                                const volt = voltKey ? (parseFloat(d.values[voltKey]) || 0) : 0;
                                strings.push({ n: parseInt(n), curr, volt, active: curr > 0.5 });
                                t++;
                                if (curr > 0.5) a++;
                            }
                        }
                        strings.sort((x,y) => x.n - y.n);
                        let dailyGen = 0, totalGen = 0;
                        if (d.values["Daily power yields"] !== undefined) dailyGen = parseFloat(d.values["Daily power yields"]) || 0;
                        else if (d.values["daily generation"] !== undefined) dailyGen = parseFloat(d.values["daily generation"]) || 0;

                        if (d.values["Total power yields precise"] !== undefined) totalGen = parseFloat(d.values["Total power yields precise"]) || 0;
                        else if (d.values["Total power yields"] !== undefined) totalGen = parseFloat(d.values["Total power yields"]) || 0;
                        else if (d.values["total generation"] !== undefined) totalGen = parseFloat(d.values["total generation"]) || 0;

                        const devName = canonicalInverterName(d.device || "Unknown Inverter");
                        liveInvDataReceived = true;
                        ensureAvailabilityInverter(devName);
                        ensureLiveInvHistory(devName);
                        const existing = state.inverters[devName] || {};
                        const hasTelemetryPacket = Object.keys(d.values || {}).length > 0;
                        const hasNumericTelemetry = hasAnyNumericValue(d.values);
                        let pwr = 0;
                        let hasPowerValue = false;
                        if (d.values["Total active power"] !== undefined) pwr = parseFloat(d.values["Total active power"]) || 0;
                        else {
                            for (const pk in d.values) {
                                const pkl = pk.toLowerCase();
                                if (/active.*power|ac.*power/i.test(pkl) && !/reactive|apparent|nominal|3.phase/i.test(pkl)) {
                                    pwr = parseFloat(d.values[pk]) || 0; hasPowerValue = true; break;
                                }
                            }
                        }
                        if (d.values["Total active power"] !== undefined) hasPowerValue = d.values["Total active power"] !== null;

                        let dcPwr = 0, acCurr = 0, acVolt = 0;
                        for (const pk in d.values) {
                            const pkl = pk.toLowerCase();
                            if (/dc.*power|d\.c\..*power/i.test(pkl)) dcPwr = parseFloat(d.values[pk]) || 0;
                            if (/a\.c\..*current|ac.*current/i.test(pkl)) acCurr = parseFloat(d.values[pk]) || 0;
                            if (/a\.c\..*voltage|ac.*voltage/i.test(pkl)) acVolt = parseFloat(d.values[pk]) || 0;
                        }
                        state.inverters[devName] = {
                            active: t > 0 ? a : (existing.active || 0),
                            total: t > 0 ? t : (existing.total || 0),
                            power: hasPowerValue ? pwr : (hasNumericTelemetry ? 0 : (existing.power || 0)),
                            dcPower: dcPwr || 0,
                            acCurrent: acCurr || 0,
                            acVoltage: acVolt || 0,
                            dailyGen: dailyGen || existing.dailyGen || 0,
                            totalGen: totalGen || existing.totalGen || 0,
                            strings: strings.length ? strings : (existing.strings || []),
                            faultCode: readFaultCode(d.values),
                            hasFault: (() => {
                                const fc = readFaultCode(d.values);
                                return Number.isFinite(parseFloat(fc)) && parseFloat(fc) > 0;
                            })(),
                            lastSeen: Date.now(),
                            hasData: hasTelemetryPacket
                        };
                    }
                    if (!isHistoricalMode) {
                        updateDash();
                    }
                } catch(err) { console.error('[Availability] WebSocket message error:', err); }
            };
            ws.onclose = function() {
                document.getElementById('refreshPulse').className = 'w-2.5 h-2.5 bg-red-500 rounded-full';
                const status = document.getElementById('wsStatusText');
                if (status) { status.textContent = 'Disconnected'; status.className = 'text-[10px] font-bold text-red-500'; }
                setTimeout(connectWS, 2000);
            };
        }

        function historicalHasRows(rows) {
            return Array.isArray(rows) && rows.some(bucket => {
                return (bucket.vcb && bucket.vcb.available !== null && bucket.vcb.available !== undefined) || Object.keys(bucket.devices || {}).length > 0;
            });
        }

        function availabilityAnalyticsPoints(message) {
            const rawDevice = String(message.deviceName || message.device || message.request?.device || '');
            let points = message.data ?? message.analyticsData ?? message.result ?? [];
            if (!Array.isArray(points) && points && typeof points === 'object') {
                points = points[rawDevice] ?? points.data ?? points.points ?? Object.values(points).flat();
            }
            return Array.isArray(points) ? points : [];
        }

        function buildUniversalAvailability(date) {
            const period = 5;
            const buckets = [];
            for (let minute = 0; minute < 24 * 60; minute += period) {
                const hh = String(Math.floor(minute / 60)).padStart(2, '0');
                const mm = String(minute % 60).padStart(2, '0');
                buckets.push({ time: `${date} ${hh}:${mm}`, devices: {}, vcb: { available: null } });
            }

            Object.entries(analyticsRowsByDevice).forEach(([device, points]) => {
                const canon = ensureAvailabilityInverter(device);
                points.forEach(point => {
                    const stamp = String(point?.timestamp ?? point?.dateTime ?? point?.datetime ?? `${point?.date || date} ${point?.time || ''}`);
                    const match = stamp.match(/(\d{4})-(\d{2})-(\d{2})[T\s](\d{1,2}):(\d{2})/);
                    if (!match || `${match[1]}-${match[2]}-${match[3]}` !== date) return;
                    const minute = Number(match[4]) * 60 + Number(match[5]);
                    // Keep the 24-hour UI, but treat non-generating night hours as
                    // no-data. Availability is measured only in the solar operating
                    // window requested for this plant: 06:00 through 19:30.
                    if (minute < 6 * 60 || minute > (19 * 60 + 30)) return;
                    const index = Math.min(buckets.length - 1, Math.max(0, Math.floor(minute / period)));
                    const value = Number(point?.value ?? point?.last ?? point?.aggregatedValue);
                    if (Number.isFinite(value)) buckets[index].devices[canon] = { available: value > 0.5, fault: value <= 0.5 };
                });
            });

            buckets.forEach(bucket => {
                if (Object.keys(bucket.devices).length) bucket.vcb.available = bucketHasActiveInverter(bucket);
            });
            return buckets;
        }

        function finishAvailabilityAnalytics(token) {
            if (token !== analyticsRequestToken) return;
            if (analyticsFinishTimer) { clearTimeout(analyticsFinishTimer); analyticsFinishTimer = null; }
            const date = document.getElementById('histDate').value;
            historicalData = buildUniversalAvailability(date);
            isHistoricalMode = true;
            const received = Object.keys(analyticsRowsByDevice).length;
            document.getElementById('timelineDate').textContent = received
                ? `${date} 00:00–23:59 · Vinoba Universal Analytics · ${received}/${analyticsExpectedDevices.length} inverters`
                : `No Universal Analytics data found for ${date}`;
            updateAvailabilityDashboard();
            updateTimelineTicks();
        }

        function handleAvailabilityAnalyticsResult(message) {
            const rawDevice = String(message.deviceName || message.device || message.request?.device || '');
            if (!/inv/i.test(rawDevice)) return;
            analyticsRowsByDevice[rawDevice] = availabilityAnalyticsPoints(message);
            analyticsReceivedDevices.add(rawDevice.toLowerCase());
            const complete = analyticsExpectedDevices.length && analyticsExpectedDevices.every(name => analyticsReceivedDevices.has(name.toLowerCase()));
            if (complete) finishAvailabilityAnalytics(analyticsRequestToken);
        }

        function fetchHistoricalAvailability() {
            const date = document.getElementById('histDate').value || localDateString();
            if (!availabilityWs || availabilityWs.readyState !== WebSocket.OPEN) {
                document.getElementById('timelineDate').textContent = 'Waiting for WebSocket connection...';
                setTimeout(fetchHistoricalAvailability, 750);
                return;
            }
            let devices = (latestDeviceList || [])
                .map(device => typeof device === 'string' ? device : (device.name || device.device || ''))
                .filter(name => /inv/i.test(name));
            if (!devices.length) {
                const count = Number(plantConfig[currentPlant]?.inverter_count || 0);
                devices = Array.from({ length: count }, (_, i) => `inverter${i + 1}`);
            }

            const token = ++analyticsRequestToken;
            analyticsRowsByDevice = {};
            analyticsExpectedDevices = devices.slice();
            analyticsReceivedDevices = new Set();
            document.getElementById('timelineDate').textContent = `Loading ${date} from Vinoba Universal Analytics...`;
            devices.forEach(device => availabilityWs.send(JSON.stringify({
                type: 'get_analytics_data', unit_id: wsUnitId, device,
                tag: 'Total active power', startDate: date, endDate: date,
                startTime: '00:00', endTime: '23:59', timePeriod: '5', method: 'last'
            })));
            if (analyticsFinishTimer) clearTimeout(analyticsFinishTimer);
            analyticsFinishTimer = setTimeout(() => finishAvailabilityAnalytics(token), 15000);
        }

        function clearHistoricalAvailability() {
            document.getElementById('histDate').value = localDateString();
            fetchHistoricalAvailability();
        }

        function updateTimelineTicks() {
            const container = document.getElementById('timelineTicks');
            if (!container) return;
            container.innerHTML = Array.from({length: 24}, (_, i) => String(i).padStart(2, '0') + ':00')
                .map(t => `<div class="flex-1 text-center">${t}</div>`).join('');
        }

        function updateHistoricalCharts() {
            if (!gridAvailChart || !plantAvailChart || !historicalData) return;

            const labels = historicalData.map(bucket => bucket.time);

            const gridData = historicalData.map(bucket => {
                const available = bucketGridAvailable(bucket);
                return available === null ? null : (available ? 100 : 0);
            });

            const plantData = historicalData.map(bucket => {
                const available = bucketPlantAvailable(bucket);
                return available === null ? null : (available ? 100 : 0);
            });

            gridAvailChart.data.labels = labels;
            gridAvailChart.data.datasets[0].data = gridData;
            gridAvailChart.options.scales.x.ticks.autoSkip = true;
            gridAvailChart.options.scales.x.ticks.maxTicksLimit = 12;
            gridAvailChart.update('none');

            plantAvailChart.data.labels = labels;
            plantAvailChart.data.datasets[0].data = plantData;
            plantAvailChart.options.scales.x.ticks.autoSkip = true;
            plantAvailChart.options.scales.x.ticks.maxTicksLimit = 12;
            plantAvailChart.update('none');
        }

        function restoreLiveCharts() {
            if (!gridAvailChart || !plantAvailChart) return;

            gridAvailChart.data.labels = hourLabels;
            plantAvailChart.data.labels = hourLabels;
            gridAvailChart.options.scales.x.ticks.autoSkip = false;
            gridAvailChart.options.scales.x.ticks.maxTicksLimit = 25;
            plantAvailChart.options.scales.x.ticks.autoSkip = false;
            plantAvailChart.options.scales.x.ticks.maxTicksLimit = 25;

            updateAvailabilityCharts();
        }

        // One date controls a fixed 24-hour Universal Analytics query.
        const todayStr = localDateString();
        document.getElementById('histDate').value = todayStr;

        let availabilityAutoFetchTimer = null;
        function scheduleAvailabilityFetch() {
            clearTimeout(availabilityAutoFetchTimer);
            availabilityAutoFetchTimer = setTimeout(fetchHistoricalAvailability, 350);
        }
        document.getElementById('histDate').addEventListener('change', scheduleAvailabilityFetch);
        setInterval(() => {
            if (document.getElementById('histDate').value === localDateString()) fetchHistoricalAvailability();
        }, 60000);

        connectWS();
    </script>
</body>
</html>

