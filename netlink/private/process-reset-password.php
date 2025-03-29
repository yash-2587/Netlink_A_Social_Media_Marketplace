<?php
session_start();
require_once("../private/db_functions.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot-password.php");
    exit();
}

// Verify token and get form data
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($password) || empty($confirm_password)) {
    $_SESSION['error'] = "All fields are required";
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

// Initialize database connection
$pdo = connect_to_db();
if (!$pdo) {
    $_SESSION['error'] = "Database connection error";
    header("Location: forgot-password.php");
    exit();
}

try {
    // Verify token and get user
    $stmt = $pdo->prepare("SELECT id FROM users_table 
                          WHERE reset_token = ? 
                          AND reset_token_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = "Invalid or expired reset link";
        header("Location: forgot-password.php");
        exit();
    }

    // Verify passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: reset-password.php?token=" . urlencode($token));
        exit();
    }

    // Validate password strength
    if (strlen($password) < 8 || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || 
        !preg_match('/[0-9]/', $password)) {
        $_SESSION['error'] = "Password must be at least 8 characters with uppercase, lowercase, and number";
        header("Location: reset-password.php?token=" . urlencode($token));
        exit();
    }

    // Update password and clear reset token
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users_table 
                          SET password = ?, 
                              reset_token = NULL, 
                              reset_token_expiry = NULL 
                          WHERE id = ?");
    $stmt->execute([$hashed_password, $user['id']]);

    // Clear session variables
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_token']);

    // Set success message
    $_SESSION['success'] = "Password reset successfully!";
    header("Location: login.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}
?>