<?php

declare(strict_types=1);

date_default_timezone_set(getenv('KANDAN_TIMEZONE') ?: 'Asia/Kolkata');

$KANDAN_CONFIG = [
    'plant_id' => getenv('KANDAN_PLANT_ID') ?: 'kandan',
    'plant_name' => getenv('KANDAN_PLANT_NAME') ?: 'Kandan Solar Plant',
    // Same SCADA WebSocket server; unit_id selects the Kandan plant stream.
    'ws_url' => getenv('KANDAN_WS_URL') ?: 'wss://vinobasolar.scadahub.in:5001',
    'ws_unit_id' => getenv('KANDAN_WS_UNIT_ID') ?: 'via-3mw',
    'inverter_count' => (int)(getenv('KANDAN_INVERTER_COUNT') ?: 10),
    'string_count' => (int)(getenv('KANDAN_STRING_COUNT') ?: 24),
    'cache_file' => getenv('KANDAN_CACHE_FILE') ?: __DIR__ . '/storage/kandan_cache.json',
    'db' => [
        'host' => getenv('KANDAN_DB_HOST') ?: 'localhost',
        'port' => (int)(getenv('KANDAN_DB_PORT') ?: 3306),
        'name' => getenv('KANDAN_DB_NAME') ?: 'kandan',
        'user' => getenv('KANDAN_DB_USER') ?: 'root',
        'pass' => getenv('KANDAN_DB_PASS') ?: '',
    ],
];

function kandan_public_config(): array
{
    global $KANDAN_CONFIG;
    $plantId = $KANDAN_CONFIG['plant_id'];

    return [
        'plantId' => $plantId,
        'plantName' => $KANDAN_CONFIG['plant_name'],
        'wsUrl' => $KANDAN_CONFIG['ws_url'],
        'unitId' => $KANDAN_CONFIG['ws_unit_id'],
        'inverterCount' => $KANDAN_CONFIG['inverter_count'],
        'stringCount' => $KANDAN_CONFIG['string_count'],
        'cacheUrl' => 'api_cache.php?plant_id=' . rawurlencode($plantId),
    ];
}

function kandan_cache_read(): array
{
    global $KANDAN_CONFIG;
    $path = $KANDAN_CONFIG['cache_file'];
    $empty = [
        'status' => 'success',
        'plant_id' => $KANDAN_CONFIG['plant_id'],
        'unit_id' => $KANDAN_CONFIG['ws_unit_id'],
        'updated_at' => null,
        'inverters' => [],
    ];

    if (!is_file($path)) return $empty;
    $decoded = json_decode((string)@file_get_contents($path), true);
    if (!is_array($decoded)) return $empty;

    $decoded['status'] = 'success';
    $decoded['plant_id'] = $KANDAN_CONFIG['plant_id'];
    $decoded['unit_id'] = $KANDAN_CONFIG['ws_unit_id'];
    $decoded['inverters'] = is_array($decoded['inverters'] ?? null) ? $decoded['inverters'] : [];
    return $decoded;
}

function kandan_cache_write(array $snapshot): bool
{
    global $KANDAN_CONFIG;
    $path = $KANDAN_CONFIG['cache_file'];
    $directory = dirname($path);

    if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) return false;

    $snapshot['status'] = 'success';
    $snapshot['plant_id'] = $KANDAN_CONFIG['plant_id'];
    $snapshot['unit_id'] = $KANDAN_CONFIG['ws_unit_id'];
    $snapshot['updated_at'] = $snapshot['updated_at'] ?? date(DATE_ATOM);

    $temp = $path . '.tmp';
    $encoded = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false || @file_put_contents($temp, $encoded, LOCK_EX) === false) return false;
    return @rename($temp, $path);
}

function kandan_db(): ?PDO
{
    global $KANDAN_CONFIG;
    static $pdo = false;
    if ($pdo instanceof PDO) return $pdo;
    if ($pdo === null) return null;

    $db = $KANDAN_CONFIG['db'];
    try {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']);
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (Throwable $error) {
        error_log('[Kandan DB] ' . $error->getMessage());
        $pdo = null;
        return null;
    }
}
