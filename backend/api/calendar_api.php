<?php
/**
 * calendar_api.php — JSON endpoint for the Availability Calendar.
 * Returns all bookings within the requested date range.
 *
 * Query params:
 *   start  (Y-m-d) — range start (defaults to today - 7 days)
 *   end    (Y-m-d) — range end   (defaults to today + 30 days)
 */
require_once __DIR__ . '/../helpers/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/cors_helper.php';
require_once __DIR__ . '/../helpers/rate_limiter.php';
require_once __DIR__ . '/../helpers/validator_helper.php';

handle_cors();
RateLimiter::enforce($conn, 'calendar_api', 120, 60);

header('Content-Type: application/json; charset=utf-8');

$start = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
$end   = $_GET['end']   ?? date('Y-m-d', strtotime('+30 days'));

// ── Validate dates ──────────────────────────────────────────────────────────
if (!Validator::validateDate($start) || !Validator::validateDate($end)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use Y-m-d.']);
    exit;
}

// ── Fetch rooms ─────────────────────────────────────────────────────────────
$rooms = [];
$roomResult = $conn->query("SELECT id, room_number, name, type, price_per_night, capacity, status FROM rooms ORDER BY type, room_number");
if ($roomResult) {
    while ($r = $roomResult->fetch_assoc()) {
        $rooms[] = [
            'id'         => (int)$r['id'],
            'number'     => $r['room_number'],
            'name'       => $r['name'],
            'type'       => $r['type'],
            'type_label' => ucwords(str_replace('_', ' ', $r['type'])),
            'price'      => (float)$r['price_per_night'],
            'capacity'   => (int)$r['capacity'],
            'status'     => $r['status'],
        ];
    }
}

// ── Fetch bookings in the date range ────────────────────────────────────────
$bookings = [];
$stmt = $conn->prepare("
    SELECT 
        b.id,
        b.guest_name,
        b.guest_email,
        b.room_id,
        b.accommodation_name,
        b.check_in,
        b.check_out,
        b.status,
        b.guests_count,
        b.eta,
        r.name       AS room_name,
        r.room_number AS room_number,
        r.type       AS room_type
    FROM bookings b
    LEFT JOIN rooms r ON r.id = b.room_id
    WHERE b.check_in <= ? 
      AND b.check_out >= ?
      AND b.room_id IS NOT NULL
    ORDER BY b.check_in ASC
");
$stmt->bind_param("ss", $end, $start);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $bookings[] = [
        'id'           => (int)$row['id'],
        'guest_name'   => $row['guest_name'],
        'guest_email'  => $row['guest_email'] ?? '',
        'room_id'      => (int)$row['room_id'],
        'room_name'    => $row['room_name'] ?? $row['accommodation_name'],
        'room_number'  => $row['room_number'] ?? '',
        'room_type'    => $row['room_type'] ?? '',
        'check_in'     => $row['check_in'],
        'check_out'    => $row['check_out'],
        'status'       => $row['status'],
        'guests_count' => (int)$row['guests_count'],
        'eta'          => $row['eta'] ?? '14:00',
    ];
}
$stmt->close();

echo json_encode([
    'rooms'    => $rooms,
    'bookings' => $bookings,
    'range'    => ['start' => $start, 'end' => $end],
]);
