<?php
require_once __DIR__ . '/../config/db.php';

// Script to automatically recognize revenue (mark as earned) for bookings
// that have passed their check-out date but were missed in manual checkout.

echo "Running Revenue Recognition Sweep...\n";

// Find bookings that are past checkout date, paid/verified, but not checked out manually
$query = "SELECT p.id, b.id as booking_id FROM payments p 
          JOIN bookings b ON p.booking_id = b.id
          WHERE p.status = 'verified' 
          AND p.accounting_status = 'deferred'
          AND b.check_out < CURDATE()";

$result = $conn->query($query);

$count = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $payment_id = $row['id'];
        
        // Update to earned
        $updateStmt = $conn->prepare("UPDATE payments SET accounting_status = 'earned' WHERE id = ?");
        $updateStmt->bind_param("i", $payment_id);
        $updateStmt->execute();
        $count++;
    }
}

echo "Swept $count payments to 'earned' status.\n";
?>
