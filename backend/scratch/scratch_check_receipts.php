<?php
require __DIR__ . '/../config/db.php';
$r = $conn->query("SELECT id, booking_id, receipt_url, payment_method FROM payments ORDER BY id DESC LIMIT 5");
while ($row = $r->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
