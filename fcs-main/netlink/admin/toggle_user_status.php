<?php
// toggle_user_status.php - Safely Toggle User Status
session_start();
require_once "../private/config.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_status'], $_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $user_id = (int)$_POST['user_id'];
    $new_status = $_POST['new_status'];

    if (!in_array($new_status, ['active', 'suspended', 'deleted'])) {
        die("Invalid status");
    }

    $stmt = $conn->prepare("UPDATE users_table SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $user_id);
    $stmt->execute();

    $log_stmt = $conn->prepare("INSERT INTO security_logs (admin_id, action, event, user_id, target_user_id) VALUES (?, ?, ?, ?, ?)");
    $admin_id = $_SESSION['admin_id'];
    $action = "STATUS_CHANGE";
    $event = "Changed user status to $new_status";
    $log_stmt->bind_param("issii", $admin_id, $action, $event, $admin_id, $user_id);
    $log_stmt->execute();

    header("Location: manage_users.php");
    exit;
} else {
    http_response_code(405);
    echo "Method Not Allowed";
}
?>
