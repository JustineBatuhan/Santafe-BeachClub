<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

if ($q === '') {
    echo json_encode([]);
    exit;
}

$like = '%' . $q . '%';
$items = [];

// 1. Search bookings
$booking_stmt = $conn->prepare("
    SELECT id, guest_name, accommodation_name, check_in, status 
    FROM bookings 
    WHERE guest_name LIKE ? OR accommodation_name LIKE ? OR CAST(id AS CHAR) LIKE ?
    ORDER BY id DESC 
    LIMIT 10
");
$booking_stmt->bind_param("sss", $like, $like, $like);
if ($booking_stmt->execute()) {
    $res = $booking_stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $items[] = [
            'type'    => 'booking',
            'id'      => $r['id'],
            'name'    => $r['guest_name'],
            'sub'     => $r['accommodation_name'] . ' · Check-in: ' . date('M j, Y', strtotime($r['check_in'])),
            'status'  => $r['status'],
            'url'     => 'admin_reservations.php',
        ];
    }
}
$booking_stmt->close();

// 2. Search rooms
$room_stmt = $conn->prepare("
    SELECT room_number, name, type, status 
    FROM rooms 
    WHERE name LIKE ? OR type LIKE ? OR room_number LIKE ?
    ORDER BY room_number ASC 
    LIMIT 10
");
$room_stmt->bind_param("sss", $like, $like, $like);
if ($room_stmt->execute()) {
    $res = $room_stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $items[] = [
            'type'    => 'room',
            'id'      => $r['room_number'],
            'name'    => $r['name'],
            'sub'     => ucwords(str_replace('_', ' ', $r['type'])),
            'status'  => $r['status'],
            'url'     => 'accommodations.php',
        ];
    }
}
$room_stmt->close();

echo json_encode($items);
