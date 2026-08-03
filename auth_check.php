<?php
// Enforce strict, secure, and HttpOnly session cookies for token storage
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', // Default domain
    'secure' => true, // Only send over HTTPS (requires SSL)
    'httponly' => true, // Prevent JavaScript access to session cookie (mitigates XSS)
    'samesite' => 'Lax' // Mitigate CSRF
]);
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
