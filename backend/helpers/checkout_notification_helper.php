<?php
require_once __DIR__ . '/business_time_helper.php';

function sf_get_checkout_time_setting(mysqli $conn): string
{
    $default_checkout_time = '12:00';

    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'checkout_time' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $checkout_time = trim((string)($row['setting_value'] ?? $default_checkout_time));
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $checkout_time)) {
        return $default_checkout_time;
    }

    return $checkout_time;
}

function sf_create_due_checkout_notifications(mysqli $conn): void
{
    $checkout_time = sf_get_checkout_time_setting($conn);
    $current_business_time = sf_get_current_business_datetime($conn);
    $property_timezone = $current_business_time->getTimezone();
    $property_timezone_name = $property_timezone->getName();
    $notification_timestamp = $current_business_time->format('Y-m-d H:i:s');

    $due_stmt = $conn->prepare("
        SELECT id, guest_name, accommodation_name, check_out
        FROM bookings
        WHERE status = 'Checked In'
          AND checkout_notified_at IS NULL
        ORDER BY check_out ASC, id ASC
    ");
    $due_stmt->execute();
    $due_result = $due_stmt->get_result();

    if (!$due_result || $due_result->num_rows === 0) {
        $due_stmt->close();
        return;
    }

    $mark_stmt = $conn->prepare("
        UPDATE bookings
        SET checkout_notified_at = ?
        WHERE id = ?
          AND status = 'Checked In'
          AND checkout_notified_at IS NULL
    ");
    $notif_stmt = $conn->prepare("
        INSERT INTO notifications (title, message, type, booking_id, created_at)
        VALUES (?, ?, ?, ?, ?)
    ");

    while ($booking = $due_result->fetch_assoc()) {
        $booking_id = (int)$booking['id'];
        $checkout_date = (string)$booking['check_out'];
        $due_checkout_at = sf_get_due_checkout_datetime($checkout_date, $checkout_time, $property_timezone);

        if ($current_business_time < $due_checkout_at) {
            continue;
        }

        $mark_stmt->bind_param("si", $notification_timestamp, $booking_id);
        $mark_stmt->execute();

        if ($mark_stmt->affected_rows !== 1) {
            continue;
        }

        $guest_name = (string)$booking['guest_name'];
        $accommodation_name = (string)$booking['accommodation_name'];

        $notif_title = 'Guest Due for Check-out';
        $notif_type = 'warning';
        $notif_message = $guest_name . ' is scheduled to check out on ' . $checkout_date . ' at ' . $checkout_time . ' ' . $property_timezone_name . ' (' . $accommodation_name . '). Please process departure.';

        $notif_stmt->bind_param("sssis", $notif_title, $notif_message, $notif_type, $booking_id, $notification_timestamp);
        $notif_stmt->execute();
    }

    $notif_stmt->close();
    $mark_stmt->close();
    $due_stmt->close();
}

function sf_get_due_checkout_datetime(string $checkout_date, string $checkout_time, DateTimeZone $property_timezone): DateTimeImmutable
{
    $due_checkout_at = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $checkout_date . ' ' . $checkout_time, $property_timezone);
    if ($due_checkout_at === false) {
        throw new RuntimeException("Invalid checkout schedule for checkout date {$checkout_date}.");
    }

    return $due_checkout_at;
}

function sf_is_due_for_checkout(string $checkout_date, string $checkout_time, DateTimeZone $property_timezone, DateTimeImmutable $current_business_time): bool
{
    return $current_business_time >= sf_get_due_checkout_datetime($checkout_date, $checkout_time, $property_timezone);
}
