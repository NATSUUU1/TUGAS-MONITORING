<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'SENSOR DETEKSI SUHU DAN KEBAKARAN';
$activePage = $activePage ?? '';
$introAnimation = $introAnimation ?? false; // animasi pembuka hanya jika diaktifkan halaman
$introOnce = $introOnce ?? false;           // true = hanya sekali per sesi (saat membuka link web)
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | X MONITORING</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&family=Shippori+Mincho:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>">
    <?php if ($introAnimation && $introOnce): ?>
    <script>
        try {
            if (sessionStorage.getItem('xm_intro')) document.documentElement.classList.add('no-intro');
            else sessionStorage.setItem('xm_intro', '1');
        } catch (e) {}
    </script>
    <?php endif; ?>
</head>
<body>
    <?php if ($introAnimation): ?>
    <div class="page-transition" aria-hidden="true">
        <span class="pt-panel pt-left"></span>
        <span class="pt-panel pt-right"></span>
        <div class="pt-center">
            <svg class="pt-enso" viewBox="0 0 100 100"><circle cx="50" cy="50" r="44" pathLength="1"/></svg>
            <span class="pt-mark">⛩</span>
            <span class="pt-text">X MONITORING</span>
            <span class="pt-sub">監視システム</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="site-shell<?= $activePage === 'home' ? ' home-shell' : '' ?><?= $introAnimation ? ' has-intro' : '' ?>">
        <header class="topbar">
            <a href="index.php" class="brand" aria-label="X MONITORING beranda">
                <span class="brand-mark">⛩</span>
                <span>X <span>MONITORING</span></span>
            </a>
            <button class="menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </header>

        <div class="overlay" aria-hidden="true"></div>
        
        <aside class="sidebar" aria-label="Navigasi utama">
            <div class="sidebar-heading">
                <span>RUANG OBSERVASI</span>
                <button class="close-menu" type="button" aria-label="Tutup menu">×</button>
            </div>
            <?php
            $navigationItems = [
                ['home', 'index.php', '⛩', 'Beranda'],
                ['sensor', 'dashboard.php', '▦', 'Data Sensor'],
                ['pengaturan', 'settings.php', '⚙︎', 'Pengaturan'],
            ];
            ?>
            <nav>
                <?php foreach ($navigationItems as [$key, $url, $icon, $label]): ?>
                    <a href="<?= $url ?>" class="<?= $activePage === $key ? 'active' : '' ?>"><span class="nav-icon"><?= $icon ?></span> <?= $label ?></a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="content">
