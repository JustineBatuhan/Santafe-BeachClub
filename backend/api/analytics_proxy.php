<?php
/**
 * analytics_proxy.php
 * PHP → Python Analytics Proxy
 *
 * The frontend calls this PHP file for all analytics requests.
 * It forwards the request to the Python Flask service running on port 5000 with the required API Key,
 * returns the Python response as JSON, and falls back to native PHP logic if Python is offline.
 */

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

// ── Try Python Flask service first ────────────────────────────────────────────
if (function_exists('curl_init')) {
    $python_url = "http://127.0.0.1:5000/api/" . urlencode($action);

    $ch = curl_init($python_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 2,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'X-API-Key: santafe-super-secret-key-2026'
        ],
        CURLOPT_FAILONERROR    => false,
    ]);
    $python_response = curl_exec($ch);
    $http_code       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // If Python responded successfully (200 OK), return Python output directly
    if ($python_response !== false && $http_code === 200) {
        echo $python_response;
        exit;
    }
}

// ── Fallback: Native PHP analytics (if Python service is not running) ─────────
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/room_status_helper.php';

// Delegate to PHP analytics API
require __DIR__ . '/analytics_api.php';

