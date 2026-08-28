<?php
/**
 * analytics_proxy.php
 * PHP → Python Analytics Proxy
 *
 * The frontend calls this PHP file exactly like the old analytics_api.php.
 * This file forwards the request to the Python Flask service running on port 5000,
 * returns the response as JSON, and falls back to the native PHP logic if Python is down.
 *
 * Usage: Same as analytics_api.php — ?action=executive-stats etc.
 */

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

// ── Try Python Flask service first ────────────────────────────────────────────
$python_url = "http://127.0.0.1:5000/api/{$action}";

$ch = curl_init($python_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 3,           // Wait max 3 seconds for Python
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_FAILONERROR    => false,
]);
$python_response = curl_exec($ch);
$http_code       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error      = curl_error($ch);
curl_close($ch);

// If Python responded successfully, return its result directly
if ($python_response !== false && $http_code === 200) {
    echo $python_response;
    exit;
}

// ── Fallback: Native PHP analytics (Python is offline) ───────────────────────
// Log the fallback for visibility (optional — remove if noisy)
error_log("[analytics_proxy] Python service unavailable (action={$action}, err={$curl_error}). Using PHP fallback.");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/room_status_helper.php';

// Delegate to the original PHP analytics API
require __DIR__ . '/analytics_api.php';
