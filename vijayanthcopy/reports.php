<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require 'check_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title>System Reports - Solar Plant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/vendor/jspdf.umd.min.js?v=20260716-2"></script>
    <script src="assets/vendor/jspdf.plugin.autotable.min.js?v=20260716-2"></script>
    <script src="assets/live_ws_store.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .report-table { table-layout: fixed; min-width: 1200px; width: 100%; border-collapse: collapse; }
        .table-hscroll { overflow-x: auto; overflow-y: visible; }
        .report-table th, .report-table td { padding: 9px 7px; text-align: center; overflow: hidden; text-overflow: ellipsis; }
        .report-table td { font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; font-variant-numeric: tabular-nums; }
        .report-table th { color: #334155; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: .01em; border-bottom: 2px solid #cbd5e1; line-height: 1.25; }
        .report-table tbody tr { transition: background-color 0.2s ease; background-color: #ffffff; }
        .report-table tbody tr:hover { background-color: #f8fafc !important; }
        .table-hscroll::-webkit-scrollbar { height: 10px; }
        .table-hscroll::-webkit-scrollbar-track { background: #f8fafc; border-radius: 5px; }
        .table-hscroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 5px; }
        .table-hscroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .pdf-mode .table-hscroll { overflow: visible !important; width: 100% !important; max-width: none !important; }
        .pdf-mode .report-table { table-layout: fixed !important; width: 100% !important; }
        .pdf-mode { background: #ffffff !important; padding: 15px !important; }
    </style>
</head>
<body class="h-full bg-slate-50 text-gray-800 font-sans">
    <div class="min-h-screen flex relative">
        <div id="overlay" class="fixed inset-0 bg-slate-900 bg-opacity-40 hidden z-30 md:hidden transition-opacity"></div>
        <div id="sidebar-container"></div>
        <main class="flex-1 flex flex-col w-full md:ml-64 overflow-x-hidden">
        <header class="bg-white border-b border-gray-200 p-4 sm:px-6 flex justify-between items-center shadow-sm sticky top-0 z-20">
            <div class="flex items-center gap-3 sm:gap-4">
                <button id="menuBtn" class="md:hidden text-green-700 text-2xl focus:outline-none">&#9776;</button>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800">System Reports</h2>
                    <p class="text-xs text-gray-500 hidden sm:block">Generate, view, and export plant data</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button id="pdfExportBtn" onclick="generateAndExportPDF()" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-3 sm:px-4 rounded-lg shadow-sm transition-colors flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-file-pdf"></i><span class="hidden sm:inline">Export PDF</span>
                </button>
                <button onclick="generateAndExportExcel()" id="dlBtn" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-3 sm:px-4 rounded-lg shadow-sm transition-colors flex items-center gap-2 text-sm opacity-50 cursor-not-allowed" disabled>
                    <i class="fa-solid fa-file-excel"></i><span class="hidden sm:inline">Excel</span>
                </button>
            </div>
        </header>

        <div class="p-4 sm:p-6 w-full flex flex-col gap-6 max-w-[1600px] mx-auto">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5 flex flex-col lg:flex-row gap-4 justify-between items-start lg:items-center">
                <div class="flex flex-wrap gap-2" id="tab-container">
                    <button class="px-4 py-2 text-sm font-bold rounded-lg bg-green-50 text-green-700 border border-green-200 transition">Inverter Generation</button>
                </div>
                <div class="flex items-center gap-2 w-full lg:w-auto flex-wrap">
                    <select id="plantSelect" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 outline-none bg-gray-50 font-medium cursor-pointer">
                        <option value="vijayanth">Bojaraj Textiles Pvt Ltd</option>
                        <option value="krishna">Krishna Poultry Farm</option>
                    </select>
                    <select id="reportType" onchange="toggleInputs()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 outline-none bg-gray-50 font-medium cursor-pointer">
                        <option value="daily">Daily</option>
                        <option value="monthly">Monthly</option>
                    </select>
                    <input type="date" id="dateSelect" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 outline-none bg-gray-50 font-medium">
                    <input type="month" id="monthSelect" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 outline-none bg-gray-50 font-medium hidden">
                    <button id="viewReportBtn" onclick="generateReportData(); startAutoRefresh();" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-eye"></i> View Report
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm flex-1 flex flex-col">
                <div id="printableReport" class="p-5 bg-white w-full">
                    <div class="border-b-2 border-green-600 pb-4 mb-6">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h1 id="reportPlantName" class="text-2xl font-black text-gray-900 tracking-tight">SOLAR ENERGY</h1>
                                <h2 id="reportMainTitle" class="text-lg font-bold text-green-700 mt-1">Inverter Generation Report</h2>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-sm mt-4 font-medium text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <div><span class="text-gray-500 uppercase text-xs font-bold block">Plant Location</span><span id="reportLocation">Tamil Nadu</span></div>
                            <div><span class="text-gray-500 uppercase text-xs font-bold block">Plant Capacity</span><span id="reportCapacity">4.0 MW</span></div>
                            <div><span class="text-gray-500 uppercase text-xs font-bold block" id="reportDateLabel">Report Date</span><span id="displayDate" class="font-bold text-gray-900">--</span><br><span id="liveStatus" class="text-xs font-bold text-emerald-600 mt-0.5 inline-flex items-center gap-1"><span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>Auto-refresh ON</span></div>
                        </div>
                    </div>
                    <div class="table-hscroll w-full">
                        <table class="w-full report-table border-collapse">
                            <thead></thead>
                            <tbody id="reportTableBody">
                                <tr><td colspan="30" class="py-10 text-center text-gray-500"><div class="flex flex-col items-center justify-center"><div class="w-8 h-8 border-3 border-gray-200 border-t-blue-600 rounded-full animate-spin mb-2"></div>Loading live data...</div></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-12 pt-8 flex justify-between text-sm font-bold text-gray-800 border-t border-gray-200">
                        <div class="text-center w-40"><div class="border-b border-gray-400 mb-2 h-8"></div>Operator Signature</div>
                        <div class="text-center w-40"><div class="border-b border-gray-400 mb-2 h-8"></div>Site Engineer</div>
                        <div class="text-center w-40"><div class="border-b border-gray-400 mb-2 h-8"></div>Plant Manager</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    </div>

    <script>
        const currentPlant = '<?php echo addslashes($currentPlant); ?>';
        const plantConfig = <?php echo getPlantPublicConfigJson(); ?>;
        const userRole = '<?php echo addslashes($user['role'] ?? 'user'); ?>';

        const plants = [
            { id: 'vijayanth', name: 'Bojaraj Textiles Pvt Ltd', unit_id: 'via-1mw', capacity: 4.0, location: 'Tamil Nadu' },
            { id: 'krishna', name: 'Krishna Poultry Farm', unit_id: 'via-3mw', capacity: 3.0, location: 'Tamil Nadu' }
        ];

        let currentReportTab = 'inv_vcb';
        let lastReportData = null;
        let refreshInterval = null;
        let ws = null;
        let wsConnected = false;
        let pendingReportRequest = false;
        let wsReportTimeout = null;
        let deviceList = [];
        let expectedDevices = [];
        let dailyDataByDevice = {};
        let receivedDeviceCount = 0;
        let analyticsDataByDevice = {};
        let analyticsReceived = new Set();
        let analyticsRequestToken = 0;
        let exportAfterGenerate = false;
        let excelAfterGenerate = false;
        
        const dateInput = document.getElementById('dateSelect');
        const monthInput = document.getElementById('monthSelect');
        dateInput.value = new Date().toISOString().split('T')[0];
        monthInput.value = new Date().toISOString().slice(0, 7);

        const plantSelect = document.getElementById('plantSelect');
        
        // Hide plant dropdown for non-admin users
        if (userRole !== 'admin') {
            plantSelect.style.display = 'none';
        }
        
        plantSelect.value = currentPlant;

        // Update plant info on page load
        const currentPlantObj = plants.find(p => p.id === currentPlant);
        if (currentPlantObj) {
            document.getElementById('reportPlantName').textContent = currentPlantObj.name.toUpperCase();
            document.getElementById('reportLocation').textContent = currentPlantObj.location;
            document.getElementById('reportCapacity').textContent = currentPlantObj.capacity + ' MW';
        }

        // When plant changes (admin only), update display
        plantSelect.addEventListener('change', function() {
            const plant = plants.find(p => p.id === this.value);
            if (plant) {
                document.getElementById('reportPlantName').textContent = plant.name.toUpperCase();
                document.getElementById('reportLocation').textContent = plant.location;
                document.getElementById('reportCapacity').textContent = plant.capacity + ' MW';
            }
        });

        function loadSidebar() {
            fetch('sidebar.html', { cache: 'no-store' }).then(r => r.text()).then(html => {
                document.getElementById('sidebar-container').innerHTML = html;
                document.getElementById('sidebar-container').querySelectorAll('script').forEach(s => { const ns = document.createElement('script'); ns.textContent = s.textContent; s.replaceWith(ns); });
                const overlay = document.getElementById('overlay');
                const sidebar = document.getElementById('sidebar');
                document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar?.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); });
                document.getElementById('closeSidebarBtn')?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay?.classList.add('hidden'); });
                overlay?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
            });
        }

        function startAutoRefresh() {
            if (refreshInterval) clearInterval(refreshInterval);
            refreshInterval = setInterval(() => { if (!pendingReportRequest) generateReportData(); }, 30000);
        }

        function generateAndExportPDF() {
            exportAfterGenerate = true;
            const button = document.getElementById('pdfExportBtn');
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span class="hidden sm:inline">Generating...</span>';
            }
            generateReportData();
            startAutoRefresh();
        }

        function generateAndExportExcel() {
            excelAfterGenerate = true;
            const button = document.getElementById('dlBtn');
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span class="hidden sm:inline">Creating Excel...</span>';
            }
            generateReportData();
            startAutoRefresh();
        }

        function stopAutoRefresh() {
            if (refreshInterval) { clearInterval(refreshInterval); refreshInterval = null; }
        }

        function updateLiveStatus() {
            const now = new Date();
            const time = now.toLocaleTimeString('en-IN', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
            document.getElementById('liveStatus').innerHTML = '<span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>Updated ' + time;
        }

        function toggleInputs() {
            const type = document.getElementById('reportType').value;
            if (type === 'daily') { dateInput.classList.remove('hidden'); monthInput.classList.add('hidden'); document.getElementById('reportDateLabel').innerText = "Report Date"; }
            else { dateInput.classList.add('hidden'); monthInput.classList.remove('hidden'); document.getElementById('reportDateLabel').innerText = "Report Month"; }
        }

        const wsUrl = "wss://vinobasolar.scadahub.in:5001";
        console.log('[Reports] WebSocket URL configured:', wsUrl);
        
        function connectReportWS() {
            if (ws && (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING)) {
                console.log('[Reports] WS already connected or connecting');
                return;
            }
            try {
                console.log('[Reports] Connecting to WebSocket:', wsUrl);
                ws = new WebSocket(wsUrl);
                ws.onopen = () => { 
                    wsConnected = true; 
                    console.log('[Reports] ✓ WS connected successfully to', wsUrl); 
                };
                ws.onmessage = (e) => {
                    try {
                        const d = JSON.parse(e.data);
                        
                        // Log non-data messages for debugging
                        if (d.type !== 'data') {
                            console.log('[Reports] ← WS message:', d.type, 'unit:', d.unit_id);
                        }
                        
                        // Ignore legacy daily/monthly broadcasts. The report must be
                        // completed only from Universal Analytics responses so an
                        // unrelated partial broadcast cannot replace the 14-inverter table.
                        if (d.type === 'daily_data_result' || d.type === 'monthly_data_result') {
                            console.log('[Reports] ⏭ Ignoring legacy', d.type, 'for:', d.deviceName || d.device);
                            return;
                        }

                        if (d.type === 'analytics_data_result') {
                            handleAnalyticsDataResult(d);
                            return;
                        }
                        
                        // Handle device list response
                        if (d.type === 'device_list') {
                            handleDeviceList(d);
                            return;
                        }
                        
                        // Ignore live telemetry "data" messages
                        if (d.type === 'data') {
                            return;
                        }
                        
                    } catch(err) { 
                        console.error('[Reports] WS parse error:', err); 
                        console.error('[Reports] Raw message:', e.data.substring(0, 200));
                    }
                };
                ws.onclose = () => { 
                    wsConnected = false; 
                    console.warn('[Reports] ✗ WS disconnected, reconnecting in 5s...');
                    setTimeout(connectReportWS, 5000); 
                };
                ws.onerror = (err) => { 
                    console.error('[Reports] ✗ WS error:', err); 
                    wsConnected = false; 
                };
            } catch(err) { 
                console.error('[Reports] ✗ WS connect failed:', err); 
            }
        }

        function handleDeviceList(d) {
            console.log('[Reports] ✓ Received device_list:', d);
            deviceList = d.devices || [];
            
            if (!deviceList.length) {
                console.error('[Reports] ✗ No devices in list');
                return;
            }
            
            // Filter only inverter devices
            const inverters = deviceList.filter(dev => {
                const name = (typeof dev === 'string' ? dev : (dev.name || dev.deviceName || dev.device || '')).toLowerCase();
                return name.includes('inverter') && !name.includes('vcb');
            });
            
            console.log('[Reports] ✓ Found', inverters.length, 'inverters:', inverters.map(inv => inv.name || inv.deviceName));
            
            if (!pendingReportRequest) {
                console.log('[Reports] Not waiting for report, skipping data request');
                return;
            }
            
            if (!inverters.length) {
                console.error('[Reports] ✗ No inverter devices found');
                const tbody = document.getElementById('reportTableBody');
                tbody.innerHTML = '<tr><td colspan="30" class="py-10 text-center text-red-500">No inverter devices found</td></tr>';
                pendingReportRequest = false;
                return;
            }
            
            // Match Vinoba Universal Analytics: one aggregated request per inverter.
            const plantId = plantSelect.value;
            const plant = plants.find(p => p.id === plantId);

            expectedDevices = inverters.map(inv => typeof inv === 'string' ? inv : (inv.name || inv.deviceName || inv.device));
            requestUniversalAnalytics(plant, expectedDevices);
        }

        function analyticsRange(type, selectedDate) {
            if (type === 'daily') return { startDate: selectedDate, endDate: selectedDate };
            const startDate = selectedDate + '-01';
            const end = new Date(Number(selectedDate.slice(0, 4)), Number(selectedDate.slice(5, 7)), 0);
            const endDate = `${end.getFullYear()}-${String(end.getMonth() + 1).padStart(2, '0')}-${String(end.getDate()).padStart(2, '0')}`;
            return { startDate, endDate };
        }

        function requestUniversalAnalytics(plant, devices) {
            const type = document.getElementById('reportType').value;
            const selectedDate = type === 'daily' ? dateInput.value : monthInput.value;
            const range = analyticsRange(type, selectedDate);
            expectedDevices = devices.slice();
            analyticsDataByDevice = {};
            analyticsReceived = new Set();

            devices.forEach(device => ws.send(JSON.stringify({
                type: 'get_analytics_data',
                unit_id: plant.unit_id,
                device,
                tag: 'Daily power yields',
                startDate: range.startDate,
                endDate: range.endDate,
                startTime: '06:00',
                endTime: '19:00',
                timePeriod: '30',
                method: 'last'
            })));
            console.log('[Reports] → Universal Analytics requested for', devices.length, 'inverters');
        }

        function analyticsPoints(d) {
            let points = d.data ?? d.analyticsData ?? d.result ?? d.results ?? [];
            if (!Array.isArray(points) && points && typeof points === 'object') {
                const deviceName = String(d.deviceName || d.device || d.request?.device || '');
                points = points[deviceName] ?? points.data ?? points.points ?? points.values ?? Object.values(points).flat();
            }
            return Array.isArray(points) ? points : [];
        }

        function analyticsPointValue(point) {
            if (typeof point === 'number' || typeof point === 'string') return Number(point) || 0;
            const values = point?.values || {};
            return Number(point?.value ?? point?.last ?? point?.aggregatedValue ??
                values['Daily power yields'] ?? values['daily power yields'] ?? 0) || 0;
        }

        function analyticsPointTime(point) {
            return String(point?.timestamp ?? point?.time ?? point?.dateTime ?? point?.datetime ?? point?.bucket ?? point?.label ?? '');
        }

        function handleAnalyticsDataResult(d) {
            if (!pendingReportRequest) return;
            const deviceName = String(d.deviceName || d.device || d.request?.device || '');
            if (!deviceName || !/inverter/i.test(deviceName)) return;
            analyticsDataByDevice[deviceName] = analyticsPoints(d);
            window.LiveWsStore?.storeAnalyticsResult(d, plantSelect.value);
            analyticsReceived.add(deviceName.toLowerCase());
            console.log('[Reports] ✓ Analytics received:', deviceName, analyticsDataByDevice[deviceName].length, 'points');

            const allReceived = expectedDevices.length > 0 && expectedDevices.every(name => analyticsReceived.has(String(name).toLowerCase()));
            if (allReceived) finishUniversalAnalytics();
        }

        function finishUniversalAnalytics() {
            if (!pendingReportRequest) return;
            const type = document.getElementById('reportType').value;
            const invNames = (expectedDevices.length ? expectedDevices : Object.keys(analyticsDataByDevice))
                .slice().sort((a, b) => (parseInt(a.match(/\d+/)?.[0] || 0) - parseInt(b.match(/\d+/)?.[0] || 0)));
            const slots = {};

            if (type === 'daily') {
                // Always show the complete solar operating window, including
                // empty intervals, so a delayed or missing inverter response
                // cannot remove a row from the report.
                for (let minutes = 6 * 60; minutes <= 19 * 60; minutes += 30) {
                    const hour = Math.floor(minutes / 60);
                    const minute = minutes % 60;
                    const label = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
                    slots[label] = { time_label: label, _latest: {} };
                }
            }

            invNames.forEach((device, index) => {
                const matchedKey = Object.keys(analyticsDataByDevice).find(key => key.toLowerCase() === String(device).toLowerCase());
                (analyticsDataByDevice[matchedKey] || []).forEach(point => {
                    const timestamp = analyticsPointTime(point);
                    const match = timestamp.match(/(\d{4})-(\d{2})-(\d{2})[T\s]?(\d{2})?:?(\d{2})?/);
                    if (!match) return;
                    const hour = Number(match[4] || 0);
                    const minute = Number(match[5] || 0);
                    const totalMinutes = hour * 60 + minute;
                    if (type === 'daily' && (totalMinutes < 6 * 60 || totalMinutes > 19 * 60)) return;
                    const bucketMinute = minute < 30 ? 0 : 30;
                    const label = type === 'daily'
                        ? `${String(hour).padStart(2, '0')}:${String(bucketMinute).padStart(2, '0')}`
                        : `${match[3]}-${match[2]}-${match[1]}`;
                    if (!slots[label]) slots[label] = { time_label: label, _latest: {} };
                    // Server already applies method:last. For monthly, retain the final
                    // hourly cumulative reading for each inverter on each day.
                    const key = 'inv' + (index + 1) + '_kwh';
                    if (!slots[label]._latest[key] || timestamp >= slots[label]._latest[key]) {
                        slots[label][key] = analyticsPointValue(point);
                        slots[label]._latest[key] = timestamp;
                    }
                });
            });

            const rows = Object.values(slots).sort((a, b) => a.time_label.localeCompare(b.time_label));
            rows.forEach(row => {
                delete row._latest;
                row.inv_total_kwh = invNames.reduce((sum, _, i) => sum + (Number(row['inv' + (i + 1) + '_kwh']) || 0), 0);
            });
            pendingReportRequest = false;
            if (wsReportTimeout) { clearTimeout(wsReportTimeout); wsReportTimeout = null; }
            if (!rows.length) return loadCachedReport('Universal Analytics returned no rows');
            lastReportData = { type, data: rows, meta: { inv_names: invNames, source: 'vinoba_universal_analytics', method: 'last', period_minutes: 30, start_time: '06:00', end_time: '19:00' } };
            renderReportData(type, rows, invNames);
        }
        
        function handleDailyDataResult(d) {
            const deviceName = d.deviceName || d.device || 'unknown';
            const data = d.data || [];
            
            // FILTER OUT VCB and non-inverter devices
            if (!/inverter/i.test(deviceName)) {
                console.log('[Reports] ⏭ Skipping non-inverter device:', deviceName);
                return;
            }
            
            if (!pendingReportRequest) {
                console.log('[Reports] Not waiting for report, ignoring daily_data_result');
                return;
            }
            
            console.log('[Reports] ✓ Received daily_data_result for:', deviceName, 'with', data.length, 'data points');
            
            // Store this device's data
            dailyDataByDevice[deviceName] = data;
            receivedDeviceCount++;
            
            console.log('[Reports] Progress:', receivedDeviceCount, '/', expectedDevices.length, 'devices received');
            
            // If expectedDevices is empty (device_list never arrived), use auto-detection
            if (expectedDevices.length === 0) {
                console.log('[Reports] ⚠ No device_list received, using auto-detection mode');
                
                // Wait for more devices with timeout
                if (!window.reportBuildTimeout) {
                    window.reportBuildTimeout = setTimeout(() => {
                        console.log('[Reports] ⏱ Auto-detect timeout, building with', receivedDeviceCount, 'devices');
                        buildReportFromDailyData();
                    }, 8000); // Wait 8 seconds to collect all inverters
                }
                
                // If we have 10+ inverters, build immediately
                if (receivedDeviceCount >= 10) {
                    if (window.reportBuildTimeout) {
                        clearTimeout(window.reportBuildTimeout);
                        window.reportBuildTimeout = null;
                    }
                    console.log('[Reports] ✓ Got 10+ inverters, building report now...');
                    buildReportFromDailyData();
                }
                return;
            }
            
            // If we have all expected devices, build immediately
            if (receivedDeviceCount >= expectedDevices.length) {
                console.log('[Reports] ✓ All devices received, building report now...');
                if (window.reportBuildTimeout) {
                    clearTimeout(window.reportBuildTimeout);
                    window.reportBuildTimeout = null;
                }
                buildReportFromDailyData();
                return;
            }
            
            // Otherwise, wait a bit for more devices (in case some are slow)
            if (!window.reportBuildTimeout) {
                window.reportBuildTimeout = setTimeout(() => {
                    console.log('[Reports] ⏱ Timeout waiting for more devices, building with', receivedDeviceCount, 'devices');
                    buildReportFromDailyData();
                }, 5000); // Wait 5 seconds after first device
            }
        }
        
        function buildReportFromDailyData() {
            console.log('[Reports] Building report from', Object.keys(dailyDataByDevice).length, 'devices');
            
            if (Object.keys(dailyDataByDevice).length === 0) {
                console.error('[Reports] No device data received');
                const tbody = document.getElementById('reportTableBody');
                tbody.innerHTML = '<tr><td colspan="30" class="py-10 text-center text-red-500">No inverter data received</td></tr>';
                pendingReportRequest = false;
                return;
            }
            
            pendingReportRequest = false;
            if (wsReportTimeout) { clearTimeout(wsReportTimeout); wsReportTimeout = null; }
            
            const type = document.getElementById('reportType').value;
            const invNames = Object.keys(dailyDataByDevice).sort();
            
            console.log('[Reports] Inverters found:', invNames);
            
            // Group data by hour (for daily) or by day (for monthly)
            const timeSlots = {};
            
            invNames.forEach((deviceName, idx) => {
                const deviceData = dailyDataByDevice[deviceName];
                
                deviceData.forEach(point => {
                    const timestamp = point.timestamp || point.time || '';
                    let timeLabel;
                    
                    if (type === 'daily') {
                        // Extract hour: "2026-07-15 05:30:00" -> "05:00"
                        const hourNum = parseInt(timestamp.substring(11, 13), 10);
                        
                        // FILTER: Only include working hours 06:00 to 18:00 (like Vinobasolar)
                        if (hourNum < 6 || hourNum > 18) {
                            return; // Skip this data point
                        }
                        
                        timeLabel = String(hourNum).padStart(2, '0') + ':00';
                    } else {
                        // Extract day: "2026-07-15" -> "15-07-2026"
                        const datePart = timestamp.substring(0, 10);
                        const [year, month, day] = datePart.split('-');
                        timeLabel = `${day}-${month}-${year}`;
                    }
                    
                    if (!timeSlots[timeLabel]) {
                        timeSlots[timeLabel] = { time_label: timeLabel };
                        invNames.forEach((_, i) => {
                            timeSlots[timeLabel]['inv' + (i+1) + '_kwh'] = 0;
                            timeSlots[timeLabel]['inv' + (i+1) + '_kw'] = 0;
                            timeSlots[timeLabel]['inv' + (i+1) + '_temp'] = 0;
                        });
                        timeSlots[timeLabel].vcb_kwh = 0;
                        timeSlots[timeLabel].vcb_kw = 0;
                        timeSlots[timeLabel].tx_loss = 0;
                        timeSlots[timeLabel].ot = 0;
                        timeSlots[timeLabel].wt1 = 0;
                        timeSlots[timeLabel].wt2 = 0;
                    }
                    
                    // Extract "Daily power yields" value
                    const dailyGen = point.values?.['Daily power yields'] || 0;
                    const activePower = point.values?.['Total active power'] || 0;
                    const temp = point.values?.['Internal temperature'] || 0;
                    
                    // Store max value for this hour/day (aggregating 5-minute intervals into hourly)
                    timeSlots[timeLabel]['inv' + (idx+1) + '_kwh'] = Math.max(
                        timeSlots[timeLabel]['inv' + (idx+1) + '_kwh'],
                        parseFloat(dailyGen) || 0
                    );
                    timeSlots[timeLabel]['inv' + (idx+1) + '_kw'] = Math.max(
                        timeSlots[timeLabel]['inv' + (idx+1) + '_kw'],
                        parseFloat(activePower) || 0
                    );
                    timeSlots[timeLabel]['inv' + (idx+1) + '_temp'] = Math.max(
                        timeSlots[timeLabel]['inv' + (idx+1) + '_temp'],
                        parseFloat(temp) || 0
                    );
                });
            });
            
            const rows = Object.values(timeSlots).sort((a, b) => a.time_label.localeCompare(b.time_label));
            
            console.log('[Reports] Built', rows.length, 'rows');
            
            if (!rows.length) {
                const tbody = document.getElementById('reportTableBody');
                tbody.innerHTML = '<tr><td colspan="30" class="py-10 text-center text-gray-500">No data found for this period.</td></tr>';
                return;
            }
            
            lastReportData = { type: type, data: rows, meta: { inv_names: invNames } };
            renderReportData(type, rows, invNames);
            
            // Reset for next request
            dailyDataByDevice = {};
            receivedDeviceCount = 0;
        }
        
        function renderTableHeaders(type, invNames) {
            const thead = document.querySelector('.report-table thead');
            const n = invNames.length;
            const timeW = 115;
            const invW  = 135;
            const totW  = 155;
            let totalW = timeW + n * invW + totW;

            let topRow = `<th rowspan="2" style="width:${timeW}px;min-width:${timeW}px;">${type==='daily'?'Time':'Date'}</th>`;
            invNames.forEach(name => {
                topRow += `<th style="width:${invW}px;min-width:${invW}px;" class="bg-blue-50/60">${name}</th>`;
            });
            topRow += `<th style="width:${totW}px;min-width:${totW}px;" class="bg-indigo-50/70">Total Generation</th>`;

            let subRow = '';
            invNames.forEach(() => { subRow += `<th class="bg-blue-50/40">kWh</th>`; });
            subRow += `<th class="bg-indigo-50/50">kWh</th>`;

            thead.innerHTML = `<tr>${topRow}</tr><tr>${subRow}</tr>`;
            document.querySelector('.report-table').style.minWidth = totalW + 'px';
        }

        function renderReportData(type, rows, invNames) {
            const tbody = document.getElementById('reportTableBody');
            if (!rows || !rows.length) {
                tbody.innerHTML = '<tr><td colspan="30" class="py-10 text-center text-gray-500">No data found for this period.</td></tr>';
                return;
            }
            if (!invNames || !invNames.length) {
                invNames = [];
                for (let i = 1; i <= 14; i++) {
                    if (rows.some(r => (r['inv'+i+'_kwh']||0) > 0)) invNames.push('INV-'+i);
                }
                if (!invNames.length) invNames = ['INV-1','INV-2'];
            }
            renderTableHeaders(type, invNames);

            const totInvKwh = new Array(invNames.length).fill(0);
            let totInvTotal = 0;

            let html = '';
            rows.forEach((row, ri) => {
                // Check if this row has any generation data
                let hasGeneration = false;
                let rowInvTotal = 0;
                
                invNames.forEach((_, i) => {
                    const n = i + 1;
                    const kwh = row['inv'+n+'_kwh'] || 0;
                    if (kwh > 0) hasGeneration = true;
                    rowInvTotal += kwh;
                });
                
                // Skip rows with no generation (all zeros)
                if (!hasGeneration && rowInvTotal === 0 && type !== 'daily') {
                    return;
                }
                
                const bgClass = ri % 2 === 0 ? 'bg-white' : 'bg-slate-50/60';
                html += `<tr class="${bgClass} hover:bg-blue-50/30 transition-colors">`;
                html += `<td class="font-semibold text-gray-700 bg-gray-50/80 sticky-col">${row.time_label || '-'}</td>`;

                invNames.forEach((_, i) => {
                    const n = i + 1;
                    const kwh = row['inv'+n+'_kwh'] || 0;
                    totInvKwh[i] += kwh;
                    html += `<td class="text-blue-700 font-medium">${fmt(kwh)}</td>`;
                });

                const invTotal = (row.inv_total_kwh > 0) ? row.inv_total_kwh : rowInvTotal;
                totInvTotal += invTotal;

                html += `<td class="font-bold text-indigo-700">${fmt(invTotal)}</td>`;
                html += '</tr>';
            });

            tbody.innerHTML = html;
            updateLiveStatus();
            document.getElementById('dlBtn').disabled = false;
            document.getElementById('dlBtn').classList.remove('opacity-50','cursor-not-allowed');
            if (exportAfterGenerate) {
                exportAfterGenerate = false;
                setTimeout(exportToPDF, 150);
            }
            if (excelAfterGenerate) {
                excelAfterGenerate = false;
                setTimeout(downloadExcel, 50);
            }
        }

        function generateReportData() {
            const type = document.getElementById('reportType').value;
            const selectedDate = type === 'daily' ? dateInput.value : monthInput.value;
            const plantId = plantSelect.value;
            const plant = plants.find(p => p.id === plantId);
            if (!plant) return;

            document.getElementById('reportPlantName').textContent = plant.name.toUpperCase();
            document.getElementById('reportLocation').textContent = plant.location;
            document.getElementById('reportCapacity').textContent = plant.capacity + ' MW';

            const dateObj = new Date(type==='daily'?selectedDate:selectedDate+'-01');
            const options = type==='daily'?{year:'numeric',month:'long',day:'numeric'}:{year:'numeric',month:'long'};
            document.getElementById('displayDate').innerText = dateObj.toLocaleDateString('en-IN', options);
            
            const tbody = document.getElementById('reportTableBody');
            tbody.innerHTML = '<tr><td colspan="30" class="py-12 bg-white"><div class="flex flex-col items-center justify-center"><div class="w-10 h-10 border-4 border-gray-200 border-t-blue-600 rounded-full animate-spin"></div><p class="mt-3 text-sm font-bold text-gray-600">Loading Vinoba Universal Analytics...</p></div></td></tr>';

            pendingReportRequest = true;
            expectedDevices = [];
            analyticsDataByDevice = {};
            analyticsReceived = new Set();
            const token = ++analyticsRequestToken;
            connectReportWS();

            function sendDeviceListRequest() {
                if (!ws || ws.readyState !== WebSocket.OPEN) {
                    return false;
                }
                // The Vinoba service requires a unit subscription before serving
                // analytics requests. It may finish its legacy daily stream first,
                // so the report timeout below allows that queue to drain.
                ws.send(JSON.stringify({ type: 'subscribe', unit_id: plant.unit_id }));
                ws.send(JSON.stringify({ type: 'get_devices', unit_id: plant.unit_id }));
                setTimeout(() => {
                    if (token === analyticsRequestToken && expectedDevices.length === 0 && pendingReportRequest) {
                        const count = Number(plantConfig?.[plantId]?.inverter_count || (plantId === 'vijayanth' ? 14 : 10));
                        requestUniversalAnalytics(plant, Array.from({ length: count }, (_, i) => `inverter${i + 1}`));
                    }
                }, 3000);
                return true;
            }

            if (!sendDeviceListRequest()) {
                const waitStarted = Date.now();
                const waitForSocket = setInterval(() => {
                    if (token !== analyticsRequestToken || !pendingReportRequest) {
                        clearInterval(waitForSocket);
                        return;
                    }
                    if (sendDeviceListRequest() || Date.now() - waitStarted > 10000) {
                        clearInterval(waitForSocket);
                    }
                }, 500);
            }

            if (wsReportTimeout) clearTimeout(wsReportTimeout);
            wsReportTimeout = setTimeout(() => {
                if (token !== analyticsRequestToken || !pendingReportRequest) return;
                if (Object.keys(analyticsDataByDevice).length) finishUniversalAnalytics();
                else loadCachedReport('Universal Analytics timeout');
            }, 45000);
        }

        function loadCachedReport(reason) {
            pendingReportRequest = false;
            const type = document.getElementById('reportType').value;
            const selectedDate = type === 'daily' ? dateInput.value : monthInput.value;
            const plantId = plantSelect.value;
            console.warn('[Reports] Falling back to database cache:', reason);
            fetch(`api_reports.php?type=${encodeURIComponent(type)}&date=${encodeURIComponent(selectedDate)}&plant=${encodeURIComponent(plantId)}`, { cache: 'no-store' })
                .then(response => { if (!response.ok) throw new Error(`Report HTTP ${response.status}`); return response.json(); })
                .then(result => {
                    if (!result.success) throw new Error(result.error || 'Report request failed');
                    const rows = result.data || [];
                    const invNames = result.meta?.inv_names || [];
                    lastReportData = { type, data: rows, meta: { ...result.meta, inv_names: invNames, fallback_reason: reason } };
                    renderReportData(type, rows, invNames);
                })
                .catch(error => {
                    document.getElementById('reportTableBody').innerHTML = `<tr><td colspan="30" class="py-10 text-center"><div class="text-red-500 font-bold">Unable to load report</div><div class="text-gray-400 text-xs mt-2">${error.message}</div></td></tr>`;
                    exportAfterGenerate = false;
                    excelAfterGenerate = false;
                    const button = document.getElementById('pdfExportBtn');
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = '<i class="fa-solid fa-file-pdf"></i><span class="hidden sm:inline">Export PDF</span>';
                    }
                    const excelButton = document.getElementById('dlBtn');
                    if (excelButton) {
                        excelButton.disabled = false;
                        excelButton.innerHTML = '<i class="fa-solid fa-file-excel"></i><span class="hidden sm:inline">Excel</span>';
                    }
                });
        }

        function fmt(v) { return v !== undefined && v !== null ? Number(v).toFixed(2) : '0.00'; }

        function exportToPDF() {
            const button = document.getElementById('pdfExportBtn');
            const resetButton = () => {
                if (!button) return;
                button.disabled = false;
                button.innerHTML = '<i class="fa-solid fa-file-pdf"></i><span class="hidden sm:inline">Export PDF</span>';
            };

            try {
                if (!window.jspdf?.jsPDF || !lastReportData?.data?.length) {
                    throw new Error('PDF library or report data is unavailable');
                }

                const type = document.getElementById('reportType').value;
                const period = type === 'daily' ? dateInput.value : monthInput.value;
                const plant = plants.find(item => item.id === plantSelect.value);
                const rows = lastReportData.data;
                const toNumber = value => {
                    const parsed = parseFloat(value);
                    return Number.isFinite(parsed) ? parsed : 0;
                };
                let invNames = lastReportData.meta?.inv_names || [];
                if (!invNames.length) {
                    const count = Number(plantConfig?.[plantSelect.value]?.inverter_count || 0);
                    invNames = Array.from({ length: count }, (_, index) => `INV-${index + 1}`);
                }

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a3', compress: true });
                if (typeof doc.autoTable !== 'function') throw new Error('PDF table library did not load');

                const headers = [type === 'daily' ? 'Time' : 'Date', ...invNames, 'Total Generation (kWh)'];
                const body = rows.map(row => {
                    let calculatedTotal = 0;
                    const inverterValues = invNames.map((_, index) => {
                        const value = toNumber(row[`inv${index + 1}_kwh`]);
                        calculatedTotal += value;
                        return value.toFixed(2);
                    });
                    const total = toNumber(row.inv_total_kwh) > 0 ? toNumber(row.inv_total_kwh) : calculatedTotal;
                    return [row.time_label || '-', ...inverterValues, total.toFixed(2)];
                });

                doc.setTextColor(15, 23, 42);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(18);
                doc.text((plant?.name || 'Solar Plant').toUpperCase(), 12, 15);
                doc.setTextColor(21, 128, 61);
                doc.setFontSize(12);
                doc.text('Inverter Generation Report', 12, 22);
                doc.setTextColor(71, 85, 105);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9);
                doc.text(`Period: ${period}   Capacity: ${plant?.capacity || '-'} MW   Location: ${plant?.location || '-'}`, 12, 28);

                doc.autoTable({
                    head: [headers],
                    body,
                    startY: 33,
                    margin: { top: 12, right: 10, bottom: 14, left: 10 },
                    theme: 'grid',
                    styles: { font: 'helvetica', fontSize: invNames.length > 12 ? 6.5 : 7.5, cellPadding: 2, halign: 'center', valign: 'middle', overflow: 'linebreak' },
                    headStyles: { fillColor: [22, 163, 74], textColor: 255, fontStyle: 'bold', lineColor: [21, 128, 61] },
                    alternateRowStyles: { fillColor: [248, 250, 252] },
                    columnStyles: { 0: { fontStyle: 'bold', fillColor: [241, 245, 249] } },
                    didDrawPage: data => {
                        const pageCount = doc.internal.getNumberOfPages();
                        doc.setFontSize(8);
                        doc.setTextColor(100);
                        doc.text(`Generated ${new Date().toLocaleString('en-IN')}`, 10, doc.internal.pageSize.height - 6);
                        doc.text(`Page ${pageCount}`, doc.internal.pageSize.width - 24, doc.internal.pageSize.height - 6);
                    }
                });

                doc.save(`solar_${plantSelect.value}_${period}.pdf`);
                document.getElementById('liveStatus').textContent = 'PDF generated successfully';
                resetButton();
            } catch (error) {
                console.error('[Reports] PDF export failed:', error);
                document.getElementById('liveStatus').textContent = `PDF export failed: ${error.message}`;
                resetButton();
            }
        }

        function downloadExcel() {
            if (!lastReportData || !lastReportData.data) return;
            const type = document.getElementById('reportType').value;
            const date = type === 'daily' ? dateInput.value : monthInput.value;
            const plantId = plantSelect.value;
            const plant = plants.find(p => p.id === plantId);
            
            const rows = lastReportData.data;
            let invNames = lastReportData.meta ? lastReportData.meta.inv_names : null;
            if (!invNames || !invNames.length) {
                invNames = [];
                for (let i = 1; i <= 14; i++) {
                    if (rows.some(r => (r['inv'+i+'_kwh']||0) > 0)) invNames.push('INV-'+i);
                }
                if (!invNames.length) invNames = ['INV-1','INV-2'];
            }
            
            const columns = [type === 'daily' ? 'Time' : 'Date'];
            invNames.forEach(name => columns.push(name + ' (kWh)'));
            columns.push('Total Generation (kWh)');
            
            const totInvKwh = {};
            invNames.forEach(name => totInvKwh[name] = 0);
            let totInvTotal = 0;
            
            const exportRows = rows.map(row => {
                const timeLabel = row.time_label || '';
                const inverters = {};
                let rowInvTotal = 0;
                
                invNames.forEach((name, i) => {
                    const n = i + 1;
                    const kwh = parseFloat(row['inv'+n+'_kwh']) || 0;
                    inverters[name] = parseFloat(kwh.toFixed(2));
                    rowInvTotal += kwh;
                    totInvKwh[name] += kwh;
                });
                
                const invTotal = (row.inv_total_kwh > 0) ? row.inv_total_kwh : rowInvTotal;
                totInvTotal += invTotal;
                
                const exportRow = {
                    [type === 'daily' ? 'time' : 'date']: timeLabel,
                    inverters: inverters,
                    total_generation: parseFloat(invTotal.toFixed(2))
                };
                return exportRow;
            });

            // Daily rows contain cumulative readings, so the report total is the
            // final row. Monthly rows contain one final reading per day and are summed.
            if (type === 'daily' && exportRows.length) {
                const finalRow = exportRows.slice().reverse().find(row => Number(row.total_generation) > 0) || exportRows[exportRows.length - 1];
                invNames.forEach(name => { totInvKwh[name] = Number(finalRow.inverters[name]) || 0; });
                totInvTotal = Number(finalRow.total_generation) || 0;
            }
            
            const exportTotals = {
                inverters: {},
                total_generation: parseFloat(totInvTotal.toFixed(2))
            };
            invNames.forEach(name => {
                exportTotals.inverters[name] = parseFloat(totInvKwh[name].toFixed(2));
            });
            
            const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&apos;'}[ch]));
            const textCell = value => `<Cell><Data ss:Type="String">${esc(value)}</Data></Cell>`;
            const numberCell = value => `<Cell><Data ss:Type="Number">${Number(value) || 0}</Data></Cell>`;
            let sheetRows = `<Row ss:StyleID="Title"><Cell ss:MergeAcross="${columns.length - 1}"><Data ss:Type="String">${esc(plant.name)} - Inverter Generation Report</Data></Cell></Row>`;
            sheetRows += `<Row><Cell><Data ss:Type="String">Report</Data></Cell><Cell><Data ss:Type="String">${esc(type)}</Data></Cell><Cell><Data ss:Type="String">Date</Data></Cell><Cell><Data ss:Type="String">${esc(date)}</Data></Cell></Row>`;
            sheetRows += `<Row ss:StyleID="Header">${columns.map(textCell).join('')}</Row>`;
            exportRows.forEach(row => {
                const label = row[type === 'daily' ? 'time' : 'date'];
                sheetRows += `<Row>${textCell(label)}${invNames.map(name => numberCell(row.inverters[name])).join('')}${numberCell(row.total_generation)}</Row>`;
            });
            sheetRows += `<Row ss:StyleID="Total">${textCell('TOTAL')}${invNames.map(name => numberCell(exportTotals.inverters[name])).join('')}${numberCell(exportTotals.total_generation)}</Row>`;
            const workbook = `<?xml version="1.0"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Styles><Style ss:ID="Title"><Font ss:Bold="1" ss:Size="16"/></Style><Style ss:ID="Header"><Font ss:Bold="1"/><Interior ss:Color="#DDEBF7" ss:Pattern="Solid"/></Style><Style ss:ID="Total"><Font ss:Bold="1"/><Interior ss:Color="#E2F0D9" ss:Pattern="Solid"/></Style></Styles><Worksheet ss:Name="Generation Report"><Table>${sheetRows}</Table></Worksheet></Workbook>`;
            const filename = `solar_${plantId}_${date}.xls`;
            const blob = new Blob([workbook], { type: 'application/vnd.ms-excel;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a'); a.href = url; a.download = filename;
            document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
            const button = document.getElementById('dlBtn');
            if (button) {
                button.disabled = false;
                button.classList.remove('opacity-50','cursor-not-allowed');
                button.innerHTML = '<i class="fa-solid fa-file-excel"></i><span class="hidden sm:inline">Excel</span>';
            }
        }

        loadSidebar();
        connectReportWS();
        setTimeout(() => { generateReportData(); startAutoRefresh(); }, 1000);
        window.addEventListener('beforeunload', () => { stopAutoRefresh(); if (ws) ws.close(); });
    </script>
</body>
</html>
