<?php
require __DIR__ . '/config.php';
$public = kandan_public_config();
$pid = rawurlencode($public['plantId']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kandan — Inverter Overview</title>
  <link rel="stylesheet" href="assets/kandan_dashboard.css">
</head>
<body data-page="overview">
<div class="app">
  <header class="topbar">
    <div class="topbar-left">
      <button id="mobileMenu" class="mobile-menu" type="button" aria-label="Open navigation">☰</button>
      <span class="preview-badge">DEVELOPER PREVIEW</span>
      <span class="topbar-title">Inverter Overview — <strong>kandan</strong></span>
    </div>
    <span class="live-indicator"><span class="live-dot" data-live-dot></span><span data-ws-status>CONNECTING</span></span>
  </header>
  <div id="sidebarBackdrop" class="sidebar-backdrop"></div>
  <div class="shell">
    <aside class="sidebar">
      <div class="nav-title">NAVIGATION</div>
      <a class="nav-link active" href="overview.php?plant_id=<?php echo $pid; ?>"><span class="nav-icon">⌘</span>Inverter Overview</a>
      <a class="nav-link" href="inverter.php?plant_id=<?php echo $pid; ?>"><span class="nav-icon">⌘</span>Inverter Strings</a>
      <a class="nav-link" href="availability.php?plant_id=<?php echo $pid; ?>"><span class="nav-icon">⌘</span>Inverter Availability</a>
    </aside>
    <main class="content">
      <div class="page-heading">
        <div class="heading-main">
          <span class="heading-icon heading-icon-green">⚡</span>
          <div class="heading-copy">
            <h1>Inverter Overview</h1>
            <p><?php echo (int)$public['inverterCount']; ?> inverters</p>
          </div>
        </div>
        <span class="page-live"><span class="live-dot" data-live-dot></span><span data-ws-status>CONNECTING</span></span>
      </div>
      <section class="overview-panel">
        <div class="table-scroll">
          <table class="overview-table">
            <thead>
              <tr><th></th><th>Inverter</th><th>Status</th><th>Active Power</th><th>DC Power</th><th>Active Strings</th><th>Today Gen</th><th>Total Gen</th><th>Int. Temp</th><th>Error</th></tr>
            </thead>
            <tbody id="overviewRows"></tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</div>
<script>window.KANDAN_CONFIG=<?php echo json_encode($public, JSON_UNESCAPED_SLASHES); ?>;</script>
<script src="assets/kandan_dashboard.js"></script>
</body>
</html>
