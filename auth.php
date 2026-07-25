<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_name('kandan_scada_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function kandan_authenticate(string $email, string $password): ?array
{
    $email = strtolower(trim($email));
    if ($email === '' || $password === '') {
        return null;
    }

    $db = kandan_db();
    if (!$db) {
        throw new RuntimeException('Database connection is unavailable. Check the KANDAN_DB_* server settings and import setup_kandan_db.sql.');
    }

    $stmt = $db->prepare(
        'SELECT id, email, password_hash, display_name, role, plant_id
         FROM users
         WHERE email = :email AND plant_id = :plant_id AND is_active = 1
         LIMIT 1'
    );
    $stmt->execute([
        ':email' => $email,
        ':plant_id' => 'kandan',
    ]);
    $account = $stmt->fetch();

    if (!$account || !password_verify($password, (string)$account['password_hash'])) {
        return null;
    }

    $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')
        ->execute([':id' => (int)$account['id']]);

    session_regenerate_id(true);
    $_SESSION['kandan_user'] = [
        'id' => (int)$account['id'],
        'email' => (string)$account['email'],
        'role' => (string)$account['role'],
        'label' => (string)$account['display_name'],
        'plant_id' => (string)$account['plant_id'],
        'signed_in_at' => time(),
    ];

    return $_SESSION['kandan_user'];
}

function kandan_current_user(): ?array
{
    $user = $_SESSION['kandan_user'] ?? null;
    if (!is_array($user) || ($user['plant_id'] ?? '') !== 'kandan') {
        return null;
    }
    return $user;
}

function require_kandan_auth(): array
{
    $user = kandan_current_user();
    if ($user) {
        return $user;
    }

    header('Location: index.php');
    exit;
}

function require_kandan_api_auth(): array
{
    $user = kandan_current_user();
    if ($user) {
        return $user;
    }

    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}

function kandan_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            (bool)$params['secure'],
            (bool)$params['httponly']
        );
    }

    session_destroy();
}
