<?php require __DIR__ . '/config.php'; $public = kandan_public_config(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kandan — Inverter Strings</title>
  <link rel="stylesheet" href="assets/kandan_dashboard.css">
</head>
<body data-page="strings">
<div class="app">
  <header class="topbar">
    <div class="brand"><button id="mobileMenu" class="mobile-menu" type="button">☰</button><span class="preview">DEVELOPER PREVIEW</span><span>Inverter Strings — Kandan</span></div>
    <div class="live-pill"><span class="live-dot" data-live-dot></span><span data-ws-status>CONNECTING</span></div>
  </header>
  <div class="shell">
    <aside class="sidebar">
      <div class="nav-title">NAVIGATION</div>
      <a class="nav-link" href="overview.php"><span class="nav-icon">⌘</span>Inverter Overview</a>
      <a class="nav-link active" href="inverter.php"><span class="nav-icon">▦</span>Inverter Strings</a>
      <a class="nav-link" href="availability.php"><span class="nav-icon">◴</span>Inverter Availability</a>
    </aside>
    <main class="content">
      <div class="page-head">
        <div><div class="page-title"><span class="title-icon">▣</span>Dense String Matrix</div><div class="page-sub"><?php echo (int)$public['inverterCount']; ?> inverters × <?php echo (int)$public['stringCount']; ?> strings</div></div>
        <div class="page-live"><span class="live-dot" data-live-dot></span><span data-ws-status>CONNECTING</span></div>
      </div>
      <section class="card panel matrix"><div id="stringMatrix" class="matrix-grid"></div></section>
    </main>
  </div>
</div>
<script>window.KANDAN_CONFIG=<?php echo json_encode($public, JSON_UNESCAPED_SLASHES); ?>;</script>
<script src="assets/kandan_dashboard.js"></script>
</body>
</html>
