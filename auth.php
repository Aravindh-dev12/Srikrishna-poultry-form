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

function kandan_accounts(): array
{
    return [
        strtolower(getenv('KANDAN_ADMIN_EMAIL') ?: 'admin@kandan.com') => [
            'password_hash' => hash('sha256', getenv('KANDAN_ADMIN_PASSWORD') ?: 'admin@123'),
            'role' => 'admin',
            'label' => 'Administrator',
            'plant_id' => 'kandan',
        ],
        strtolower(getenv('KANDAN_USER_EMAIL') ?: 'kandan@scada.com') => [
            'password_hash' => hash('sha256', getenv('KANDAN_USER_PASSWORD') ?: 'landan@123'),
            'role' => 'user',
            'label' => 'Kandan User',
            'plant_id' => 'kandan',
        ],
    ];
}

function kandan_authenticate(string $email, string $password): ?array
{
    $email = strtolower(trim($email));
    $account = kandan_accounts()[$email] ?? null;

    if (!$account || !hash_equals($account['password_hash'], hash('sha256', $password))) {
        return null;
    }

    session_regenerate_id(true);
    $_SESSION['kandan_user'] = [
        'email' => $email,
        'role' => $account['role'],
        'label' => $account['label'],
        'plant_id' => $account['plant_id'],
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
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
    }

    session_destroy();
}
