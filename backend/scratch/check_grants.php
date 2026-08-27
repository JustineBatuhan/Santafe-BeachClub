<?php
require 'backend/config/db.php';
$result = $conn->query('SHOW GRANTS FOR CURRENT_USER()');
while ($row = $result->fetch_row()) {
    echo $row[0] . PHP_EOL;
}
