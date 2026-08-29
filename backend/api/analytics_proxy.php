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

// ── Try Live Render Python service first (or Local Flask if running) ──────────
if (function_exists('curl_init')) {
    // Check if local Flask is active, else use Live Render URL
    $is_local_dev = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
    
    // Choose Python endpoint
    $python_url = "https://santafe-beachclub-analytics.onrender.com/api/" . urlencode($action);
    if ($is_local_dev) {
        // For local development testing, try local port 5000 first if reachable
        $local_test = @fsockopen('127.0.0.1', 5000, $errno, $errstr, 0.2);
        if ($local_test) {
            fclose($local_test);
            $python_url = "http://127.0.0.1:5000/api/" . urlencode($action);
        }
    }

    $ch = curl_init($python_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'X-API-Key: santafe-super-secret-key-2026'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
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

