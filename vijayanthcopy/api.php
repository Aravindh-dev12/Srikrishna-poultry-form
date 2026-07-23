<?php
// api.php - Multi-plant authentication API
session_start();
require 'config.php';
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$data = json_decode(file_get_contents('php://input'), true);

function getBearerToken() {
    $headers = getallheaders();
    $auth = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');
    if (preg_match('/Bearer\s+(\S+)/', $auth, $m)) return $m[1];
    return '';
}

function canonicalApiInverterName($name) {
    if (preg_match('/\d+/', (string)$name, $m)) {
        return 'INVERTER' . (int)$m[0];
    }
    return strtoupper(preg_replace('/\s+/', '', (string)($name ?: 'INVERTER')));
}

function latestInverterRowFromPayload($plant_id, $device_name, $snapshot_at, $payload, $includeStrings = false) {
    return [
        "plant_id" => $plant_id,
        "inverter_name" => canonicalApiInverterName($device_name),
        "snapshot_at" => $snapshot_at,
        "power_kw" => (string)(float)($payload['power'] ?? 0),
        "reactive_kvar" => (string)(float)($payload['reactive'] ?? 0),
        "power_factor" => (string)(float)($payload['pf'] ?? 0),
        "vac_ab" => (string)(float)($payload['vac_ab'] ?? 0),
        "vac_bc" => (string)(float)($payload['vac_bc'] ?? 0),
        "vac_ca" => (string)(float)($payload['vac_ca'] ?? 0),
        "frequency_hz" => (string)(float)($payload['freq'] ?? 0),
        "current_a" => (string)(float)($payload['i_a'] ?? 0),
        "current_b" => (string)(float)($payload['i_b'] ?? 0),
        "current_c" => (string)(float)($payload['i_c'] ?? 0),
        "efficiency" => (string)(float)($payload['eff'] ?? 0),
        "ambient_temp" => (string)(float)($payload['amb'] ?? 0),
        "daily_gen_kwh" => (string)(float)($payload['dailyGen'] ?? 0),
        "total_gen_kwh" => (string)(float)($payload['totalGen'] ?? 0),
        "daily_co2_kg" => (string)(float)($payload['dailyCO2'] ?? 0),
        "total_co2_kg" => (string)(float)($payload['totalCO2'] ?? 0),
        "daily_hours" => (string)(float)($payload['dailyHrs'] ?? 0),
        "total_hours" => (string)(float)($payload['totalHrs'] ?? 0),
        "active_strings" => (string)(int)($payload['activeStr'] ?? 0),
        "total_strings" => (string)(int)($payload['totalStr'] ?? 0),
        "has_alarm" => !empty($payload['hasAlarm']) ? "1" : "0",
        "has_fault" => !empty($payload['hasFault']) ? "1" : "0",
        "fault_code" => (string)($payload['faultCode'] ?? ''),
        "work_state" => (string)($payload['workState'] ?? ''),
        "status_text" => (string)($payload['statusText'] ?? ''),
        "strings" => $includeStrings && isset($payload['strings']) && is_array($payload['strings'])
            ? array_map(function($s) {
                return [
                    "string_n" => (string)(int)($s['n'] ?? 0),
                    "current_a" => (string)(float)($s['curr'] ?? 0),
                    "voltage_v" => (string)(float)($s['volt'] ?? 0),
                    "active" => !empty($s['active']) ? "1" : "0"
                ];
            }, $payload['strings'])
            : []
    ];
}

function latestVcbRowFromPayload($plant_id, $device_name, $snapshot_at, $payload) {
    return [
        "plant_id" => $plant_id,
        "device_name" => $device_name,
        "snapshot_at" => $snapshot_at,
        "power_3phase_kw" => (string)(float)($payload['power_3phase_kw'] ?? 0),
        "frequency_hz" => (string)(float)($payload['frequency_hz'] ?? 0),
        "voltage_r_v" => (string)(float)($payload['voltage_r_v'] ?? 0),
        "voltage_y_v" => (string)(float)($payload['voltage_y_v'] ?? 0),
        "voltage_b_v" => (string)(float)($payload['voltage_b_v'] ?? 0),
        "voltage_ry_v" => (string)(float)($payload['voltage_ry_v'] ?? 0),
        "voltage_yb_v" => (string)(float)($payload['voltage_yb_v'] ?? 0),
        "voltage_br_v" => (string)(float)($payload['voltage_br_v'] ?? 0),
        "current_r_a" => (string)(float)($payload['current_r_a'] ?? 0),
        "current_y_a" => (string)(float)($payload['current_y_a'] ?? 0),
        "current_b_a" => (string)(float)($payload['current_b_a'] ?? 0),
        "power_r_kw" => (string)(float)($payload['power_r_kw'] ?? 0),
        "power_y_kw" => (string)(float)($payload['power_y_kw'] ?? 0),
        "power_b_kw" => (string)(float)($payload['power_b_kw'] ?? 0),
        "pf_q1" => (string)(float)($payload['pf_q1'] ?? 0),
        "pf_q2" => (string)(float)($payload['pf_q2'] ?? 0),
        "pf_q3" => (string)(float)($payload['pf_q3'] ?? 0),
        "vthd_r" => (string)(float)($payload['vthd_r'] ?? 0),
        "vthd_y" => (string)(float)($payload['vthd_y'] ?? 0),
        "vthd_b" => (string)(float)($payload['vthd_b'] ?? 0),
        "active_export_kwh" => (string)(float)($payload['active_export_kwh'] ?? 0),
        "active_import_kwh" => (string)(float)($payload['active_import_kwh'] ?? 0),
        "reactive_import_kvar" => (string)(float)($payload['reactive_import_kvar'] ?? 0),
        "reactive_export_kvar" => (string)(float)($payload['reactive_export_kvar'] ?? 0),
        "today_energy_kwh" => (string)(float)($payload['today_energy_kwh'] ?? 0)
    ];
}

if ($action === 'login') {
    $email = isset($data['email']) ? trim($data['email']) : '';
    $pass = isset($data['password']) ? $data['password'] : '';
    
    // Map email to plant ID
    $emailToPlant = [
        'admin@vijayanth.com' => getDefaultPlantId(),
        'bojaraj@scada.com' => 'vijayanth',
        'krishna@scada.com' => 'krishna'
    ];
    
    $selectedPlant = isset($emailToPlant[$email]) ? $emailToPlant[$email] : '';

    $foundUser = null;
    $foundPlant = '';
    $foundConn = null;

    if ($selectedPlant && isset($PLANTS[$selectedPlant])) {
        $userConn = getPlantDbConn($selectedPlant);
        if ($userConn) {
            $esc = $userConn->real_escape_string($email);
            $res = $userConn->query("SELECT * FROM users WHERE email='$esc' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $foundUser = $res->fetch_assoc();
                $foundPlant = $selectedPlant;
                $foundConn = $userConn;
            } else {
                $userConn->close();
            }
        }
    }

    if (!$foundUser) {
        foreach ($PLANTS as $pid => $pinfo) {
            $userConn = getPlantDbConn($pid);
            if (!$userConn) continue;
            $esc = $userConn->real_escape_string($email);
            $res = $userConn->query("SELECT * FROM users WHERE email='$esc' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $foundUser = $res->fetch_assoc();
                $foundPlant = $foundUser['role'] === 'admin' ? getDefaultPlantId() : ($foundUser['plant_id'] ?: $pid);
                $foundConn = $userConn;
                break;
            }
            $userConn->close();
        }
    }

    if (!$foundUser) {
        echo json_encode(["status" => "error", "message" => "User not found."]);
        exit;
    }

    if (password_verify($pass, $foundUser['password'])) {
        $token = bin2hex(random_bytes(32));
        $uid = (int)$foundUser['id'];
        $foundConn->query("UPDATE users SET auth_token = '$token', plant_id = '$foundPlant' WHERE id = $uid");
        $foundConn->close();

        $foundUser['plant_id'] = $foundPlant;
        $_SESSION['user'] = $foundUser;
        $_SESSION['plant_id'] = $foundPlant;
        
        echo json_encode([
            "status" => "success",
            "token" => $token,
            "user" => [
                "email" => $foundUser['email'],
                "role" => $foundUser['role'],
                "plant_id" => $foundPlant
            ]
        ]);
    } else {
        $foundConn->close();
        echo json_encode(["status" => "error", "message" => "Invalid password."]);
    }
}
elseif ($action === 'get_user') {
    $token = getBearerToken();
    if (!$token) {
        echo json_encode(["status" => "error", "message" => "Unauthorized."]);
        exit;
    }
    $found = findUserByTokenAcrossPlants($token, $foundPlantId);
    if (!$found) {
        echo json_encode(["status" => "error", "message" => "Invalid session."]);
        exit;
    }
    $user = $found['user'];
    
    $_SESSION['user'] = $user;
    $_SESSION['plant_id'] = $foundPlantId;
    
    if (isset($found['conn'])) {
        $found['conn']->close();
    }
    
    echo json_encode([
        "status" => "success",
        "user" => [
            "email" => $user['email'],
            "role" => $user['role'],
            "plant_id" => $foundPlantId
        ]
    ]);
}
elseif ($action === 'add_user') {
    $token = getBearerToken();
    $found = $token ? findUserByTokenAcrossPlants($token, $foundPlantId) : null;
    if (!$found || $found['user']['role'] !== 'admin') {
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        exit;
    }

    $targetPlant = isset($data['plant_id']) && isset($PLANTS[$data['plant_id']]) ? $data['plant_id'] : $foundPlantId;
    $targetConn = getPlantDbConn($targetPlant);
    if (!$targetConn) {
        echo json_encode(["status" => "error", "message" => "Cannot connect to target plant database."]);
        exit;
    }

    $email = $targetConn->real_escape_string($data['email']);
    $pass = password_hash($data['password'], PASSWORD_DEFAULT);
    $role = isset($data['role']) ? $targetConn->real_escape_string($data['role']) : 'user';
    $plant_id = $targetConn->real_escape_string($targetPlant);

    $check = $targetConn->query("SELECT * FROM users WHERE email='$email'");
    if ($check && $check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already exists in this plant."]);
    } else {
        if ($targetConn->query("INSERT INTO users (email, password, role, plant_id) VALUES ('$email', '$pass', '$role', '$plant_id')")) {
            echo json_encode(["status" => "success", "message" => "User created successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error: " . $targetConn->error]);
        }
    }
    $targetConn->close();
}
elseif ($action === 'get_weekly_energy') {
    $plant_id = isset($data['plant_id']) ? $data['plant_id'] : (isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
    if (!$plant_id || !isset($PLANTS[$plant_id])) {
        echo json_encode(["status" => "error", "message" => "Invalid plant_id."]);
        exit;
    }
    $conn = getPlantDbConn($plant_id);
    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Database connection failed."]);
        exit;
    }
    $escPlant = $conn->real_escape_string($plant_id);
    $cap = (float)($PLANTS[$plant_id]['capacity'] ?? 1);
    // Expected daily energy: capacity(MW) * 1000 * 0.8 * 5 peak hours
    $expectedDaily = $cap * 1000 * 0.8 * 5;

    $sql = "SELECT DATE(snapshot_at) as day, MAX(today_energy_kwh) as actual
            FROM vcb_data
            WHERE plant_id = '$escPlant' AND snapshot_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(snapshot_at)
            ORDER BY day ASC";
    $res = $conn->query($sql);
    $dbData = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $dbData[$row['day']] = (float)$row['actual'];
        }
    }
    $conn->close();

    // Build last 7 days labels
    $result = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $result[] = [
            'date' => $d,
            'day' => date('D', strtotime($d)),
            'actual' => $dbData[$d] ?? 0,
            'expected' => $expectedDaily
        ];
    }
    echo json_encode(["status" => "success", "plant_id" => $plant_id, "data" => $result]);
    exit;
}
elseif ($action === 'get_overview_hourly') {
    $plant_id = isset($data['plant_id']) ? $data['plant_id'] : (isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
    if (!$plant_id || !isset($PLANTS[$plant_id])) {
        echo json_encode(["status" => "error", "message" => "Invalid plant_id."]);
        exit;
    }
    $conn = getPlantDbConn($plant_id);
    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Database connection failed."]);
        exit;
    }

    $escPlant = $conn->real_escape_string($plant_id);
    $date = isset($data['date']) ? $data['date'] : (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'));
    $escDate = $conn->real_escape_string($date);

    $powerByInvHour = [];
    $genByInvHour = [];
    $escStart = $conn->real_escape_string($date . ' 00:00:00');
    $escEnd = $conn->real_escape_string($date . ' 23:59:59');
    $sql = "SELECT i.inverter_name, i.snapshot_at, i.power_kw, i.daily_gen_kwh
            FROM inverter_data i
            INNER JOIN (
                SELECT inverter_name, HOUR(snapshot_at) AS hr, MAX(snapshot_at) AS snapshot_at
                FROM inverter_data
                WHERE plant_id = '$escPlant' AND snapshot_at BETWEEN '$escStart' AND '$escEnd'
                GROUP BY inverter_name, HOUR(snapshot_at)
            ) latest ON latest.inverter_name = i.inverter_name AND latest.snapshot_at = i.snapshot_at
            WHERE i.plant_id = '$escPlant'
            ORDER BY i.inverter_name ASC, i.snapshot_at ASC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $inv = canonicalApiInverterName($row['inverter_name']);
            $hour = (int)date('G', strtotime($row['snapshot_at']));
            if (!isset($powerByInvHour[$inv])) $powerByInvHour[$inv] = array_fill(0, 24, null);
            if (!isset($genByInvHour[$inv])) $genByInvHour[$inv] = array_fill(0, 24, null);
            $powerByInvHour[$inv][$hour] = (float)$row['power_kw'];
            $genByInvHour[$inv][$hour] = (float)$row['daily_gen_kwh'];
        }
    }

    $power = array_fill(0, 24, 0.0);
    foreach ($powerByInvHour as $series) {
        foreach ($series as $hour => $value) {
            if ($value !== null) $power[$hour] += $value;
        }
    }

    $generation = array_fill(0, 24, 0.0);
    foreach ($genByInvHour as $series) {
        $previous = 0.0;
        foreach ($series as $hour => $value) {
            if ($value === null) continue;
            $delta = max(0, $value - $previous);
            $generation[$hour] += $delta;
            $previous = $value;
        }
    }

    $conn->close();
    echo json_encode([
        "status" => "success",
        "plant_id" => $plant_id,
        "date" => $date,
        "data" => [
            "power" => array_map(fn($v) => round($v, 2), $power),
            "generation" => array_map(fn($v) => round($v, 2), $generation)
        ]
    ]);
    exit;
}
elseif ($action === 'get_latest_snapshot') {
    $plant_id = isset($data['plant_id']) ? $data['plant_id'] : (isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
    if (!$plant_id || !isset($PLANTS[$plant_id])) {
        echo json_encode(["status" => "error", "message" => "Invalid plant_id."]);
        exit;
    }

    $conn = getPlantDbConn($plant_id);
    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Database connection failed."]);
        exit;
    }

    $escPlant = $conn->real_escape_string($plant_id);
    $includeStrings = isset($_GET['include_strings']) && $_GET['include_strings'] === '1';
    $response = [
        "status" => "success",
        "plant_id" => $plant_id,
        "fresh_after" => date('Y-m-d H:i:s', time() - 300),
        "data" => [
            "vcb" => null,
            "inverters" => [],
            "transformers" => []
        ]
    ];
    $freshAfter = $conn->real_escape_string($response["fresh_after"]);

    $latestTable = @$conn->query("SHOW TABLES LIKE 'telemetry_latest'");
    if ($latestTable && $latestTable->num_rows > 0) {
        $latestSql = "SELECT type, device_name, snapshot_at, payload_json
            FROM telemetry_latest
            WHERE plant_id = '$escPlant' AND snapshot_at >= '$freshAfter'
            ORDER BY type ASC, device_name ASC";
        $latestRes = @$conn->query($latestSql);
        if ($latestRes) {
            while ($latest = $latestRes->fetch_assoc()) {
                $payload = json_decode($latest['payload_json'], true);
                if (!is_array($payload)) continue;
                if ($latest['type'] === 'inverter') {
                    $response["data"]["inverters"][] = latestInverterRowFromPayload($plant_id, $latest['device_name'], $latest['snapshot_at'], $payload, $includeStrings);
                } elseif ($latest['type'] === 'vcb') {
                    $response["data"]["vcb"] = latestVcbRowFromPayload($plant_id, $latest['device_name'], $latest['snapshot_at'], $payload);
                } elseif ($latest['type'] === 'transformer') {
                    $response["data"]["transformers"][] = [
                        "plant_id" => $plant_id,
                        "device_name" => $latest['device_name'],
                        "snapshot_at" => $latest['snapshot_at'],
                        "oil_temp_c" => (string)(float)($payload['oil_temp_c'] ?? 0),
                        "winding_temp_c" => (string)(float)($payload['winding_temp_c'] ?? 0)
                    ];
                }
            }
            $expectedInverters = (int)($PLANTS[$plant_id]['inverter_count'] ?? 0);
            $cacheHasEnoughInverters = $expectedInverters <= 0 || count($response["data"]["inverters"]) >= $expectedInverters;
            if ($cacheHasEnoughInverters && ($response["data"]["vcb"] || count($response["data"]["inverters"]) || count($response["data"]["transformers"]))) {
                usort($response["data"]["inverters"], function($a, $b) {
                    $na = preg_match('/\d+/', $a['inverter_name'], $ma) ? (int)$ma[0] : 0;
                    $nb = preg_match('/\d+/', $b['inverter_name'], $mb) ? (int)$mb[0] : 0;
                    return $na <=> $nb;
                });
                $conn->close();
                echo json_encode($response);
                exit;
            }
        }
    }

    $vcbSql = "SELECT * FROM vcb_data WHERE plant_id = '$escPlant' AND snapshot_at >= '$freshAfter' ORDER BY snapshot_at DESC LIMIT 1";
    $vcbRes = @$conn->query($vcbSql);
    if ($vcbRes && $vcbRes->num_rows > 0) {
        $response["data"]["vcb"] = $vcbRes->fetch_assoc();
    }

    $invSql = "SELECT i.* FROM inverter_data i
        INNER JOIN (
            SELECT inverter_name, MAX(snapshot_at) AS snapshot_at
            FROM inverter_data
            WHERE plant_id = '$escPlant' AND snapshot_at >= '$freshAfter'
            GROUP BY inverter_name
        ) latest ON latest.inverter_name = i.inverter_name AND latest.snapshot_at = i.snapshot_at
        WHERE i.plant_id = '$escPlant'
        ORDER BY i.snapshot_at DESC, i.id DESC";
    $invRes = @$conn->query($invSql);
    if ($invRes) {
        $seenInverters = [];
        while ($row = $invRes->fetch_assoc()) {
            $sourceName = $row['inverter_name'];
            $canonicalName = canonicalApiInverterName($sourceName);
            if (isset($seenInverters[$canonicalName])) continue;
            $seenInverters[$canonicalName] = true;
            $row['inverter_name'] = $canonicalName;
            $row['strings'] = [];
            if ($includeStrings) {
                $safeName = $conn->real_escape_string($sourceName);
                $safeSnapshot = $conn->real_escape_string($row['snapshot_at']);
                $strSql = "SELECT string_n, current_a, voltage_v, active
                    FROM inverter_strings
                    WHERE plant_id = '$escPlant' AND inverter_name = '$safeName' AND snapshot_at = '$safeSnapshot'
                    ORDER BY string_n ASC";
                $strRes = @$conn->query($strSql);
                if ($strRes) {
                    while ($s = $strRes->fetch_assoc()) {
                        $row['strings'][] = $s;
                    }
                }
            }
            $response["data"]["inverters"][] = $row;
        }
        usort($response["data"]["inverters"], function($a, $b) {
            $na = preg_match('/\d+/', $a['inverter_name'], $ma) ? (int)$ma[0] : 0;
            $nb = preg_match('/\d+/', $b['inverter_name'], $mb) ? (int)$mb[0] : 0;
            return $na <=> $nb;
        });
    }

    $trafoSql = "SELECT t.* FROM transformer_data t
        INNER JOIN (
            SELECT device_name, MAX(snapshot_at) AS snapshot_at
            FROM transformer_data
            WHERE plant_id = '$escPlant' AND snapshot_at >= '$freshAfter'
            GROUP BY device_name
        ) latest ON latest.device_name = t.device_name AND latest.snapshot_at = t.snapshot_at
        WHERE t.plant_id = '$escPlant'
        ORDER BY t.device_name ASC";
    $trafoRes = @$conn->query($trafoSql);
    if ($trafoRes) {
        while ($row = $trafoRes->fetch_assoc()) {
            $response["data"]["transformers"][] = $row;
        }
    }

    $conn->close();
    echo json_encode($response);
    exit;
}
elseif ($action === 'get_fast_snapshot') {
    $plant_id = isset($data['plant_id']) ? $data['plant_id'] : (isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
    if (!$plant_id || !isset($PLANTS[$plant_id])) {
        echo json_encode(["status" => "error", "message" => "Invalid plant_id."]);
        exit;
    }
    $cachedSnapshot = readPlantSnapshotCache($plant_id, 300);
    if ($cachedSnapshot) {
        header('X-Plant-Cache: HIT');
        echo json_encode($cachedSnapshot, JSON_UNESCAPED_SLASHES);
        exit;
    }
    $conn = getPlantDbConn($plant_id);
    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Database connection failed."]);
        exit;
    }

    $escPlant = $conn->real_escape_string($plant_id);
    $freshAfter = date('Y-m-d H:i:s', time() - 300);
    $escFreshAfter = $conn->real_escape_string($freshAfter);
    $response = [
        "status" => "success",
        "plant_id" => $plant_id,
        "fresh_after" => $freshAfter,
        "data" => ["vcb" => null, "inverters" => [], "transformers" => []]
    ];

    $latestSql = "SELECT type, device_name, snapshot_at, payload_json
        FROM telemetry_latest
        WHERE plant_id = '$escPlant' AND snapshot_at >= '$escFreshAfter'
        ORDER BY type ASC, device_name ASC";
    $latestRes = @$conn->query($latestSql);
    if ($latestRes) {
        while ($latest = $latestRes->fetch_assoc()) {
            $payload = json_decode($latest['payload_json'], true);
            if (!is_array($payload)) continue;
            if ($latest['type'] === 'inverter') {
                $response["data"]["inverters"][] = latestInverterRowFromPayload($plant_id, $latest['device_name'], $latest['snapshot_at'], $payload, false);
            } elseif ($latest['type'] === 'vcb') {
                $response["data"]["vcb"] = latestVcbRowFromPayload($plant_id, $latest['device_name'], $latest['snapshot_at'], $payload);
            } elseif ($latest['type'] === 'transformer') {
                $response["data"]["transformers"][] = [
                    "plant_id" => $plant_id,
                    "device_name" => $latest['device_name'],
                    "snapshot_at" => $latest['snapshot_at'],
                    "oil_temp_c" => (string)(float)($payload['oil_temp_c'] ?? 0),
                    "winding_temp_c" => (string)(float)($payload['winding_temp_c'] ?? 0)
                ];
            }
        }
    }
    usort($response["data"]["inverters"], function($a, $b) {
        $na = preg_match('/\d+/', $a['inverter_name'], $ma) ? (int)$ma[0] : 0;
        $nb = preg_match('/\d+/', $b['inverter_name'], $mb) ? (int)$mb[0] : 0;
        return $na <=> $nb;
    });
    $conn->close();
    echo json_encode($response);
    exit;
}
elseif ($action === 'get_availability_data') {
    $plant_id = isset($data['plant_id']) ? $data['plant_id'] : (isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
    if (!$plant_id || !isset($PLANTS[$plant_id])) {
        echo json_encode(["status" => "error", "message" => "Invalid plant_id."]);
        exit;
    }
    $conn = getPlantDbConn($plant_id);
    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Database connection failed."]);
        exit;
    }

    $startDate = isset($data['startDate']) ? $data['startDate'] : (isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-d'));
    $endDate = isset($data['endDate']) ? $data['endDate'] : (isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d'));
    $startTime = isset($data['startTime']) ? $data['startTime'] : (isset($_GET['startTime']) ? $_GET['startTime'] : '00:00:00');
    $endTime = isset($data['endTime']) ? $data['endTime'] : (isset($_GET['endTime']) ? $_GET['endTime'] : '23:59:59');

    // Ensure seconds are included in times
    if (strlen($startTime) == 5) $startTime .= ':00';
    if (strlen($endTime) == 5) $endTime .= ':59';

    $escPlant = $conn->real_escape_string($plant_id);
    $escStart = $conn->real_escape_string($startDate . ' ' . $startTime);
    $escEnd = $conn->real_escape_string($endDate . ' ' . $endTime);

    // Fetch inverters data
    $sql = "SELECT inverter_name, snapshot_at, power_kw, has_fault, has_alarm, status_text
            FROM inverter_data
            WHERE plant_id = '$escPlant' AND snapshot_at BETWEEN '$escStart' AND '$escEnd'
            ORDER BY snapshot_at ASC";
    
    $res = $conn->query($sql);
    $rawData = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $val = (float)$row['power_kw'];
            $fault = (int)$row['has_fault'];
            $rawData[] = [
                'device' => canonicalApiInverterName($row['inverter_name']),
                'time' => $row['snapshot_at'],
                'power' => $val,
                'fault' => $fault,
                'alarm' => (int)$row['has_alarm'],
                'status' => $row['status_text'],
                'available' => ($val > 0 && !$fault) ? 1 : 0
            ];
        }
    }

    // Fetch VCB data
    $vcbSql = "SELECT snapshot_at, power_3phase_kw
               FROM vcb_data
               WHERE plant_id = '$escPlant' AND snapshot_at BETWEEN '$escStart' AND '$escEnd'
               ORDER BY snapshot_at ASC";
    $vcbRes = $conn->query($vcbSql);
    $rawVcb = [];
    if ($vcbRes) {
        while ($row = $vcbRes->fetch_assoc()) {
            $rawVcb[] = [
                'time' => $row['snapshot_at'],
                'power' => (float)$row['power_3phase_kw']
            ];
        }
    }

    $conn->close();

    echo json_encode([
        "status" => "success",
        "plant_id" => $plant_id,
        "startDate" => $startDate,
        "endDate" => $endDate,
        "startTime" => $startTime,
        "endTime" => $endTime,
        "inverters" => $rawData,
        "vcb" => $rawVcb
    ]);
    exit;
}
?>
