<?php
require __DIR__ . '/../config/db.php';
// Set Justinebatuhan017@gmail.com as the initial MFA delivery email for admin and Justine accounts
$stmt = $conn->prepare("UPDATE admins SET email = 'Justinebatuhan017@gmail.com' WHERE username IN ('admin@beachclub.com', 'Justine@beachclub.com')");
$stmt->execute();
echo "Updated " . $stmt->affected_rows . " admin accounts with default email Justinebatuhan017@gmail.com\n";
