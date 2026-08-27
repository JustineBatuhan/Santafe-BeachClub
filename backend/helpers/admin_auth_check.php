<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security_headers.php';
require_once __DIR__ . '/csrf_helper.php';

if (isset($_SESSION['mfa_pending_admin_id']) && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    header('Location: verify_otp');
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    header('Location: login');
    exit;
}
?>
