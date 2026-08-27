<?php
header('Content-Type: application/json');

$scriptPath = realpath(__DIR__ . '/../../desktop_scanner.py');

if (!$scriptPath || !file_exists($scriptPath)) {
    echo json_encode(["success" => false, "error" => "Scanner script not found"]);
    exit;
}

// pythonw completely hides the terminal but still allows the OpenCV camera preview window
$cmd = 'start "" pythonw "' . $scriptPath . '"';
pclose(popen($cmd, "r"));

echo json_encode(["success" => true, "message" => "Scanner launched on the desktop!"]);
