<?php
// api_store.php - Stores real-time telemetry data posted by authenticated pages
// or by the background collector using a private ingestion token.
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header("Content-Type: application/json");
require 'config.php';
session_start();

$providedToken = (string)($_SERVER['HTTP_X_STORE_TOKEN'] ?? '');
$collectorAuthorized = $STORE_TOKEN !== '' && $providedToken !== '' && hash_equals($STORE_TOKEN, $providedToken);
$sessionUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
if (!$collectorAuthorized && !$sessionUser) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Storage authentication required"]);
    exit;
}

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if (!$input) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit;
}

$plant_id = isset($input['plant_id']) ? strtolower(trim($input['plant_id'])) : '';
if ($plant_id === 'bojaraj') {
    $plant_id = 'vijayanth';
}

if (!$collectorAuthorized && ($sessionUser['role'] ?? '') !== 'admin' &&
    !empty($sessionUser['plant_id']) && $sessionUser['plant_id'] !== $plant_id) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Plant access denied"]);
    exit;
}

$type = isset($input['type']) ? trim($input['type']) : '';
$device_name = isset($input['device_name']) ? trim($input['device_name']) : '';
$payload = isset($input['payload']) ? $input['payload'] : null;

if (!$plant_id || !$type || !$payload) {
    echo json_encode(["status" => "error", "message" => "Missing plant_id, type, or payload"]);
    exit;
}

// Get DB connection
$conn = getPlantDbConn($plant_id);
if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Cannot connect to database for plant: $plant_id"]);
    exit;
}

// Parse snapshot time
$snapshot_at = date('Y-m-d H:i:s');
if (!empty($input['source_time'])) {
    try {
        $dt = new DateTime($input['source_time']);
        $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $snapshot_at = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        // Fallback to current time
    }
}

// Update telemetry_latest cache table. Aggregated report points belong in the
// historical inverter table and must not replace a full live snapshot.
$payload_json = json_encode($payload);
if ($type !== 'inverter_report') {
    $stmtLatest = $conn->prepare("INSERT INTO telemetry_latest (plant_id, type, device_name, snapshot_at, payload_json)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE snapshot_at = VALUES(snapshot_at), payload_json = VALUES(payload_json)");
    if ($stmtLatest) {
        $stmtLatest->bind_param("sssss", $plant_id, $type, $device_name, $snapshot_at, $payload_json);
        $stmtLatest->execute();
        $stmtLatest->close();
    }
}

// Keep an atomic file snapshot updated by the continuous collector. Pages can
// serve this immediately without opening a database connection.
if (in_array($type, ['inverter', 'vcb', 'transformer'], true)) {
    updatePlantSnapshotCache($plant_id, $type, $device_name, $payload, $snapshot_at);
}

$success = false;
$errorMsg = '';

if ($type === 'inverter_report') {
    $daily_gen_kwh = (float)($payload['dailyGen'] ?? $payload['daily_gen_kwh'] ?? 0);
    $stmt = $conn->prepare("INSERT INTO inverter_data (plant_id, inverter_name, snapshot_at, daily_gen_kwh)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE daily_gen_kwh = VALUES(daily_gen_kwh)");
    if ($stmt) {
        $stmt->bind_param("sssd", $plant_id, $device_name, $snapshot_at, $daily_gen_kwh);
        $success = $stmt->execute();
        if (!$success) $errorMsg = $stmt->error;
        $stmt->close();
    } else {
        $errorMsg = $conn->error;
    }
}
elseif ($type === 'inverter') {
    $power_kw = (float)($payload['power'] ?? 0);
    $reactive_kvar = (float)($payload['reactive'] ?? 0);
    $power_factor = (float)($payload['pf'] ?? 0);
    $vac_ab = (float)($payload['vac_ab'] ?? 0);
    $vac_bc = (float)($payload['vac_bc'] ?? 0);
    $vac_ca = (float)($payload['vac_ca'] ?? 0);
    $frequency_hz = (float)($payload['freq'] ?? 0);
    $current_a = (float)($payload['i_a'] ?? 0);
    $current_b = (float)($payload['i_b'] ?? 0);
    $current_c = (float)($payload['i_c'] ?? 0);
    $efficiency = (float)($payload['eff'] ?? 0);
    $ambient_temp = (float)($payload['amb'] ?? 0);
    $daily_gen_kwh = (float)($payload['dailyGen'] ?? 0);
    $total_gen_kwh = (float)($payload['totalGen'] ?? 0);
    $daily_co2_kg = (float)($payload['dailyCO2'] ?? 0);
    $total_co2_kg = (float)($payload['totalCO2'] ?? 0);
    $daily_hours = (float)($payload['dailyHrs'] ?? 0);
    $total_hours = (float)($payload['totalHrs'] ?? 0);
    $active_strings = (int)($payload['activeStr'] ?? 0);
    $total_strings = (int)($payload['totalStr'] ?? 0);
    $has_alarm = !empty($payload['hasAlarm']) ? 1 : 0;
    $has_fault = !empty($payload['hasFault']) ? 1 : 0;
    $fault_code = (string)($payload['faultCode'] ?? '');
    $work_state = (string)($payload['workState'] ?? '');
    $status_text = (string)($payload['statusText'] ?? '');

    $stmt = $conn->prepare("INSERT INTO inverter_data (
        plant_id, inverter_name, snapshot_at, power_kw, reactive_kvar, power_factor,
        vac_ab, vac_bc, vac_ca, frequency_hz, current_a, current_b, current_c,
        efficiency, ambient_temp, daily_gen_kwh, total_gen_kwh, daily_co2_kg, total_co2_kg,
        daily_hours, total_hours, active_strings, total_strings, has_alarm, has_fault,
        fault_code, work_state, status_text
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        power_kw = VALUES(power_kw), reactive_kvar = VALUES(reactive_kvar), power_factor = VALUES(power_factor),
        vac_ab = VALUES(vac_ab), vac_bc = VALUES(vac_bc), vac_ca = VALUES(vac_ca), frequency_hz = VALUES(frequency_hz),
        current_a = VALUES(current_a), current_b = VALUES(current_b), current_c = VALUES(current_c),
        efficiency = VALUES(efficiency), ambient_temp = VALUES(ambient_temp), daily_gen_kwh = VALUES(daily_gen_kwh),
        total_gen_kwh = VALUES(total_gen_kwh), daily_co2_kg = VALUES(daily_co2_kg), total_co2_kg = VALUES(total_co2_kg),
        daily_hours = VALUES(daily_hours), total_hours = VALUES(total_hours), active_strings = VALUES(active_strings),
        total_strings = VALUES(total_strings), has_alarm = VALUES(has_alarm), has_fault = VALUES(has_fault),
        fault_code = VALUES(fault_code), work_state = VALUES(work_state), status_text = VALUES(status_text)");

    if ($stmt) {
        $stmt->bind_param("sssddddddddddddddddddiiiisss",
            $plant_id, $device_name, $snapshot_at, $power_kw, $reactive_kvar, $power_factor,
            $vac_ab, $vac_bc, $vac_ca, $frequency_hz, $current_a, $current_b, $current_c,
            $efficiency, $ambient_temp, $daily_gen_kwh, $total_gen_kwh, $daily_co2_kg, $total_co2_kg,
            $daily_hours, $total_hours, $active_strings, $total_strings, $has_alarm, $has_fault,
            $fault_code, $work_state, $status_text
        );
        $success = $stmt->execute();
        if (!$success) $errorMsg = $stmt->error;
        $stmt->close();
    } else {
        $errorMsg = $conn->error;
    }

    // Insert inverter strings
    if ($success && isset($payload['strings']) && is_array($payload['strings'])) {
        foreach ($payload['strings'] as $s) {
            $string_n = (int)($s['n'] ?? 0);
            $curr_a = (float)($s['curr'] ?? 0);
            $volt_v = (float)($s['volt'] ?? 0);
            $active = !empty($s['active']) ? 1 : 0;

            $stmtStr = $conn->prepare("INSERT INTO inverter_strings (plant_id, inverter_name, snapshot_at, string_n, current_a, voltage_v, active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE current_a = VALUES(current_a), voltage_v = VALUES(voltage_v), active = VALUES(active)");
            if ($stmtStr) {
                $stmtStr->bind_param("sssiddi", $plant_id, $device_name, $snapshot_at, $string_n, $curr_a, $volt_v, $active);
                $stmtStr->execute();
                $stmtStr->close();
            }
        }
    }
}
elseif ($type === 'vcb') {
    $power_3phase_kw = (float)($payload['power_3phase_kw'] ?? 0);
    $frequency_hz = (float)($payload['frequency_hz'] ?? 0);
    $voltage_r_v = (float)($payload['voltage_r_v'] ?? 0);
    $voltage_y_v = (float)($payload['voltage_y_v'] ?? 0);
    $voltage_b_v = (float)($payload['voltage_b_v'] ?? 0);
    $voltage_ry_v = (float)($payload['voltage_ry_v'] ?? 0);
    $voltage_yb_v = (float)($payload['voltage_yb_v'] ?? 0);
    $voltage_br_v = (float)($payload['voltage_br_v'] ?? 0);
    $current_r_a = (float)($payload['current_r_a'] ?? 0);
    $current_y_a = (float)($payload['current_y_a'] ?? 0);
    $current_b_a = (float)($payload['current_b_a'] ?? 0);
    $power_r_kw = (float)($payload['power_r_kw'] ?? 0);
    $power_y_kw = (float)($payload['power_y_kw'] ?? 0);
    $power_b_kw = (float)($payload['power_b_kw'] ?? 0);
    $pf_q1 = (float)($payload['pf_q1'] ?? 0);
    $pf_q2 = (float)($payload['pf_q2'] ?? 0);
    $pf_q3 = (float)($payload['pf_q3'] ?? 0);
    $vthd_r = (float)($payload['vthd_r'] ?? 0);
    $vthd_y = (float)($payload['vthd_y'] ?? 0);
    $vthd_b = (float)($payload['vthd_b'] ?? 0);
    $active_export_kwh = (float)($payload['active_export_kwh'] ?? 0);
    $active_import_kwh = (float)($payload['active_import_kwh'] ?? 0);
    $reactive_import_kvar = (float)($payload['reactive_import_kvar'] ?? 0);
    $reactive_export_kvar = (float)($payload['reactive_export_kvar'] ?? 0);
    $today_energy_kwh = (float)($payload['today_energy_kwh'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO vcb_data (
        plant_id, device_name, snapshot_at, power_3phase_kw, frequency_hz,
        voltage_r_v, voltage_y_v, voltage_b_v, voltage_ry_v, voltage_yb_v, voltage_br_v,
        current_r_a, current_y_a, current_b_a, power_r_kw, power_y_kw, power_b_kw,
        pf_q1, pf_q2, pf_q3, vthd_r, vthd_y, vthd_b, active_export_kwh, active_import_kwh,
        reactive_import_kvar, reactive_export_kvar, today_energy_kwh
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        power_3phase_kw = VALUES(power_3phase_kw), frequency_hz = VALUES(frequency_hz),
        voltage_r_v = VALUES(voltage_r_v), voltage_y_v = VALUES(voltage_y_v), voltage_b_v = VALUES(voltage_b_v),
        voltage_ry_v = VALUES(voltage_ry_v), voltage_yb_v = VALUES(voltage_yb_v), voltage_br_v = VALUES(voltage_br_v),
        current_r_a = VALUES(current_r_a), current_y_a = VALUES(current_y_a), current_b_a = VALUES(current_b_a),
        power_r_kw = VALUES(power_r_kw), power_y_kw = VALUES(power_y_kw), power_b_kw = VALUES(power_b_kw),
        pf_q1 = VALUES(pf_q1), pf_q2 = VALUES(pf_q2), pf_q3 = VALUES(pf_q3),
        vthd_r = VALUES(vthd_r), vthd_y = VALUES(vthd_y), vthd_b = VALUES(vthd_b),
        active_export_kwh = VALUES(active_export_kwh), active_import_kwh = VALUES(active_import_kwh),
        reactive_import_kvar = VALUES(reactive_import_kvar), reactive_export_kvar = VALUES(reactive_export_kvar),
        today_energy_kwh = VALUES(today_energy_kwh)");

    if ($stmt) {
        $stmt->bind_param("sssddddddddddddddddddddddddd",
            $plant_id, $device_name, $snapshot_at, $power_3phase_kw, $frequency_hz,
            $voltage_r_v, $voltage_y_v, $voltage_b_v, $voltage_ry_v, $voltage_yb_v, $voltage_br_v,
            $current_r_a, $current_y_a, $current_b_a, $power_r_kw, $power_y_kw, $power_b_kw,
            $pf_q1, $pf_q2, $pf_q3, $vthd_r, $vthd_y, $vthd_b, $active_export_kwh, $active_import_kwh,
            $reactive_import_kvar, $reactive_export_kvar, $today_energy_kwh
        );
        $success = $stmt->execute();
        if (!$success) $errorMsg = $stmt->error;
        $stmt->close();
    } else {
        $errorMsg = $conn->error;
    }
}
elseif ($type === 'transformer') {
    $oil_temp_c = (float)($payload['oil_temp_c'] ?? 0);
    $winding_temp_c = (float)($payload['winding_temp_c'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO transformer_data (plant_id, device_name, snapshot_at, oil_temp_c, winding_temp_c)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE oil_temp_c = VALUES(oil_temp_c), winding_temp_c = VALUES(winding_temp_c)");

    if ($stmt) {
        $stmt->bind_param("sssdd", $plant_id, $device_name, $snapshot_at, $oil_temp_c, $winding_temp_c);
        $success = $stmt->execute();
        if (!$success) $errorMsg = $stmt->error;
        $stmt->close();
    } else {
        $errorMsg = $conn->error;
    }
}
elseif ($type === 'raw') {
    $unit_id = isset($input['unit_id']) ? trim($input['unit_id']) : '';
    $task = isset($input['task']) ? trim($input['task']) : '';
    $source_time = isset($input['source_time']) ? trim($input['source_time']) : '';

    $stmt = $conn->prepare("INSERT INTO ws_raw_messages (plant_id, unit_id, task, device_name, source_time, snapshot_at, payload_json)
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param("sssssss", $plant_id, $unit_id, $task, $device_name, $source_time, $snapshot_at, $payload_json);
        $success = $stmt->execute();
        if (!$success) $errorMsg = $stmt->error;
        $stmt->close();
    } else {
        $errorMsg = $conn->error;
    }
}

$conn->close();

if ($success) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $errorMsg]);
}
?>
