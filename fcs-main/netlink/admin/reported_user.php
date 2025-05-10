<?php
// reported_user.php - Handle Reports
session_start();
require_once "../private/config.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'], $_POST['status'], $_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $report_id = (int)$_POST['report_id'];
    $new_status = $_POST['status'];

    if (!in_array($new_status, ['pending', 'reviewed', 'resolved'])) {
        die("Invalid status");
    }

    $stmt = $conn->prepare("UPDATE reported_users SET status = ? WHERE report_id = ?");
    $stmt->bind_param("si", $new_status, $report_id);
    $stmt->execute();

    $log_stmt = $conn->prepare("INSERT INTO security_logs (admin_id, action, event, user_id) VALUES (?, ?, ?, ?)");
    $admin_id = $_SESSION['admin_id'];
    $action = "REPORT_UPDATE";
    $event = "Report #$report_id marked as $new_status";
    $log_stmt->bind_param("issi", $admin_id, $action, $event, $admin_id);
    $log_stmt->execute();
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$reports = $conn->query("SELECT * FROM reported_users ORDER BY reported_at DESC");
?>
<!-- Display reports in a secure HTML table with CSRF tokens for actions -->
