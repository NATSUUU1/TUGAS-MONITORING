<?php
declare(strict_types=1);

require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/settings_auth.php';
require __DIR__ . '/config/credentials.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$isAdmin = settingsAdminIsAuthenticated();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    $action = (string) ($_POST['action'] ?? '');

    if (!dashboardCsrfIsValid($submittedToken)) {
        dashboardFlash('error', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        header('Location: settings.php');
        exit;
    }

    if ($action === 'logout') {
        settingsAdminLogout();
        header('Location: settings.php');
        exit;
    }

    if (!$isAdmin) {
        $adminUsername = trim((string) ($_POST['username'] ?? ''));
        $adminPassword = (string) ($_POST['password'] ?? '');

        if (settingsAdminLogin($adminUsername, $adminPassword)) {
            header('Location: settings.php');
            exit;
        }

        dashboardFlash('error', 'Username atau password admin tidak sesuai.');
        dashboardRememberInput(['username' => $adminUsername]);
        header('Location: settings.php');
        exit;
    }

    if ($action === 'create_account') {
        $newUsername = trim((string) ($_POST['new_username'] ?? ''));
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newConfirmation = (string) ($_POST['new_password_confirmation'] ?? '');

        $errors = dashboardNewAccountInputErrors($newUsername, $newPassword, $newConfirmation);

        if ($errors !== []) {
            dashboardFlash('error', implode(' ', $errors));
            dashboardRememberInput(['new_username' => $newUsername]);
        } else {
            $created = dashboardCreateAccount($pdo, $newUsername, $newPassword);
            if ($created) {
                $_SESSION['dashboard_csrf_token'] = bin2hex(random_bytes(32));
                dashboardFlash('success', 'Akun "' . $newUsername . '" berhasil ditambahkan.');
            } else {
                dashboardFlash('error', 'Username "' . $newUsername . '" sudah digunakan.');
                dashboardRememberInput(['new_username' => $newUsername]);
            }
        }

        header('Location: settings.php');
        exit;
    }

    if ($action === 'delete_account') {
        $deleteId = (int) ($_POST['account_id'] ?? 0);

        if ($deleteId > 0) {
            $deleted = dashboardDeleteAccount($pdo, $deleteId);
            if ($deleted) {
                dashboardFlash('success', 'Akun berhasil dihapus.');
            } else {
                dashboardFlash('error', 'Minimal harus ada satu akun tersisa.');
            }
        }
        header('Location: settings.php');
        exit;
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    $editId = (int) ($_POST['edit_account_id'] ?? 0);

    $account = $editId > 0 ? dashboardAccountById($pdo, $editId) : null;
    $accountId = (int) ($account['id'] ?? 0);

    $errors = dashboardAccountInputErrors($username, $password, $confirmation);
    if ($errors !== []) {
        dashboardFlash('error', implode(' ', $errors));
        dashboardRememberInput(['username' => $username, 'edit_account_id' => $editId]);
    } else {
        if ($password === '') {
            dashboardUpdateUsername($pdo, $username, $accountId);
        } else {
            dashboardUpdateCredentials($pdo, $username, $password, $accountId);
        }
        $_SESSION['dashboard_csrf_token'] = bin2hex(random_bytes(32));
        dashboardFlash('success', 'Akses login berhasil diperbarui.');
    }

    header('Location: settings.php' . ($editId > 0 ? '?edit_account_id=' . $editId : ''));
    exit;
}

$flash = dashboardPullFlash();
$oldInput = dashboardPullInput();

$pageTitle = 'Pengaturan';
$activePage = 'pengaturan';
$introAnimation = !$isAdmin; // animasi hanya pada halaman login pengaturan
require __DIR__ . '/partials/header.php';
?>

<?php if (!$isAdmin): ?>
    <main class="settings-page">
        <div class="settings-login">
            <section class="login-card">
                <div class="login-stamp" aria-hidden="true">管</div>
                <span class="eyebrow">AKSES ADMIN</span>
                <h1>Masuk Pengaturan</h1>
                <p>Halaman ini khusus admin. Masukkan username dan password admin untuk mengelola akses login.</p>

                <?php if ($flash !== null): ?>
                    <div class="settings-alert <?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="alert">
                        <?= htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="post" action="settings.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(dashboardCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="login">

                    <label for="admin_username">Username admin</label>
                    <input id="admin_username" name="username" type="text" autocomplete="username" value="<?= htmlspecialchars((string) ($oldInput['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="admin_password">Password admin</label>
                    <input id="admin_password" name="password" type="password" autocomplete="current-password" required>

                    <button type="submit">Masuk</button>
                </form>

                <a class="login-home-button" href="index.php">← Kembali ke Beranda</a>
            </section>
        </div>
    </main>
<?php else: ?>
    <?php
    $accounts = dashboardAllAccounts($pdo);
    $totalAccounts = count($accounts);
    $editAccountId = (int) ($_GET['edit_account_id'] ?? $oldInput['edit_account_id'] ?? 0);
    $account = $editAccountId > 0 ? dashboardAccountById($pdo, $editAccountId) : dashboardAccount($pdo);
    $currentUsername = (string) ($account['username'] ?? '');
    $usernameValue = (string) ($oldInput['username'] ?? $currentUsername);
    ?>
    <main class="settings-page">
        <div class="page-heading">
            <div>
                <span class="eyebrow">KONFIGURASI AKSES</span>
                <h1>Pengaturan</h1>
                <p>Kelola daftar akun yang dapat mengakses halaman dashboard.</p>
            </div>
            <div class="dashboard-actions">
                <span class="live-badge"><i></i> Admin: <?= htmlspecialchars(SETTINGS_ADMIN_USERNAME, ENT_QUOTES, 'UTF-8') ?></span>
                <button class="logout-button" type="button" data-open-logout>Keluar</button>
            </div>
        </div>

        <dialog class="logout-dialog" aria-labelledby="logout-confirmation-title">
            <form method="post" action="settings.php">
                <span class="eyebrow">KONFIRMASI</span>
                <h2 id="logout-confirmation-title">Apakah Anda yakin akan keluar?</h2>
                <p>Pilih “YA” untuk keluar atau “TIDAK” untuk tetap di halaman pengaturan.</p>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(dashboardCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="logout">
                <div class="logout-dialog-actions">
                    <button class="logout-cancel" type="button" data-cancel-logout>TIDAK</button>
                    <button class="logout-confirm" type="submit">YA</button>
                </div>
            </form>
        </dialog>

        <?php if ($flash !== null): ?>
            <p class="settings-alert <?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="alert" style="padding: 12px; margin-bottom: 20px; background: rgba(129,199,132,0.15); border: 1px solid #81c784; font-size: 13px;">
                <?= htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <section class="settings-panel accounts-panel">
            <div class="settings-panel-heading">
                <div>
                    <h2>Daftar Akun Terdaftar</h2>
                    <p>Total <strong><?= $totalAccounts ?></strong> akun yang dapat mengakses dashboard.</p>
                </div>
                <span class="live-badge"><i></i> <?= $totalAccounts ?> akun aktif</span>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><small>番</small>No.</th>
                            <th><small>名前</small>Username</th>
                            <th><small>作成</small>Dibuat</th>
                            <th><small>更新</small>Diperbarui</th>
                            <th><small>操作</small>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $index => $acc): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><span class="account-username"><?= htmlspecialchars((string) $acc['username'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= date('d M Y, H:i', strtotime((string) $acc['created_at'])) ?></td>
                                <td><?= date('d M Y, H:i', strtotime((string) $acc['updated_at'])) ?></td>
                                <td>
                                    <a class="btn-edit" href="settings.php?edit_account_id=<?= (int) $acc['id'] ?>#edit-account">Ubah</a>
                                    <?php if ($totalAccounts > 1): ?>
                                        <form method="post" action="settings.php" class="inline-form" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus akun ini?')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(dashboardCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="delete_account">
                                            <input type="hidden" name="account_id" value="<?= (int) $acc['id'] ?>">
                                            <button type="submit" class="btn-delete" title="Hapus">✕</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="settings-panel add-account-panel">
            <div class="settings-panel-heading">
                <div>
                    <h2>Tambah Akun Baru</h2>
                    <p>Buat akun baru agar user lain dapat mengakses dashboard.</p>
                </div>
            </div>

            <form class="settings-form" method="post" action="settings.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(dashboardCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="create_account">

                <div class="settings-field">
                    <label for="new_username">Username</label>
                    <input id="new_username" name="new_username" type="text" value="<?= htmlspecialchars((string) ($oldInput['new_username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="settings-field">
                    <label for="new_password">Password</label>
                    <input id="new_password" name="new_password" type="password" required>
                </div>

                <div class="settings-field">
                    <label for="new_password_confirmation">Konfirmasi Password</label>
                    <input id="new_password_confirmation" name="new_password_confirmation" type="password" required>
                </div>

                <button type="submit">+ Tambah Akun</button>
            </form>
        </section>

        <section class="settings-panel" id="edit-account">
            <div class="settings-panel-heading">
                <div>
                    <h2>Edit Akun Akses Dashboard</h2>
                    <p>Pilih username yang ingin diubah, lalu isi password baru.</p>
                </div>
            </div>

            <form class="settings-form" method="post" action="settings.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(dashboardCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="save">

                <div class="settings-field">
                    <label for="edit_account_id">Pilih Akun</label>
                    <select id="edit_account_id" name="edit_account_id" required>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= (int) $acc['id'] ?>" data-username="<?= htmlspecialchars((string) $acc['username'], ENT_QUOTES, 'UTF-8') ?>" <?= (int) $acc['id'] === $editAccountId ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $acc['username'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="settings-field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="<?= htmlspecialchars($usernameValue, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="settings-field">
                    <label for="password">Password baru (opsional)</label>
                    <input id="password" name="password" type="password">
                </div>

                <div class="settings-field">
                    <label for="password_confirmation">Konfirmasi password baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password">
                </div>

                <button type="submit">Simpan Perubahan</button>
            </form>
        </section>
        
        <script>
            const accountSelect = document.getElementById('edit_account_id');
            if (accountSelect) {
                accountSelect.addEventListener('change', function () {
                    const selectedAccount = accountSelect.options[accountSelect.selectedIndex];
                    document.getElementById('username').value = selectedAccount.dataset.username;
                });
            }
        </script>
    </main>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
