<?php
// Enforce strict, secure, and HttpOnly session cookies for token storage
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', // Default domain
    'secure' => false, // Set to true in production over HTTPS
    'httponly' => true, // Prevent JavaScript access to session cookie (mitigates XSS)
    'samesite' => 'Lax' // Mitigate CSRF
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Security Headers and CSRF Helper
require_once __DIR__ . '/security_headers.php';
require_once __DIR__ . '/csrf_helper.php';

$is_api_request = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                   (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                   (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/backend/api/') !== false);

if (isset($_SESSION['mfa_pending_admin_id']) && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    if ($is_api_request) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'MFA verification required', 'redirect' => 'verify_otp']);
        exit;
    }
    header('Location: verify_otp');
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($is_api_request) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required', 'redirect' => 'login']);
        exit;
    }
    header('Location: login');
    exit;
}
?>
