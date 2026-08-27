<?php
/**
 * security_headers.php — HTTP Security Headers Helper
 * Sets industry standard security headers to prevent XSS, Clickjacking, MIME sniffing, and more.
 */

if (!headers_sent()) {
    // Prevent Clickjacking attacks
    header('X-Frame-Options: SAMEORIGIN');

    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Control Referrer information
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Cross-Site Scripting filter for legacy browsers
    header('X-XSS-Protection: 1; mode=block');

    // Enforce modern permissions policy
    header("Permissions-Policy: camera=(self), microphone=(self), geolocation=()");
}
?>
