<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $snapshot = kandan_cache_read();
    $db = kandan_db();

    if ($db) {
        try {
            $stmt = $db->query(
                'SELECT inverter_name, snapshot_at, power_kw, dc_power_kw, daily_gen_kwh, total_gen_kwh, internal_temp_c, fault_code, strings_json
                 FROM inverter_latest
                 ORDER BY CAST(REGEXP_REPLACE(inverter_name, "[^0-9]", "") AS UNSIGNED), inverter_name'
            );
            $rows = $stmt->fetchAll();
            if ($rows) {
                $snapshot['inverters'] = array_map(static function (array $row): array {
                    $strings = json_decode((string)($row['strings_json'] ?? '[]'), true);
                    return [
                        'name' => $row['inverter_name'],
                        'snapshotAt' => $row['snapshot_at'],
                        'power' => (float)$row['power_kw'],
                        'dcPower' => (float)$row['dc_power_kw'],
                        'daily' => (float)$row['daily_gen_kwh'],
                        'total' => (float)$row['total_gen_kwh'],
                        'temp' => (float)$row['internal_temp_c'],
                        'fault' => (string)$row['fault_code'],
                        'strings' => is_array($strings) ? $strings : [],
                    ];
                }, $rows);
                $snapshot['source'] = 'database';
            }
        } catch (Throwable $error) {
            error_log('[Kandan cache GET] ' . $error->getMessage());
        }
    }

    echo json_encode($snapshot, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload) || !is_array($payload['inverters'] ?? null)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid snapshot payload']);
    exit;
}

$snapshot = [
    'status' => 'success',
    'unit_id' => $KANDAN_CONFIG['ws_unit_id'],
    'updated_at' => date(DATE_ATOM),
    'inverters' => array_values($payload['inverters']),
];

$cacheSaved = kandan_cache_write($snapshot);
$dbSaved = false;
$db = kandan_db();

if ($db) {
    try {
        $db->beginTransaction();
        $stmt = $db->prepare(
            'INSERT INTO inverter_latest
                (inverter_name, snapshot_at, power_kw, dc_power_kw, daily_gen_kwh, total_gen_kwh, internal_temp_c, fault_code, strings_json)
             VALUES
                (:name, :snapshot_at, :power, :dc_power, :daily, :total, :temp, :fault, :strings)
             ON DUPLICATE KEY UPDATE
                snapshot_at = VALUES(snapshot_at),
                power_kw = VALUES(power_kw),
                dc_power_kw = VALUES(dc_power_kw),
                daily_gen_kwh = VALUES(daily_gen_kwh),
                total_gen_kwh = VALUES(total_gen_kwh),
                internal_temp_c = VALUES(internal_temp_c),
                fault_code = VALUES(fault_code),
                strings_json = VALUES(strings_json)'
        );

        $history = $db->prepare(
            'INSERT INTO inverter_history
                (inverter_name, snapshot_at, power_kw, dc_power_kw, daily_gen_kwh, total_gen_kwh, internal_temp_c, fault_code, strings_json)
             VALUES
                (:name, :snapshot_at, :power, :dc_power, :daily, :total, :temp, :fault, :strings)'
        );

        foreach ($snapshot['inverters'] as $inverter) {
            if (!is_array($inverter) || empty($inverter['name'])) {
                continue;
            }
            $params = [
                ':name' => (string)$inverter['name'],
                ':snapshot_at' => date('Y-m-d H:i:s'),
                ':power' => (float)($inverter['power'] ?? 0),
                ':dc_power' => (float)($inverter['dcPower'] ?? 0),
                ':daily' => (float)($inverter['daily'] ?? 0),
                ':total' => (float)($inverter['total'] ?? 0),
                ':temp' => (float)($inverter['temp'] ?? 0),
                ':fault' => (string)($inverter['fault'] ?? ''),
                ':strings' => json_encode($inverter['strings'] ?? [], JSON_UNESCAPED_SLASHES),
            ];
            $stmt->execute($params);
            $history->execute($params);
        }

        $db->commit();
        $dbSaved = true;
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[Kandan cache POST] ' . $error->getMessage());
    }
}

echo json_encode([
    'status' => 'success',
    'cache_saved' => $cacheSaved,
    'database_saved' => $dbSaved,
    'updated_at' => $snapshot['updated_at'],
]);
