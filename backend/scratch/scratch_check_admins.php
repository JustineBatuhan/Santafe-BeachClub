<?php
require __DIR__ . '/../config/db.php';
$r = $conn->query("SELECT id, username, email, role FROM admins");
while ($row = $r->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
