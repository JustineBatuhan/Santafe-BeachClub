<?php
$start = microtime(true);
require 'backend/config/db.php';
$duration = round((microtime(true) - $start) * 1000, 2);
echo "db.php took: {$duration} ms\n";
