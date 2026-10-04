<?php
declare(strict_types=1);

require __DIR__ . '/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !dashboardCsrfIsValid((string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Permintaan keluar tidak valid.');
}

dashboardEndSession();
header('Location: index.php');
exit;
