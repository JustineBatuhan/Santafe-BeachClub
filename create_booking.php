<?php

require_once __DIR__ . '/booking_service.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPdoConnection();

    $result = createBooking($pdo, [
        'room_type_id' => $_POST['room_type_id'] ?? null,
        'check_in' => $_POST['check_in'] ?? null,
        'check_out' => $_POST['check_out'] ?? null,
        'guest_name' => $_POST['guest_name'] ?? null,
        'guest_email' => $_POST['guest_email'] ?? null,
        'status' => $_POST['status'] ?? 'pending',
    ]);

    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
