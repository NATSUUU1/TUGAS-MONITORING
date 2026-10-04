<?php
declare(strict_types=1);

$base = 'http://127.0.0.1:8123';
$failures = [];

function check(string $label, bool $ok): void
{
    global $failures;
    echo ($ok ? 'PASS' : 'FAIL') . ' - ' . $label . "\n";
    if (!$ok) {
        $failures[] = $label;
    }
}

function extractToken(string $html): string
{
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $matches) === 1) {
        return $matches[1];
    }

    return '';
}

function session(string $name)
{
    $handle = curl_init();
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => sys_get_temp_dir() . '/tgs_adm_' . $name . '.txt',
        CURLOPT_COOKIEFILE => sys_get_temp_dir() . '/tgs_adm_' . $name . '.txt',
        CURLOPT_TIMEOUT => 20,
    ]);

    return $handle;
}

function request($handle, string $url, ?array $fields = null): string
{
    curl_setopt($handle, CURLOPT_URL, $url);
    if ($fields === null) {
        curl_setopt($handle, CURLOPT_HTTPGET, true);
    } else {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($fields));
    }

    return (string) curl_exec($handle);
}

// 1. Tanpa login apa pun, halaman Pengaturan tampil dengan form login admin
$session = session('main');
$page = request($session, $base . '/settings.php');
check('halaman Pengaturan terbuka tanpa login dashboard', str_contains($page, 'Masuk Pengaturan'));
check('panel kelola belum tampil sebelum login admin', !str_contains($page, 'Akun Akses Dashboard'));
check('form login admin tersedia', str_contains($page, 'Username admin'));

// 2. Login admin dengan kredensial salah ditolak
$token = extractToken($page);
$wrong = request($session, $base . '/settings.php', [
    'csrf_token' => $token,
    'action' => 'login',
    'username' => 'admin',
    'password' => 'salah',
]);
check('password admin salah ditolak', str_contains($wrong, 'tidak sesuai'));
check('panel kelola tetap tertutup', !str_contains($wrong, 'Akun Akses Dashboard'));

// 3. Login admin dengan admin/1175 berhasil
$token = extractToken($wrong);
$ok = request($session, $base . '/settings.php', [
    'csrf_token' => $token,
    'action' => 'login',
    'username' => 'admin',
    'password' => '1175',
]);
check('login admin berhasil', str_contains($ok, 'Akun Akses Dashboard'));
check('username dashboard aktif ditampilkan', str_contains($ok, 'value="user"'));
check('form edit menyediakan pilihan username terdaftar', str_contains($ok, 'name="edit_account_id"'));

// 4. Simpan kredensial dashboard (username sama, password dikosongkan)
$token = extractToken($ok);
$saved = request($session, $base . '/settings.php', [
    'csrf_token' => $token,
    'action' => 'save',
    'edit_account_id' => 1,
    'username' => 'user',
    'password' => '',
    'password_confirmation' => '',
]);
check('simpan kredensial dari halaman admin berhasil', str_contains($saved, 'berhasil diperbarui'));

// 5. Keluar dari admin mengembalikan ke form login admin
$token = extractToken($saved);
$loggedOut = request($session, $base . '/settings.php', [
    'csrf_token' => $token,
    'action' => 'logout',
]);
check('keluar admin kembali ke form login', str_contains($loggedOut, 'Masuk Pengaturan'));
check('panel kelola tertutup setelah keluar', !str_contains($loggedOut, 'Akun Akses Dashboard'));

// 6. Dashboard tetap butuh login terpisah
$session2 = session('dash');
$dashboard = request($session2, $base . '/dashboard.php');
check('dashboard tetap memerlukan login', str_contains($dashboard, 'Masuk ke Dashboard'));

// 7. Login dashboard user/123 masih berfungsi
$token = extractToken(request($session2, $base . '/login.php'));
$dashOk = request($session2, $base . '/login.php', [
    'csrf_token' => $token,
    'username' => 'user',
    'password' => '123',
]);
check('login dashboard user/123 tetap berfungsi', str_contains($dashOk, 'Ruang Observasi'));

foreach ([$session, $session2] as $handle) {
    curl_close($handle);
}
foreach (glob(sys_get_temp_dir() . '/tgs_adm_*.txt') ?: [] as $file) {
    unlink($file);
}

echo "\n" . (count($failures) === 0 ? 'ALL ADMIN TESTS PASSED' : 'FAILURES: ' . implode(' | ', $failures)) . "\n";
