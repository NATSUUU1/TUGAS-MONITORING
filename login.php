<?php
declare(strict_types=1);

require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/credentials.php';

if (dashboardIsAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$csrfToken = dashboardCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!dashboardCsrfIsValid($submittedToken)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif (dashboardVerifyCredentials($pdo, $username, $password)) {
        session_regenerate_id(true);
        $_SESSION['dashboard_authenticated'] = true;
        $_SESSION['dashboard_csrf_token'] = bin2hex(random_bytes(32));
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau password tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | X MONITORING</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&family=Shippori+Mincho:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?: time() ?>">
</head>
<body class="login-standalone has-intro">
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

    <a class="login-brand" href="index.php" aria-label="Kembali ke beranda X MONITORING">
        <span class="brand-mark">⛩</span>
        <span>X <strong>MONITORING</strong></span>
    </a>
    <main class="login-page">
        <section class="login-card">
            <div class="login-stamp" aria-hidden="true">入</div>
            <span class="eyebrow">RUANG OBSERVASI</span>
            <h1>Masuk ke Dashboard</h1>
            <p>Silakan masuk untuk melihat data dan jurnal sensor.</p>

            <?php if ($error !== ''): ?>
                <p class="login-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form class="login-form" method="post" action="login.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" autocomplete="username" placeholder="Masukkan username" required>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" required>

                <button type="submit">Masuk</button>
            </form>
            <a class="login-home-button" href="index.php">← Kembali ke Beranda</a>
        </section>
    </main>
    <footer class="login-footer">
        <span>© <?= date('Y') ?> X MONITORING</span>
        <span>Journal of Atmospheric Observation</span>
    </footer>
    <script src="assets/js/menu.js"></script>
</body>
</html>
