<?php
$_GET['action'] = 'executive-stats';
ob_start();
include __DIR__ . '/../api/analytics_api.php';
$out = ob_get_clean();
echo "Executive Stats: " . $out . "\n";

$_GET['action'] = 'status-breakdown';
ob_start();
include __DIR__ . '/../api/analytics_api.php';
$out2 = ob_get_clean();
echo "Status Breakdown: " . $out2 . "\n";
