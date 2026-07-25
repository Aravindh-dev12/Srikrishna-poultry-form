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
  <title>Kandan — Inverter Strings</title>
  <link rel="stylesheet" href="assets/kandan_dashboard.css">
</head>
<body data-page="strings">
<div class="app">
  <header class="topbar">
    <div class="topbar-left">
      <button id="mobileMenu" class="mobile-menu" type="button" aria-label="Open navigation">☰</button>
      <span class="preview-badge">DEVELOPER PREVIEW</span>
      <span class="topbar-title">Inverter Strings — <strong>kandan</strong></span>
    </div>
    <span class="live-indicator"><span class="live-dot" data-live-dot></span><span data-ws-status>CONNECTING</span></span>
  </header>
  <div id="sidebarBackdrop" class="sidebar-backdrop"></div>
  <div class="shell">
    <aside class="sidebar">
      <div class="nav-title">NAVIGATION</div>
      <a class="nav-link" href="overview.php?plant_id=<?php echo $pid; ?>"><span class="nav-icon">⌘</span>Inverter Overview</a>
      <a class="nav-link active" href="inverter.php?plant_id=<?php echo $pid; ?>"><span class="nav-icon">⌘</span>Inverter Strings</a>
      <a class="nav-link" href="availability.php?plant_id=<?php echo $pid; ?>"><span class="nav-icon">⌘</span>Inverter Availability</a>
    </aside>
    <main class="content">
      <div class="page-heading">
        <div class="heading-main">
          <span class="heading-icon heading-icon-blue">▣</span>
          <div class="heading-copy">
            <h1>Dense String Matrix</h1>
            <p><?php echo (int)$public['inverterCount']; ?> Inv × <?php echo (int)$public['stringCount']; ?> Str</p>
          </div>
        </div>
        <span class="page-live"><span class="live-dot" data-live-dot></span><span data-ws-status>CONNECTING</span></span>
      </div>
      <section class="matrix-panel">
        <div class="matrix-scroll"><div id="stringMatrix" class="matrix-grid"></div></div>
      </section>
    </main>
  </div>
</div>
<script>window.KANDAN_CONFIG=<?php echo json_encode($public, JSON_UNESCAPED_SLASHES); ?>;</script>
<script src="assets/kandan_dashboard.js"></script>
</body>
</html>
