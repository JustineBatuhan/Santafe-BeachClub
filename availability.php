<?php

require_once __DIR__ . '/booking_service.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $roomTypeId = isset($_GET['room_type_id']) ? (int) $_GET['room_type_id'] : 0;
    $checkIn = $_GET['check_in'] ?? '';
    $checkOut = $_GET['check_out'] ?? '';

    if ($roomTypeId <= 0 || $checkIn === '' || $checkOut === '') {
        http_response_code(400);
        echo json_encode(['error' => 'room_type_id, check_in, and check_out are required.']);
        exit;
    }

    if (!isValidDateRange($checkIn, $checkOut)) {
        http_response_code(400);
        echo json_encode(['error' => 'check_out must be later than check_in.']);
        exit;
    }

    $pdo = getPdoConnection();
    $available = getAvailableRooms($pdo, $roomTypeId, $checkIn, $checkOut);

    echo json_encode([
        'available' => $available,
        'low_stock' => $available >= 1 && $available <= 3,
        'sold_out' => $available === 0,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
