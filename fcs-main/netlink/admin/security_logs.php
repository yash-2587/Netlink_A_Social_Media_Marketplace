<?php
// security_logs.php - View Security Logs
session_start();
require_once "../private/config.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php");
    exit;
}

$result = $conn->query("SELECT * FROM security_logs ORDER BY timestamp DESC LIMIT 100");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Security Logs</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Security Logs</h2>
    <nav><a href="dashboard.php">Back to Dashboard</a></nav>
    <table>
        <thead>
            <tr><th>ID</th><th>Admin ID</th><th>Action</th><th>Event</th><th>User ID</th><th>Target User ID</th><th>Time</th></tr>
        </thead>
        <tbody>
            <?php while ($log = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($log['log_id']) ?></td>
                <td><?= htmlspecialchars($log['admin_id']) ?></td>
                <td><?= htmlspecialchars($log['action']) ?></td>
                <td><?= htmlspecialchars($log['event']) ?></td>
                <td><?= htmlspecialchars($log['user_id']) ?></td>
                <td><?= htmlspecialchars($log['target_user_id']) ?></td>
                <td><?= htmlspecialchars($log['timestamp']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
