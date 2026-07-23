<?php require 'check_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title id="pageTitle">Solar Plant – Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/live_ws_store.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .gauge-container { position: relative; width: 100px; height: 60px; margin: 0 auto; }
        .gauge-bg { fill: none; stroke: #e2e8f0; stroke-width: 10; stroke-linecap: round; }
        .gauge-fill { fill: none; stroke: #10b981; stroke-width: 10; stroke-linecap: round; transition: stroke-dasharray 0.5s ease; }
        .gauge-text { position: absolute; bottom: 0; left: 0; right: 0; text-align: center; font-size: 14px; font-weight: 800; color: #10b981; }
        .gauge-label { text-align: center; font-size: 10px; color: #64748b; margin-top: 4px; font-weight: 600; }
        .status-box { display: flex; align-items: center; justify-content: center; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .status-on { background: #dcfce7; color: #166534; }
        .status-off { background: #fee2e2; color: #991b1b; }
        .inv-bar { height: 24px; border-radius: 4px; transition: width 0.5s ease; }
        .table-row-alt:nth-child(even) { background: #f8fafc; }
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
                        <div><h2 class="text-lg font-black text-slate-800 tracking-tight" id="headerPlantName">Plant Overview</h2></div>
                    </div>
                    <div class="flex items-center gap-3 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                        <div id="refreshPulse" class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                        <span class="text-[10px] font-bold text-slate-500" id="wsStatusText">Connecting...</span>
                        <span class="text-xs font-bold text-slate-600 tracking-widest hidden sm:inline" id="clockDisplay">--:--:--</span>
                    </div>
                </div>
            </header>

            <div class="p-3 sm:p-4 w-full flex flex-col gap-4 max-w-[1600px] mx-auto">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Plant Profile</h3>
                        <p class="font-black text-slate-800 text-xl mb-1" id="headerInfoName">--</p>
                        <p class="text-sm font-bold text-emerald-600"><span id="headerInfoSize">--</span> MW</p>
                        <p class="text-xs font-medium text-slate-500 mt-0.5" id="headerInfoLocation">Tamil Nadu</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Today Energy</h3>
                        <p class="font-black text-slate-800 text-2xl mb-1"><span id="headerInfoTotal">--</span> <span class="text-sm font-black text-purple-600">kWh</span></p>
                        <p class="text-xs font-medium text-slate-500">Peak Today: <span class="font-bold text-slate-700" id="headerInfoPeakPower">--</span> kW</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Live Active Power</h3>
                        <p class="font-black text-slate-800 text-2xl mb-1"><span id="headerInfoPower">--</span> <span class="text-sm font-black text-blue-600">kW</span></p>
                        <p class="text-xs font-medium text-slate-500">Peak Today: <span class="font-bold text-slate-700" id="headerInfoLivePeak">--</span> kW</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Weather</h3>
                        <div class="space-y-2 text-[11px]">
                            <div class="flex justify-between items-center">
                                <span class="font-black text-slate-500 uppercase tracking-wider">Radiation</span>
                                <span class="font-bold text-orange-500"><span id="radiationVal">--</span> W/m2</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-black text-slate-500 uppercase tracking-wider">Panel Temp</span>
                                <span class="font-bold text-red-500"><span id="panelTempVal">--</span> degC</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-black text-slate-500 uppercase tracking-wider">Wind Speed</span>
                                <span class="font-bold text-blue-500"><span id="windSpeedVal">--</span> m/s</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col h-[240px] sm:h-[340px]">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Solar Power (kW) - Hourly</h3>
                        <div class="flex gap-3 text-[10px]">
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Solar Power</span>
                        </div>
                    </div>
                    <div class="flex-grow relative"><canvas id="powerChart"></canvas></div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col h-[240px] sm:h-[340px]">
                    <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Actual Vs Expected Weekly Solar Energy (kWh) - Last 7 Days</h3>
                    <div class="flex-grow relative"><canvas id="expectedChart"></canvas></div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col items-center justify-center">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Performance Ratio</h3>
                        <div class="gauge-container">
                            <svg viewBox="0 0 100 60" class="w-full h-full">
                                <path d="M 10 50 A 40 40 0 0 1 90 50" class="gauge-bg"/>
                                <path id="gaugePrPct" d="M 10 50 A 40 40 0 0 1 90 50" class="gauge-fill" stroke-dasharray="0 126" />
                            </svg>
                            <div class="gauge-text" id="prPctVal">0%</div>
                        </div>
                        <div class="gauge-label">PR(%)</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col items-center justify-center">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Performance Ratio</h3>
                        <div class="gauge-container">
                            <svg viewBox="0 0 100 60" class="w-full h-full">
                                <path d="M 10 50 A 40 40 0 0 1 90 50" class="gauge-bg"/>
                                <path id="gaugePrKwh" d="M 10 50 A 40 40 0 0 1 90 50" class="gauge-fill" stroke-dasharray="0 126" />
                            </svg>
                            <div class="gauge-text" id="prKwhVal">0%</div>
                        </div>
                        <div class="gauge-label">PR(kWh)</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 text-center">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Inverter Power</h3>
                        <p class="font-black text-emerald-600 text-3xl" id="invPowerVal">0</p>
                        <p class="text-[10px] text-slate-500 font-bold mt-1">kW</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 text-center">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Today In...</h3>
                        <p class="font-black text-emerald-600 text-3xl" id="todayGenVal">0</p>
                        <p class="text-[10px] text-slate-500 font-bold mt-1">kWh</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 text-center">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">WS Source</h3>
                        <p class="font-black text-orange-500 text-3xl" id="expectedVal">0</p>
                        <p class="text-[10px] text-slate-500 font-bold mt-1">kWh</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 text-center">
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Co2 Red.</h3>
                        <p class="font-black text-blue-600 text-3xl" id="co2Val">0</p>
                        <p class="text-[10px] text-slate-500 font-bold mt-1">t</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                    <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Inverter Status (based on strings)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4" id="invStringStatusGrid">
                        <div class="text-xs text-slate-400 italic col-span-full">Waiting for inverter data...</div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 overflow-x-auto">
                    <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Inverter Parameters</h3>
                    <table class="w-full min-w-[480px] text-xs border-collapse border border-slate-400">
                        <thead>
                            <tr class="font-bold">
                                <th class="text-center p-2 text-slate-700 border border-slate-400">Inverter #</th>
                                <th class="text-center p-2 text-orange-600 border border-slate-400">AC Power (kW)</th>
                                <th class="text-center p-2 text-blue-600 border border-slate-400">DC Power (kW)</th>
                                <th class="text-center p-2 text-blue-600 border border-slate-400">AC Current (A)</th>
                                <th class="text-center p-2 text-blue-600 border border-slate-400">AC Voltage (V)</th>
                            </tr>
                        </thead>
                        <tbody id="invTableBody">
                            <tr><td colspan="5" class="text-center p-4 text-slate-400 italic">Waiting for inverter telemetry...</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

    <div id="stringModal" class="fixed inset-0 bg-slate-900 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-lg font-black text-slate-800" id="stringModalTitle">String Details</h3>
                <button onclick="closeStringModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-700 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-5 overflow-y-auto" id="stringModalBody">
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3" id="stringGrid"></div>
            </div>
        </div>
    </div>

    <script>
        const currentPlant = '<?php echo addslashes($currentPlant); ?>';
        const wsUnitId = "<?php echo getPlantWsUnitId($currentPlant); ?>";
        const plantConfig = <?php echo getPlantPublicConfigJson(); ?>;
        const cfg = plantConfig[currentPlant] || { name: currentPlant, capacity: '--', location: '--' };
        document.getElementById('pageTitle').textContent = cfg.name + ' - Home';
        document.getElementById('headerPlantName').textContent = cfg.name;
        document.getElementById('headerInfoName').textContent = cfg.name;
        document.getElementById('headerInfoSize').textContent = cfg.capacity;
        if (document.getElementById('headerInfoLocation')) document.getElementById('headerInfoLocation').textContent = cfg.location;
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
        let powerChart, expectedChart;
        function buildWeekLabels() {
            const labels = [];
            for (let i = 6; i >= 0; i--) {
                const d = new Date();
                d.setDate(d.getDate() - i);
                labels.push(d.toLocaleDateString('en-IN', { weekday: 'short' }));
            }
            return labels;
        }
        function initCharts() {
            Chart.defaults.color = '#64748b';
            Chart.defaults.font.size = 10;
            powerChart = new Chart(document.getElementById('powerChart'), {
                type: 'line',
                data: { labels: [], datasets: [
                    { label: 'Solar Power', borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', borderWidth: 2, fill: true, tension: 0.3, pointRadius: 0, data: [] }
                ]},
                options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false } },
                    scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 24, maxRotation: 45, minRotation: 45 } },
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' } } }
                }
            });
            const weekLabels = buildWeekLabels();
            expectedChart = new Chart(document.getElementById('expectedChart'), {
                type: 'bar',
                data: { labels: weekLabels, datasets: [
                    { label: 'WS Daily Energy', backgroundColor: '#10b981', borderRadius: 3, barPercentage: 0.6, data: new Array(7).fill(0) }
                ]},
                options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true, font: { size: 10 } } } },
                    scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 7 } },
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' } } }
                }
            });
        }
        initCharts();
        
        const state = { vcbPower: 0, dailyEnergy: 0, inverters: {}, peakPower: 0, expectedEnergy: 0, weather: { radiation: 0, panelTemp: 0, windSpeed: 0 } };
        const invData = state.inverters;
        let globalVacAB = 0, globalVacBC = 0, globalVacCA = 0;
        let chartHasData = false;
        let dashRenderTimer = null;

        function scheduleDashUpdate() {
            clearTimeout(dashRenderTimer);
            updateDash();
            dashRenderTimer = null;
        }

        function canonicalInverterName(name) {
            const match = (name || '').toString().match(/\d+/);
            return match ? `INVERTER${parseInt(match[0], 10)}` : (name || 'INVERTER').toString().toUpperCase().replace(/\s+/g, '');
        }

        

        function applyDeviceList(devices) {
            if (!Array.isArray(devices)) return;
            devices.forEach(device => {
                const name = (device.name || device.device || '').toString();
                if (!/inv/i.test(name)) return;
                const key = canonicalInverterName(name);
                if (!invData[key]) {
                    invData[key] = { power: 0, reactive: 0, pf: 0, dailyGen: 0, totalGen: 0, dcPower: 0, acCurrent: 0, acVoltage: 0, activeStr: 0, totalStr: 0, strings: [] };
                }
            });
            updateDash();
        }

        function setGauge(id, val, max=100) {
            const pct = Math.min(Math.max(val / max, 0), 1);
            const arcLen = 126;
            const fill = pct * arcLen;
            const el = document.getElementById(id);
            if (el) el.setAttribute('stroke-dasharray', `${fill} ${arcLen}`);
        }
        function pushChartPoint() {
            chartHasData = true;
            const h = new Date().getHours();
            const label = String(h).padStart(2,'0') + ':00';
            if (powerChart.data.labels.includes(label)) {
                powerChart.data.datasets[0].data[powerChart.data.labels.length - 1] = state.vcbPower;
            } else {
                powerChart.data.labels.push(label);
                powerChart.data.datasets[0].data.push(state.vcbPower);
            }
            if (powerChart.data.labels.length > 24) {
                powerChart.data.labels.shift();
                powerChart.data.datasets.forEach(ds => ds.data.shift());
            }
            powerChart.update('none');
        }
        function pushExpectedPoint() {
            const lastIdx = expectedChart.data.labels.length - 1;
            if (lastIdx >= 0) {
                expectedChart.data.datasets[0].data[lastIdx] = state.dailyEnergy;
                expectedChart.update('none');
            }
        }
        function buildStringDots(inv) {
            if (!inv.strings || !inv.strings.length) return '';
            return `<div class="grid grid-cols-8 gap-1 mt-3 mb-3">${inv.strings.map(s =>
                `<div class="w-3 h-3 rounded-full ${s.active ? 'bg-emerald-500' : 'bg-red-400'}" title="String ${s.n}: ${s.curr.toFixed(1)}A / ${s.volt.toFixed(1)}V"></div>`
            ).join('')}</div>`;
        }
        function openStringModal(invName) {
            const inv = state.inverters[invName];
            if (!inv || !inv.strings) return;
            document.getElementById('stringModalTitle').textContent = invName + ' - String Details';
            const grid = document.getElementById('stringGrid');
            grid.innerHTML = inv.strings.map(s => {
                const ok = s.active;
                return `<div class="border ${ok ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'} rounded-lg p-2 text-center">
                    <p class="text-[10px] font-bold ${ok ? 'text-emerald-700' : 'text-red-700'} uppercase tracking-wider">String ${s.n}</p>
                    <p class="mt-1 text-sm font-black ${ok ? 'text-slate-800' : 'text-red-700'}">${s.curr.toFixed(1)} <span class="text-[10px] text-slate-500">A</span></p>
                    <p class="text-[10px] font-medium text-slate-500">${s.volt.toFixed(1)} V</p>
                </div>`;
            }).join('');
            document.getElementById('stringModal').classList.remove('hidden');
        }
        function closeStringModal() {
            document.getElementById('stringModal').classList.add('hidden');
        }
        document.getElementById('stringModal').addEventListener('click', function(e) {
            if (e.target === this) closeStringModal();
        });
        function updateInvStringStatus() {
            const grid = document.getElementById('invStringStatusGrid');
            if (!grid) return;
            const keys = Object.keys(state.inverters).sort((a,b) => (parseInt(a.replace(/\D/g,''))||0) - (parseInt(b.replace(/\D/g,''))||0));
            if (!keys.length) {
                grid.innerHTML = '<div class="text-xs text-slate-400 italic col-span-full">Waiting for inverter data...</div>';
                return;
            }
            grid.innerHTML = keys.map(k => {
                const inv = state.inverters[k];
                const active = inv.activeStr || inv.active || 0;
                const total = inv.totalStr || inv.total || 0;
                const num = (k.match(/\d+/) || ['0'])[0];
                const allOk = active > 0 && active === total;
                const someOk = active > 0 && active < total;
                const cardClass = allOk ? 'bg-emerald-50 border-emerald-100' : someOk ? 'bg-amber-50 border-amber-100' : 'bg-red-50 border-red-100';
                const badgeColor = allOk ? 'bg-emerald-100 text-emerald-700' : someOk ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700';
                return `<div class="${cardClass} border rounded-xl p-5 shadow-sm flex flex-col justify-between h-[120px]">
                    <div class="flex justify-between items-start">
                        <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider">INVERTER${String(num).padStart(2,'0')}</span>
                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full ${badgeColor}">${active}/${total} STRINGS</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-black text-slate-800">${inv.power.toFixed(1)}</span>
                        <span class="text-sm font-black text-blue-600">kW</span>
                    </div>
                </div>`;
            }).join('');
        }
        function updateInvAvailability() {
            updateInvStringStatus();
        }
        function updateInvTable() {
            const tbody = document.getElementById('invTableBody');
            const keys = Object.keys(state.inverters).sort((a,b) => (parseInt(a.replace(/\D/g,''))||0) - (parseInt(b.replace(/\D/g,''))||0));
            if (!keys.length) return;
            tbody.innerHTML = keys.map((k, i) => {
                const inv = state.inverters[k];
                const num = (k.match(/\d+/) || ['0'])[0];
                const dcPower = inv.dcPower || 0;
                const acCurrent = inv.acCurrent || 0;
                const acVoltage = inv.acVoltage || 0;
                return `<tr class="font-bold text-white">
                    <td class="p-2 text-center text-slate-700 bg-transparent border border-slate-400">Inverter${String(num).padStart(2,'0')}</td>
                    <td class="p-2 text-center bg-gradient-to-r from-orange-500 to-red-500 border border-slate-400">${inv.power.toFixed(1)}</td>
                    <td class="p-2 text-center bg-gradient-to-r from-blue-500 to-blue-600 border border-slate-400">${typeof dcPower === 'number' ? dcPower.toFixed(1) : dcPower}</td>
                    <td class="p-2 text-center bg-gradient-to-r from-blue-500 to-blue-600 border border-slate-400">${acCurrent}</td>
                    <td class="p-2 text-center bg-gradient-to-r from-blue-500 to-blue-600 border border-slate-400">${acVoltage}</td>
                </tr>`;
            }).join('');
        }
        function updateDash() {
            const invTotal = Object.values(state.inverters).reduce((s,i) => s + (i.power || 0), 0);
            const final = state.vcbPower > 0 ? state.vcbPower : invTotal;
            
            if (!state.dailyEnergy || state.dailyEnergy === 0) {
                state.dailyEnergy = Object.values(state.inverters).reduce((s,i) => s + (i.dailyGen || 0), 0);
            }
            
            const capacity = parseFloat(cfg.capacity) || 1;
            if (final > state.peakPower) state.peakPower = final;
            document.getElementById('invPowerVal').textContent = final.toFixed(0);
            document.getElementById('todayGenVal').textContent = state.dailyEnergy.toFixed(0);
            document.getElementById('headerInfoPower').textContent = final.toFixed(2);
            document.getElementById('headerInfoTotal').textContent = state.dailyEnergy.toFixed(2);
            document.getElementById('headerInfoPeakPower').textContent = state.peakPower.toFixed(2);
            document.getElementById('headerInfoLivePeak').textContent = state.peakPower.toFixed(2);
            const w = state.weather;
            if (document.getElementById('radiationVal')) document.getElementById('radiationVal').textContent = w.radiation ? w.radiation.toFixed(1) : '--';
            if (document.getElementById('panelTempVal')) document.getElementById('panelTempVal').textContent = w.panelTemp ? w.panelTemp.toFixed(1) : '--';
            if (document.getElementById('windSpeedVal')) document.getElementById('windSpeedVal').textContent = w.windSpeed ? w.windSpeed.toFixed(1) : '--';
            
            const totalGen = Object.values(state.inverters).reduce((s,i) => s + (i.totalGen || 0), 0);
            if (document.getElementById('co2Val')) {
                document.getElementById('co2Val').textContent = Object.values(state.inverters).some(i => i.dailyCO2 || i.totalCO2) ? (Object.values(state.inverters).reduce((s,i) => s + (i.dailyCO2 || 0), 0) / 1000).toFixed(1) : '--';
            }
            if (document.getElementById('expectedVal')) {
                document.getElementById('expectedVal').textContent = '--';
            }

            const prPct = capacity > 0 ? Math.min((final / (capacity * 1000)) * 100, 100) : 0;
            document.getElementById('prPctVal').textContent = prPct.toFixed(1) + '%';
            setGauge('gaugePrPct', prPct, 100);
            document.getElementById('prKwhVal').textContent = '--';
            setGauge('gaugePrKwh', 0, 100);
            updateInvAvailability();
            updateInvTable();
            pushChartPoint();
            pushExpectedPoint();
        }
        function buildHourlyLabels() {
            const now = new Date();
            const curHour = now.getHours();
            const endHour = Math.min(curHour, 19);
            const labels = [];
            for (let h = 5; h <= endHour; h++) {
                labels.push(String(h).padStart(2,'0') + ':00');
            }
            return labels;
        }
        function loadChartFromWSData(d) {
            if (!powerChart) return;
            let readings = d.data || d.records || d.results || d.values || [];
            if (!Array.isArray(readings) || readings.length === 0) return;
            const hourlyMap = {};
            readings.forEach(r => {
                let ts = r.timestamp || r.time || r.recorded_at || r.date || r.datetime || '';
                let vals = r.values || r.data || r;
                let pwr = 0;
                if (typeof vals === 'object' && !Array.isArray(vals)) {
                    for (const k in vals) {
                        const kl = k.toLowerCase();
                        if (/active.*power|3.*phase.*active|power.*active/i.test(kl) && !/reactive|apparent/i.test(kl)) {
                            pwr = parseFloat(vals[k]) || 0; break;
                        }
                    }
                } else if (typeof vals === 'number') {
                    pwr = vals;
                }
                if (ts) {
                    let safeTs = ts.toString().replace(" ", "T");
                    const dt = new Date(safeTs);
                    if (!isNaN(dt)) {
                        const hourKey = String(dt.getHours()).padStart(2,'0') + ':00';
                        if (!hourlyMap[hourKey] || pwr > hourlyMap[hourKey]) hourlyMap[hourKey] = pwr;
                    }
                }
            });
            const allLabels = buildHourlyLabels();
            if (allLabels.length > 1) {
                const allData = allLabels.map(l => hourlyMap[l] || 0);
                powerChart.data.labels = allLabels;
                powerChart.data.datasets[0].data = allData;
                
                chartHasData = true;
                powerChart.update('none');
                
            }
        }
        
        function connectWS() {
            const wsUrl = "<?php echo getPlantWsUrl($currentPlant); ?>";
            if (!wsUrl) { console.error('[WS] No wsUrl configured'); return; }
            console.log('[WS] Connecting to', wsUrl, 'unit_id=', wsUnitId);
            const ws = new WebSocket(wsUrl);
            ws.onopen = function() {
                console.log('[WS] Connected successfully');
                document.getElementById('refreshPulse').className = 'w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]';
                const status = document.getElementById('wsStatusText');
                if (status) { status.textContent = 'Connected'; status.className = 'text-[10px] font-bold text-emerald-600'; }
                ws.send(JSON.stringify({ type: "subscribe", unit_id: wsUnitId }));
                ws.send(JSON.stringify({ type: "get_devices", unit_id: wsUnitId }));
            };
            ws.onmessage = function(e) {
                try {
                    let d = JSON.parse(e.data);
                    console.log('[WS] RX:', d.type, d.unit_id, d.task, d.device);
                    if (d.unit_id && d.unit_id !== wsUnitId) return;
                    if (d.type === 'device_list') {
                        applyDeviceList(d.devices);
                        const today = new Date().toISOString().slice(0, 10);
                        (d.devices || []).forEach(device => {
                            const name = device.name || device.device || '';
                            if (/vcb|inv/i.test(name)) ws.send(JSON.stringify({ type: 'get_daily_data', unit_id: wsUnitId, device: name, date: today }));
                        });
                        return;
                    }
                    if (d.type === 'daily_data_result') {
                        const latest = Array.isArray(d.data) && d.data.length ? d.data[d.data.length - 1] : null;
                        if (!latest || !latest.values) return;
                        d = { type: 'data', unit_id: d.unit_id, task: /vcb/i.test(d.deviceName || latest.device || '') ? 'VCB' : 'Inverter', device: latest.device || d.deviceName, time: latest.time || '', values: latest.values };
                    }
                    if (!d.values) return;
                    const taskStr = d.task ? d.task.toString().toLowerCase() : '';
                    const deviceStr = d.device ? d.device.toString().toLowerCase() : '';
                    
                    if (taskStr === 'vcb' || deviceStr.includes('vcb')) {
                        const pwr = parseFloat(d.values["3 Phase Active Power"] || d.values["3 phase active power"] || 0) / 1000;
                        const totalExport = parseFloat(d.values["Active Total Export"] || 0);
                        const today = parseFloat(d.values["Today Energy"] || d.values["Day Energy"] || d.values["vcb-today"] || 0);
                        if (d.values["V12 (RY)"] !== undefined) globalVacAB = parseFloat(d.values["V12 (RY)"]) || 0;
                        if (d.values["V23 (YB)"] !== undefined) globalVacBC = parseFloat(d.values["V23 (YB)"]) || 0;
                        if (d.values["V31 (BR)"] !== undefined) globalVacCA = parseFloat(d.values["V31 (BR)"]) || 0;
                        if (pwr > 0) state.vcbPower = pwr;
                        if (today > 0) state.dailyEnergy = today;
                        else if (totalExport > 0 && !state.dailyEnergy) state.dailyEnergy = totalExport % 10000;
                        updateDash();
                        return;
                    }
                    
                    {
                        const keys = Object.keys(d.values);
                        const hasInvPower = keys.some(pk => {
                            const pkl = pk.toLowerCase();
                            return (/power/.test(pkl) && /active|ac/.test(pkl) && !/reactive|apparent/.test(pkl));
                        });
                        const hasNumberedCurrents = keys.some(k => /\d/.test(k) && /curr|current|amp/i.test(k) && !/phase|3.phase|reactive|apparent|freq|temp/i.test(k.toLowerCase()));
                        const isInv = (d.task && d.task.toString().toLowerCase() === 'inverter') || hasInvPower || hasNumberedCurrents;
                        if (!isInv) return;

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
                        let activeStr = 0, totalStr = 0;
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
                                totalStr++;
                                if (curr > 0.5) activeStr++;
                            }
                        }
                        strings.sort((x,y) => x.n - y.n);
                        const devName = canonicalInverterName(d.device || 'Inverter');
                        let pwr = 0, reactive = 0, pf = 0, freq = 0, amb = 0, eff = 0;
                        let vac_ab = 0, vac_bc = 0, vac_ca = 0;
                        let i_a = 0, i_b = 0, i_c = 0;
                        let dailyGen = 0, totalGen = 0, dailyCO2 = 0, totalCO2 = 0, dailyHrs = 0, totalHrs = 0;

                        if (d.values["Total active power"] !== undefined) pwr = parseFloat(d.values["Total active power"]) || 0;
                        else {
                            for (const pk in d.values) {
                                const pkl = pk.toLowerCase();
                                if (/active.*power|ac.*power/i.test(pkl) && !/reactive|apparent|nominal|3.phase/i.test(pkl)) {
                                    pwr = parseFloat(d.values[pk]) || 0; break;
                                }
                            }
                        }

                        if (d.values["Total reactive power"] !== undefined) reactive = parseFloat(d.values["Total reactive power"]) || 0;
                        if (d.values["Power factor"] !== undefined) pf = parseFloat(d.values["Power factor"]) || 0;
                        if (d.values["Grid frequency"] !== undefined) freq = parseFloat(d.values["Grid frequency"]) || 0;
                        if (d.values["Internal temperature"] !== undefined) amb = parseFloat(d.values["Internal temperature"]) || 0;
                        
                        if (d.values["Daily power yields"] !== undefined) dailyGen = parseFloat(d.values["Daily power yields"]) || 0;
                        if (d.values["Total power yields precise"] !== undefined) totalGen = parseFloat(d.values["Total power yields precise"]) || 0;
                        else if (d.values["Total power yields"] !== undefined) totalGen = parseFloat(d.values["Total power yields"]) || 0;
                        
                        if (d.values["Daily running time"] !== undefined) dailyHrs = parseFloat(d.values["Daily running time"]) || 0;
                        if (d.values["Total running time"] !== undefined) totalHrs = parseFloat(d.values["Total running time"]) || 0;

                        const dcPwr = parseFloat(d.values["Total DC power"]) || 0;
                        if (dcPwr > 0 && pwr > 0) {
                            eff = Math.min((pwr / (dcPwr / 1000)) * 100, 100);
                        }

                        for (const pk in d.values) {
                            const pkl = pk.toLowerCase();
                            const val = parseFloat(d.values[pk]) || 0;
                            
                            if (!vac_ab && (/voltage.*ab|v.*ab|ab.*voltage/i.test(pkl))) vac_ab = val;
                            else if (!vac_bc && (/voltage.*bc|v.*bc|bc.*voltage/i.test(pkl))) vac_bc = val;
                            else if (!vac_ca && (/voltage.*ca|v.*ca|ca.*voltage/i.test(pkl))) vac_ca = val;
                            else if (!i_a && (/current.*a|a.*phase.*current|i.*a\b/i.test(pkl) && !/voltage/i.test(pkl))) i_a = val;
                            else if (!i_b && (/current.*b|b.*phase.*current|i.*b\b/i.test(pkl) && !/voltage/i.test(pkl))) i_b = val;
                            else if (!i_c && (/current.*c|c.*phase.*current|i.*c\b/i.test(pkl) && !/voltage/i.test(pkl))) i_c = val;
                            else if (!dailyCO2 && /daily.*co2/i.test(pkl)) dailyCO2 = val;
                            else if (!totalCO2 && /total.*co2/i.test(pkl)) totalCO2 = val;
                            else if (!eff && /efficiency|eff/i.test(pkl)) eff = val;
                        }
                        if (vac_ab === 0 && globalVacAB > 0) vac_ab = globalVacAB;
                        if (vac_bc === 0 && globalVacBC > 0) vac_bc = globalVacBC;
                        if (vac_ca === 0 && globalVacCA > 0) vac_ca = globalVacCA;

                        if (!invData[devName]) invData[devName] = {};
                        invData[devName] = Object.assign({}, invData[devName], {
                            power: pwr || invData[devName].power || 0,
                            reactive: reactive || invData[devName].reactive || 0,
                            pf: pf || invData[devName].pf || 0,
                            vac_ab: vac_ab || invData[devName].vac_ab || 0,
                            vac_bc: vac_bc || invData[devName].vac_bc || 0,
                            vac_ca: vac_ca || invData[devName].vac_ca || 0,
                            freq: freq || invData[devName].freq || 0,
                            i_a: i_a || invData[devName].i_a || 0,
                            i_b: i_b || invData[devName].i_b || 0,
                            i_c: i_c || invData[devName].i_c || 0,
                            eff: eff || invData[devName].eff || 0,
                            amb: amb || invData[devName].amb || 0,
                            dailyGen: dailyGen || invData[devName].dailyGen || 0,
                            totalGen: totalGen || invData[devName].totalGen || 0,
                            dailyCO2: dailyCO2 || invData[devName].dailyCO2 || 0,
                            totalCO2: totalCO2 || invData[devName].totalCO2 || 0,
                            dailyHrs: dailyHrs || invData[devName].dailyHrs || 0,
                            totalHrs: totalHrs || invData[devName].totalHrs || 0,
                            activeStr, totalStr, strings
                        });
                        state.dailyEnergy = Object.values(state.inverters).reduce((s,i) => s + (i.dailyGen || 0), 0) || state.dailyEnergy;
                        scheduleDashUpdate();
                    }
                } catch(err) { console.error('[WS] message error:', err); }
            };
            ws.onerror = function(e) {
                console.error('[WS] Connection error:', e);
            };
            ws.onclose = function() {
                console.warn('[WS] Disconnected, reconnecting in 5s...');
                document.getElementById('refreshPulse').className = 'w-2.5 h-2.5 bg-red-500 rounded-full';
                const status = document.getElementById('wsStatusText');
                if (status) { status.textContent = 'Disconnected'; status.className = 'text-[10px] font-bold text-red-600'; }
                setTimeout(connectWS, 5000); 
            };
        }
        function loadLatestSnapshot() {
            window.LiveWsStore.fastSnapshot(currentPlant)
                .then(response => response.json())
                .then(snapshot => {
                    if (!snapshot || snapshot.status !== 'success' || !snapshot.data) return;
                    (snapshot.data.inverters || []).forEach(row => {
                        const name = canonicalInverterName(row.inverter_name);
                        invData[name] = Object.assign({}, invData[name] || {}, {
                            power: parseFloat(row.power_kw) || 0,
                            reactive: parseFloat(row.reactive_kvar) || 0,
                            pf: parseFloat(row.power_factor) || 0,
                            freq: parseFloat(row.frequency_hz) || 0,
                            acCurrent: ([row.current_a, row.current_b, row.current_c].reduce((s, v) => s + (parseFloat(v) || 0), 0) / 3),
                            acVoltage: parseFloat(row.vac_ab) || 0,
                            dailyGen: parseFloat(row.daily_gen_kwh) || 0,
                            totalGen: parseFloat(row.total_gen_kwh) || 0,
                            dailyCO2: parseFloat(row.daily_co2_kg) || 0,
                            totalCO2: parseFloat(row.total_co2_kg) || 0,
                            activeStr: parseInt(row.active_strings, 10) || 0,
                            totalStr: parseInt(row.total_strings, 10) || 0,
                            strings: Array.isArray(row.strings) ? row.strings.map(s => ({
                                n: parseInt(s.n ?? s.string_n, 10) || 0,
                                curr: parseFloat(s.curr ?? s.current_a) || 0,
                                volt: parseFloat(s.volt ?? s.voltage_v) || 0,
                                active: String(s.active) === '1' || s.active === true
                            })) : []
                        });
                    });
                    if (snapshot.data.vcb) state.vcbPower = parseFloat(snapshot.data.vcb.power_3phase_kw) || 0;
                    state.dailyEnergy = Object.values(invData).reduce((sum, inv) => sum + (inv.dailyGen || 0), 0);
                    updateDash();
                })
                .catch(() => {});
        }

        loadLatestSnapshot();
        connectWS();
    </script>
</body>
</html>
