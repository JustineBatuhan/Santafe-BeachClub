<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
