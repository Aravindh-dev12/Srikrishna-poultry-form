<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (kandan_current_user()) {
    header('Location: overview.php?plant_id=kandan');
    exit;
}

$error = '';
$email = '';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $user = kandan_authenticate($email, $password);

    if ($user) {
        header('Location: overview.php?plant_id=' . rawurlencode($user['plant_id']));
        exit;
    }

    $error = 'Invalid email address or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kandan SCADA Sign In</title>
  <link rel="stylesheet" href="assets/kandan_dashboard.css">
  <link rel="stylesheet" href="assets/kandan_auth.css">
</head>
<body class="login-page">
  <main class="login-shell">
    <section class="login-card" aria-labelledby="loginTitle">
      <div class="login-brand-mark">⚡</div>
      <p class="login-eyebrow">KANDAN SOLAR PLANT</p>
      <h1 id="loginTitle">SCADA Dashboard</h1>
      <p class="login-copy">Sign in to open the Kandan plant dashboard and connect to its assigned WebSocket data stream.</p>

      <?php if ($error !== ''): ?>
        <div class="login-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post" class="login-form" autocomplete="on">
        <label><span>Email address</span><input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" autocomplete="username" required autofocus></label>
        <label><span>Password</span><input type="password" name="password" autocomplete="current-password" required></label>
        <button type="submit">Sign in to Kandan</button>
      </form>

      <div class="login-plant-meta"><span>Plant ID</span><strong>kandan</strong><span>Unit ID</span><strong>via-3mw</strong></div>
    </section>
  </main>
</body>
</html>
