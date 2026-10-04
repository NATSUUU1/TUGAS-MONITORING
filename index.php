<?php
declare(strict_types=1);

require __DIR__ . '/config/auth.php';

if (dashboardIsAuthenticated()) {
    dashboardEndSession();
}

$pageTitle = 'SENSOR DETEKSI SUHU DAN KEBAKARAN';
$activePage = 'home';
$introAnimation = true;
$introOnce = true; // hanya saat web pertama kali dibuka
require __DIR__ . '/partials/header.php';
?>

<main class="bento-home">
    <section class="bento-hero">
        <div class="bento-hero-content">
            <span class="eyebrow">Pusat Kendali Utama</span>
            <h1>Harmoni &amp; <span>Presisi</span></h1>
            <p>Sistem pemantauan cerdas dengan antarmuka terstruktur. Menghadirkan ketenangan dalam setiap kendali, memadukan estetika minimalis Jepang dengan teknologi modern.</p>
        </div>
        <div class="bento-hero-visual">
            <img src="https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?auto=format&fit=crop&w=800&q=80" alt="Kyoto Shrine">
            <div class="visual-overlay"></div>
        </div>
    </section>

    <section class="bento-grid">
        <article class="bento-card">
            <div class="bento-icon">🌡️</div>
            <h3>Suhu & Kelembaban</h3>
            <p>Pemantauan iklim mikro secara real-time dengan akurasi tinggi.</p>
            <div class="card-footer">Status: <span class="text-green">Aktif</span></div>
        </article>
        
        <article class="bento-card">
            <div class="bento-icon">💨</div>
            <h3>Kualitas Udara</h3>
            <p>Deteksi dini partikel gas dan asap untuk keamanan lingkungan.</p>
            <div class="card-footer">Status: <span class="text-green">Normal</span></div>
        </article>

        <article class="bento-card bento-card-status">
            <h3>Log Sistem Terkini</h3>
            <ul class="status-list">
                <li><span class="dot active"></span> <strong>Node Alpha:</strong> Sinkronisasi berhasil</li>
                <li><span class="dot active"></span> <strong>Node Beta:</strong> Kalibrasi selesai</li>
                <li><span class="dot standby"></span> <strong>Database:</strong> Menunggu pembaruan</li>
            </ul>
        </article>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
