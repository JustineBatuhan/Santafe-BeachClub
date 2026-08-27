<?php require __DIR__ . "/../config/db.php"; $res = $conn->query("SHOW CREATE TABLE payments"); $row = $res->fetch_row(); echo $row[1]; ?>
