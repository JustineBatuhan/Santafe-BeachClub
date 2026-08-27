<?php
/**
 * guest_auth_helper.php
 * Manages guest portal session state.
 * Guests are verified by email + booking reference (no password).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Store a verified guest booking in session after a successful lookup.
 */
function set_guest_booking(array $booking_data): void {
    $_SESSION['guest_booking'] = $booking_data;
    $_SESSION['guest_booking_verified_at'] = time();
}

/**
 * Retrieve the current guest's booking from session.
 * Returns null if not set or session has expired (30 min idle).
 */
function get_guest_booking(): ?array {
    $timeout = 1800; // 30 minutes
    if (
        isset($_SESSION['guest_booking'], $_SESSION['guest_booking_verified_at']) &&
        (time() - $_SESSION['guest_booking_verified_at']) < $timeout
    ) {
        // Refresh the timestamp on every access (sliding expiry)
        $_SESSION['guest_booking_verified_at'] = time();
        return $_SESSION['guest_booking'];
    }
    return null;
}

/**
 * Clear the guest booking session (logout).
 */
function clear_guest_booking(): void {
    unset($_SESSION['guest_booking'], $_SESSION['guest_booking_verified_at']);
}

/**
 * Guard: redirect to my_booking lookup form if guest has not authenticated.
 */
function require_guest_lookup(string $redirect_to = 'my_booking'): void {
    if (get_guest_booking() === null) {
        header("Location: {$redirect_to}");
        exit;
    }
}
