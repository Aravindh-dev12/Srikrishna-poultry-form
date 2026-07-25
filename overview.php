<?php require __DIR__ . '/config.php'; $public = kandan_public_config(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kandan — Inverter Overview</title>
  <link rel="stylesheet" href="assets/kandan_dashboard.css">
</head>
<body data-page="overview">
<div class="app">
  <header class="topbar">
    <div class="brand"><button id="mobileMenu" class="mobile-menu" type="button">☰</button><span class="preview">DEVELOPER PREVIEW</span><span>Inverter Overview — Kandan</span></div>
    <div class="live-pill"><span class="live-dot" data-live-dot></span><span data-ws-status>CONNECTING</span></div>
  </header>
  <div class="shell">
    <aside class="sidebar">
      <div class="nav-title">NAVIGATION</div>
      <a class="nav-link active" href="overview.php"><span class="nav-icon">⌘</span>Inverter Overview</a>
      <a class="nav-link" href="inverter.php"><span class="nav-icon">▦</span>Inverter Strings</a>
      <a class="nav-link" href="availability.php"><span class="nav-icon">◴</span>Inverter Availability</a>
    </aside>
    <main class="content">
      <div class="page-head">
        <div><div class="page-title"><span class="title-icon">⚡</span>Inverter Overview</div><div class="page-sub"><?php echo (int)$public['inverterCount']; ?> inverters · <?php echo htmlspecialchars($public['plantName']); ?></div></div>
        <div class="page-live"><span class="live-dot" data-live-dot></span><span data-ws-status>CONNECTING</span></div>
      </div>
      <section class="card panel table-wrap">
        <table class="data-table">
          <thead><tr><th>#</th><th>Inverter</th><th>Status</th><th>Active Power</th><th>DC Power</th><th>Active Strings</th><th>Today Gen</th><th>Total Gen</th><th>Int. Temp</th><th>Error</th></tr></thead>
          <tbody id="overviewRows"></tbody>
        </table>
      </section>
    </main>
  </div>
</div>
<script>window.KANDAN_CONFIG=<?php echo json_encode($public, JSON_UNESCAPED_SLASHES); ?>;</script>
<script src="assets/kandan_dashboard.js"></script>
</body>
</html>
