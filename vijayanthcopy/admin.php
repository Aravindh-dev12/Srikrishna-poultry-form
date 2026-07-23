<?php
require 'check_auth.php';
if ($user['role'] !== 'admin') {
    header('Location: overview.php?plant=' . urlencode($user['plant_id'] ?? getDefaultPlantId()));
    exit;
}
// Use plants directly from config.php $PLANTS array - single source of truth
$iconMap = [
    'vijayanth' => 'fa-bolt',
    'krishna'   => 'fa-egg',
];
$plants = [];
foreach ($PLANTS as $pid => $pinfo) {
    $plants[] = [
        'id'    => $pid,
        'name'  => $pinfo['name'],
        'theme' => $pinfo['theme'],
        'icon'  => $iconMap[$pid] ?? 'fa-solar-panel',
        'capacity' => $pinfo['capacity'],
        'location' => $pinfo['location'],
        'ws_unit_id' => $pinfo['ws_unit_id'],
        'status'  => $pinfo['status'],
    ];
}
$themeClasses = [
    'violet'  => ['bg' => 'bg-violet-50',  'border' => 'border-violet-100',  'text' => 'text-violet-600',  'badge' => 'bg-violet-100 text-violet-700'],
    'blue'    => ['bg' => 'bg-blue-50',    'border' => 'border-blue-100',    'text' => 'text-blue-600',    'badge' => 'bg-blue-100 text-blue-700'],
    'emerald' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-100', 'text' => 'text-emerald-600', 'badge' => 'bg-emerald-100 text-emerald-700'],
    'rose'    => ['bg' => 'bg-rose-50',    'border' => 'border-rose-100',    'text' => 'text-rose-600',    'badge' => 'bg-rose-100 text-rose-700'],
    'orange'  => ['bg' => 'bg-orange-50',  'border' => 'border-orange-100',  'text' => 'text-orange-600',  'badge' => 'bg-orange-100 text-orange-700'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title>Vijayantha Cosmic Power Ltd - Real-Time Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        .alert-state {
            animation: alert-glow 1.5s infinite;
            border-width: 2px !important;
        }
        @keyframes alert-glow {
            0%, 100% { box-shadow: 0 0 5px rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); }
            50% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.8); border-color: rgba(239, 68, 68, 1); }
        }
        
        .status-badge-pulse { 
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; 
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9; 
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; 
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex justify-between h-14 items-center">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-md flex items-center justify-center">
                        <i class="fa-solid fa-user-shield text-sm"></i>
                    </div>
                    <div class="min-w-0 leading-tight">
                        <h1 class="text-sm sm:text-lg font-bold tracking-tight text-slate-800 truncate">Vijayantha Cosmic Power Ltd</h1>
                        <p class="hidden sm:block text-[10px] font-semibold text-slate-500">Real-Time Admin Dashboard</p>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Admin</span>
                </div>
                <div class="flex items-center gap-3">
                    <span id="ws-status" class="hidden sm:inline text-xs font-bold text-amber-600"><i class="fa-solid fa-circle text-[8px] mr-1"></i>Loading cached data</span>
                    
                    <div class="h-6 w-px bg-slate-200 mx-1"></div>
                    
                    <button onclick="document.getElementById('manage-users-modal').classList.remove('hidden')" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100 rounded-md transition-colors">
                        <i class="fa-solid fa-users-gear"></i> Manage Users
                    </button>
                    <a href="logout.php" class="px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-red-50 hover:text-red-600 rounded-md transition-colors">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-7xl mx-auto w-full py-6 px-4">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-solar-panel"></i>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Plants</div>
                    <div class="text-2xl font-black text-slate-800"><?php echo count($plants); ?></div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Live Monitoring</div>
                    <div id="live-plant-count" class="text-2xl font-black text-slate-800">0 / <?php echo count($plants); ?></div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Fleet Active Power</div>
                    <div id="fleet-active-power" class="text-2xl font-black text-slate-800">0.00 kW</div>
                </div>
            </div>
        </div>

        <!-- Plant Cards -->
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-800">Plants</h2>
            <p class="text-xs text-slate-500">Live monitoring with real-time inverter data.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="plants-container">
            <?php foreach ($plants as $p):
                $theme = $p['theme'];
                $t = $themeClasses[$theme] ?? $themeClasses['blue'];
                $isLive = $p['status'] === 'live';
            ?>
            <a href="overview.php?plant=<?php echo urlencode($p['id']); ?>" class="block">
                <div id="card-<?php echo $p['id']; ?>" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm flex flex-col h-[420px] transition-all hover:-translate-y-1 hover:shadow-lg duration-200">
                    <div class="<?php echo $t['bg']; ?> px-4 py-3 border-b <?php echo $t['border']; ?> flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid <?php echo $p['icon']; ?> <?php echo $t['text']; ?>"></i>
                            <h3 class="text-base font-bold text-slate-800"><?php echo htmlspecialchars($p['name']); ?></h3>
                        </div>
                        <div id="badge-<?php echo $p['id']; ?>" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-500">Wait...</div>
                    </div>
                    
                    <div class="p-4 grid grid-cols-2 gap-3 shrink-0">
                        <div class="bg-slate-50 rounded-lg p-3 border border-slate-100 flex flex-col justify-center">
                            <div class="text-slate-400 text-[10px] font-bold uppercase mb-1 tracking-wider"><i class="fa-solid fa-bolt text-yellow-500 mr-1"></i>Active</div>
                            <div class="text-sm font-black text-slate-800" id="active-<?php echo $p['id']; ?>">0.00 kW</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3 border border-slate-100 flex flex-col justify-center">
                            <div class="text-slate-400 text-[10px] font-bold uppercase mb-1 tracking-wider"><i class="fa-solid fa-sun text-orange-400 mr-1"></i>Today</div>
                            <div class="text-sm font-black text-slate-800" id="today-<?php echo $p['id']; ?>">0.00 kWh</div>
                        </div>
                    </div>
                    
                    <div class="px-4 pb-4 flex flex-col overflow-hidden">
                        <div class="flex justify-between items-center mb-2 shrink-0">
                            <div class="text-slate-500 text-[10px] font-bold uppercase tracking-wider"><i class="fa-solid fa-network-wired mr-1 text-slate-400"></i>Inverter Details</div>
                            <div class="text-[9px] text-slate-400 font-medium">Update: <span id="time-<?php echo $p['id']; ?>">--</span></div>
                        </div>
                        <div id="inverters-<?php echo $p['id']; ?>" class="bg-slate-50 rounded-lg p-2 border border-slate-200 flex-grow overflow-y-auto space-y-1.5 custom-scrollbar">
                            <div class="text-xs text-slate-400 italic text-center py-4">Waiting for telemetry data...</div>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Manage Users Modal -->
    <div id="manage-users-modal" class="fixed inset-0 bg-slate-900/50 hidden z-[100] flex items-center justify-center backdrop-blur-sm px-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-800">Add New User</h3>
                <button onclick="document.getElementById('manage-users-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="addUserForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">User Email</label>
                    <input type="email" id="new_email" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                    <input type="password" id="new_password" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Plant</label>
                    <select id="new_plant_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($plants as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-sm transition-colors">Create User</button>
                <p id="user-msg" class="text-xs text-center font-medium mt-2 hidden"></p>
            </form>
        </div>
    </div>

    <script src="assets/live_ws_store.js"></script>
    <script>
        // Auth guard for admin page
        (function() {
            const token = sessionStorage.getItem('vs_token');
            const userStr = sessionStorage.getItem('vs_user');
            if (!token || !userStr) { window.location.replace('index.php'); return; }
            try {
                const u = JSON.parse(userStr);
                if (u.role !== 'admin') window.location.replace('index.php');
            } catch(e) { window.location.replace('index.php'); }
        })();

        function getToken() {
            return sessionStorage.getItem('vs_token') || '';
        }

        // Plant state management
        const plants = <?php echo json_encode(array_map(function($p) { 
            return ['id' => $p['id'], 'ws_unit_id' => $p['ws_unit_id'], 'name' => $p['name']]; 
        }, $plants)); ?>;

        const plantState = {};
        plants.forEach(p => {
            plantState[p.ws_unit_id] = {
                plantId: p.id,
                vcbPower: 0,
                hasVCB: false,
                dailyEnergy: 0,
                inverters: {},
                lastUpdate: '--',
                lastSeenAt: 0
            };
        });

        document.addEventListener('DOMContentLoaded', () => {
            plants.forEach(loadFastSnapshot);
            connectWS();
            setInterval(() => plants.forEach(p => updateUI(p.ws_unit_id)), 15000);
        });

        const numberValue = value => {
            const parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : 0;
        };
        const powerKw = value => {
            const parsed = numberValue(value);
            return Math.abs(parsed) > 10000 ? parsed / 1000 : parsed;
        };
        const canonicalInverterName = name => {
            const match = String(name || '').match(/\d+/);
            return match ? `Inverter${parseInt(match[0], 10)}` : String(name || 'Inverter');
        };

        function applySnapshot(plant, json) {
            if (!json || json.status !== 'success' || !json.data) return;
            const st = plantState[plant.ws_unit_id];
            const vcb = json.data.vcb || null;
            if (vcb) {
                st.vcbPower = numberValue(vcb.power_3phase_kw);
                st.hasVCB = true;
                st.dailyEnergy = numberValue(vcb.today_energy_kwh);
                if (vcb.snapshot_at) st.lastUpdate = new Date(vcb.snapshot_at).toLocaleTimeString();
                const stamp = Date.parse(vcb.snapshot_at || '');
                if (Number.isFinite(stamp)) st.lastSeenAt = Math.max(st.lastSeenAt, stamp);
            }
            (json.data.inverters || []).forEach(row => {
                const name = canonicalInverterName(row.inverter_name);
                st.inverters[name] = {
                    active: numberValue(row.active_strings), total: numberValue(row.total_strings),
                    power: numberValue(row.power_kw), dailyGen: numberValue(row.daily_gen_kwh)
                };
                const stamp = Date.parse(row.snapshot_at || '');
                if (Number.isFinite(stamp)) st.lastSeenAt = Math.max(st.lastSeenAt, stamp);
                if (row.snapshot_at) st.lastUpdate = new Date(row.snapshot_at).toLocaleTimeString();
            });
            updateUI(plant.ws_unit_id);
        }

        function loadFastSnapshot(plant) {
            if (!window.LiveWsStore) return;
            window.LiveWsStore.fastSnapshot(plant.id).then(response => response.json())
                .then(json => applySnapshot(plant, json))
                .catch(error => console.warn(`Snapshot unavailable for ${plant.id}:`, error.message));
        }

        window.addEventListener('plant-fast-snapshot', event => {
            const plant = plants.find(p => p.id === event.detail?.plantId);
            if (plant) applySnapshot(plant, event.detail.snapshot);
        });

        function updateUI(unit_id) {
            const st = plantState[unit_id];
            if(!st) return;

            const plantId = st.plantId;

            let sumInverterPower = 0;
            for (const inv in st.inverters) {
                sumInverterPower += st.inverters[inv].power;
            }

            const finalActivePower = st.vcbPower > 0 ? st.vcbPower : sumInverterPower;
            const inverterDailyEnergy = Object.values(st.inverters).reduce((sum, inv) => sum + numberValue(inv.dailyGen), 0);
            const finalDailyEnergy = st.dailyEnergy > 0 ? st.dailyEnergy : inverterDailyEnergy;

            document.getElementById(`active-${plantId}`).textContent = `${finalActivePower.toFixed(2)} kW`;
            document.getElementById(`today-${plantId}`).textContent  = `${finalDailyEnergy.toFixed(2)} kWh`;
            document.getElementById(`time-${plantId}`).textContent   = st.lastUpdate;

            const invContainer = document.getElementById(`inverters-${plantId}`);
            let invHTML = '';

            const sortedInverters = Object.keys(st.inverters).sort((a, b) => {
                const numA = parseInt(a.replace(/\D/g, '')) || 0;
                const numB = parseInt(b.replace(/\D/g, '')) || 0;
                return numA - numB;
            });
            
            if (sortedInverters.length > 0) {
                sortedInverters.forEach(invName => {
                    const inv = st.inverters[invName];
                    const hasStrings = inv.total > 0;
                    const greenThreshold = Math.ceil(inv.total * 0.7);
                    const isGreen = hasStrings && inv.active >= greenThreshold;
                    const isYellow = hasStrings && inv.active > 0 && inv.active < greenThreshold;
                    const isRed = hasStrings && inv.active === 0;

                    let strColor, strBorder, rowBg, iconColor;
                    if (isGreen) {
                        strColor = 'text-emerald-700 bg-emerald-100'; strBorder = 'border-emerald-200'; rowBg = 'bg-emerald-50/30'; iconColor = 'text-emerald-500';
                    } else if (isRed) {
                        strColor = 'text-red-700 bg-red-100'; strBorder = 'border-red-200'; rowBg = 'bg-red-50'; iconColor = 'text-red-500';
                    } else if (isYellow) {
                        strColor = 'text-amber-700 bg-amber-100'; strBorder = 'border-amber-300'; rowBg = 'bg-amber-50/50'; iconColor = 'text-amber-400';
                    } else {
                        strColor = 'text-slate-600 bg-slate-100'; strBorder = 'border-slate-200'; rowBg = 'bg-white'; iconColor = 'text-slate-300';
                    }
                    const strBadge = hasStrings
                        ? `<span class="font-bold px-1.5 py-0.5 rounded border ${strBorder} ${strColor} min-w-[36px] text-center text-[10px]">${inv.active}/${inv.total}</span>`
                        : '';

                    invHTML += `
                        <div class="flex justify-between items-center text-xs ${rowBg} px-2.5 py-1.5 rounded border border-slate-200 shadow-sm transition-colors">
                            <span class="font-semibold text-slate-700 capitalize flex items-center gap-1.5">
                                <i class="fa-solid fa-server ${iconColor} text-[10px]"></i>
                                ${invName}
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="text-slate-500 font-medium">${inv.power.toFixed(1)} kW</span>
                                ${strBadge}
                            </div>
                        </div>
                    `;
                });
                invContainer.innerHTML = invHTML;
            }

            const badge = document.getElementById(`badge-${plantId}`);
            const card = document.getElementById(`card-${plantId}`);

            const isFresh = st.lastSeenAt > 0 && (Date.now() - st.lastSeenAt) < 120000;
            if (finalActivePower > 0.01) {
                badge.className = "text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700";
                badge.innerHTML = `<span class="h-2 w-2 rounded-full bg-green-500 inline-block mr-1 shadow-[0_0_5px_green]"></span> ON`;
                card.classList.remove('alert-state');
            } else if (isFresh) {
                badge.className = "text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700";
                badge.innerHTML = `<span class="h-2 w-2 rounded-full bg-amber-500 inline-block mr-1"></span> STANDBY`;
                card.classList.remove('alert-state');
            } else if (st.lastSeenAt || Object.keys(st.inverters).length > 0) {
                badge.className = "text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700";
                badge.innerHTML = `<span class="h-2 w-2 rounded-full bg-blue-500 inline-block mr-1"></span> LAST DATA`;
                card.classList.remove('alert-state');
            } else {
                badge.className = "text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700 status-badge-pulse";
                badge.innerHTML = `<span class="h-2 w-2 rounded-full bg-red-500 inline-block mr-1"></span> OFF`;
                card.classList.add('alert-state');
            }
            updateFleetSummary();
        }

        function updateFleetSummary() {
            let live = 0;
            let fleetPower = 0;
            plants.forEach(plant => {
                const st = plantState[plant.ws_unit_id];
                const inverterPower = Object.values(st.inverters).reduce((sum, inv) => sum + numberValue(inv.power), 0);
                fleetPower += st.vcbPower > 0 ? st.vcbPower : inverterPower;
                if (st.lastSeenAt && Date.now() - st.lastSeenAt < 120000) live++;
            });
            document.getElementById('live-plant-count').textContent = `${live} / ${plants.length}`;
            document.getElementById('fleet-active-power').textContent = `${fleetPower.toFixed(2)} kW`;
        }

        let ws;
        function connectWS() {
            ws = new WebSocket("wss://vinobasolar.scadahub.in:5001");

            ws.onopen = function() {
                const wsStatus = document.getElementById('ws-status');
                wsStatus.className = "text-xs font-bold text-green-500";
                wsStatus.innerHTML = '<i class="fa-solid fa-circle text-[8px] mr-1 shadow-[0_0_5px_green]"></i> Live Data';
                
                plants.forEach(p => {
                    ws.send(JSON.stringify({ type: "subscribe", unit_id: p.ws_unit_id }));
                    ws.send(JSON.stringify({ type: "get_devices", unit_id: p.ws_unit_id }));
                });
            };

            ws.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    const unit = data.unit_id;
                    if(!plantState[unit]) return;

                    const plant = plants.find(p => p.ws_unit_id === unit);
                    if (data.type === 'device_list') {
                        if (window.LiveWsStore) window.LiveWsStore.requestTodayForDevices(ws, unit, data.devices || []);
                        return;
                    }
                    if (window.LiveWsStore && plant) window.LiveWsStore.storeMessage(data, plant.id);

                    plantState[unit].lastUpdate = data.time || new Date().toLocaleTimeString();
                    plantState[unit].lastSeenAt = Date.now();

                    if (data.values && data.values["3 Phase Active Power"] !== undefined) {
                        plantState[unit].vcbPower = powerKw(data.values["3 Phase Active Power"]);
                        plantState[unit].hasVCB = true;
                    }

                    if (data.virtualTags && data.virtualTags["vcb-today"] !== undefined) {
                        const tag = data.virtualTags["vcb-today"];
                        plantState[unit].dailyEnergy = numberValue(tag && typeof tag === 'object' ? tag.value : tag);
                    }

                    if (data.values) {
                        const taskStr = data.task ? data.task.toString().toLowerCase() : '';
                        const deviceStr = data.device ? data.device.toString().toLowerCase() : '';
                        const isVCBMsg = (taskStr === 'vcb' || deviceStr.includes('vcb'));

                        if (!isVCBMsg) {
                            const keys = Object.keys(data.values);
                            const hasInvPower = keys.some(pk => {
                                const pkl = pk.toLowerCase();
                                return (/power/.test(pkl) && /active|ac/.test(pkl) && !/reactive|apparent/.test(pkl));
                            });
                            const hasNumberedCurrents = keys.some(k => /\d/.test(k) && /curr|current|amp/i.test(k) && !/phase|3.phase|reactive|apparent|freq|temp/i.test(k.toLowerCase()));
                            const isInv = (taskStr === 'inverter') || hasInvPower || hasNumberedCurrents;
                            if (isInv) {
                                let activeStrCount = 0;
                                let totalStrCount = 0;
                                for (const key in data.values) {
                                    const kl = key.toLowerCase();
                                    if (/phase|phasa|ph_|r.phase|y.phase|b.phase|a.phase|c.phase|3.phase|three.phase/i.test(kl)) continue;
                                    if (/inverter.*curr|inv.*curr|total.*curr|grid.*curr|load.*curr|reactive.*curr|mppt.*curr|dc.*curr/i.test(kl)) continue;
                                    if (/freq|temperature|temp|ambient|cosphi|pf.*_/i.test(kl)) continue;
                                    if (/\b(curr|current|amp|i)\b/i.test(kl) && !/\b(volt|voltage|temp|freq)\b/i.test(kl) && /\d/.test(key)) {
                                        totalStrCount++;
                                        if (parseFloat(data.values[key]) > 0.5) activeStrCount++;
                                    }
                                }
                                const deviceName = canonicalInverterName(data.device || data.deviceName);
                                const hasExactLivePower = data.values['Total active power'] !== undefined;
                                let pwr = hasExactLivePower ? powerKw(data.values['Total active power']) : 0;
                                if (!hasExactLivePower) {
                                    for (const pk in data.values) {
                                        const pkl = pk.toLowerCase();
                                        const isRatedValue = /nominal|rated|capacity|maximum|max\.?\s*power/.test(pkl);
                                        if (/active.*power|ac.*power|power.*ac|a\.c\..*power/i.test(pkl) && !/reactive|apparent|3.phase/i.test(pkl) && !isRatedValue) {
                                            pwr = powerKw(data.values[pk]);
                                            break;
                                        }
                                    }
                                }
                                plantState[unit].inverters[deviceName] = {
                                    active: activeStrCount,
                                    total: totalStrCount,
                                    power: pwr,
                                    dailyGen: numberValue(data.values['Daily power yields'] ?? data.values['Today Energy'] ?? 0)
                                };
                            }
                        }
                    }

                    updateUI(unit);

                } catch(e) {
                    console.error("WS Parse Error:", e);
                }
            };

            ws.onclose = function() {
                const wsStatus = document.getElementById('ws-status');
                wsStatus.className = "text-xs font-bold text-red-500 status-badge-pulse";
                wsStatus.innerHTML = '<i class="fa-solid fa-circle text-[8px] mr-1"></i> Disconnected - Retrying...';
                
                setTimeout(connectWS, 5000); 
            };
            
            ws.onerror = function() {
                console.log("WebSocket encountered an error.");
            };
        }

        document.getElementById('addUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = document.getElementById('user-msg');
            msg.classList.add('hidden');
            try {
                const res = await fetch('api.php?action=add_user', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + getToken()
                    },
                    body: JSON.stringify({ email: new_email.value, password: new_password.value, plant_id: new_plant_id.value })
                });
                const data = await res.json();
                msg.innerHTML = data.status === 'success'
                    ? `<i class="fa-solid fa-circle-check mr-1"></i> ${data.message}`
                    : `<i class="fa-solid fa-circle-exclamation mr-1"></i> ${data.message}`;
                msg.className = `text-xs text-center font-medium mt-2 ${data.status === 'success' ? 'text-green-600' : 'text-red-600'}`;
                msg.classList.remove('hidden');
                if (data.status === 'success') document.getElementById('addUserForm').reset();
            } catch (error) {
                console.error('User Add Error:', error);
            }
        });
    </script>
</body>
</html>
