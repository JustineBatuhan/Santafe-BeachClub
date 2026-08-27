<?php
require 'backend/config/db.php';

// Check OTP tables store only hashes, not plain OTPs
$result = $conn->query("DESCRIBE admin_otps");
echo "=== admin_otps columns ===" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . PHP_EOL;
}

$result2 = $conn->query("DESCRIBE admins");
echo PHP_EOL . "=== admins columns ===" . PHP_EOL;
while ($row = $result2->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . PHP_EOL;
}

// Check if admin passwords are properly hashed (should start with $2y$)
$result3 = $conn->query("SELECT username, LEFT(password, 10) as pw_prefix FROM admins LIMIT 5");
echo PHP_EOL . "=== Admin password hashes (prefix only) ===" . PHP_EOL;
while ($row = $result3->fetch_assoc()) {
    echo $row['username'] . " => " . $row['pw_prefix'] . "..." . PHP_EOL;
}
