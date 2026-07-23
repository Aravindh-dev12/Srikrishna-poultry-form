<?php require 'check_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title id="pageTitle">Solar Plant - VCB</title>
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
            <header class="bg-white p-4 sm:px-6 flex justify-between items-center sticky top-0 z-20 border-b border-slate-200 shadow-sm">
                <div class="flex items-center gap-3">
                    <button id="menuBtn" class="md:hidden text-emerald-600 text-2xl focus:outline-none">&#9776;</button>
                    <div><h2 class="text-xl font-black text-slate-800 tracking-tight">VCB Panel Status</h2></div>
                </div>
                <div class="flex items-center gap-3 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                    <div id="refreshPulse" class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                    <span class="text-xs font-bold text-slate-600 tracking-widest hidden sm:inline" id="clockDisplay">--:--:--</span>
                </div>
            </header>
            <div class="p-4 sm:p-6 w-full flex flex-col gap-6 max-w-[1600px] mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 sm:gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full blur-xl -z-10 group-hover:bg-emerald-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">VCB Status</h3>
                        <p class="font-black text-emerald-700 text-3xl" id="vcb_status">--</p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Circuit breaker</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full blur-xl -z-10 group-hover:bg-blue-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">3-Phase Active Power</h3>
                        <p class="font-black text-slate-800 text-3xl" id="vcb_load">-- <span class="text-sm font-bold text-blue-600">kW</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Current export</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-50 rounded-full blur-xl -z-10 group-hover:bg-purple-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Today Energy</h3>
                        <p class="font-black text-slate-800 text-3xl" id="vcb_today">-- <span class="text-sm font-bold text-purple-600">kWh</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1">vcb-today</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-orange-50 rounded-full blur-xl -z-10 group-hover:bg-orange-100 transition"></div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Frequency</h3>
                        <p class="font-black text-slate-800 text-3xl" id="vcb_freq">-- <span class="text-sm font-bold text-orange-600">Hz</span></p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Grid frequency</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest mb-4">3-Phase Voltages</h3>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">R Phase-N</p>
                            <p class="mt-3 text-2xl font-black text-slate-800" id="v_r">-- <span class="text-sm font-bold text-slate-500">V</span></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Y Phase-N</p>
                            <p class="mt-3 text-2xl font-black text-slate-800" id="v_y">-- <span class="text-sm font-bold text-slate-500">V</span></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">B Phase-N</p>
                            <p class="mt-3 text-2xl font-black text-slate-800" id="v_b">-- <span class="text-sm font-bold text-slate-500">V</span></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">V12 (RY)</p>
                            <p class="mt-3 text-2xl font-black text-slate-800" id="v_ry">-- <span class="text-sm font-bold text-slate-500">V</span></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">V23 (YB)</p>
                            <p class="mt-3 text-2xl font-black text-slate-800" id="v_yb">-- <span class="text-sm font-bold text-slate-500">V</span></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">V31 (BR)</p>
                            <p class="mt-3 text-2xl font-black text-slate-800" id="v_br">-- <span class="text-sm font-bold text-slate-500">V</span></p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest mb-4">Phase Currents & Power</h3>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">L1 (R)</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="i_r">-- <span class="text-xs text-slate-500">A</span></p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">L2 (Y)</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="i_y">-- <span class="text-xs text-slate-500">A</span></p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">L3 (B)</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="i_b">-- <span class="text-xs text-slate-500">A</span></p>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3 mt-3">
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Power R</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="p_r">-- <span class="text-xs text-slate-500">kW</span></p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Power Y</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="p_y">-- <span class="text-xs text-slate-500">kW</span></p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Power B</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="p_b">-- <span class="text-xs text-slate-500">kW</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest mb-4">Power Factor & THD</h3>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Q1 PF</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="pf_q1">--</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Q2 PF</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="pf_q2">--</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Q3 PF</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="pf_q3">--</p>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3 mt-3">
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Voltage THD R</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="vthd_r">-- <span class="text-xs text-slate-500">%</span></p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Voltage THD Y</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="vthd_y">-- <span class="text-xs text-slate-500">%</span></p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Voltage THD B</p>
                                <p class="mt-2 text-xl font-black text-slate-800" id="vthd_b">-- <span class="text-xs text-slate-500">%</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest mb-4">Energy & Reactive Power</h3>
                    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Total Export</p>
                            <p class="mt-2 text-xl font-black text-slate-800" id="act_exp">-- <span class="text-xs text-slate-500">kWh</span></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Total Import</p>
                            <p class="mt-2 text-xl font-black text-slate-800" id="act_imp">-- <span class="text-xs text-slate-500">kWh</span></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Reactive Import</p>
                            <p class="mt-2 text-xl font-black text-slate-800" id="react_imp">-- <span class="text-xs text-slate-500">kVAR</span></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Reactive Export</p>
                            <p class="mt-2 text-xl font-black text-slate-800" id="react_exp">-- <span class="text-xs text-slate-500">kVAR</span></p>
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
        document.getElementById('pageTitle').textContent = (plantNames[currentPlant] || currentPlant) + ' - VCB';
        setInterval(() => { document.getElementById('clockDisplay').innerText = new Date().toLocaleTimeString('en-IN', {hour12: false}); }, 1000);
        fetch('sidebar.html', { cache: 'no-store' }).then(r => r.text()).then(html => {
            document.getElementById('sidebar-container').innerHTML = html;
            document.getElementById('sidebar-container').querySelectorAll('script').forEach(s => { const ns = document.createElement('script'); ns.textContent = s.textContent; s.replaceWith(ns); });
            const overlay = document.getElementById('overlay'), sidebar = document.getElementById('sidebar');
            document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar?.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); });
            document.getElementById('closeSidebarBtn')?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay?.classList.add('hidden'); });
            overlay?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
        });
        let globalDailyEnergy = 0;
        function setText(id, val, unit='') { const el = document.getElementById(id); if(el) el.innerHTML = val + (unit ? ' <span class="text-sm font-bold text-slate-500">' + unit + '</span>' : ''); }
        function renderVCB(row) {
            if (!row) return;
            const p = parseFloat(row.power_3phase_kw) || 0;
            document.getElementById('vcb_status').textContent = p > 0 ? 'Online' : 'Standby';
            document.getElementById('vcb_status').className = 'font-black text-3xl ' + (p > 0 ? 'text-emerald-700' : 'text-slate-400');
            setText('vcb_load', p.toFixed(2), 'kW');
            setText('vcb_today', (parseFloat(row.today_energy_kwh) || 0).toFixed(2), 'kWh');
            setText('vcb_freq', (parseFloat(row.frequency_hz) || 0).toFixed(2), 'Hz');
            setText('v_r', (parseFloat(row.voltage_r_v) || 0).toFixed(1), 'V');
            setText('v_y', (parseFloat(row.voltage_y_v) || 0).toFixed(1), 'V');
            setText('v_b', (parseFloat(row.voltage_b_v) || 0).toFixed(1), 'V');
            setText('v_ry', (parseFloat(row.voltage_ry_v) || 0).toFixed(1), 'V');
            setText('v_yb', (parseFloat(row.voltage_yb_v) || 0).toFixed(1), 'V');
            setText('v_br', (parseFloat(row.voltage_br_v) || 0).toFixed(1), 'V');
            setText('i_r', (parseFloat(row.current_r_a) || 0).toFixed(2), 'A');
            setText('i_y', (parseFloat(row.current_y_a) || 0).toFixed(2), 'A');
            setText('i_b', (parseFloat(row.current_b_a) || 0).toFixed(2), 'A');
            setText('p_r', (parseFloat(row.power_r_kw) || 0).toFixed(2), 'kW');
            setText('p_y', (parseFloat(row.power_y_kw) || 0).toFixed(2), 'kW');
            setText('p_b', (parseFloat(row.power_b_kw) || 0).toFixed(2), 'kW');
            setText('pf_q1', (parseFloat(row.pf_q1) || 0).toFixed(3), '');
            setText('pf_q2', (parseFloat(row.pf_q2) || 0).toFixed(3), '');
            setText('pf_q3', (parseFloat(row.pf_q3) || 0).toFixed(3), '');
            setText('vthd_r', (parseFloat(row.vthd_r) || 0).toFixed(2), '%');
            setText('vthd_y', (parseFloat(row.vthd_y) || 0).toFixed(2), '%');
            setText('vthd_b', (parseFloat(row.vthd_b) || 0).toFixed(2), '%');
            setText('act_exp', (parseFloat(row.active_export_kwh) || 0).toFixed(2), 'kWh');
            setText('act_imp', (parseFloat(row.active_import_kwh) || 0).toFixed(2), 'kWh');
            setText('react_imp', (parseFloat(row.reactive_import_kvar) || 0).toFixed(2), 'kVAR');
            setText('react_exp', (parseFloat(row.reactive_export_kvar) || 0).toFixed(2), 'kVAR');
        }
        const defaultVCB = {
            power_3phase_kw: 0, today_energy_kwh: 0, frequency_hz: 0,
            voltage_r_v: 0, voltage_y_v: 0, voltage_b_v: 0,
            voltage_ry_v: 0, voltage_yb_v: 0, voltage_br_v: 0,
            current_r_a: 0, current_y_a: 0, current_b_a: 0,
            power_r_kw: 0, power_y_kw: 0, power_b_kw: 0,
            pf_q1: 0, pf_q2: 0, pf_q3: 0,
            vthd_r: 0, vthd_y: 0, vthd_b: 0,
            active_export_kwh: 0, active_import_kwh: 0,
            reactive_import_kvar: 0, reactive_export_kvar: 0
        };

        function loadLatestSnapshot() {
            window.LiveWsStore.fastSnapshot(currentPlant)
                .then(res => res.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data) return;
                    if (res.data.vcb) renderVCB(res.data.vcb);
                    const invDaily = (res.data.inverters || []).reduce((sum, row) => sum + (parseFloat(row.daily_gen_kwh) || 0), 0);
                    if (invDaily > 0) {
                        globalDailyEnergy = invDaily;
                        if (!res.data.vcb || !(parseFloat(res.data.vcb.today_energy_kwh) > 0)) setText('vcb_today', invDaily.toFixed(2), 'kWh');
                    }
                })
                .catch(() => {});
        }
        
        function connectWS() {
            const wsUrl = "<?php echo getPlantWsUrl($currentPlant); ?>";
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
                        window.LiveWsStore?.requestTodayForDevices(ws, wsUnitId, d.devices);
                        return;
                    }
                    if (d.type === 'daily_data_result') {
                        const latest = Array.isArray(d.data) && d.data.length ? d.data[d.data.length - 1] : null;
                        if (!latest || !latest.values) return;
                        d = { type: 'data', unit_id: d.unit_id, task: /vcb/i.test(d.deviceName || latest.device || '') ? 'VCB' : 'Inverter', device: latest.device || d.deviceName, time: latest.time || '', values: latest.values };
                    }
                    if (d.unit_id !== wsUnitId) return;
                    
                    if (d.values && d.device && d.device.toLowerCase().includes('inverter')) {
                        let gen = 0;
                        if (d.values["Daily power yields"] !== undefined) gen = parseFloat(d.values["Daily power yields"]);
                        else if (d.values["daily generation"] !== undefined) gen = parseFloat(d.values["daily generation"]);
                        
                        if (gen > 0) {
                            if (!window.invGen) window.invGen = {};
                            window.invGen[d.device] = gen;
                            globalDailyEnergy = Object.values(window.invGen).reduce((a, b) => a + b, 0);
                        }
                    }

                    if (d.task !== 'VCB' || !d.values) return;
                    const v = d.values;
                    const p = parseFloat(v["3 Phase Active Power"]) || 0;
                    
                    let todayEnergy = '--';
                    if (d.virtualTags && d.virtualTags["vcb-today"] !== undefined) {
                        todayEnergy = parseFloat(d.virtualTags["vcb-today"].value).toFixed(2);
                    } else if (d.values["vcb-today"] !== undefined) {
                        todayEnergy = parseFloat(d.values["vcb-today"]).toFixed(2);
                    } else if (globalDailyEnergy > 0) {
                        todayEnergy = globalDailyEnergy.toFixed(2);
                    }
                    const vcbPayload = {
                        power_3phase_kw: p,
                        frequency_hz: parseFloat(v["Frequency (Hz)"]) || 0,
                        voltage_r_v: parseFloat(v["R Phase-N Voltage"]) || 0,
                        voltage_y_v: parseFloat(v["Y Phase-N Voltage"]) || 0,
                        voltage_b_v: parseFloat(v["B Phase-N Voltage"]) || 0,
                        voltage_ry_v: parseFloat(v["V12 (RY)"]) || 0,
                        voltage_yb_v: parseFloat(v["V23 (YB)"]) || 0,
                        voltage_br_v: parseFloat(v["V31 (BR)"]) || 0,
                        current_r_a: parseFloat(v["L1 (R)"]) || 0,
                        current_y_a: parseFloat(v["L2 (Y)"]) || 0,
                        current_b_a: parseFloat(v["L3 (B)"]) || 0,
                        power_r_kw: parseFloat(v["Active Power R"]) || 0,
                        power_y_kw: parseFloat(v["Active Power Y"]) || 0,
                        power_b_kw: parseFloat(v["Active Power B"]) || 0,
                        pf_q1: parseFloat(v["Q1 PF"]) || 0,
                        pf_q2: parseFloat(v["Q2 PF"]) || 0,
                        pf_q3: parseFloat(v["Q3 PF"]) || 0,
                        vthd_r: parseFloat(v["Voltage THD R"]) || 0,
                        vthd_y: parseFloat(v["Voltage THD Y"]) || 0,
                        vthd_b: parseFloat(v["Voltage THD B"]) || 0,
                        active_export_kwh: parseFloat(v["Active Total Export"]) || 0,
                        active_import_kwh: parseFloat(v["Active Total Import"]) || 0,
                        reactive_import_kvar: parseFloat(v["Reactive Import (Q1+Q2)"]) || 0,
                        reactive_export_kvar: parseFloat(v["Reactive Export (Q3+Q4)"]) || 0,
                        today_energy_kwh: parseFloat(todayEnergy) || 0
                    };
                    renderVCB(vcbPayload);
                } catch(err) {}
            };
            ws.onclose = function() { document.getElementById('refreshPulse').className = 'w-2.5 h-2.5 bg-red-500 rounded-full'; setTimeout(connectWS, 2000); };
        }
        connectWS();
    </script>
</body>
</html>
