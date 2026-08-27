<?php
require __DIR__ . '/../config/db.php';
$r = $conn->query("SELECT id, room_number, name, type, price_per_night, capacity, status FROM rooms");
echo "Count: " . $r->num_rows . "\n";
while ($row = $r->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
