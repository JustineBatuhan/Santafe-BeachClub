<?php
/**
 * notifications_api.php — AJAX endpoint for header notification popover
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../helpers/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$action = $_REQUEST['action'] ?? 'get_recent';

if ($action === 'get_recent') {
    $unread_count = (int)($conn->query("SELECT COUNT(*) as c FROM notifications WHERE is_read = 0")->fetch_assoc()['c'] ?? 0);
    
    $res = $conn->query("SELECT id, title, message, type, is_read, booking_id, created_at FROM notifications ORDER BY id DESC LIMIT 8");
    $items = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            // Calculate time ago
            $time_diff = time() - strtotime($row['created_at']);
            if ($time_diff < 60) {
                $time_ago = 'Just now';
            } elseif ($time_diff < 3600) {
                $time_ago = floor($time_diff / 60) . 'm ago';
            } elseif ($time_diff < 86400) {
                $time_ago = floor($time_diff / 3600) . 'h ago';
            } elseif ($time_diff < 604800) {
                $time_ago = floor($time_diff / 86400) . 'd ago';
            } else {
                $time_ago = date('M j', strtotime($row['created_at']));
            }
            $row['time_ago'] = $time_ago;
            $items[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count,
        'notifications' => $items
    ]);
    exit;
}

if ($action === 'stream') {
    // Disable output buffering
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    @ini_set('zlib.output_compression', '0');
    @ini_set('implicit_flush', '1');
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    ob_implicit_flush(1);

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache, no-transform');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    $lastEventId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? (int)$_SERVER['HTTP_LAST_EVENT_ID'] : (int)($_GET['last_id'] ?? 0);
    $startTime = time();

    while (time() - $startTime < 25) { // Stream for 25 seconds per connection cycle
        $stmt = $conn->prepare("
            SELECT id, title, message, type, is_read, booking_id, created_at 
            FROM notifications 
            WHERE id > ? 
            ORDER BY id ASC
        ");
        $stmt->bind_param("i", $lastEventId);
        $stmt->execute();
        $res = $stmt->get_result();

        $newEvents = false;
        while ($row = $res->fetch_assoc()) {
            $lastEventId = (int)$row['id'];
            echo "id: {$lastEventId}\n";
            echo "event: notification\n";
            echo "data: " . json_encode($row) . "\n\n";
            $newEvents = true;
        }
        $stmt->close();

        // Send unread badge count sync
        $cnt = (int)($conn->query("SELECT COUNT(*) as c FROM notifications WHERE is_read = 0")->fetch_assoc()['c'] ?? 0);
        echo "event: badge_sync\n";
        echo "data: " . json_encode(['unread_count' => $cnt, 'last_id' => $lastEventId]) . "\n\n";

        if ($newEvents) {
            flush();
        }

        sleep(2);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'mark_read' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $unread_count = (int)($conn->query("SELECT COUNT(*) as c FROM notifications WHERE is_read = 0")->fetch_assoc()['c'] ?? 0);
        echo json_encode(['success' => true, 'unread_count' => $unread_count]);
        exit;
    }

    if ($action === 'mark_all_read') {
        $conn->query("UPDATE notifications SET is_read = 1");
        echo json_encode(['success' => true, 'unread_count' => 0]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
