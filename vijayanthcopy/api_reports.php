<?php
require 'config.php';
session_start();
header('Content-Type: application/json');
header('Cache-Control: private, max-age=10, stale-while-revalidate=30');
date_default_timezone_set('Asia/Kolkata');

$type = ($_GET['type'] ?? 'daily') === 'monthly' ? 'monthly' : 'daily';
$date = trim((string)($_GET['date'] ?? date('Y-m-d')));
$plant = strtolower(trim((string)($_GET['plant'] ?? getDefaultPlantId())));

$reportUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
if (!$reportUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.', 'data' => []]);
    exit;
}
if (($reportUser['role'] ?? 'user') !== 'admin') {
    $plant = strtolower((string)($reportUser['plant_id'] ?? getDefaultPlantId()));
}

if (!isset($PLANTS[$plant])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid plant.', 'data' => []]);
    exit;
}
if (($type === 'daily' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) ||
    ($type === 'monthly' && !preg_match('/^\d{4}-\d{2}$/', $date))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid report date.', 'data' => []]);
    exit;
}

$conn = getPlantDbConn($plant);
if (!$conn) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Database connection failed.', 'data' => []]);
    exit;
}

$escPlant = $conn->real_escape_string($plant);
$escDate = $conn->real_escape_string($date);
$periodSql = $type === 'daily'
    ? "snapshot_at >= '{$escDate} 00:00:00' AND snapshot_at <= '{$escDate} 23:59:59'"
    : "snapshot_at >= '{$escDate}-01 00:00:00' AND snapshot_at < DATE_ADD('{$escDate}-01 00:00:00', INTERVAL 1 MONTH)";
$bucketSql = $type === 'daily'
    ? "CONCAT(DATE_FORMAT(snapshot_at, '%H:'), IF(MINUTE(snapshot_at) < 30, '00', '30'))"
    : "DATE_FORMAT(snapshot_at, '%d-%m-%Y')";

$sql = "SELECT {$bucketSql} AS time_label, inverter_name,
               MAX(daily_gen_kwh) AS generation_kwh,
               MAX(power_kw) AS power_kw,
               MAX(ambient_temp) AS ambient_temp
        FROM inverter_data
        WHERE plant_id = '{$escPlant}' AND {$periodSql}
        GROUP BY time_label, inverter_name
        ORDER BY MIN(snapshot_at), inverter_name";
$result = @$conn->query($sql);
$byBucket = [];
$names = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($type === 'daily') {
            $hour = (int)substr($row['time_label'], 0, 2);
            $minute = (int)substr($row['time_label'], 3, 2);
            if ($hour < 6 || $hour > 19 || ($hour === 19 && $minute > 0)) continue;
        }
        $name = (string)$row['inverter_name'];
        $names[$name] = true;
        $byBucket[$row['time_label']][$name] = (float)$row['generation_kwh'];
    }
}
$conn->close();

$configuredCount = (int)($PLANTS[$plant]['inverter_count'] ?? 0);
for ($i = 1; $i <= $configuredCount; $i++) $names['INVERTER' . $i] = true;
$invNames = array_keys($names);
usort($invNames, function($a, $b) {
    preg_match('/\d+/', $a, $ma); preg_match('/\d+/', $b, $mb);
    return ((int)($ma[0] ?? 0)) <=> ((int)($mb[0] ?? 0));
});
$orderedBuckets = $byBucket;
if ($type === 'daily') {
    $orderedBuckets = [];
    for ($minutes = 6 * 60; $minutes <= 19 * 60; $minutes += 30) {
        $label = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        $orderedBuckets[$label] = $byBucket[$label] ?? [];
    }
}
$rows = [];
foreach ($orderedBuckets as $label => $values) {
    $row = ['time_label' => $label];
    $total = 0;
    foreach ($invNames as $i => $name) {
        $value = (float)($values[$name] ?? 0);
        $row['inv' . ($i + 1) . '_kwh'] = $value;
        $total += $value;
    }
    $row['inv_total_kwh'] = $total;
    $rows[] = $row;
}

echo json_encode([
    'success' => true,
    'meta' => ['type' => $type, 'date' => $date, 'plant' => $plant, 'inv_names' => $invNames, 'source' => 'database_cache'],
    'data' => $rows
], JSON_UNESCAPED_SLASHES);
?>
