<?php
require 'check_auth.php';
$pid = $currentPlant;
$pinfo = $PLANTS[$pid] ?? $PLANTS[array_key_first($PLANTS)];
$plantDisplayName = htmlspecialchars($pinfo['name']);
$plantCapacity    = htmlspecialchars($pinfo['capacity']);
$plantLocation    = htmlspecialchars($pinfo['location']);
$plantWsUnitId    = htmlspecialchars($pinfo['ws_unit_id']);
$plantWsUrl       = htmlspecialchars($pinfo['ws_url']);
$plantServiceNum  = htmlspecialchars($pinfo['service_number'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title><?php echo $plantDisplayName; ?> - Overview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/live_ws_store.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .inv-on { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
        .inv-off { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen">

    <div class="min-h-screen flex relative">
        <div id="overlay" class="fixed inset-0 bg-slate-900 bg-opacity-40 hidden z-30 md:hidden transition-opacity"></div>
        <div id="sidebar-container"></div>

        <main class="flex-1 flex flex-col w-full md:ml-64 overflow-x-hidden min-h-screen transition-all duration-300 ease-in-out">
            <header class="bg-white p-4 sm:px-6 flex justify-between items-center sticky top-0 z-20 border-b border-slate-200 shadow-sm">
                <div class="flex items-center gap-3">
                    <button id="menuBtn" class="md:hidden text-emerald-600 text-2xl focus:outline-none">&#9776;</button>
                    <div><h2 class="text-xl font-black text-slate-800 tracking-tight" id="headerPlantName">Plant Overview</h2></div>
                </div>
            </header>

            <div class="p-4 sm:p-6 w-full flex flex-col gap-6 max-w-[1600px] mx-auto">

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-4">
            <div class="bg-emerald-700 text-white text-center font-bold text-lg py-1">PLANT OVERVIEW</div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] table-fixed text-xs text-center">
                    <thead class="bg-gray-100 text-gray-700 font-bold">
                        <tr>
                            <th class="w-36 px-3 py-2 border whitespace-nowrap">Last Updated Time</th>
                            <th class="w-32 px-3 py-2 border whitespace-nowrap">Active Power (kW)</th>
                            <th class="w-32 px-3 py-2 border whitespace-nowrap">Frequency (Hz)</th>
                            <th class="w-20 px-3 py-2 border whitespace-nowrap">PF</th>
                            <th class="w-36 px-3 py-2 border whitespace-nowrap">Day Energy (kWh)</th>
                            <th class="w-44 px-3 py-2 border whitespace-nowrap">Life Time Energy (MWh)</th>
                        </tr>
                    </thead>
                    <tbody class="font-semibold">
                        <tr>
                            <td class="px-3 py-2 border text-slate-500 tabular-nums whitespace-nowrap" id="vcb_time">--:--:--</td>
                            <td class="px-3 py-2 border text-blue-600 font-extrabold tabular-nums" id="vcb_power">--</td>
                            <td class="px-3 py-2 border text-orange-600 tabular-nums" id="vcb_freq">--</td>
                            <td class="px-3 py-2 border text-slate-700 tabular-nums" id="vcb_pf">--</td>
                            <td class="px-3 py-2 border text-purple-600 font-extrabold tabular-nums" id="vcb_today">--</td>
                            <td class="px-3 py-2 border text-purple-700 font-extrabold tabular-nums" id="vcb_total">--</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4 mb-4">
            <div class="col-span-12 lg:col-span-9 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2 mb-3">
                    <h3 class="text-sm font-bold text-gray-700">Inverters</h3>
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider"><span id="overviewInvCount">--</span> Units</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-7 gap-2" id="inverterGrid"></div>
            </div>

             <div class="col-span-12 lg:col-span-3 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <h3 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Plant Information</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between border-b border-gray-100 pb-1">
                        <span class="text-gray-500">Name</span>
                        <span class="font-semibold text-gray-800"><?php echo $plantDisplayName; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-1">
                        <span class="text-gray-500">Capacity</span>
                        <span class="font-semibold text-gray-800"><?php echo $plantCapacity; ?> MW</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-1">
                        <span class="text-gray-500">Location</span>
                        <span class="font-semibold text-gray-800"><?php echo $plantLocation; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-1">
                        <span class="text-gray-500">Service Number</span>
                        <span class="font-semibold text-gray-800"><?php echo $plantServiceNum ?: '--'; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span id="plantStatusBadge" class="font-bold text-slate-400">Connecting...</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 h-80 flex flex-col overflow-hidden">
                <h4 class="text-xs font-bold text-gray-600 mb-3 text-center shrink-0">Active Power vs Radiation</h4>
                <div class="relative flex-1 min-h-0">
                    <canvas id="powerChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 h-80 flex flex-col overflow-hidden">
                <h4 class="text-xs font-bold text-gray-600 mb-3 text-center shrink-0">Generation Day (kWh)</h4>
                <div class="relative flex-1 min-h-0">
                    <canvas id="genChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <div class="bg-gradient-to-r from-red-400 to-red-600 text-white rounded-lg p-3 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider opacity-90">Today Energy</p>
                <p class="text-2xl font-black mt-1" id="today_energy_val">--</p>
                <p class="text-[10px] font-semibold opacity-80">kWh</p>
            </div>
            <div class="bg-gradient-to-r from-orange-400 to-orange-600 text-white rounded-lg p-3 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider opacity-90">Total Energy</p>
                <p class="text-2xl font-black mt-1" id="total_energy_val">--</p>
                <p class="text-[10px] font-semibold opacity-80">MWh</p>
            </div>
            <div class="bg-gradient-to-r from-blue-400 to-blue-600 text-white rounded-lg p-3 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider opacity-90">Specific Yield</p>
                <p class="text-2xl font-black mt-1" id="specific_yield_val">--</p>
                <p class="text-[10px] font-semibold opacity-80">kWh/kWp</p>
            </div>
            <div class="bg-gradient-to-r from-purple-400 to-purple-600 text-white rounded-lg p-3 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider opacity-90">Performance Ratio</p>
                <p class="text-2xl font-black mt-1" id="pr_val">--</p>
                <p class="text-[10px] font-semibold opacity-80">%</p>
            </div>
            <div class="bg-gradient-to-r from-emerald-400 to-emerald-600 text-white rounded-lg p-3 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider opacity-90">CO2 Saved</p>
                <p class="text-2xl font-black mt-1" id="co2_saved_val">--</p>
                <p class="text-[10px] font-semibold opacity-80">Tons</p>
            </div>
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
        const currentPlant  = '<?php echo addslashes($pid); ?>';
        const wsUnitIdConst = '<?php echo addslashes($plantWsUnitId); ?>';
        const wsUrlConst    = '<?php echo addslashes($plantWsUrl); ?>';
        const plantCapacity = parseFloat('<?php echo $plantCapacity; ?>') || 1;
        const plantConfig = <?php echo getPlantPublicConfigJson(); ?>;

        const headerEl = document.getElementById('headerPlantName');
        if (headerEl) headerEl.textContent = '<?php echo addslashes($plantDisplayName); ?>';

        fetch('sidebar.html', { cache: 'no-store' }).then(r => r.text()).then(html => {
            document.getElementById('sidebar-container').innerHTML = html;
            document.getElementById('sidebar-container').querySelectorAll('script').forEach(s => { const ns = document.createElement('script'); ns.textContent = s.textContent; s.replaceWith(ns); });
            const overlay = document.getElementById('overlay');
            const sidebar = document.getElementById('sidebar');
            document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar?.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); });
            document.getElementById('closeSidebarBtn')?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay?.classList.add('hidden'); });
            overlay?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
        });

        const inverterLive = {};
        let inverterRenderTimer = null;
        let inverterInitialRenderDone = false;

        const invGrid = document.getElementById('inverterGrid');

        function canonicalInverterName(name) {
            const match = (name || '').toString().match(/\d+/);
            return match ? `INVERTER${parseInt(match[0], 10)}` : (name || 'INVERTER').toString().toUpperCase().replace(/\s+/g, '');
        }

        function emptyInverterTelemetry() {
            return { power: 0, current: 0, freq: 0, pf: 0, dailyGen: 0, totalGen: 0, co2: null, strings: [], lastSeen: 0 };
        }

        function readNumber(value) {
            if (value === null || value === undefined || value === '') return null;
            const n = parseFloat(value);
            return Number.isFinite(n) ? n : null;
        }

        function firstNumber(values, patterns, rejectPatterns = []) {
            for (const key in values) {
                const kl = key.toLowerCase();
                if (rejectPatterns.some(rx => rx.test(kl))) continue;
                if (!patterns.some(rx => rx.test(kl))) continue;
                const n = readNumber(values[key]);
                if (n !== null) return n;
            }
            return null;
        }

        function applyDeviceList(devices) {
            if (!Array.isArray(devices)) return;
            devices.forEach(device => {
                const name = (device.name || device.device || '').toString();
                if (!/inv/i.test(name)) return;
                const key = canonicalInverterName(name);
                if (!inverterLive[key]) inverterLive[key] = emptyInverterTelemetry();
            });
            scheduleInverterRender();
        }

        function scheduleInverterRender(sourceTime = '') {
            clearTimeout(inverterRenderTimer);
            inverterRenderTimer = setTimeout(() => {
                updateInverterGrid();
                updateLiveCharts(sourceTime);
                inverterInitialRenderDone = true;
                inverterRenderTimer = null;
            }, inverterInitialRenderDone ? 250 : 0);
        }

        function getInverterAggregate() {
            const items = Object.values(inverterLive);
            const activeItems = items.filter(i => (i.lastSeen || 0) > 0 || (i.power || 0) > 0 || (i.dailyGen || 0) > 0);
            const power = items.reduce((sum, item) => sum + (item.power || 0), 0);
            const current = items.reduce((sum, item) => sum + (item.current || 0), 0);
            const dailyKwh = items.reduce((sum, item) => sum + (item.dailyGen || 0), 0);
            const totalKwh = items.reduce((sum, item) => sum + (item.totalGen || 0), 0);
            const co2Items = items.filter(i => i.co2 !== null && i.co2 !== undefined);
            const co2 = co2Items.length ? co2Items.reduce((sum, item) => sum + item.co2, 0) : null;
            const pfItems = activeItems.filter(i => (i.pf || 0) > 0);
            const freqItems = activeItems.filter(i => (i.freq || 0) > 0);
            const pf = pfItems.length ? pfItems.reduce((sum, item) => sum + item.pf, 0) / pfItems.length : null;
            const freq = freqItems.length ? freqItems.reduce((sum, item) => sum + item.freq, 0) / freqItems.length : null;
            return { power, current, dailyKwh, totalKwh, co2, pf, freq, count: activeItems.length };
        }

        function updateOverviewEnergyCards() {
            const agg = getInverterAggregate();
            const totalMwh = agg.totalKwh / 1000;
            const sy = plantCapacity > 0 ? (agg.dailyKwh / (plantCapacity * 1000)) : 0;
            document.getElementById('today_energy_val').textContent = agg.dailyKwh > 0 ? agg.dailyKwh.toFixed(2) : '--';
            document.getElementById('total_energy_val').textContent = agg.totalKwh > 0 ? totalMwh.toFixed(1) : '--';
            document.getElementById('specific_yield_val').textContent = agg.dailyKwh > 0 ? sy.toFixed(2) : '--';
            document.getElementById('pr_val').textContent = '--';
            document.getElementById('co2_saved_val').textContent = agg.co2 !== null ? agg.co2.toFixed(2) : '--';

            document.getElementById('vcb_power').textContent = agg.power > 0 ? agg.power.toFixed(2) : '--';
            document.getElementById('vcb_freq').textContent = agg.freq ? agg.freq.toFixed(2) : '--';
            document.getElementById('vcb_pf').textContent = agg.pf ? agg.pf.toFixed(3) : '--';
            document.getElementById('vcb_today').textContent = agg.dailyKwh > 0 ? agg.dailyKwh.toFixed(1) : '--';
            document.getElementById('vcb_total').textContent = agg.totalKwh > 0 ? totalMwh.toFixed(2) : '--';

            const sb = document.getElementById('plantStatusBadge');
            if (sb) {
                sb.textContent = agg.power > 0 ? 'Live' : 'Standby';
                sb.className = 'font-bold ' + (agg.power > 0 ? 'text-emerald-600' : 'text-slate-400');
            }
        }

        function formatInverterLabel(name) {
            const n = (name || '').toString().match(/\d+/);
            return n ? `Inv ${parseInt(n[0], 10)}` : (name || 'Inv');
        }

        function updateInverterGrid() {
            const names = Object.keys(inverterLive).sort((a, b) => {
                const na = parseInt(a.replace(/\D+/g, '')) || 0;
                const nb = parseInt(b.replace(/\D+/g, '')) || 0;
                return na - nb;
            });
            if (!names.length) return;
            let invHTML = '';
            const countEl = document.getElementById('overviewInvCount');
            if (countEl) countEl.textContent = names.length;
            for (const name of names) {
                const data = inverterLive[name];
                const isOn = data.power > 0.5;
                const cls = isOn ? 'inv-on' : 'inv-off';
                const safeName = name.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                const label = formatInverterLabel(name);
                invHTML += `<div class="${cls} text-white rounded-lg p-2.5 shadow-sm hover:scale-[1.02] transition duration-200 relative min-h-[96px]">
                    <button onclick="openStringModal('${safeName}')" title="View strings" class="absolute top-3 right-3 w-4 h-4 text-white/85 hover:text-white flex items-center justify-center leading-none">
                        <i class="fa-solid fa-eye text-[10px]"></i>
                    </button>
                    <p class="text-[10px] font-bold tracking-wider text-center px-6 leading-4 truncate uppercase">${label}</p>
                    <p class="text-base font-black mt-2 text-center">${data.power.toFixed(1)} <span class="text-[10px] font-bold">kW</span></p>
                    <div class="absolute left-2.5 right-2.5 bottom-2 border-t border-white/20 pt-1 text-center">
                        <p class="text-[11px] font-black tabular-nums">${data.dailyGen.toFixed(1)} kWh</p>
                    </div>
                </div>`;
            }
            invGrid.innerHTML = invHTML;
        }

        invGrid.innerHTML = '<div class="col-span-full text-center text-xs text-slate-400 italic py-6">Connecting to live data stream...</div>';

        const wsUnitId = wsUnitIdConst;
        const wsUrl    = wsUrlConst;
        let powerChart;
        let genChart;
        const chartHourLabels = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0') + ':00');
        const livePowerSeries = new Array(24).fill(0);
        const generationHourValues = new Array(24).fill(0);
        const powerByInverterHour = {};
        const generationCumulativeByInverterHour = {};
        const liveGenerationBaselineByInverterHour = {};
        
        function connectWS() {
            if (!wsUrl) { console.error('[WS] No wsUrl configured'); return; }
            console.log('[WS] Connecting to', wsUrl, 'unit_id=', wsUnitId);
            const ws = new WebSocket(wsUrl);
            ws.onopen = function() {
                console.log('[WS] Connected successfully');
                const pulse = document.getElementById('refreshPulse');
                if (pulse) pulse.className = 'w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]';
                ws.send(JSON.stringify({ type: "subscribe", unit_id: wsUnitId }));
                ws.send(JSON.stringify({ type: "get_devices", unit_id: wsUnitId }));
            };
            ws.onmessage = function(e) {
                try {
                    let d = JSON.parse(e.data);
                    console.log('[WS] RX:', d.type, d.unit_id, d.task, d.device);
                    if (d.unit_id !== wsUnitId) return;
                    window.LiveWsStore?.storeMessage(d, currentPlant);
                    if (d.type === 'device_list') {
                        applyDeviceList(d.devices);
                        window.LiveWsStore?.requestTodayForDevices(ws, wsUnitId, d.devices);
                        return;
                    }
                    if (d.type === 'daily_data_result') {
                        const latest = Array.isArray(d.data) && d.data.length ? d.data[d.data.length - 1] : null;
                        if (!latest || !latest.values) return;
                        const dailyDevice = d.deviceName || d.device || latest.device || '';
                        if (/inv/i.test(dailyDevice)) {
                            const rows = (d.data || []).filter(row => row.values).sort((a, b) => {
                                return (a.time || a.timestamp || '').toString().localeCompare((b.time || b.timestamp || '').toString());
                            });
                            rows.forEach(row => applyInverterChartSample(row.device || dailyDevice, row.values, row.time || row.timestamp || '', false));
                            updateInverterTelemetry(latest.device || dailyDevice, latest.values, latest.time || latest.timestamp || '', false);
                            updateLiveCharts();
                            return;
                        }
                        d = { type: 'data', unit_id: d.unit_id, task: /vcb/i.test(d.deviceName || latest.device || '') ? 'VCB' : 'Inverter', device: latest.device || d.deviceName, time: latest.time || '', values: latest.values };
                    }

                    const taskStr   = d.task   ? d.task.toString().toLowerCase()   : '';
                    const deviceStr = d.device ? d.device.toString().toLowerCase() : '';
                    const vals      = d.values || {};
                    const valKeys   = Object.keys(vals);

                    const isVCB = taskStr === 'vcb' || deviceStr.includes('vcb') ||
                                  valKeys.some(k => /3.phase.active.power|active.total.export|l1.*\(r\)/i.test(k));

                    const isInv = !isVCB && (
                        taskStr === 'inverter' ||
                        deviceStr.includes('inv') || deviceStr.includes('solar') ||
                        valKeys.some(k => /daily.power.yield|total.active.power/i.test(k)) ||
                        valKeys.some(k => /\d/.test(k) && /curr|current|amp/i.test(k) && !/3.phase|reactive|apparent/i.test(k))
                    );

                    if (isVCB) {
                        updateVCBTable(vals, d.time || '');
                    } else if (isInv && d.device) {
                        updateInverterTelemetry(d.device, vals, d.time || '');
                    }
                } catch(err) { console.error('[WS] message error:', err); }
            };
            ws.onerror = function(e) {
                console.error('[WS] Connection error:', e);
            };
            ws.onclose = function() {
                console.warn('[WS] Disconnected, reconnecting in 5s...');
                const pulse = document.getElementById('refreshPulse');
                if (pulse) pulse.className = 'w-2.5 h-2.5 bg-red-500 rounded-full';
                setTimeout(connectWS, 2000);
            };
        }

        function updateVCBTable(values, sourceTime = '') {
            if (!values) return;

            let pwr = null, pf = null, total = null, todayKwh = null, freq = null;
            let i_r = null, i_y = null, i_b = null;

            for (const k in values) {
                const kl = k.toLowerCase();
                const v  = readNumber(values[k]);
                if (v === null) continue;
                if (/3.phase.active.power|three.phase.active.power/i.test(k))  pwr      = v;
                else if (/power.factor|pf/i.test(kl) && !/reactive/i.test(kl)) pf       = v;
                else if (/grid.freq|frequency/i.test(kl))                       freq     = v;
                else if (/active.total.export|total.export/i.test(kl))          total    = v;
                else if (/today.*energy|day.*energy|active.*import.*today/i.test(kl)) todayKwh = v;
                else if (/^l1|^l1 \(r\)/i.test(k))                              i_r      = v;
                else if (/^l2|^l2 \(y\)/i.test(k))                              i_y      = v;
                else if (/^l3|^l3 \(b\)/i.test(k))                              i_b      = v;
            }

            const agg = getInverterAggregate();
            const vcbPowerKw = pwr !== null ? (Math.abs(pwr) > 10000 ? pwr / 1000 : pwr) : agg.power;
            const avgCurrent = [i_r, i_y, i_b].filter(v => v !== null).length
                ? [i_r, i_y, i_b].filter(v => v !== null).reduce((sum, v) => sum + v, 0) / [i_r, i_y, i_b].filter(v => v !== null).length
                : agg.current;
            const displayFreq = freq !== null ? freq : agg.freq;
            const displayPf = pf !== null ? pf : agg.pf;
            const displayToday = todayKwh !== null ? todayKwh : agg.dailyKwh;
            const displayTotalKwh = total !== null ? total : agg.totalKwh;
            const mwh = displayTotalKwh / 1000;
            const timeStr = sourceTime || new Date().toLocaleTimeString('en-IN', { hour12: false });

            document.getElementById('vcb_time').textContent    = timeStr;
            document.getElementById('vcb_power').textContent   = vcbPowerKw > 0 ? vcbPowerKw.toFixed(2) : '--';
            document.getElementById('vcb_freq').textContent    = displayFreq ? displayFreq.toFixed(2) : '--';
            document.getElementById('vcb_pf').textContent      = displayPf ? displayPf.toFixed(3) : '--';

            document.getElementById('vcb_total').textContent       = displayTotalKwh > 0 ? mwh.toFixed(2) : '--';
            document.getElementById('total_energy_val').textContent = displayTotalKwh > 0 ? mwh.toFixed(1) : '--';
            document.getElementById('vcb_today').textContent        = displayToday > 0 ? displayToday.toFixed(1) : '--';
            document.getElementById('today_energy_val').textContent = displayToday > 0 ? displayToday.toFixed(2) : '--';
            document.getElementById('co2_saved_val').textContent = '--';

            const sy = plantCapacity > 0 ? (displayToday / (plantCapacity * 1000)) : 0;
            document.getElementById('specific_yield_val').textContent = displayToday > 0 ? sy.toFixed(2) : '--';
            document.getElementById('pr_val').textContent = '--';

            const sb = document.getElementById('plantStatusBadge');
            if (sb) {
                sb.textContent = vcbPowerKw > 0 ? 'Live' : 'Standby';
                sb.className = 'font-bold ' + (vcbPowerKw > 0 ? 'text-emerald-600' : 'text-slate-400');
            }
        }

        function updateInverterTelemetry(devName, values, sourceTime = '', updateCharts = true) {
            if (!values) return;
            const name = canonicalInverterName(devName);

            if (!inverterLive[name]) {
                inverterLive[name] = emptyInverterTelemetry();
            }

            let pwr = firstNumber(values, [/^total active power$/, /active.*power/, /ac.*power/], [/reactive/, /apparent/, /nominal/, /3.phase/]);
            let dailyGen = firstNumber(values, [/^daily power yields$/, /daily.*yield/, /day.*energy/]);
            let totalGen = firstNumber(values, [/^total power yields precise$/, /^total power yields$/, /life.*energy/, /lifetime.*energy/]);
            let pf = firstNumber(values, [/^power factor$/, /\bpf\b/], [/reactive/]);
            let freq = firstNumber(values, [/^grid frequency precise$/, /^grid frequency$/, /frequency/]);
            const phaseCurrents = [
                firstNumber(values, [/^ry current$/, /current.*a/, /a.*phase.*current/], [/string/, /mppt/, /dc/]),
                firstNumber(values, [/^yb current$/, /current.*b/, /b.*phase.*current/], [/string/, /mppt/, /dc/]),
                firstNumber(values, [/^br current$/, /current.*c/, /c.*phase.*current/], [/string/, /mppt/, /dc/])
            ].filter(v => v !== null);
            const acCurrent = phaseCurrents.length ? phaseCurrents.reduce((sum, v) => sum + v, 0) / phaseCurrents.length : null;
            const co2 = firstNumber(values, [/co2/]);
            const strings = [];

            for (const pk in values) {
                const match = pk.match(/^string\s*(\d+)\s*current$/i);
                if (!match) continue;
                const curr = readNumber(values[pk]) || 0;
                strings.push({ n: parseInt(match[1], 10), curr, volt: 0, active: curr > 0.5 });
            }
            strings.sort((a, b) => a.n - b.n);

            if (pwr !== null) inverterLive[name].power = pwr;
            if (dailyGen !== null) inverterLive[name].dailyGen = dailyGen;
            if (totalGen !== null) inverterLive[name].totalGen = totalGen;
            if (acCurrent !== null) inverterLive[name].current = acCurrent;
            if (freq !== null) inverterLive[name].freq = freq;
            if (pf !== null) inverterLive[name].pf = pf;
            if (co2 !== null) inverterLive[name].co2 = co2;
            inverterLive[name].lastSeen = Date.now();
            if (strings.length) inverterLive[name].strings = strings;
            if (updateCharts) {
                applyInverterChartSample(name, values, sourceTime, true);
                scheduleInverterRender();
            } else {
                updateInverterGrid();
            }
            updateOverviewEnergyCards();
        }

        function dbInverterValues(row) {
            return {
                "Total active power": row.power_kw,
                "Daily power yields": row.daily_gen_kwh,
                "Total power yields precise": row.total_gen_kwh,
                "Power factor": row.power_factor,
                "Grid frequency": row.frequency_hz,
                "RY current": row.current_a,
                "YB current": row.current_b,
                "BR current": row.current_c,
                "CO2": row.total_co2_kg
            };
        }

        function dbVcbValues(row) {
            return {
                "3 Phase Active Power": row.power_3phase_kw,
                "Frequency (Hz)": row.frequency_hz,
                "L1 (R)": row.current_r_a,
                "L2 (Y)": row.current_y_a,
                "L3 (B)": row.current_b_a,
                "Power factor": row.pf_q1,
                "Active Total Export": row.active_export_kwh,
                "vcb-today": row.today_energy_kwh
            };
        }

        function loadLatestSnapshot() {
            return window.LiveWsStore.fastSnapshot(currentPlant)
                .then(res => res.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data) return;
                    (res.data.inverters || []).forEach(row => {
                        const values = dbInverterValues(row);
                        updateInverterTelemetry(row.inverter_name, values, row.snapshot_at || '', false);
                        applyInverterChartSample(row.inverter_name, values, row.snapshot_at || '', false);
                    });
                    if (res.data.vcb) updateVCBTable(dbVcbValues(res.data.vcb), res.data.vcb.snapshot_at || '');
                    inverterInitialRenderDone = true;
                    updateInverterGrid();
                    updateOverviewEnergyCards();
                    updateLiveCharts();
                })
                .catch(() => {});
        }

        

        function hourIndexFromSource(sourceTime = '') {
            const match = (sourceTime || '').toString().match(/\b(\d{1,2}):\d{2}/);
            if (match) return Math.max(0, Math.min(23, parseInt(match[1], 10)));
            return new Date().getHours();
        }

        function ensureChartSeries(bucket, invName, fillValue = null) {
            const key = canonicalInverterName(invName);
            if (!bucket[key]) bucket[key] = new Array(24).fill(fillValue);
            return bucket[key];
        }

        function extractInverterChartValues(values) {
            return {
                power: firstNumber(values, [/^total active power$/, /active.*power/, /ac.*power/], [/reactive/, /apparent/, /nominal/, /3.phase/]),
                dailyGen: firstNumber(values, [/^daily power yields$/, /daily.*yield/, /day.*energy/])
            };
        }

        function applyInverterChartSample(invName, values, sourceTime = '', isLive = false) {
            if (!values) return;
            const key = canonicalInverterName(invName);
            const hourIndex = hourIndexFromSource(sourceTime);
            const sample = extractInverterChartValues(values);
            if (sample.power !== null) ensureChartSeries(powerByInverterHour, key, null)[hourIndex] = sample.power;
            if (sample.dailyGen !== null) {
                const genSeries = ensureChartSeries(generationCumulativeByInverterHour, key, null);
                if (isLive) {
                    const baseKey = `${key}:${hourIndex}`;
                    const existingHourValue = genSeries[hourIndex];
                    let baseline = existingHourValue !== null && existingHourValue !== undefined ? existingHourValue : sample.dailyGen;
                    if (liveGenerationBaselineByInverterHour[baseKey] === undefined || sample.dailyGen < liveGenerationBaselineByInverterHour[baseKey]) {
                        liveGenerationBaselineByInverterHour[baseKey] = baseline;
                    }
                }
                genSeries[hourIndex] = sample.dailyGen;
            }
        }

        function recomputeChartSeries() {
            livePowerSeries.fill(0);
            generationHourValues.fill(0);

            Object.values(powerByInverterHour).forEach(series => {
                series.forEach((value, hour) => {
                    if (value !== null && value !== undefined) livePowerSeries[hour] += value || 0;
                });
            });

            Object.entries(generationCumulativeByInverterHour).forEach(([invName, series]) => {
                let previous = null;
                series.forEach((value, hour) => {
                    if (value === null || value === undefined) return;
                    const baseKey = `${invName}:${hour}`;
                    let delta = 0;
                    if (liveGenerationBaselineByInverterHour[baseKey] !== undefined) {
                        delta = value - liveGenerationBaselineByInverterHour[baseKey];
                    } else if (previous !== null) {
                        delta = value - previous;
                    }
                    generationHourValues[hour] += Math.max(0, delta);
                    previous = value;
                });
            });
        }

        function updateLiveCharts() {
            recomputeChartSeries();

            if (!powerChart || !genChart) return;
            powerChart.data.labels = chartHourLabels;
            powerChart.data.datasets[0].data = livePowerSeries.map(v => Number(v.toFixed(2)));
            genChart.data.labels = chartHourLabels;
            genChart.data.datasets[0].data = generationHourValues.map(v => Number(v.toFixed(2)));
            powerChart.update('none');
            genChart.update('none');
        }

        function loadOverviewHourly() {
            fetch(`api.php?action=get_overview_hourly&plant_id=${encodeURIComponent(currentPlant)}`, { cache: 'no-store' })
                .then(res => res.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data || !powerChart || !genChart) return;
                    const power = Array.isArray(res.data.power) ? res.data.power : [];
                    const generation = Array.isArray(res.data.generation) ? res.data.generation : [];
                    for (let i = 0; i < 24; i++) {
                        livePowerSeries[i] = Number(power[i] || 0);
                        generationHourValues[i] = Number(generation[i] || 0);
                    }
                    powerChart.data.datasets[0].data = livePowerSeries.map(v => Number(v.toFixed(2)));
                    genChart.data.datasets[0].data = generationHourValues.map(v => Number(v.toFixed(2)));
                    powerChart.update('none');
                    genChart.update('none');
                })
                .catch(() => {});
        }

        function openStringModal(invName) {
            document.getElementById('stringModalTitle').textContent = invName + ' - String Details';
            const grid = document.getElementById('stringGrid');
            const inv = inverterLive[invName];
            if (!inv || !inv.strings || !inv.strings.length) {
                grid.innerHTML = '<div class="col-span-full text-center text-xs text-slate-400 italic py-6">No string telemetry received for this inverter yet.</div>';
            } else {
                grid.innerHTML = inv.strings.map(s => {
                    const ok = s.active;
                    return `<div class="border ${ok ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'} rounded-lg p-2 text-center">
                        <p class="text-[10px] font-bold ${ok ? 'text-emerald-700' : 'text-red-700'} uppercase tracking-wider">String ${s.n}</p>
                        <p class="mt-1 text-sm font-black ${ok ? 'text-slate-800' : 'text-red-700'}">${s.curr.toFixed(1)} <span class="text-[10px] text-slate-500">A</span></p>
                    </div>`;
                }).join('');
            }
            document.getElementById('stringModal').classList.remove('hidden');
        }
        
        function closeStringModal() {
            document.getElementById('stringModal').classList.add('hidden');
        }
        
        document.getElementById('stringModal').addEventListener('click', function(e) {
            if (e.target === this) closeStringModal();
        });

        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.font.size = 10;

        powerChart = new Chart(document.getElementById('powerChart'), {
            type: 'line',
            data: {
                labels: chartHourLabels,
                datasets: [
                    {
                        label: 'Active Power (kW)',
                        data: livePowerSeries,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8 } } },
                scales: {
                    x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } },
                    y: { position: 'left', beginAtZero: true, title: { display: true, text: 'kW' }, grid: { color: '#f1f5f9' } }
                }
            }
        });

        genChart = new Chart(document.getElementById('genChart'), {
            type: 'bar',
            data: {
                labels: chartHourLabels,
                datasets: [{
                    label: 'Energy (kWh)',
                    data: generationHourValues,
                    backgroundColor: '#10b981',
                    borderRadius: 3,
                    barPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } },
                    y: { title: { display: true, text: 'kWh' }, grid: { color: '#f1f5f9' } }
                }
            }
        });

        updateLiveCharts();
        connectWS();
    </script>
</body>
</html>
