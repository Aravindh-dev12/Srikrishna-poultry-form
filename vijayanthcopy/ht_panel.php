<?php
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'ht_panel.php') {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: sld.php' . ($query ? '?' . $query : ''));
    exit;
}
require 'check_auth.php';
$htPlantInfo = $PLANTS[$currentPlant] ?? $PLANTS[array_key_first($PLANTS)];
$htPlantName = htmlspecialchars($htPlantInfo['name'] ?? 'Solar Plant');
$htCapacity = (float)($htPlantInfo['capacity'] ?? 0);
$htCapacityLabel = rtrim(rtrim(number_format($htCapacity, 1), '0'), '.');
$htConfiguredCount = (int)($htPlantInfo['inverter_count'] ?? 0);
$htInverterCount = $htConfiguredCount > 0 ? $htConfiguredCount : 10;
$htInverterNumbers = range(1, $htInverterCount);
$htTransformerMva = rtrim(rtrim(number_format(max($htCapacity * 1.1, $htCapacity), 1), '0'), '.');
$htSldWidth = max(1660, 700 + ($htInverterCount * 112));
$htLegendX = $htSldWidth - 305;
$htMainCenterX = (int)round($htLegendX / 2);
$htBusStartX = 260;
$htBusEndX = $htLegendX - 185;
$htInvSpan = max(1, $htBusEndX - $htBusStartX - 120);
$htInvSpacing = $htInverterCount > 1 ? min(112, $htInvSpan / max(1, $htInverterCount - 1)) : 0;
$htFirstInvX = $htMainCenterX - (($htInverterCount - 1) * $htInvSpacing / 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/responsive.css">
    <title id="pageTitle">Solar Plant - SLD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/live_ws_store.js"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .sld-svg text { font-family: Arial, Helvetica, sans-serif; }
        .sld-line { stroke: #111827; stroke-width: 2; fill: none; }
        .sld-thin { stroke: #111827; stroke-width: 1.2; fill: none; }
        .sld-dash { stroke: #111827; stroke-width: 1.4; stroke-dasharray: 6 5; fill: none; }
        .sld-blue { fill: #001f8f; font-weight: 700; }
        .sld-red { fill: #e60000; font-weight: 800; }
        .sld-tiny { font-size: 10px; }
        .sld-small { font-size: 11px; }
        .sld-label { font-size: 13px; font-weight: 700; fill: #111827; }
        .sld-status-live { stroke: #16a34a; fill: #dcfce7; }
        .sld-status-open { stroke: #dc2626; fill: #fee2e2; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800 font-sans">
    <div class="min-h-screen flex relative">
        <div id="overlay" class="fixed inset-0 bg-slate-900 bg-opacity-40 hidden z-30 md:hidden transition-opacity"></div>
        <div id="sidebar-container"></div>
        
        <main class="flex-1 flex flex-col w-full md:ml-64 overflow-x-hidden transition-all duration-300 ease-in-out">
            <header class="bg-white p-4 sm:px-6 flex justify-between items-center sticky top-0 z-20 border-b border-slate-200 shadow-sm">
                <div class="flex items-center gap-3">
                    <button id="menuBtn" class="md:hidden text-emerald-600 text-2xl focus:outline-none">&#9776;</button>
                    <div><h2 class="text-xl font-black text-slate-800 tracking-tight">Single Line Diagram</h2></div>
                </div>
                <div class="flex items-center gap-3 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                    <div id="refreshPulse" class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                    <span class="text-xs font-bold text-slate-600 tracking-widest hidden sm:inline" id="clockDisplay">--:--:--</span>
                </div>
            </header>

            <div class="p-4 sm:p-6 w-full flex flex-col gap-6 max-w-[1600px] mx-auto">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-5">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Plant SLD</p>
                            <h2 class="text-xl font-black text-slate-900">Power Flow Overview</h2>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Live view</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">PV Array (DC)</h3>
                            <p class="text-3xl font-black text-slate-900" id="sld_pv">-- <span class="text-lg text-slate-500">kW</span></p>
                            <p class="text-xs text-slate-500 font-medium mt-2">Total inverter DC input</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Inverter AC Output</h3>
                            <p class="text-3xl font-black text-slate-900" id="sld_inv">-- <span class="text-lg text-slate-500">kW</span></p>
                            <p class="text-xs text-slate-500 font-medium mt-2">Combined AC active power</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Grid Export (VCB)</h3>
                            <p class="text-3xl font-black text-slate-900" id="sld_grid">-- <span class="text-lg text-slate-500">kW</span></p>
                            <p class="text-xs text-slate-500 font-medium mt-2">3 Phase Active Power</p>
                        </div>
                    </div>
                    <div class="mt-7 rounded-xl bg-slate-900 p-8 sm:p-12 min-h-[260px] flex items-center justify-center">
                        <div class="w-full max-w-xl text-center">
                            <h3 class="text-lg font-black text-emerald-400 mb-7">SLD Power Flow</h3>
                            <div class="grid grid-cols-3 gap-5 items-end">
                            <div>
                                <p class="text-sm font-medium text-slate-300">PV Array</p>
                                <p class="mt-2 text-2xl font-black text-yellow-400" id="sld_pv2">-- kW</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-300">Inverters</p>
                                <p class="mt-2 text-2xl font-black text-blue-400" id="sld_inv2">-- kW</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-300">Grid</p>
                                <p class="mt-2 text-2xl font-black text-emerald-400" id="sld_grid2">-- kW</p>
                            </div>
                            </div>
                            <p class="mt-7 text-xs font-medium text-slate-500">Live data from WebSocket | Plant: <?php echo $htPlantName; ?></p>
                        </div>
                    </div>
                    <div class="hidden">
                        <span id="ht_breaker_status"></span>
                        <span id="ht_active_power"></span>
                        <span id="ht_today_gen"></span>
                        <span id="ht_pf"></span>
                        <span id="v_ry"></span>
                        <span id="v_yb"></span>
                        <span id="v_br"></span>
                        <span id="i_r"></span>
                        <span id="i_y"></span>
                        <span id="i_b"></span>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest">Single Line Diagram</h3>
                        <span class="text-[10px] text-slate-400 sm:hidden"><i class="fa-solid fa-arrows-left-right mr-1"></i>Scroll to see full diagram</span>
                    </div>
                    <div class="bg-white border border-slate-300 rounded-lg overflow-auto">
                        <svg class="sld-svg w-full h-auto bg-white" style="min-width: <?php echo $htSldWidth; ?>px" viewBox="0 0 <?php echo $htSldWidth; ?> 900" role="img" aria-label="<?php echo $htCapacityLabel; ?>MW solar power plant single line diagram">
                            <rect x="1" y="1" width="<?php echo $htSldWidth - 2; ?>" height="898" fill="#fff" stroke="#111827" stroke-width="1.5"/>
                            <text x="<?php echo $htMainCenterX; ?>" y="24" text-anchor="middle" class="sld-red" font-size="18"><?php echo strtoupper($htPlantName); ?></text>
                            <text x="<?php echo $htMainCenterX; ?>" y="48" text-anchor="middle" class="sld-red" font-size="16">SINGLE LINE DIAGRAM (SLD)</text>

                            <line x1="1" y1="270" x2="485" y2="270" class="sld-dash"/>
                            <line x1="835" y1="270" x2="<?php echo $htLegendX; ?>" y2="270" class="sld-dash"/>
                            <line x1="1" y1="400" x2="485" y2="400" class="sld-dash"/>
                            <line x1="735" y1="400" x2="<?php echo $htLegendX; ?>" y2="400" class="sld-dash"/>
                            <line x1="<?php echo $htLegendX; ?>" y1="1" x2="<?php echo $htLegendX; ?>" y2="849" class="sld-line"/>

                            <text x="28" y="118" class="sld-blue" font-size="14">1. EB LINE 33kV</text>
                            <text x="28" y="308" class="sld-blue" font-size="14">2. HT PANEL</text>
                            <text x="48" y="330" class="sld-blue" font-size="13">(33kV)</text>
                            <text x="28" y="438" class="sld-blue" font-size="14">3. LT PANEL</text>
                            <text x="48" y="460" class="sld-blue" font-size="13">(800V)</text>
                            <text x="28" y="555" class="sld-blue" font-size="14">4. MCCB TO</text>
                            <text x="38" y="575" class="sld-blue" font-size="14">INVERTERS</text>
                            <text x="28" y="650" class="sld-blue" font-size="14">5. INVERTERS</text>
                            <text x="38" y="670" class="sld-blue" font-size="13">(<?php echo $htInverterCount; ?> NOS)</text>

                            <text x="520" y="82" text-anchor="middle" class="sld-label">EB LINE INCOMING</text>
                            <text x="520" y="100" text-anchor="middle" class="sld-small">33kV, 50Hz</text>
                            <line x1="520" y1="110" x2="520" y2="330" class="sld-line"/>
                            <polygon points="520,132 514,112 526,112" fill="#111827"/>

                            <g transform="translate(405 142)">
                                <rect x="-8" y="-18" width="16" height="38" fill="#fff" stroke="#111827"/>
                                <polyline points="-3,-12 3,-12 -4,2 4,2 -2,16" class="sld-thin"/>
                                <text x="28" y="-8" class="sld-small">LA</text>
                                <text x="28" y="9" class="sld-small">33kV, 10kA</text>
                            </g>
                            <line x1="405" y1="184" x2="405" y2="220" class="sld-thin"/>
                            <circle cx="405" cy="198" r="10" fill="#fff" stroke="#111827"/>
                            <line x1="390" y1="198" x2="420" y2="198" class="sld-thin"/>
                            <text x="434" y="198" class="sld-small">PT</text>
                            <text x="434" y="215" class="sld-small">33kV / 110V</text>
                            <line x1="405" y1="220" x2="405" y2="238" class="sld-thin"/>
                            <line x1="396" y1="238" x2="414" y2="238" class="sld-thin"/>
                            <line x1="400" y1="244" x2="410" y2="244" class="sld-thin"/>

                            <circle cx="520" cy="192" r="11" fill="#fff" stroke="#111827"/>
                            <path d="M509 192 C500 180, 497 206, 509 192 M531 192 C540 180, 543 206, 531 192" class="sld-thin"/>
                            <text x="565" y="184" class="sld-small">CT</text>
                            <text x="565" y="202" class="sld-small">33kV / 1A</text>
                            <line x1="532" y1="202" x2="695" y2="202" class="sld-dash"/>
                            <rect x="695" y="174" width="54" height="54" fill="#fff" stroke="#111827" stroke-dasharray="5 4"/>
                            <circle cx="722" cy="214" r="8" fill="#fff" stroke="#111827"/>
                            <path d="M714 214 H730 M722 206 V222" class="sld-thin"/>
                            <path d="M722 182 L722 198 M716 190 L722 182 L728 190" class="sld-thin"/>
                            <text x="760" y="195" class="sld-small">MFM</text>
                            <text x="760" y="212" class="sld-small">33kV</text>
                            <text x="760" y="229" class="sld-small">(EB METER)</text>
                            <rect x="895" y="178" width="92" height="58" fill="#f8fafc" stroke="#e2e8f0"/>
                            <text id="sld_mfm_power" x="941" y="195" text-anchor="middle" class="sld-small" fill="#2563eb">-- kW</text>
                            <text id="sld_mfm_pf" x="941" y="214" text-anchor="middle" class="sld-small" fill="#7c3aed">PF --</text>
                            <text id="sld_mfm_energy" x="941" y="233" text-anchor="middle" class="sld-small" fill="#059669">-- kWh</text>

                            <rect id="sld_vcb_body" x="500" y="248" width="42" height="38" class="sld-status-live" stroke-width="1.5"/>
                            <circle cx="520" cy="257" r="3" fill="#111827"/>
                            <circle cx="520" cy="278" r="3" fill="#111827"/>
                            <line x1="514" y1="276" x2="526" y2="259" class="sld-line"/>
                            <text x="562" y="256" class="sld-small">VCB</text>
                            <text x="562" y="273" class="sld-small">33kV, 1250A</text>
                            <text x="562" y="290" class="sld-small">25kA</text>
                            <line x1="608" y1="250" x2="785" y2="250" class="sld-dash"/>
                            <rect x="800" y="258" width="100" height="54" fill="#fff" stroke="#111827" stroke-dasharray="5 4"/>
                            <text x="850" y="274" text-anchor="middle" class="sld-small">VCB STATUS</text>
                            <text id="sld_vcb_status_text" x="850" y="291" text-anchor="middle" class="sld-small">--</text>
                            <text x="850" y="307" text-anchor="middle" class="sld-small">TO SCADA</text>

                            <g transform="translate(520 330)">
                                <circle cx="0" cy="0" r="22" fill="#fff" stroke="#111827" stroke-width="1.5"/>
                                <circle cx="0" cy="48" r="22" fill="#fff" stroke="#111827" stroke-width="1.5"/>
                                <text x="0" y="5" text-anchor="middle" font-size="13">D</text>
                                <text x="0" y="53" text-anchor="middle" font-size="13">Y</text>
                            </g>
                            <text x="565" y="340" class="sld-small">POWER TRANSFORMER</text>
                            <text x="565" y="357" class="sld-small">33kV / 800V</text>
                            <text x="565" y="374" class="sld-small"><?php echo $htTransformerMva; ?>MVA, ONAN</text>
                            <text x="565" y="391" class="sld-small">Dyn11</text>
                            <line x1="540" y1="384" x2="560" y2="384" class="sld-thin"/>
                            <line x1="560" y1="384" x2="560" y2="392" class="sld-thin"/>
                            <line x1="552" y1="392" x2="568" y2="392" class="sld-thin"/>
                            <line x1="556" y1="398" x2="564" y2="398" class="sld-thin"/>

                            <line x1="520" y1="400" x2="520" y2="475" class="sld-line"/>
                            <rect id="sld_acb_body" x="500" y="424" width="42" height="48" class="sld-status-live" stroke-width="1.5"/>
                            <circle cx="520" cy="434" r="3" fill="#111827"/>
                            <circle cx="520" cy="462" r="3" fill="#111827"/>
                            <line x1="514" y1="460" x2="526" y2="437" class="sld-line"/>
                            <text x="562" y="432" class="sld-small">ACB</text>
                            <text x="562" y="449" class="sld-small">800V</text>
                            <text x="562" y="466" class="sld-small">4000A</text>
                            <text x="562" y="483" class="sld-small">50kA</text>
                            <line x1="630" y1="440" x2="795" y2="440" class="sld-dash"/>
                            <rect x="810" y="424" width="100" height="54" fill="#fff" stroke="#111827" stroke-dasharray="5 4"/>
                            <text x="860" y="440" text-anchor="middle" class="sld-small">ACB STATUS</text>
                            <text id="sld_acb_status_text" x="860" y="457" text-anchor="middle" class="sld-small">--</text>
                            <text x="860" y="473" text-anchor="middle" class="sld-small">TO SCADA</text>

                            <line x1="<?php echo $htBusStartX; ?>" y1="500" x2="<?php echo $htBusEndX; ?>" y2="500" class="sld-line"/>
                            <text x="<?php echo $htMainCenterX; ?>" y="492" text-anchor="middle" class="sld-small">800V, 3P, 3W, 50Hz</text>

                            <?php
                            foreach ($htInverterNumbers as $idx => $invNo):
                                $x = $htFirstInvX + ($idx * $htInvSpacing);
                                $label = str_pad((string)$invNo, 2, '0', STR_PAD_LEFT);
                            ?>
                            <g>
                                <line x1="<?php echo $x; ?>" y1="500" x2="<?php echo $x; ?>" y2="600" class="sld-line"/>
                                <polygon points="<?php echo $x; ?>,522 <?php echo $x - 5; ?>,500 <?php echo $x + 5; ?>,500" fill="#111827"/>
                                <text x="<?php echo $x + 11; ?>" y="526" class="sld-blue sld-small"><?php echo $invNo; ?></text>
                                <circle cx="<?php echo $x; ?>" cy="548" r="7" fill="#fff" stroke="#111827"/>
                                <circle cx="<?php echo $x; ?>" cy="570" r="3" fill="#fff" stroke="#111827"/>
                                <line x1="<?php echo $x - 7; ?>" y1="563" x2="<?php echo $x + 7; ?>" y2="535" class="sld-thin"/>
                                <text x="<?php echo $x + 13; ?>" y="548" class="sld-tiny">MCCB</text>
                                <text x="<?php echo $x + 13; ?>" y="562" class="sld-tiny">800V</text>
                                <text x="<?php echo $x + 13; ?>" y="576" class="sld-tiny">630A</text>
                                <text x="<?php echo $x + 13; ?>" y="590" class="sld-tiny">36kA</text>
                                <rect id="sld_inv_body_<?php echo $invNo; ?>" x="<?php echo $x - 22; ?>" y="610" width="48" height="46" fill="#fff" stroke="#111827"/>
                                <line x1="<?php echo $x - 21; ?>" y1="655" x2="<?php echo $x + 25; ?>" y2="611" class="sld-thin"/>
                                <text x="<?php echo $x - 5; ?>" y="629" font-size="15">~</text>
                                <text x="<?php echo $x + 12; ?>" y="646" font-size="15">=</text>
                                <text x="<?php echo $x + 2; ?>" y="671" text-anchor="middle" class="sld-blue sld-small">INV-<?php echo $label; ?></text>
                                <rect x="<?php echo $x - 38; ?>" y="682" width="76" height="48" rx="2" fill="#f8fafc" stroke="#e2e8f0"/>
                                <text id="sld_inv_power_<?php echo $invNo; ?>" x="<?php echo $x; ?>" y="699" text-anchor="middle" class="sld-tiny" fill="#2563eb">-- kW</text>
                                <text id="sld_inv_gen_<?php echo $invNo; ?>" x="<?php echo $x; ?>" y="716" text-anchor="middle" class="sld-tiny" fill="#7c3aed">-- kWh</text>
                                <rect x="<?php echo $x - 28; ?>" y="750" width="56" height="38" fill="#fff" stroke="#111827" stroke-dasharray="4 3"/>
                                <text x="<?php echo $x + 2; ?>" y="765" text-anchor="middle" class="sld-small">PV</text>
                                <text id="sld_inv_strings_<?php echo $invNo; ?>" x="<?php echo $x + 2; ?>" y="781" text-anchor="middle" class="sld-tiny">-- STR</text>
                            </g>
                            <?php endforeach; ?>

                            <rect x="<?php echo $htMainCenterX - 260; ?>" y="820" width="520" height="42" fill="#fff" stroke="#001f8f"/>
                            <text x="<?php echo $htMainCenterX; ?>" y="837" text-anchor="middle" class="sld-blue" font-size="14"><?php echo $htInverterCount; ?> Nos. STRING INVERTERS (800V AC OUTPUT)</text>
                            <text x="<?php echo $htMainCenterX; ?>" y="855" text-anchor="middle" class="sld-blue" font-size="14">TOTAL PLANT CAPACITY : <?php echo $htCapacityLabel; ?> MWp (DC) / APPROX <?php echo $htCapacityLabel; ?> MW (AC)</text>

                            <g transform="translate(<?php echo $htLegendX; ?> 0)">
                                <rect x="0" y="0" width="304" height="450" fill="#fff" stroke="#111827"/>
                                <text x="152" y="24" text-anchor="middle" class="sld-blue" font-size="14">LEGEND</text>
                                <?php
                                $legend = [
                                    ['LA', 'LIGHTNING ARRESTER'],
                                    ['PT', 'POTENTIAL TRANSFORMER'],
                                    ['CT', 'CURRENT TRANSFORMER'],
                                    ['MFM', 'MULTI FUNCTION METER'],
                                    ['VCB', 'VACUUM CIRCUIT BREAKER'],
                                    ['ACB', 'AIR CIRCUIT BREAKER'],
                                    ['ES', 'EARTH SWITCH'],
                                    ['MCCB', 'MOULDED CASE CIRCUIT BREAKER'],
                                    ['INV', 'STRING INVERTER'],
                                    ['PV', 'PV STRINGS / MODULES'],
                                ];
                                foreach ($legend as $idx => $row):
                                    $y = 38 + ($idx * 39);
                                ?>
                                <line x1="0" y1="<?php echo $y; ?>" x2="304" y2="<?php echo $y; ?>" class="sld-thin"/>
                                <text x="18" y="<?php echo $y + 24; ?>" class="sld-small"><?php echo $row[0]; ?></text>
                                <rect x="64" y="<?php echo $y + 8; ?>" width="24" height="22" fill="#fff" stroke="#111827" <?php echo in_array($row[0], ['MFM','PV']) ? 'stroke-dasharray="3 3"' : ''; ?>/>
                                <text x="107" y="<?php echo $y + 24; ?>" class="sld-small"><?php echo $row[1]; ?></text>
                                <?php endforeach; ?>

                                <rect x="0" y="450" width="304" height="190" fill="#fff" stroke="#111827"/>
                                <text x="152" y="476" text-anchor="middle" class="sld-blue" font-size="14">PLANT DETAILS</text>
                                <text x="12" y="504" class="sld-small">PLANT CAPACITY</text><text x="122" y="504" class="sld-small">: <?php echo $htCapacityLabel; ?> MWp (DC) / <?php echo $htCapacityLabel; ?> MW (AC)</text>
                                <text x="12" y="528" class="sld-small">GRID CONNECTION</text><text x="122" y="528" class="sld-small">: 33kV, 50Hz</text>
                                <text x="12" y="552" class="sld-small">INVERTER TYPE</text><text x="122" y="552" class="sld-small">: STRING INVERTER</text>
                                <text x="12" y="576" class="sld-small">NO. OF INVERTERS</text><text x="122" y="576" class="sld-small">: <?php echo $htInverterCount; ?> NOS</text>
                                <text x="12" y="600" class="sld-small">TRANSFORMER</text><text x="122" y="600" class="sld-small">: 33kV / 800V, <?php echo $htTransformerMva; ?>MVA</text>
                                <text x="12" y="624" class="sld-small">LT SYSTEM</text><text x="122" y="624" class="sld-small">: 800V AC, 3P, 3W</text>

                                <rect x="0" y="640" width="304" height="259" fill="#fff" stroke="#111827"/>
                                <text x="152" y="664" text-anchor="middle" font-size="14" font-weight="700">TITLE</text>
                                <text x="152" y="691" text-anchor="middle" font-size="14" font-weight="700"><?php echo $htCapacityLabel; ?>MW SOLAR POWER PLANT</text>
                                <text x="152" y="710" text-anchor="middle" font-size="13" font-weight="700">SINGLE LINE DIAGRAM (SLD)</text>
                                <text x="12" y="742" class="sld-small">DRAWN BY</text><text x="92" y="742" class="sld-small">: NUCLEI TECH</text>
                                <text x="12" y="762" class="sld-small">DRAWING NO</text><text x="92" y="762" class="sld-small">: NUC/SLD/<?php echo $htCapacityLabel; ?>MW/01</text>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const currentPlant = '<?php echo addslashes($currentPlant); ?>';
        const plantConfig = <?php echo getPlantPublicConfigJson(); ?>;
        const cfg = plantConfig[currentPlant] || { name: 'Vijayanth Solar', capacity: '5.0', location: 'Tamil Nadu' };
        document.getElementById('pageTitle').textContent = cfg.name + ' - SLD';

        fetch('sidebar.html', { cache: 'no-store' }).then(r => r.text()).then(html => {
            document.getElementById('sidebar-container').innerHTML = html;
            document.getElementById('sidebar-container').querySelectorAll('script').forEach(s => {
                const ns = document.createElement('script');
                ns.textContent = s.textContent;
                s.replaceWith(ns);
            });
            const overlay = document.getElementById('overlay');
            const sidebar = document.getElementById('sidebar');
            document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar?.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); });
            overlay?.addEventListener('click', () => { sidebar?.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
        });

        setInterval(() => {
            document.getElementById('clockDisplay').innerText = new Date().toLocaleTimeString('en-IN', { hour12: false });
        }, 1000);

        const wsUnitId = "<?php echo getPlantWsUnitId($currentPlant); ?>";
        const wsUrl = "wss://vinobasolar.scadahub.in:5001";
        const sldInverterCount = <?php echo $htInverterCount; ?>;
        const sldInverterNumbers = <?php echo json_encode($htInverterNumbers); ?>;

        function setText(id, value) {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        }

        function setHtml(id, value) {
            const el = document.getElementById(id);
            if (el) el.innerHTML = value;
        }

        function readNumber(value) {
            if (value === null || value === undefined || value === '') return null;
            const n = parseFloat(value);
            return Number.isFinite(n) ? n : null;
        }

        function firstNumber(values, keys) {
            for (const key of keys) {
                if (!values || values[key] === undefined) continue;
                const n = readNumber(values[key]);
                if (n !== null) return n;
            }
            return null;
        }

        const htState = {
            vcb: {},
            inverters: {}
        };

        function normalizePowerKw(value) {
            if (value === null || value === undefined) return null;
            return Math.abs(value) > 10000 ? value / 1000 : value;
        }

        function getHtAggregate() {
            const rows = Object.values(htState.inverters);
            const active = rows.filter(row => row && row.lastSeen);
            const totalPower = rows.reduce((sum, row) => sum + (row.power || 0), 0);
            const totalDcPower = rows.reduce((sum, row) => sum + (row.dcPower || row.power || 0), 0);
            const totalToday = rows.reduce((sum, row) => sum + (row.dailyGen || 0), 0);
            const pfRows = active.filter(row => (row.pf || 0) > 0);
            const pf = pfRows.length ? pfRows.reduce((sum, row) => sum + row.pf, 0) / pfRows.length : null;
            const avg = (field) => {
                const vals = active.map(row => row[field]).filter(v => v !== null && v !== undefined && v > 0 && v < 5000);
                return vals.length ? vals.reduce((sum, v) => sum + v, 0) / vals.length : null;
            };
            const sum = (field) => {
                const vals = active.map(row => row[field]).filter(v => v !== null && v !== undefined && v > 0 && v < 5000);
                return vals.length ? vals.reduce((total, v) => total + v, 0) : null;
            };
            return {
                power: totalPower,
                dcPower: totalDcPower,
                today: totalToday,
                pf,
                v_ry: avg('v_ry'),
                v_yb: avg('v_yb'),
                v_br: avg('v_br'),
                i_r: sum('i_r'),
                i_y: sum('i_y'),
                i_b: sum('i_b'),
                hasInverters: active.length > 0
            };
        }

        function renderHtPanel() {
            const agg = getHtAggregate();
            const vcb = htState.vcb || {};
            const powerKw = vcb.power !== null && vcb.power !== undefined ? vcb.power : (agg.hasInverters ? agg.power : null);
            const pvKw = agg.hasInverters ? agg.dcPower : null;
            const invKw = agg.hasInverters ? agg.power : null;
            const today = vcb.today !== null && vcb.today !== undefined && vcb.today > 0 ? vcb.today : (agg.hasInverters ? agg.today : null);
            const pf = vcb.pf !== null && vcb.pf !== undefined && vcb.pf > 0 ? vcb.pf : agg.pf;

            setHtml('ht_active_power', powerKw !== null ? powerKw.toFixed(1) + ' <span class="text-sm font-bold text-blue-600">kW</span>' : '-- <span class="text-sm font-bold text-blue-600">kW</span>');
            setText('ht_pf', pf ? pf.toFixed(3) : '--');
            setHtml('ht_today_gen', today !== null && today > 0 ? today.toFixed(1) + ' <span class="text-sm font-bold text-purple-600">kWh</span>' : '-- <span class="text-sm font-bold text-purple-600">kWh</span>');
            setText('sld_mfm_power', powerKw !== null ? powerKw.toFixed(1) + ' kW' : '-- kW');
            setText('sld_mfm_pf', pf ? 'PF ' + pf.toFixed(3) : 'PF --');
            setText('sld_mfm_energy', today !== null && today > 0 ? today.toFixed(1) + ' kWh' : '-- kWh');
            setHtml('sld_pv', (pvKw !== null ? pvKw.toFixed(2) : '0.00') + ' <span class="text-lg text-slate-500">kW</span>');
            setHtml('sld_inv', (invKw !== null ? invKw.toFixed(2) : '0.00') + ' <span class="text-lg text-slate-500">kW</span>');
            setHtml('sld_grid', (powerKw !== null ? powerKw.toFixed(2) : '0.00') + ' <span class="text-lg text-slate-500">kW</span>');
            setText('sld_pv2', (pvKw !== null ? pvKw.toFixed(2) : '0.00') + ' kW');
            setText('sld_inv2', (invKw !== null ? invKw.toFixed(2) : '0.00') + ' kW');
            setText('sld_grid2', (powerKw !== null ? powerKw.toFixed(2) : '0.00') + ' kW');

            const voltageScale = 33000 / 800;
            const currentScale = 800 / 33000;
            const vRy = vcb.v_ry || agg.v_ry;
            const vYb = vcb.v_yb || agg.v_yb;
            const vBr = vcb.v_br || agg.v_br;
            const iR = vcb.i_r || agg.i_r;
            const iY = vcb.i_y || agg.i_y;
            const iB = vcb.i_b || agg.i_b;

            const formatKv = (v) => v ? (v * voltageScale / 1000).toFixed(2) : '--';
            setHtml('v_ry', formatKv(vRy) + ' <span class="text-sm font-bold text-slate-500">kV</span>');
            setHtml('v_yb', formatKv(vYb) + ' <span class="text-sm font-bold text-slate-500">kV</span>');
            setHtml('v_br', formatKv(vBr) + ' <span class="text-sm font-bold text-slate-500">kV</span>');
            setHtml('i_r', iR ? (iR * currentScale).toFixed(2) + ' <span class="text-xs text-slate-500">A</span>' : '-- <span class="text-xs text-slate-500">A</span>');
            setHtml('i_y', iY ? (iY * currentScale).toFixed(2) + ' <span class="text-xs text-slate-500">A</span>' : '-- <span class="text-xs text-slate-500">A</span>');
            setHtml('i_b', iB ? (iB * currentScale).toFixed(2) + ' <span class="text-xs text-slate-500">A</span>' : '-- <span class="text-xs text-slate-500">A</span>');

            const hasLive = powerKw !== null || agg.hasInverters;
            const isClosed = hasLive && (powerKw || 0) > 0.5;
            const statusEl = document.getElementById('ht_breaker_status');
            const vcbBody = document.getElementById('sld_vcb_body');
            const acbBody = document.getElementById('sld_acb_body');
            if (statusEl) {
                statusEl.textContent = hasLive ? (isClosed ? 'CLOSED' : 'OPEN') : 'NO DATA';
                statusEl.className = 'font-black text-3xl ' + (!hasLive ? 'text-slate-400' : (isClosed ? 'text-emerald-700' : 'text-red-700'));
            }
            if (vcbBody) vcbBody.setAttribute('class', isClosed ? 'sld-status-live' : 'sld-status-open');
            if (acbBody) acbBody.setAttribute('class', isClosed ? 'sld-status-live' : 'sld-status-open');
            setText('sld_vcb_status_text', hasLive ? (isClosed ? 'CLOSED' : 'OPEN') : '--');
            setText('sld_acb_status_text', hasLive ? (isClosed ? 'CLOSED' : 'OPEN') : '--');
        }

        function isInverterPayload(message) {
            const values = message.values || {};
            const keys = Object.keys(values);
            const taskStr = message.task ? message.task.toString().toLowerCase() : '';
            const deviceStr = message.device ? message.device.toString().toLowerCase() : '';
            return taskStr.includes('inverter') ||
                deviceStr.includes('inverter') ||
                keys.some(k => /total active power|daily power yields|string\d+\s*current/i.test(k));
        }
        
        function connectWS() {
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
                    
                    const taskStr = d.task ? d.task.toString().toLowerCase() : '';
                    const deviceStr = d.device ? d.device.toString().toLowerCase() : '';
                    
                    if (taskStr === 'vcb' || deviceStr.includes('vcb')) {
                        updateVCBTelemetry(d.values);
                    } else if (isInverterPayload(d)) {
                        updateInverterTelemetry(d);
                    }
                } catch(err) {}
            };
            ws.onclose = function() {
                document.getElementById('refreshPulse').className = 'w-2.5 h-2.5 bg-red-500 rounded-full';
                setTimeout(connectWS, 2000);
            };
        }

        function updateVCBTelemetry(values) {
            if (!values) return;

            const rawPower = firstNumber(values, ["3 Phase Active Power", "3 phase active power"]);
            const rawPf = firstNumber(values, ["Power factor", "QA Avg PF", "Q1 PF"]);
            const rawToday = firstNumber(values, ["vcb-today", "Today Energy", "Day Energy", "Active Total Export"]);
            htState.vcb = {
                power: normalizePowerKw(rawPower),
                pf: rawPf,
                today: rawToday,
                v_ry: firstNumber(values, ["V12 (RY)"]),
                v_yb: firstNumber(values, ["V23 (YB)"]),
                v_br: firstNumber(values, ["V31 (BR)"]),
                i_r: firstNumber(values, ["L1 (R)"]),
                i_y: firstNumber(values, ["L2 (Y)"]),
                i_b: firstNumber(values, ["L3 (B)"])
            };
            renderHtPanel();
        }

        function updateInverterTelemetry(message) {
            if (!message.values) return;
            const invNoMatch = (message.device || '').toString().match(/\d+/);
            if (!invNoMatch) return;
            const invNo = parseInt(invNoMatch[0], 10);
            if (invNo < 1) return;

            const values = message.values;
            let power = readNumber(values["Total active power"]) || 0;
            let dcPower = readNumber(values["Total DC power"]);
            // Vinoba publishes Total DC power in watts while AC power is in kW.
            // Normalize the DC value before aggregating it for the SLD display.
            if (dcPower !== null && Math.abs(dcPower) > 10000) dcPower /= 1000;
            if (dcPower === null) dcPower = power;
            let dailyGen = readNumber(values["Daily power yields"]) || 0;
            htState.inverters[invNo] = {
                power,
                dcPower,
                dailyGen,
                pf: readNumber(values["Power factor"]),
                v_ry: readNumber(values["RYvolatge"] ?? values["RY voltage"] ?? values["V12 (RY)"]),
                v_yb: readNumber(values["YB voltage"] ?? values["V23 (YB)"]),
                v_br: readNumber(values["BR voltage"] ?? values["V31 (BR)"]),
                i_r: readNumber(values["RY current"]),
                i_y: readNumber(values["YB current"]),
                i_b: readNumber(values["BR current"]),
                lastSeen: Date.now()
            };
            renderHtPanel();

            if (!sldInverterNumbers.includes(invNo)) return;

            let activeStrings = 0;
            let totalStrings = 0;
            Object.keys(values).forEach((key) => {
                if (!/^string\s*\d+\s*current$/i.test(key)) return;
                totalStrings++;
                if ((parseFloat(values[key]) || 0) > 0.5) activeStrings++;
            });

            const body = document.getElementById('sld_inv_body_' + invNo);
            if (body) {
                body.setAttribute('fill', power > 0.5 ? '#dcfce7' : '#fee2e2');
                body.setAttribute('stroke', power > 0.5 ? '#16a34a' : '#dc2626');
            }
            setText('sld_inv_power_' + invNo, power ? power.toFixed(1) + ' kW' : '0.0 kW');
            setText('sld_inv_gen_' + invNo, dailyGen ? dailyGen.toFixed(1) + ' kWh' : '0.0 kWh');
            setText('sld_inv_strings_' + invNo, totalStrings ? `${activeStrings}/${totalStrings} STR` : '-- STR');
        }

        function dbVcbValues(row) {
            return {
                "3 Phase Active Power": row.power_3phase_kw,
                "Power factor": row.pf_q1,
                "vcb-today": row.today_energy_kwh || row.active_export_kwh,
                "V12 (RY)": row.voltage_ry_v,
                "V23 (YB)": row.voltage_yb_v,
                "V31 (BR)": row.voltage_br_v,
                "L1 (R)": row.current_r_a,
                "L2 (Y)": row.current_y_a,
                "L3 (B)": row.current_b_a
            };
        }

        function dbInverterValues(row) {
            const values = {
                "Total active power": row.power_kw,
                "Daily power yields": row.daily_gen_kwh,
                "Power factor": row.power_factor,
                "RYvolatge": row.vac_ab,
                "YB voltage": row.vac_bc,
                "BR voltage": row.vac_ca,
                "RY current": row.current_a,
                "YB current": row.current_b,
                "BR current": row.current_c
            };
            (row.strings || []).forEach(s => {
                values[`String ${parseInt(s.string_n || 0, 10)} Current`] = s.current_a;
            });
            return values;
        }

        function loadLatestSnapshot() {
            window.LiveWsStore.fastSnapshot(currentPlant)
                .then(res => res.json())
                .then(res => {
                    if (res.status !== 'success' || !res.data) return;
                    if (res.data.vcb) updateVCBTelemetry(dbVcbValues(res.data.vcb));
                    (res.data.inverters || []).forEach(row => {
                        updateInverterTelemetry({
                            device: row.inverter_name,
                            values: dbInverterValues(row)
                        });
                    });
                    renderHtPanel();
                })
                .catch(() => {});
        }
        
        renderHtPanel();
        connectWS();
    </script>
</body>
</html>
