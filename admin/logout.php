<?php
require_once '../includes/config.php';

// Clear admin session
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_name']);

// Destroy session if no other data
if (empty($_SESSION)) {
    session_destroy();
}

// Redirect to admin login
header('Location: login.php?message=logged_out');
exit();
?>