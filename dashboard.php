<?php
declare(strict_types=1);

require __DIR__ . '/config/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!dashboardIsAuthenticated()) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config/database.php';

$pageTitle = 'Dashboard';
$activePage = 'sensor';
$rows = $pdo->query('SELECT waktu, suhu, kelembapan, asap FROM data_sensor ORDER BY waktu DESC LIMIT 20')->fetchAll();
$latest = $rows[0] ?? null;
$num = static fn($v): string => number_format((float) $v, 1, '.', '');

$cards = $latest ? [
    ['temperature', '🌡️', 'Suhu', $num($latest['suhu']), ' °C'],
    ['humidity', '💧', 'Kelembapan', $num($latest['kelembapan']), ' %'],
    ['smoke', '💨', 'Partikel Asap', (string) $latest['asap'], ' ppm'],
    ['records', '📊', 'Total Data', (string) $pdo->query('SELECT COUNT(*) FROM data_sensor')->fetchColumn(), ' data'],
] : [];

require __DIR__ . '/partials/header.php';
?>

<main class="dashboard-page">
    <div class="page-heading">
        <div>
            <span class="eyebrow">CATATAN SENSOR</span>
            <h1>Ruang Observasi</h1>
            <p>Rangkuman pengamatan yang tersimpan dalam arsip data_sensor.</p>
        </div>
        <div class="dashboard-actions">
            <span class="live-badge"><i></i> Arsip terhubung</span>
            <button class="logout-button" type="button" data-open-logout>Keluar</button>
        </div>
    </div>

    <dialog class="logout-dialog" aria-labelledby="logout-confirmation-title">
        <form method="post" action="logout.php">
            <span class="eyebrow">KONFIRMASI</span>
            <h2 id="logout-confirmation-title">Apakah Anda yakin ingin logout?</h2>
            <p>Sesi dashboard akan diakhiri.</p>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(dashboardCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="logout-dialog-actions">
                <button class="logout-cancel" type="button" data-cancel-logout>Tidak</button>
                <button class="logout-confirm" type="submit">Ya</button>
            </div>
        </form>
    </dialog>

    <?php if ($latest): ?>
        <section class="sensor-grid" aria-label="Data sensor terbaru">
            <?php foreach ($cards as [$type, $icon, $label, $value, $unit]): ?>
                <article class="sensor-card <?= $type ?>">
                    <div class="sensor-icon"><?= $icon ?></div>
                    <div>
                        <span class="sensor-label"><?= $label ?></span>
                        <strong><?= $value ?><small><?= $unit ?></small></strong>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="table-panel">
            <div class="panel-heading">
                <div>
                    <h2>Jurnal Pengamatan</h2>
                    <p>20 entri terakhir yang tersimpan.</p>
                </div>
                <span class="last-update">Entri terakhir: <?= date('d/m/Y H:i', strtotime($latest['waktu'])) ?></span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><small>番</small>No.</th>
                            <th><small>時刻</small>Waktu</th>
                            <th><small>温度</small>Suhu</th>
                            <th><small>湿度</small>Kelembapan</th>
                            <th><small>煙</small>Asap</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= date('d M Y, H:i:s', strtotime($row['waktu'])) ?></td>
                                <td><span class="value-chip orange"><?= $num($row['suhu']) ?> °C</span></td>
                                <td><span class="value-chip blue"><?= $num($row['kelembapan']) ?> %</span></td>
                                <td><span class="value-chip red"><?= (string) $row['asap'] ?> ppm</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else: ?>
        <section class="empty-state">
            <div class="empty-icon">⌁</div>
            <h2>Jurnal masih kosong</h2>
            <p>Tambahkan entri ke tabel <code>data_sensor</code> untuk memulai pengamatan.</p>
        </section>
    <?php endif; ?>
</main>

<script>
(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    // Kelopak sakura jatuh
    const layer = document.createElement('div');
    layer.className = 'sakura';
    layer.setAttribute('aria-hidden', 'true');
    for (let i = 0; i < 16; i++) {
        const p = document.createElement('span');
        p.className = 'petal';
        p.style.left = (Math.random() * 100) + 'vw';
        p.style.setProperty('--size', (8 + Math.random() * 10).toFixed(1) + 'px');
        p.style.setProperty('--dur', (11 + Math.random() * 10).toFixed(1) + 's');
        p.style.setProperty('--delay', (Math.random() * -20).toFixed(1) + 's');
        p.style.setProperty('--sway', (Math.random() * 120 - 40).toFixed(0) + 'px');
        layer.appendChild(p);
    }
    document.body.appendChild(layer);

    // Angka sensor menghitung naik dari 0
    document.querySelectorAll('.sensor-card strong').forEach((el) => {
        const node = el.firstChild;
        if (!node || node.nodeType !== Node.TEXT_NODE) return;
        const text = node.nodeValue.trim();
        const target = parseFloat(text);
        if (Number.isNaN(target)) return;
        const decimals = (text.split('.')[1] || '').length;
        const duration = 1400, delay = 300;
        node.nodeValue = (0).toFixed(decimals);
        setTimeout(() => {
            const start = performance.now();
            const tick = (now) => {
                const k = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - k, 3);
                node.nodeValue = (target * eased).toFixed(decimals);
                if (k < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        }, delay);
    });
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
