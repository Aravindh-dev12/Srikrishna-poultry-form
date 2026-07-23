<?php
require 'check_auth.php';
$pid    = $currentPlant;
$pinfo  = $PLANTS[$pid] ?? $PLANTS[array_key_first($PLANTS)];
$plantDisplayName = htmlspecialchars($pinfo['name']);
$plantWsUnitId    = addslashes($pinfo['ws_unit_id']);
$plantWsUrl       = addslashes($pinfo['ws_url']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title><?php echo $plantDisplayName; ?> - Inverter</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <header class="bg-white p-3 sm:p-4 sm:px-6 flex justify-between items-center sticky top-0 z-20 border-b border-slate-200 shadow-sm gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <button id="menuBtn" class="md:hidden text-emerald-600 text-2xl focus:outline-none shrink-0">&#9776;</button>
                <div class="min-w-0"><h2 class="text-base sm:text-xl font-black text-slate-800 tracking-tight truncate leading-tight" id="headerPlantName"><?php echo $plantDisplayName; ?></h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-tight">Inverter Overview</p></div>
                </div>
                <div class="flex items-center gap-2 bg-slate-50 px-2 sm:px-3 py-1.5 rounded-lg border border-slate-100 shrink-0">
                    <div id="refreshPulse" class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                    <span class="text-xs font-bold text-slate-600 tracking-widest hidden sm:inline" id="clockDisplay">--:--:--</span>
                </div>
            </header>
            <div class="p-3 sm:p-6 w-full flex flex-col gap-4 sm:gap-6 max-w-[1600px] mx-auto">
                <div id="inv_summary_cards" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-3 sm:p-5 relative overflow-hidden group hover:shadow-md transition duration-300 min-w-0">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full blur-xl -z-10 group-hover:bg-emerald-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Inverters</h3>
                        <p class="font-black text-slate-800 text-2xl sm:text-3xl truncate" id="inv_total_count">--</p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Active units</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-3 sm:p-5 relative overflow-hidden group hover:shadow-md transition duration-300 min-w-0">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full blur-xl -z-10 group-hover:bg-blue-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Power</h3>
                        <p class="font-black text-slate-800 text-2xl sm:text-3xl truncate" id="inv_total">-- <span class="text-sm font-bold text-blue-600">kW</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Combined AC output</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-3 sm:p-5 relative overflow-hidden group hover:shadow-md transition duration-300 min-w-0">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-50 rounded-full blur-xl -z-10 group-hover:bg-purple-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Generation</h3>
                        <p class="font-black text-slate-800 text-2xl sm:text-3xl truncate" id="inv_total_gen">-- <span class="text-sm font-bold text-purple-600">kWh</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Today combined</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-3 sm:p-5 relative overflow-hidden group hover:shadow-md transition duration-300 min-w-0">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-sky-50 rounded-full blur-xl -z-10 group-hover:bg-sky-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Efficiency</h3>
                        <p class="font-black text-slate-800 text-2xl sm:text-3xl truncate" id="inv_avg_eff">-- <span class="text-sm font-bold text-sky-600">%</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Average across units</p>
                    </div>
                </div>
                <div id="inv_detail_container" class="space-y-6">
                    <div class="text-xs text-slate-400 italic text-center py-6">Waiting for inverter telemetry...</div>
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
        const wsUnitId      = '<?php echo $plantWsUnitId; ?>';
        const plantWsUrl    = '<?php echo $plantWsUrl; ?>';
        const plantConfig   = <?php echo getPlantPublicConfigJson(); ?>;

        setInterval(() => { document.getElementById('clockDisplay').innerText = new Date().toLocaleTimeString('en-IN', {hour12: false}); }, 1000);
        fetch('sidebar.html', { cache: 'no-store' }).then(r => r.text()).then(html => {
            document.getElementById('sidebar-container').innerHTML = html;
            document.getElementById('sidebar-container').querySelectorAll('script').forEach(s => { const ns = document.createElement('script'); ns.textContent = s.textContent; s.replaceWith(ns); });
            const overlay = document.getElementById('overlay'), sidebar = document.getElementById('sidebar');
            document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar?.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); });
            document.getElementById('closeSidebarBtn')?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay?.classList.add('hidden'); });
            overlay?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
        });
        const invData = {};
        let globalVacAB = 0, globalVacBC = 0, globalVacCA = 0;
        let renderTimer = null;
        let initialSnapshotLoaded = false;

        function scheduleRenderAll() {
            clearTimeout(renderTimer);
            renderTimer = setTimeout(() => {
                renderAll();
                renderTimer = null;
            }, initialSnapshotLoaded ? 250 : 0);
        }

        function canonicalInverterName(name) {
            const match = (name || '').toString().match(/\d+/);
            return match ? `INVERTER${parseInt(match[0], 10)}` : (name || 'INVERTER').toString().toUpperCase().replace(/\s+/g, '');
        }
        function readNumber(value) {
            if (value === null || value === undefined || value === '') return null;
            const n = parseFloat(value);
            return Number.isFinite(n) ? n : null;
        }

        function formatInverterLabel(name) {
            const match = (name || '').toString().match(/\d+/);
            return match ? `Inv ${parseInt(match[0], 10)}` : (name || 'Inv');
        }

        

        function applyDeviceList(devices) {
            if (!Array.isArray(devices)) return;
            devices.forEach(device => {
                const name = (device.name || device.device || '').toString();
                if (!/inv/i.test(name)) return;
                const key = canonicalInverterName(name);
                if (!invData[key]) {
                    invData[key] = {
                        power: 0, reactive: 0, pf: 0, vac_ab: 0, vac_bc: 0, vac_ca: 0,
                        freq: 0, i_a: 0, i_b: 0, i_c: 0, eff: 0, amb: 0,
                        dailyGen: 0, totalGen: 0, dailyCO2: 0, totalCO2: 0,
                        dailyHrs: 0, totalHrs: 0, hasAlarm: false, hasFault: false,
                        faultCode: '', workState: '', statusText: '', lastSeen: '',
                        activeStr: 0, totalStr: 0, strings: []
                    };
                }
            });
            if (!initialSnapshotLoaded) {
                initialSnapshotLoaded = true;
                renderAll();
                return;
            }
            scheduleRenderAll();
        }

        function isInverterPayload(message) {
            const values = message.values || {};
            const keys = Object.keys(values);
            const taskStr = message.task ? message.task.toString().toLowerCase() : '';
            const deviceStr = message.device ? message.device.toString().toLowerCase() : '';
            if (taskStr.includes('inverter') || deviceStr.includes('inverter') || deviceStr.includes('inv')) return true;
            return keys.some(k => /total active power|daily power yields|string\d+\s*current|mppt\d+\s*current/i.test(k));
        }

        function readAlarmFaultState(values) {
            let hasAlarm = false;
            let hasFault = false;
            let faultCode = '';
            let workState = '';
            let statusText = '';

            for (const pk in values) {
                const pkl = pk.toLowerCase();
                const raw = values[pk];
                const textVal = raw === null || raw === undefined ? '' : raw.toString().trim().toLowerCase();
                const numVal = parseFloat(raw);
                const hasActiveValue = textVal !== '' && textVal !== '0' && textVal !== 'null' && textVal !== 'normal' && textVal !== 'false';

                if (/fault\s*code|faultcode/i.test(pk)) {
                    faultCode = raw === null || raw === undefined ? '' : raw.toString();
                    if (!Number.isNaN(numVal) ? numVal > 0 : hasActiveValue) hasFault = true;
                    continue;
                }

                if (/fault|trip|error/i.test(pkl) && hasActiveValue) hasFault = true;
                if (/alarm|warning|warn/i.test(pkl) && hasActiveValue) hasAlarm = true;

                if (/work\s*state/i.test(pk)) {
                    workState = raw === null || raw === undefined ? '' : raw.toString();
                    statusText = workState !== '' ? `Work state ${workState}` : statusText;
                } else if (/status|state/i.test(pkl) && textVal) {
                    statusText = raw.toString();
                    if (/fault|trip|error/i.test(textVal)) hasFault = true;
                    if (/alarm|warning|warn/i.test(textVal)) hasAlarm = true;
                }
            }

            return { hasAlarm, hasFault, faultCode, workState, statusText };
        }

        function connectWS() {
            const wsUrl = plantWsUrl;
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
                        d = { type: 'data', unit_id: d.unit_id, task: /vcb/i.test(d.deviceName || latest.device || '') ? 'VCB' : 'Inverter', device: latest.device || d.deviceName, time: latest.time || '', values: latest.values };
                    }
                    const taskStr = d.task ? d.task.toString().toLowerCase() : '';
                    const deviceStr = d.device ? d.device.toString().toLowerCase() : '';
                    
                    if (taskStr === 'vcb' || deviceStr.includes('vcb')) {
                        if (d.values) {
                            const v12 = readNumber(d.values["V12 (RY)"]);
                            const v23 = readNumber(d.values["V23 (YB)"]);
                            const v31 = readNumber(d.values["V31 (BR)"]);
                            if (v12 !== null) globalVacAB = v12;
                            if (v23 !== null) globalVacBC = v23;
                            if (v31 !== null) globalVacCA = v31;
                        }
                        return;
                    }
                    
                    if (d.values) {
                        const keys = Object.keys(d.values);
                        if (!isInverterPayload(d)) return;
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
                        const directStrings = [];
                        for (const pk of keys) {
                            const match = pk.match(/^string\s*(\d+)\s*current$/i);
                            if (!match) continue;
                            const n = parseInt(match[1], 10);
                            const voltKey = keys.find(k => new RegExp(`^string\\s*${n}\\s*volt(age)?$`, 'i').test(k));
                            const curr = parseFloat(d.values[pk]) || 0;
                            const volt = voltKey ? (parseFloat(d.values[voltKey]) || 0) : 0;
                            directStrings.push({ n, curr, volt, active: curr > 0.5 });
                        }
                        if (directStrings.length) {
                            directStrings.sort((x, y) => x.n - y.n);
                            strings.length = 0;
                            strings.push(...directStrings);
                            totalStr = strings.length;
                            activeStr = strings.filter(s => s.active).length;
                        }
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

                        const alarmFault = readAlarmFaultState(d.values);

                        const dcPwr = parseFloat(d.values["Total DC power"]) || 0;
                        if (dcPwr > 0 && pwr > 0) {
                            eff = Math.min((pwr / (dcPwr / 1000)) * 100, 100);
                        }

                        const ryVoltage = readNumber(d.values["RYvolatge"] ?? d.values["RY voltage"] ?? d.values["V12 (RY)"]);
                        const ybVoltage = readNumber(d.values["YB voltage"] ?? d.values["V23 (YB)"]);
                        const brVoltage = readNumber(d.values["BR voltage"] ?? d.values["V31 (BR)"]);
                        const ryCurrent = readNumber(d.values["RY current"]);
                        const ybCurrent = readNumber(d.values["YB current"]);
                        const brCurrent = readNumber(d.values["BR current"]);
                        if (ryVoltage !== null) vac_ab = ryVoltage;
                        if (ybVoltage !== null) vac_bc = ybVoltage;
                        if (brVoltage !== null) vac_ca = brVoltage;
                        if (ryCurrent !== null) i_a = ryCurrent;
                        if (ybCurrent !== null) i_b = ybCurrent;
                        if (brCurrent !== null) i_c = brCurrent;

                        for (const pk in d.values) {
                            const pkl = pk.toLowerCase();
                            const val = readNumber(d.values[pk]);
                            if (val === null) continue;
                            
                            if (!vac_ab && (/ry.*volt|v12|voltage.*ab|v.*ab|ab.*voltage/i.test(pkl))) vac_ab = val;
                            else if (!vac_bc && (/yb.*volt|v23|voltage.*bc|v.*bc|bc.*voltage/i.test(pkl))) vac_bc = val;
                            else if (!vac_ca && (/br.*volt|v31|voltage.*ca|v.*ca|ca.*voltage/i.test(pkl))) vac_ca = val;
                            else if (!i_a && (/ry.*current|current.*a|a.*phase.*current|i.*a\b/i.test(pkl) && !/voltage|volt/i.test(pkl))) i_a = val;
                            else if (!i_b && (/yb.*current|current.*b|b.*phase.*current|i.*b\b/i.test(pkl) && !/voltage|volt/i.test(pkl))) i_b = val;
                            else if (!i_c && (/br.*current|current.*c|c.*phase.*current|i.*c\b/i.test(pkl) && !/voltage|volt/i.test(pkl))) i_c = val;
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
                            hasAlarm: alarmFault.hasAlarm,
                            hasFault: alarmFault.hasFault,
                            faultCode: alarmFault.faultCode,
                            workState: alarmFault.workState,
                            statusText: alarmFault.statusText,
                            lastSeen: Date.now(),
                            activeStr, totalStr, strings
                        });
                        scheduleRenderAll();

                    }
                } catch(err) {}
            };
            ws.onclose = function() { document.getElementById('refreshPulse').className = 'w-2.5 h-2.5 bg-red-500 rounded-full'; setTimeout(connectWS, 2000); };
        }
        function buildStringDots(v) {
            return '';
        }
        function openStringModal(invName) {
            const inv = invData[invName];
            document.getElementById('stringModalTitle').textContent = formatInverterLabel(invName) + ' - String Details';
            const grid = document.getElementById('stringGrid');
            if (!inv || !inv.strings || !inv.strings.length) {
                grid.innerHTML = '<div class="col-span-full text-center text-xs text-slate-400 italic py-6">No string telemetry received for this inverter yet.</div>';
                document.getElementById('stringModal').classList.remove('hidden');
                return;
            }
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
        let activeFilter = 'all';
        function setInvFilter(filter) {
            activeFilter = filter;
            renderAll();
        }

        function renderAll() {
            const keys = Object.keys(invData).sort((a,b) => (parseInt(a.replace(/\D/g,''))||0) - (parseInt(b.replace(/\D/g,''))||0));
            if (!keys.length) return;
            const existingScrollBox = document.getElementById('inverterTableScroll');
            const savedScrollLeft = existingScrollBox ? existingScrollBox.scrollLeft : 0;
            
            const totalPower = keys.reduce((s,k) => s + invData[k].power, 0);
            const totalGen = keys.reduce((s,k) => s + invData[k].dailyGen, 0);
            const avgEff = keys.length ? (keys.reduce((s,k) => s + invData[k].eff, 0) / keys.length) : 0;
            
            document.getElementById('inv_total_count').textContent = keys.length;
            document.getElementById('inv_total').innerHTML = totalPower.toFixed(2) + ' <span class="text-sm font-bold text-blue-600">kW</span>';
            document.getElementById('inv_total_gen').innerHTML = totalGen.toFixed(2) + ' <span class="text-sm font-bold text-purple-600">kWh</span>';
            document.getElementById('inv_avg_eff').innerHTML = avgEff.toFixed(1) + ' <span class="text-sm font-bold text-sky-600">%</span>';
            
            const container = document.getElementById('inv_detail_container');

            let onlineCount = 0;
            let offlineCount = 0;
            let alarmCount = 0;
            let faultCount = 0;

            const processedList = keys.map(k => {
                const v = invData[k];
                let status = 'Offline';
                if (v.hasFault) {
                    status = 'Fault';
                    faultCount++;
                } else if (v.hasAlarm) {
                    status = 'Alarm';
                    alarmCount++;
                } else if (v.power > 0) {
                    status = 'Online';
                    onlineCount++;
                } else {
                    offlineCount++;
                }
                return { key: k, data: v, status };
            });

            const filteredList = processedList.filter(item => {
                if (activeFilter === 'all') return true;
                return item.status.toLowerCase() === activeFilter.toLowerCase();
            });

            const activeClass = (filter) => activeFilter === filter
                ? 'border-emerald-600 text-emerald-700'
                : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300';
            const countClass = (filter) => activeFilter === filter
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-slate-100 text-slate-500';
            
            container.innerHTML = `
                <div class="flex flex-wrap gap-5 mb-4 border-b border-slate-200">
                    <button onclick="setInvFilter('all')" class="flex items-center gap-2 px-1 pb-3 border-b-2 text-xs font-black transition ${activeClass('all')}">
                        <span>All</span>
                        <span class="px-1.5 py-0.5 rounded-full ${countClass('all')} text-[10px]">${processedList.length}</span>
                    </button>
                    <button onclick="setInvFilter('online')" class="flex items-center gap-2 px-1 pb-3 border-b-2 text-xs font-black transition ${activeClass('online')}">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_6px_rgba(34,197,94,0.6)]"></span>
                        <span>Online</span>
                        <span class="px-1.5 py-0.5 rounded-full ${countClass('online')} text-[10px]">${onlineCount}</span>
                    </button>
                    <button onclick="setInvFilter('offline')" class="flex items-center gap-2 px-1 pb-3 border-b-2 text-xs font-black transition ${activeClass('offline')}">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                        <span>Offline</span>
                        <span class="px-1.5 py-0.5 rounded-full ${countClass('offline')} text-[10px]">${offlineCount}</span>
                    </button>
                    <button onclick="setInvFilter('alarm')" class="flex items-center gap-2 px-1 pb-3 border-b-2 text-xs font-black transition ${activeClass('alarm')}">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-[0_0_6px_rgba(245,158,11,0.6)] animate-pulse"></span>
                        <span>Alarm</span>
                        <span class="px-1.5 py-0.5 rounded-full ${countClass('alarm')} text-[10px]">${alarmCount}</span>
                    </button>
                    <button onclick="setInvFilter('fault')" class="flex items-center gap-2 px-1 pb-3 border-b-2 text-xs font-black transition ${activeClass('fault')}">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-600 shadow-[0_0_6px_rgba(220,38,38,0.6)] animate-pulse"></span>
                        <span>Fault</span>
                        <span class="px-1.5 py-0.5 rounded-full ${countClass('fault')} text-[10px]">${faultCount}</span>
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div id="inverterTableScroll" class="overflow-x-auto overscroll-x-contain">
                        <table class="w-full min-w-[1280px] table-fixed text-xs">
                            <thead class="bg-slate-50 text-slate-700 font-bold">
                                <tr>
                                    <th class="px-4 py-3 text-left border-b border-slate-200">Inverter</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">AC Power (kW)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">Reactive (kVAR)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">Power Factor</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">Efficiency (%)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">AC Freq (Hz)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">Ambient (°C)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">Vac AB (V)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">Vac BC (V)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">Vac CA (V)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">I A (A)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">I B (A)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">I C (A)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">Daily Gen (kWh)</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-200">Total Gen (kWh)</th>
                                    <th class="px-4 py-3 text-center border-b border-slate-200">Status</th>
                                    <th class="px-4 py-3 text-center border-b border-slate-200">Action</th>
                                </tr>
                            </thead>
                            <tbody class="font-semibold">
                                ${filteredList.map(item => {
                                    const k = item.key;
                                    const v = item.data;
                                    const status = item.status;
                                    const label = formatInverterLabel(k);
                                    let badgeCls = 'bg-slate-100 text-slate-500';
                                    if (status === 'Online') badgeCls = 'bg-emerald-100 text-emerald-700';
                                    else if (status === 'Alarm') badgeCls = 'bg-amber-100 text-amber-700';
                                    else if (status === 'Fault') badgeCls = 'bg-red-100 text-red-700';
                                    
                                    return `<tr class="border-b border-slate-100 hover:bg-slate-50 transition align-middle">
                                        <td class="px-3 py-3 text-left font-bold text-slate-800 whitespace-nowrap">${label}</td>
                                        <td class="px-3 py-3 text-right text-blue-600 tabular-nums">${v.power.toFixed(2)}</td>
                                        <td class="px-3 py-3 text-right text-sky-600 tabular-nums">${v.reactive.toFixed(2)}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">${v.pf.toFixed(3)}</td>
                                        <td class="px-3 py-3 text-right text-emerald-600 tabular-nums">${v.eff.toFixed(1)}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">${v.freq.toFixed(2)}</td>
                                        <td class="px-3 py-3 text-right text-orange-600 tabular-nums">${v.amb.toFixed(1)}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">${v.vac_ab.toFixed(1)}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">${v.vac_bc.toFixed(1)}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">${v.vac_ca.toFixed(1)}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">${v.i_a.toFixed(2)}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">${v.i_b.toFixed(2)}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">${v.i_c.toFixed(2)}</td>
                                        <td class="px-3 py-3 text-right text-purple-600 tabular-nums">${v.dailyGen.toFixed(1)}</td>
                                        <td class="px-3 py-3 text-right text-purple-600 tabular-nums">${v.totalGen.toFixed(0)}</td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="inline-flex min-w-[76px] justify-center rounded-full ${badgeCls} px-2.5 py-1 text-[11px] font-black uppercase tracking-wider">${status}</span>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <button onclick="openStringModal('${k}')" class="bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-bold min-w-[82px] justify-center px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1.5 mx-auto">
                                                <i class="fa-solid fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>`;
                                }).join('')}
                                ${filteredList.length === 0 ? `<tr><td colspan="17" class="text-center py-6 font-medium text-slate-400 italic">No inverters found matching this filter</td></tr>` : ''}
                            </tbody>
                        </table>
                    </div>
                </div>`;
            const nextScrollBox = document.getElementById('inverterTableScroll');
            if (nextScrollBox) nextScrollBox.scrollLeft = Math.min(savedScrollLeft, nextScrollBox.scrollWidth - nextScrollBox.clientWidth);
        }

        function loadLatestSnapshot() {
            return window.LiveWsStore.fastSnapshot(currentPlant)
                .then(res => res.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data) return;
                    (res.data.inverters || []).forEach(row => {
                        const key = canonicalInverterName(row.inverter_name);
                        invData[key] = Object.assign({}, invData[key] || {}, {
                            power: readNumber(row.power_kw) || 0,
                            reactive: readNumber(row.reactive_kvar) || 0,
                            pf: readNumber(row.power_factor) || 0,
                            vac_ab: readNumber(row.vac_ab) || 0,
                            vac_bc: readNumber(row.vac_bc) || 0,
                            vac_ca: readNumber(row.vac_ca) || 0,
                            freq: readNumber(row.frequency_hz) || 0,
                            i_a: readNumber(row.current_a) || 0,
                            i_b: readNumber(row.current_b) || 0,
                            i_c: readNumber(row.current_c) || 0,
                            eff: readNumber(row.efficiency) || 0,
                            amb: readNumber(row.ambient_temp) || 0,
                            dailyGen: readNumber(row.daily_gen_kwh) || 0,
                            totalGen: readNumber(row.total_gen_kwh) || 0,
                            dailyCO2: readNumber(row.daily_co2_kg) || 0,
                            totalCO2: readNumber(row.total_co2_kg) || 0,
                            dailyHrs: readNumber(row.daily_hours) || 0,
                            totalHrs: readNumber(row.total_hours) || 0,
                            hasAlarm: row.has_alarm == 1,
                            hasFault: row.has_fault == 1,
                            faultCode: row.fault_code || '',
                            workState: row.work_state || '',
                            statusText: row.status_text || '',
                            lastSeen: row.snapshot_at || '',
                            activeStr: parseInt(row.active_strings || 0, 10),
                            totalStr: parseInt(row.total_strings || 0, 10),
                            strings: (row.strings || []).map(s => ({
                                n: parseInt(s.string_n || 0, 10),
                                curr: readNumber(s.current_a) || 0,
                                volt: readNumber(s.voltage_v) || 0,
                                active: s.active == 1
                            }))
                        });
                    });
                    initialSnapshotLoaded = true;
                    renderAll();
                })
                .catch(() => { initialSnapshotLoaded = true; });
        }
        
        connectWS();
    </script>
</body>
</html>
