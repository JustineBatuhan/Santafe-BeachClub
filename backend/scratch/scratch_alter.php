<?php require __DIR__ . "/../config/db.php"; $conn->query("ALTER TABLE payments ADD COLUMN receipt_url VARCHAR(255) DEFAULT NULL"); echo "Altered\n"; ?>
