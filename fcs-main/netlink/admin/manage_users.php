<?php
// manage_users.php - Manage User Status with HTML
session_start();
require_once "../private/config.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'], $_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $action = $_POST['action'];
    $user_id = (int)$_POST['user_id'];

    // Initialize new_status
    $new_status = '';

    if ($action === 'suspend') {
        $new_status = 'suspended';
    } elseif ($action === 'activate') {
        $new_status = 'active';
    }

    if ($new_status) {
        // Update the status in the database
        $stmt = $conn->prepare("UPDATE users_table SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();

        // Log the action in the security logs
        $log_stmt = $conn->prepare("INSERT INTO security_logs (admin_id, action, event, user_id, target_user_id) VALUES (?, ?, ?, ?, ?)");
        $admin_id = $_SESSION['admin_id'];
        $action_str = strtoupper($action);
        $event = "User status changed to $new_status";
        $log_stmt->bind_param("issii", $admin_id, $action_str, $event, $admin_id, $user_id);
        $log_stmt->execute();
    } elseif ($action === 'delete') {
        // Delete the user from the database
        $stmt = $conn->prepare("DELETE FROM users_table WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Log the deletion action
        $log_stmt = $conn->prepare("INSERT INTO security_logs (admin_id, action, event, user_id, target_user_id) VALUES (?, ?, ?, ?, ?)");
        $admin_id = $_SESSION['admin_id'];
        $action_str = "DELETE";
        $event = "User with ID $user_id deleted";
        $log_stmt->bind_param("issii", $admin_id, $action_str, $event, $admin_id, $user_id);
        $log_stmt->execute();
    }
}

// Regenerate CSRF token for next request
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Fetch users from the database
$result = $conn->query("SELECT id, username, email, status FROM users_table ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Manage Users</h2>
    <nav><a href="dashboard.php">Back to Dashboard</a></nav>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Username</th><th>Email</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['status']) ?></td>
                <td>
                    <form method="post" action="" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <?php if ($user['status'] !== 'suspended'): ?>
                            <button type="submit" name="action" value="suspend">Suspend</button>
                        <?php endif; ?>
                        <?php if ($user['status'] !== 'active'): ?>
                            <button type="submit" name="action" value="activate">Activate</button>
                        <?php endif; ?>
                        <?php if ($user['status'] !== 'deleted'): ?>
                            <button type="submit" name="action" value="delete">Delete</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
