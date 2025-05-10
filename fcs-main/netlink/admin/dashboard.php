<?php
// dashboard.php - Admin Panel Dashboard
session_start();
require_once "../private/config.php"; 

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Welcome to Admin Dashboard</h2>
    <nav>
        <a href="manage_users.php">Manage Users</a>
        <a href="security_logs.php">Security Logs</a>
        <a href="SV4.php">Doc verification  </a>
        <a href="logout.php">Logout</a>
    </nav>
</body>
</html>
