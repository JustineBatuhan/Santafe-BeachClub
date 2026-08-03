<?php
/**
 * _page_header.php — Shared page header component.
 * Include this on EVERY page (reception and admin) right after opening
 * <main class="main-content"> or <div class="admin-main"> / <div class="admin-body">
 * so every page gets the exact same header: hamburger, title + subtitle on
 * the left, notifications + user menu on the right.
 *
 * Before including, set these variables:
 *   $page_title        (string, required) e.g. "Dashboard"
 *   $page_subtitle      (string, optional) e.g. "Overview of today's operations"
 *   $header_extra_html   (string, optional) raw HTML rendered before the bell —
 *                        use this for a page-specific button or search box
 *                        (e.g. a "New Reservation" button, or a search input).
 *
 * Requires $conn (mysqli) to already be connected if you want the live
 * notification-badge count; otherwise it just won't show a badge.
 */
$_ph_title      = $page_title ?? '';
$_ph_subtitle   = $page_subtitle ?? '';
$_ph_username   = $_SESSION['admin_username'] ?? '';
$_ph_role       = $_SESSION['admin_role'] ?? 'receptionist';
$_ph_is_admin   = ($_ph_role === 'admin');
$_ph_notifications_href = $_ph_is_admin ? 'admin_notifications.php' : 'notifications.php';
$_ph_role_label = $_ph_is_admin ? 'Administrator' : 'Receptionist';

$_ph_unread = 0;
if (isset($conn)) {
    $_ph_notif_row = $conn->query("SELECT COUNT(*) as unread FROM notifications WHERE is_read = 0")->fetch_assoc();
    $_ph_unread = $_ph_notif_row ? (int)$_ph_notif_row['unread'] : 0;
}
?>
<header class="page-header">
    <button class="sidebar-toggle-btn" aria-label="Toggle menu">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div class="page-header-titles">
        <h1 class="page-header-title"><?php echo htmlspecialchars($_ph_title); ?></h1>
        <?php if ($_ph_subtitle !== ''): ?>
        <p class="page-header-subtitle"><?php echo htmlspecialchars($_ph_subtitle); ?></p>
        <?php endif; ?>
    </div>

    <div class="page-header-actions">
        <?php if (!empty($header_extra_html)) { echo $header_extra_html; } ?>

        <a href="<?php echo $_ph_notifications_href; ?>" class="header-icon-btn" title="Notifications" style="text-decoration:none;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <?php if ($_ph_unread > 0): ?><span class="notification-badge"></span><?php endif; ?>
        </a>

        <div class="header-user-menu" id="pageHeaderUserMenu">
            <button class="header-user-trigger" onclick="document.getElementById('pageHeaderUserMenu').classList.toggle('open')">
                <span class="header-user-avatar"><?php echo strtoupper(substr($_ph_username !== '' ? $_ph_username : 'U', 0, 1)); ?></span>
                <span class="header-user-name"><?php echo htmlspecialchars($_ph_role_label); ?></span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="header-user-dropdown">
                <?php if ($_ph_is_admin): ?>
                <a href="dashboard.php">Switch to Reception View</a>
                <?php endif; ?>
                <a href="settings.php">Settings</a>
                <a href="logout.php" class="danger">Logout</a>
            </div>
        </div>
    </div>
</header>
<script>
document.addEventListener('click', function(e) {
    var menu = document.getElementById('pageHeaderUserMenu');
    if (menu && !menu.contains(e.target)) {
        menu.classList.remove('open');
    }
});
</script>
