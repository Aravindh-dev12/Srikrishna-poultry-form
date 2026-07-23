<?php
// Local-only CLI account provisioning. Passwords are read from environment
// variables and hashed on the server; credentials never need to enter Git.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/config.php';

$accounts = [
    ['admin@vijayanth.com', getenv('SCADA_ADMIN_PASSWORD'), 'admin', ''],
    ['bojaraj@scada.com', getenv('SCADA_BOJARAJ_PASSWORD'), 'user', 'vijayanth'],
    ['krishna@scada.com', getenv('SCADA_KRISHNA_PASSWORD'), 'user', 'krishna'],
];

foreach ($accounts as $account) {
    if ($account[1] === false || $account[1] === '') {
        fwrite(STDERR, "All SCADA password environment variables are required.\n");
        exit(1);
    }
}

$conn = getPlantDbConn(getDefaultPlantId());
if (!$conn) {
    fwrite(STDERR, "Database connection failed. Check config.local.php.\n");
    exit(1);
}

$stmt = $conn->prepare("INSERT INTO users (email, password, role, plant_id)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role),
        plant_id = VALUES(plant_id), auth_token = NULL");
if (!$stmt) {
    fwrite(STDERR, "Could not prepare account update.\n");
    exit(1);
}

foreach ($accounts as [$email, $password, $role, $plantId]) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bind_param('ssss', $email, $hash, $role, $plantId);
    if (!$stmt->execute()) {
        fwrite(STDERR, "Failed to provision {$email}.\n");
        exit(1);
    }
    echo "Provisioned {$email} for " . ($plantId ?: 'all plants') . PHP_EOL;
}

$stmt->close();
$conn->close();
?>
