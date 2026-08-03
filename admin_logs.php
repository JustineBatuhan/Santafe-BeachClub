<?php
require_once 'admin_auth_check.php';
require_once 'db.php';

$admin = $_SESSION['admin_username'];

// Filters
$filter_user   = trim($_GET['user'] ?? '');
$filter_action = trim($_GET['action_type'] ?? '');
$filter_date   = trim($_GET['date'] ?? '');

$where = [];
if ($filter_user)   $where[] = "admin_username LIKE '%" . $conn->real_escape_string($filter_user) . "%'";
if ($filter_action) $where[] = "action LIKE '%" . $conn->real_escape_string($filter_action) . "%'";
if ($filter_date)   $where[] = "DATE(created_at) = '" . $conn->real_escape_string($filter_date) . "'";
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$per_page   = 30;
$page_num   = max(1, (int)($_GET['p'] ?? 1));
$offset     = ($page_num - 1) * $per_page;
$total_rows = (int)$conn->query("SELECT COUNT(*) AS c FROM activity_logs $where_sql")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_rows / $per_page));

$logs = $conn->query("SELECT admin_username, action, details, ip_address, created_at FROM activity_logs $where_sql ORDER BY id DESC LIMIT $per_page OFFSET $offset");

// Handle clear logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_logs') {
    $conn->query("DELETE FROM activity_logs");
    log_activity($conn, $admin, 'Logs Cleared', 'Activity log table was cleared');
    header('Location: admin_logs.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs — Santa Fe Beach Club</title>
    <link rel="stylesheet" href="admin.css?v=2">
    <link rel="stylesheet" href="dashboard.css?v=2">
</head>
<body>
    <?php $active_page = 'logs'; include '_sidebar.php'; ?>

    <main class="main-content">
        <?php
        $page_title = 'Activity Logs';
        $page_subtitle = number_format($total_rows).' total entries. Page '.$page_num.' of '.$total_pages.'.';
        $header_extra_html = '
            <form method="POST" onsubmit="return confirm(\'Clear all activity logs? This cannot be undone.\')">  
                <input type="hidden" name="action" value="clear_logs">
                <button type="submit" style="cursor:pointer;border:1px solid #FCA5A5;color:#DC2626;background:none;padding:7px 14px;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:6px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                    Clear Logs
                </button>
            </form>
        ';
        include '_page_header.php';
        ?>

        <!-- Filters -->
        <form method="GET" class="filter-bar">
            <input type="text" name="user" placeholder="Filter by username" value="<?php echo htmlspecialchars($filter_user); ?>">
            <input type="text" name="action_type" placeholder="Filter by action" value="<?php echo htmlspecialchars($filter_action); ?>">
            <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
            <button type="submit" class="btn-primary">Filter</button>
            <a href="admin_logs.php" class="btn-secondary">Clear</a>
        </form>

        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($logs->num_rows === 0): ?>
                <tr><td colspan="5"><div class="empty-state"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><p>No activity logs found.</p></div></td></tr>
                <?php else: ?>
                <?php while ($log = $logs->fetch_assoc()):
                    $dot = 'default';
                    if (stripos($log['action'], 'login')   !== false) $dot = 'login';
                    if (stripos($log['action'], 'booking') !== false) $dot = 'booking';
                    if (stripos($log['action'], 'payment') !== false) $dot = 'payment';
                    $dotColors = ['login'=>'#10B981','booking'=>'#3B82F6','payment'=>'#F59E0B','default'=>'#94A3B8'];
                ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:12.5px;white-space:nowrap;"><?php echo date('M j, Y g:i a', strtotime($log['created_at'])); ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:7px;height:7px;border-radius:50%;background:<?php echo $dotColors[$dot]; ?>;flex-shrink:0;"></div>
                            <span style="font-weight:600;"><?php echo htmlspecialchars($log['admin_username']); ?></span>
                        </div>
                    </td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($log['action']); ?></td>
                    <td style="color:var(--text-muted);font-size:13px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($log['details'] ?? ''); ?>"><?php echo htmlspecialchars($log['details'] ?? '—'); ?></td>
                    <td style="color:var(--text-muted);font-size:12.5px;"><?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?></td>
                </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page_num > 1): ?><a href="?p=<?php echo $page_num-1; ?>&user=<?php echo urlencode($filter_user); ?>&action_type=<?php echo urlencode($filter_action); ?>&date=<?php echo urlencode($filter_date); ?>">← Prev</a><?php endif; ?>
                <?php for ($i = max(1,$page_num-2); $i <= min($total_pages,$page_num+2); $i++): ?>
                    <?php if ($i === $page_num): ?><span class="current"><?php echo $i; ?></span>
                    <?php else: ?><a href="?p=<?php echo $i; ?>&user=<?php echo urlencode($filter_user); ?>&action_type=<?php echo urlencode($filter_action); ?>&date=<?php echo urlencode($filter_date); ?>"><?php echo $i; ?></a><?php endif; ?>
                <?php endfor; ?>
                <?php if ($page_num < $total_pages): ?><a href="?p=<?php echo $page_num+1; ?>&user=<?php echo urlencode($filter_user); ?>&action_type=<?php echo urlencode($filter_action); ?>&date=<?php echo urlencode($filter_date); ?>">Next →</a><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
<script src="sidebar-toggle.js"></script>
</body>
</html>
