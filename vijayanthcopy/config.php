<?php
$DB_HOST = getenv('VS_DB_HOST') !== false ? getenv('VS_DB_HOST') : "localhost";
$DB_USER = getenv('VS_DB_USER') !== false ? getenv('VS_DB_USER') : "root";
$DB_PASS = getenv('VS_DB_PASS') !== false ? getenv('VS_DB_PASS') : "Arun@811001";
$STORE_TOKEN = getenv('VS_STORE_TOKEN') !== false ? getenv('VS_STORE_TOKEN') : "";

// Keep production credentials outside Git. A local file may override any of
// the DB variables above (copy config.local.example.php to config.local.php).
$localConfig = __DIR__ . DIRECTORY_SEPARATOR . 'config.local.php';
if (is_file($localConfig)) require $localConfig;

$PLANTS = [
    'vijayanth' => [
        'name'     => 'Bojaraj Textiles Pvt Ltd',
        'db'       => 'vijayanth',
        'capacity' => '4.0',
        'location' => 'Tamil Nadu',
        'theme'    => 'violet',
        'ws_url'   => 'wss://vinobasolar.scadahub.in:5001',
        'ws_unit_id' => 'via-1mw',
        'inverter_count' => 14,
        'service_number' => '069-044-6600',
        'status'   => 'live',
    ],
    'krishna' => [
        'name'     => 'Krishna poultry farm',
        'db'       => 'vijayanth',
        'capacity' => '3.0',
        'location' => 'Tamil Nadu',
        'theme'    => 'emerald',
        'ws_url'   => 'wss://vinobasolar.scadahub.in:5001',
        'ws_unit_id' => 'via-3mw',
        'inverter_count' => 10,
        'service_number' => '069-044-6601',
        'status'   => 'live',
    ],
    'vijayanth_cosmic' => [
        'name'     => 'Vijayanth Cosmic Powers Pvt Ltd',
        'db'       => 'vijayanth',
        'capacity' => '7.0',
        'location' => 'Tamil Nadu',
        'theme'    => 'blue',
        'ws_url'   => 'wss://vinobasolar.scadahub.in:5001',
        'ws_unit_id' => 'via-7mw',
        'inverter_count' => 3,
        'service_number' => '',
        'status'   => 'live',
    ],
];

function getDefaultPlantId() {
    global $PLANTS;
    return array_key_first($PLANTS);
}

function getPlantConfig($plant_id) {
    global $PLANTS;
    return $PLANTS[$plant_id] ?? null;
}

function getPlantPublicConfigJson() {
    global $PLANTS;
    $public = [];
    foreach ($PLANTS as $pid => $pinfo) {
        $public[$pid] = [
            'name' => $pinfo['name'],
            'capacity' => $pinfo['capacity'],
            'location' => $pinfo['location'],
            'ws_url' => $pinfo['ws_url'] ?? '',
            'ws_unit_id' => $pinfo['ws_unit_id'],
            'inverter_count' => $pinfo['inverter_count'] ?? 0,
            'service_number' => $pinfo['service_number'] ?? '',
        ];
    }
    return json_encode($public, JSON_UNESCAPED_SLASHES);
}

function getPlantDbConn($plant_id) {
    global $DB_HOST, $DB_USER, $DB_PASS, $PLANTS;
    if (!isset($PLANTS[$plant_id])) return null;
    $db = $PLANTS[$plant_id]['db'];
    try {
        $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $db);
        if ($conn->connect_error) return null;
        return $conn;
    } catch (Exception $e) {
        return null;
    }
}

function getPlantWsUrl($plant_id) {
    global $PLANTS;
    return $PLANTS[$plant_id]['ws_url'] ?? '';
}

function getPlantWsUnitId($plant_id) {
    global $PLANTS;
    return $PLANTS[$plant_id]['ws_unit_id'] ?? $plant_id;
}

function getPlantCachePath($plant_id) {
    $safePlantId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$plant_id);
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "plant_{$safePlantId}_cache.json";
}

function readPlantSnapshotCache($plant_id, $maxAgeSeconds = 300) {
    $path = getPlantCachePath($plant_id);
    if (!is_file($path) || (time() - (int)@filemtime($path)) > $maxAgeSeconds) return null;
    $decoded = json_decode((string)@file_get_contents($path), true);
    return is_array($decoded) && isset($decoded['data']) ? $decoded : null;
}

function updatePlantSnapshotCache($plant_id, $type, $device_name, array $payload, $snapshot_at) {
    $path = getPlantCachePath($plant_id);
    $lockPath = $path . '.lock';
    $lock = @fopen($lockPath, 'c');
    if (!$lock || !@flock($lock, LOCK_EX)) {
        if ($lock) @fclose($lock);
        return false;
    }
    $cache = readPlantSnapshotCache($plant_id, 86400) ?: ['status' => 'success', 'data' => ['vcb' => null, 'inverters' => [], 'transformers' => []]];
    $row = ['type' => $type, 'device_name' => $device_name, 'snapshot_at' => $snapshot_at, 'payload' => $payload];
    if ($type === 'vcb') {
        $cache['data']['vcb'] = $row;
    } elseif ($type === 'inverter') {
        $cache['data']['inverters'][$device_name] = $row;
    } elseif ($type === 'transformer') {
        $cache['data']['transformers'][$device_name] = $row;
    }
    @file_put_contents($path, json_encode($cache));
    @flock($lock, LOCK_UN);
    @fclose($lock);
    return true;
}

function findUserByTokenAcrossPlants($token, &$plantId = null) {
    global $PLANTS;
    foreach ($PLANTS as $pid => $pinfo) {
        $conn = getPlantDbConn($pid);
        if (!$conn) continue;
        $esc = $conn->real_escape_string($token);
        $res = $conn->query("SELECT * FROM users WHERE auth_token='$esc' LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $plantId = $pid;
            return ['user' => $res->fetch_assoc(), 'conn' => $conn];
        }
        $conn->close();
    }
    return null;
}
?>