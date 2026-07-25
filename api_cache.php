<?php

declare(strict_types=1);
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$plantId = (string)($_GET['plant_id'] ?? $KANDAN_CONFIG['plant_id']);
if ($plantId !== $KANDAN_CONFIG['plant_id']) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Unknown plant_id']);
    exit;
}

if ($method === 'GET') {
    $snapshot = kandan_cache_read();
    $db = kandan_db();
    if ($db) {
        try {
            $stmt = $db->prepare('SELECT inverter_name, snapshot_at, power_kw, dc_power_kw, daily_gen_kwh, total_gen_kwh, internal_temp_c, fault_code, strings_json, history_json FROM inverter_latest WHERE plant_id = :plant_id ORDER BY inverter_name');
            $stmt->execute([':plant_id' => $plantId]);
            $rows = $stmt->fetchAll();
            if ($rows) {
                $snapshot['inverters'] = array_map(static function(array $row): array {
                    $strings = json_decode((string)($row['strings_json'] ?? '[]'), true);
                    $history = json_decode((string)($row['history_json'] ?? '[]'), true);
                    return [
                        'name' => $row['inverter_name'], 'snapshotAt' => $row['snapshot_at'],
                        'power' => (float)$row['power_kw'], 'dcPower' => (float)$row['dc_power_kw'],
                        'daily' => (float)$row['daily_gen_kwh'], 'total' => (float)$row['total_gen_kwh'],
                        'temp' => (float)$row['internal_temp_c'], 'fault' => (string)$row['fault_code'],
                        'strings' => is_array($strings) ? $strings : [], 'history' => is_array($history) ? $history : [],
                    ];
                }, $rows);
                $snapshot['source'] = 'database';
            }
        } catch (Throwable $error) { error_log('[Kandan cache GET] ' . $error->getMessage()); }
    }
    echo json_encode($snapshot, JSON_UNESCAPED_SLASHES); exit;
}

if ($method !== 'POST') {
    http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit;
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload) || !is_array($payload['inverters'] ?? null) || (($payload['plant_id'] ?? $plantId) !== $plantId)) {
    http_response_code(400); echo json_encode(['status'=>'error','message'=>'Invalid Kandan snapshot']); exit;
}

$snapshot = ['status'=>'success','plant_id'=>$plantId,'unit_id'=>$KANDAN_CONFIG['ws_unit_id'],'updated_at'=>date(DATE_ATOM),'inverters'=>array_values($payload['inverters'])];
$cacheSaved = kandan_cache_write($snapshot); $dbSaved = false; $db = kandan_db();
if ($db) {
    try {
        $db->beginTransaction();
        $latest = $db->prepare('INSERT INTO inverter_latest (plant_id,inverter_name,snapshot_at,power_kw,dc_power_kw,daily_gen_kwh,total_gen_kwh,internal_temp_c,fault_code,strings_json,history_json) VALUES (:plant_id,:name,:snapshot_at,:power,:dc_power,:daily,:total,:temp,:fault,:strings,:history) ON DUPLICATE KEY UPDATE snapshot_at=VALUES(snapshot_at),power_kw=VALUES(power_kw),dc_power_kw=VALUES(dc_power_kw),daily_gen_kwh=VALUES(daily_gen_kwh),total_gen_kwh=VALUES(total_gen_kwh),internal_temp_c=VALUES(internal_temp_c),fault_code=VALUES(fault_code),strings_json=VALUES(strings_json),history_json=VALUES(history_json)');
        $historyStmt = $db->prepare('INSERT INTO inverter_history (plant_id,inverter_name,snapshot_at,power_kw,dc_power_kw,daily_gen_kwh,total_gen_kwh,internal_temp_c,fault_code,strings_json) VALUES (:plant_id,:name,:snapshot_at,:power,:dc_power,:daily,:total,:temp,:fault,:strings)');
        foreach ($snapshot['inverters'] as $inverter) {
            if (!is_array($inverter) || empty($inverter['name'])) continue;
            $base = [':plant_id'=>$plantId,':name'=>(string)$inverter['name'],':snapshot_at'=>date('Y-m-d H:i:s'),':power'=>(float)($inverter['power']??0),':dc_power'=>(float)($inverter['dcPower']??0),':daily'=>(float)($inverter['daily']??0),':total'=>(float)($inverter['total']??0),':temp'=>(float)($inverter['temp']??0),':fault'=>(string)($inverter['fault']??''),':strings'=>json_encode($inverter['strings']??[],JSON_UNESCAPED_SLASHES)];
            $latest->execute($base + [':history'=>json_encode($inverter['history']??[],JSON_UNESCAPED_SLASHES)]);
            $historyStmt->execute($base);
        }
        $db->commit(); $dbSaved = true;
    } catch (Throwable $error) { if ($db->inTransaction()) $db->rollBack(); error_log('[Kandan cache POST] '.$error->getMessage()); }
}

echo json_encode(['status'=>'success','plant_id'=>$plantId,'cache_saved'=>$cacheSaved,'database_saved'=>$dbSaved,'updated_at'=>$snapshot['updated_at']]);
