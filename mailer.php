<?php
/**
 * mailer.php
 * Sends booking-related emails via Gmail SMTP using PHPMailer.
 *
 * SETUP REQUIRED:
 * 1. This file expects PHPMailer's source files at: libs/PHPMailer/src/
 *    (PHPMailer.php, SMTP.php, Exception.php)
 * 2. Fill in GMAIL_USER and GMAIL_APP_PASSWORD below.
 *    GMAIL_APP_PASSWORD must be a Gmail "App Password" (16 chars, no spaces),
 *    NOT your normal Gmail login password. Generate one at:
 *    https://myaccount.google.com/apppasswords
 *    (Requires 2-Step Verification to be enabled on the Gmail account.)
 */

require_once __DIR__ . '/libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ---- Gmail SMTP credentials (fill these in) ----
define('GMAIL_USER', 'Justinebatuhan017@gmail.com');
define('GMAIL_APP_PASSWORD', 'owxi hskd qzlq nczl'); // 16-char App Password, spaces are fine
define('MAIL_FROM_NAME', 'Santa Fe Beach Club');

/**
 * Sends a booking confirmation email to the guest.
 *
 * @param string $to_email    Guest email address
 * @param string $guest_name  Guest full name
 * @param string $booking_ref Booking reference (e.g. REF-001)
 * @param string $room_name   Accommodation name
 * @param string $check_in    Check-in date (Y-m-d)
 * @param string $check_out   Check-out date (Y-m-d)
 * @param float  $total_amount Total booking amount
 * @param string|null $cancellation_url Optional self-service cancellation link
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendBookingConfirmationEmail(
    ?string $to_email,
    string $guest_name,
    string $booking_ref,
    string $room_name,
    string $check_in,
    string $check_out,
    float $total_amount,
    ?string $cancellation_url = null
): array {
    if (empty($to_email)) {
        return ['success' => false, 'error' => 'No email address provided for this guest.'];
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'Justinebatuhan017@gmail.com';
        $mail->Password   = 'owxi hskd qzlq nczl';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom(GMAIL_USER, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $guest_name);

        // Content
        $checkin_fmt  = date('F j, Y', strtotime($check_in));
        $checkout_fmt = date('F j, Y', strtotime($check_out));
        $amount_fmt   = number_format($total_amount, 2);
        $cancel_section = '';
        if ($cancellation_url) {
            $cancel_section = "<p>If you need to cancel your booking, use this secure link: <a href='" . htmlspecialchars($cancellation_url) . "'>" . htmlspecialchars($cancellation_url) . "</a></p>";
        }

        $mail->isHTML(true);
        $mail->Subject = "Booking Confirmed – {$booking_ref} – Santa Fe Beach Club";
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;'>
                <h2 style='color:#0ea5e9;'>Your booking is confirmed!</h2>
                <p>Hi " . htmlspecialchars($guest_name) . ",</p>
                <p>Great news — your reservation at <strong>Santa Fe Beach Club</strong> has been confirmed.</p>
                <table style='width:100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr><td style='padding:6px 0; color:#777;'>Booking Reference</td><td style='padding:6px 0; text-align:right; font-weight:bold;'>" . htmlspecialchars($booking_ref) . "</td></tr>
                    <tr><td style='padding:6px 0; color:#777;'>Room</td><td style='padding:6px 0; text-align:right;'>" . htmlspecialchars($room_name) . "</td></tr>
                    <tr><td style='padding:6px 0; color:#777;'>Check-in</td><td style='padding:6px 0; text-align:right;'>{$checkin_fmt}</td></tr>
                    <tr><td style='padding:6px 0; color:#777;'>Check-out</td><td style='padding:6px 0; text-align:right;'>{$checkout_fmt}</td></tr>
                    <tr><td style='padding:6px 0; color:#777;'>Total Amount</td><td style='padding:6px 0; text-align:right; font-weight:bold;'>₱ {$amount_fmt}</td></tr>
                </table>
                <p>Please keep your booking reference and QR code handy for check-in.</p>
                {$cancel_section}
                <p style='color:#999; font-size:12px; margin-top:30px;'>See you soon at Santa Fe Beach Club!</p>
            </div>
        ";
        $mail->AltBody = "Hi {$guest_name}, your booking {$booking_ref} at Santa Fe Beach Club is confirmed. "
            . "Room: {$room_name}. Check-in: {$checkin_fmt}. Check-out: {$checkout_fmt}. Total: PHP {$amount_fmt}.";
        if ($cancellation_url) {
            $mail->AltBody .= " Cancel here: {$cancellation_url}.";
        }

        $mail->send();
        return ['success' => true, 'error' => null];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Sends a booking cancellation email to the guest.
 */
function sendBookingCancellationEmail(
    string $to_email,
    string $guest_name,
    string $booking_ref,
    string $room_name,
    string $check_in,
    string $check_out
): array {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USER;
        $mail->Password   = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom(GMAIL_USER, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $guest_name);

        $checkin_fmt  = date('F j, Y', strtotime($check_in));
        $checkout_fmt = date('F j, Y', strtotime($check_out));

        $mail->isHTML(true);
        $mail->Subject = "Booking Cancelled – {$booking_ref} – Santa Fe Beach Club";
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;'>
                <h2 style='color:#dc2626;'>Your booking has been cancelled</h2>
                <p>Hi " . htmlspecialchars($guest_name) . ",</p>
                <p>Your reservation at <strong>Santa Fe Beach Club</strong> has been cancelled successfully.</p>
                <ul>
                    <li><strong>Booking Reference:</strong> " . htmlspecialchars($booking_ref) . "</li>
                    <li><strong>Room:</strong> " . htmlspecialchars($room_name) . "</li>
                    <li><strong>Check-in:</strong> {$checkin_fmt}</li>
                    <li><strong>Check-out:</strong> {$checkout_fmt}</li>
                </ul>
                <p>If this was a mistake, please contact the front desk to rebook.</p>
            </div>
        ";
        $mail->AltBody = "Hi {$guest_name}, your booking {$booking_ref} at Santa Fe Beach Club has been cancelled. Room: {$room_name}. Check-in: {$checkin_fmt}. Check-out: {$checkout_fmt}.";

        $mail->send();
        return ['success' => true, 'error' => null];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}
