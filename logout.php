<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && isset($_SESSION['admin_username'])) {
    require_once 'db.php';
    log_activity($conn, $_SESSION['admin_username'], 'Logout', 'Logged out');
}
session_unset();
session_destroy();
header('Location: login.php');
exit;
