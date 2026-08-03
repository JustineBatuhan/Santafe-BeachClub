<?php
/**
 * _sidebar.php — Role-aware sidebar include.
 * Include this file in any shared page (reservations, checkin, etc.)
 * Pass $active_page = 'reservations' | 'checkin' | 'checkout' | 'guests'
 *                   | 'payments' | 'notifications' | 'settings' before including.
 */
$_role      = $_SESSION['admin_role'] ?? 'receptionist';
$_user      = $_SESSION['admin_username'] ?? '';
$_page      = $active_page ?? '';

if (!empty($_user)) {
    $roleStmt = $conn->prepare("SELECT role FROM admins WHERE username = ?");
    $roleStmt->bind_param("s", $_user);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result()->fetch_assoc();
    $roleStmt->close();

    if ($roleResult && isset($roleResult['role']) && $roleResult['role'] !== '') {
        $_SESSION['admin_role'] = $roleResult['role'];
        $_role = $roleResult['role'];
    }
}

$_is_admin  = ($_role === 'admin');

// Unread notification count (assumes: table `notifications`, column `is_read` tinyint(1), 0 = unread)
$_unread_count = 0;
if ($countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE is_read = 0")) {
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $_unread_count = (int)($countResult['cnt'] ?? 0);
    $countStmt->close();
}

// Unread inquiries count
$_unread_inquiries = 0;
$tableCheck = $conn->query("SHOW TABLES LIKE 'inquiries'");
if ($tableCheck->num_rows > 0) {
    if ($countInqStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM inquiries WHERE status = 'Unread'")) {
        $countInqStmt->execute();
        $countInqResult = $countInqStmt->get_result()->fetch_assoc();
        $_unread_inquiries = (int)($countInqResult['cnt'] ?? 0);
        $countInqStmt->close();
    }
}

// Helper: returns 'active' css class if page matches
function _sb_active($page, $current) {
    return $page === $current ? 'active' : '';
}

// Helper: returns badge HTML span for unread count, or empty string if none
function _sb_badge($count) {
    if ($count <= 0) return '';
    $display = $count > 99 ? '99+' : (string)$count;
    return '<span class="sidebar-badge">' . htmlspecialchars($display) . '</span>';
}
?>

<style>
/* Scoped notification badge styling (no shared admin.css / styles.css dependency) */
.sidebar-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    margin-left: auto;
    background-color: #E0483E;
    color: #FFFFFF;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    border-radius: 999px;
    font-family: var(--font-body, 'Outfit', sans-serif);
}
.admin-sidebar .sidebar-link,
.sidebar .nav-item {
    display: flex;
    align-items: center;
}
</style>

<?php if ($_is_admin): ?>
<!-- ═══════════════ ADMIN SIDEBAR ═══════════════ -->
<script src="dark-mode-toggle.js?v=2"></script>
<link rel="stylesheet" href="admin.css?v=2">
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <div class="brand-circle">SF</div>
        <div class="brand-text"><h2>Santa Fe</h2><p>Admin Panel</p></div>
        <button class="dark-mode-btn" title="Toggle Dark Mode" aria-label="Toggle Dark Mode" style="margin-left:auto;background:transparent;border:none;cursor:pointer;border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background 0.2s;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
    </div>

    <p class="sidebar-section-label">Overview</p>
    <ul class="sidebar-nav">
        <li><a href="admin_dashboard.php" class="sidebar-link <?php echo _sb_active('dashboard',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
            Dashboard
        </a></li>
    </ul>

    <p class="sidebar-section-label">Reservations</p>
    <ul class="sidebar-nav">
        <li><a href="admin_reservations.php" class="sidebar-link <?php echo _sb_active('reservations',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Reservations
        </a></li>
        <li><a href="admin_checkin.php" class="sidebar-link <?php echo _sb_active('checkin',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Check-in
        </a></li>
        <li><a href="admin_checkout.php" class="sidebar-link <?php echo _sb_active('checkout',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Check-out
        </a></li>
    </ul>

    <p class="sidebar-section-label">Operations</p>
    <ul class="sidebar-nav">
        <li><a href="guests.php" class="sidebar-link <?php echo _sb_active('guests',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Customers
        </a></li>
        <li><a href="payments.php" class="sidebar-link <?php echo _sb_active('payments',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="12" y1="10" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            Payments
        </a></li>
        <li><a href="accommodations.php" class="sidebar-link <?php echo _sb_active('accommodations',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Accommodations
        </a></li>
        <li><a href="admin_reports.php" class="sidebar-link <?php echo _sb_active('reports',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Reports
        </a></li>
    </ul>

    <p class="sidebar-section-label">Admin Only</p>
    <ul class="sidebar-nav">
        <li><a href="admin_staff.php" class="sidebar-link <?php echo _sb_active('staff',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Staff Management
        </a></li>
        <li><a href="admin_promotions.php" class="sidebar-link <?php echo _sb_active('promotions',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Promotions
        </a></li>
        <li><a href="admin_gallery.php" class="sidebar-link <?php echo _sb_active('gallery',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Gallery
        </a></li>
        <li><a href="admin_room_types.php" class="sidebar-link <?php echo _sb_active('room_photos',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><rect x="9" y="14" width="6" height="8"/><circle cx="12" cy="7" r="1.5"/></svg>
            Room Photos
        </a></li>
        <li><a href="admin_logs.php" class="sidebar-link <?php echo _sb_active('logs',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Activity Logs
        </a></li>
        <li><a href="admin_notifications.php" class="sidebar-link <?php echo _sb_active('notifications',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Notifications
            <?php echo _sb_badge($_unread_count); ?>
        </a></li>
        <li><a href="admin_inquiries.php" class="sidebar-link <?php echo _sb_active('inquiries',$_page); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            Inquiries
            <?php echo _sb_badge($_unread_inquiries); ?>
        </a></li>
    </ul>

    <div class="sidebar-bottom">
        <div class="user-pill">
            <div class="user-avatar"><?php echo strtoupper(substr($_user, 0, 1)); ?></div>
            <div>
                <div class="user-info-text"><?php echo htmlspecialchars($_user); ?></div>
                <div class="user-info-role">Administrator</div>
            </div>
        </div>

    </div>
</aside>

<?php else: ?>
<!-- ═══════════════ RECEPTION SIDEBAR ═══════════════ -->
<script src="dark-mode-toggle.js?v=2"></script>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-circle">SF</div>
        <div class="logo-text-group">
            <h2>Santa Fe</h2>
            <p>Reception Desk</p>
        </div>
        <button class="dark-mode-btn" title="Toggle Dark Mode" aria-label="Toggle Dark Mode" style="margin-left:auto;background:transparent;border:none;cursor:pointer;border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background 0.2s;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
    </div>

    <ul class="nav-links">
        <li><a href="dashboard.php" class="nav-item <?php echo _sb_active('dashboard',$_page); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            Dashboard
        </a></li>
        <li><a href="reservations.php" class="nav-item <?php echo _sb_active('reservations',$_page); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Reservations
        </a></li>
        <li><a href="checkin.php" class="nav-item <?php echo _sb_active('checkin',$_page); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            Check-in
        </a></li>
        <li><a href="checkout.php" class="nav-item <?php echo _sb_active('checkout',$_page); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Check-out
        </a></li>
        <li><a href="guests.php" class="nav-item <?php echo _sb_active('guests',$_page); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Guest Management
        </a></li>
        <li><a href="payments.php" class="nav-item <?php echo _sb_active('payments',$_page); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><line x1="12" y1="10" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
            Payment
        </a></li>
        <li><a href="notifications.php" class="nav-item <?php echo _sb_active('notifications',$_page); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            Notifications
            <?php echo _sb_badge($_unread_count); ?>
        </a></li>
        <li><a href="admin_inquiries.php" class="nav-item <?php echo _sb_active('inquiries',$_page); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            Inquiries
            <?php echo _sb_badge($_unread_inquiries); ?>
        </a></li>
    </ul>
</aside>
<?php endif; ?>

<?php if ($_is_admin): ?>
<script>
// Switch body wrapper class so admin.css layout applies on shared pages
document.addEventListener('DOMContentLoaded', function() {
    var mc = document.querySelector('.main-content');
    if (mc) { 
        mc.classList.remove('main-content'); 
        mc.classList.add('admin-main'); 
        
        // Add admin-body wrapper for proper padding
        var wrapper = document.createElement('div');
        wrapper.className = 'admin-body';
        while (mc.firstChild) {
            wrapper.appendChild(mc.firstChild);
        }
        mc.appendChild(wrapper);
    }
});
</script>
<?php endif; ?>
