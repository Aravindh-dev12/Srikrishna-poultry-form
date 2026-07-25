<?php require __DIR__ . '/config.php'; $public = kandan_public_config(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kandan — Inverter Availability</title>
  <link rel="stylesheet" href="assets/kandan_dashboard.css">
</head>
<body data-page="availability">
<div class="app">
  <header class="topbar">
    <div class="brand"><button id="mobileMenu" class="mobile-menu" type="button">☰</button><span class="preview">DEVELOPER PREVIEW</span><span>Inverter Availability — Kandan</span></div>
    <div class="live-pill"><span class="live-dot" data-live-dot></span><span data-ws-status>CONNECTING</span></div>
  </header>
  <div class="shell">
    <aside class="sidebar">
      <div class="nav-title">NAVIGATION</div>
      <a class="nav-link" href="overview.php"><span class="nav-icon">⌘</span>Inverter Overview</a>
      <a class="nav-link" href="inverter.php"><span class="nav-icon">▦</span>Inverter Strings</a>
      <a class="nav-link active" href="availability.php"><span class="nav-icon">◴</span>Inverter Availability</a>
    </aside>
    <main class="content">
      <div class="page-head">
        <div><div class="page-title"><span class="title-icon">◴</span>Plant Availability Timeline</div><div class="page-sub"><?php echo htmlspecialchars($public['plantName']); ?> · live inverter operating status</div></div>
        <div class="legend"><span><i style="background:#1f9f4f"></i>ONLINE</span><span><i style="background:#e92f3d"></i>OFFLINE</span><span><i style="background:#d9dee7"></i>COMM ERR</span></div>
      </div>
      <section id="availabilityList" class="timeline-list"></section>
    </main>
  </div>
</div>
<script>window.KANDAN_CONFIG=<?php echo json_encode($public, JSON_UNESCAPED_SLASHES); ?>;</script>
<script src="assets/kandan_dashboard.js"></script>
</body>
</html>
