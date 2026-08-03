<?php

function sf_get_checked_in_room_ids(mysqli $conn): array
{
    $room_ids = [];
    $result = $conn->query("SELECT DISTINCT room_id FROM bookings WHERE status = 'Checked In' AND room_id IS NOT NULL");

    if (!$result) {
        return $room_ids;
    }

    while ($row = $result->fetch_assoc()) {
        $room_ids[(int)$row['room_id']] = true;
    }

    return $room_ids;
}

function sf_room_has_checked_in_booking(mysqli $conn, int $room_id): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM bookings WHERE room_id = ? AND status = 'Checked In' LIMIT 1");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result && $result->num_rows > 0;
}

function sf_resolve_room_display_status(array $room, array $occupied_room_ids): string
{
    if (($room['status'] ?? '') === 'maintenance') {
        return 'maintenance';
    }

    $room_id = isset($room['id']) ? (int)$room['id'] : 0;
    if ($room_id > 0 && isset($occupied_room_ids[$room_id])) {
        return 'occupied';
    }

    return 'ready';
}

function sf_room_status_label(string $status): string
{
    switch ($status) {
        case 'occupied':
            return 'Occupied';
        case 'maintenance':
            return 'Maintenance';
        default:
            return 'Available';
    }
}
