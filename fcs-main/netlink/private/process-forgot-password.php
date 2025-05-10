<?php
session_start();
require_once("../private/db_functions.php");

// Initialize database connection
$pdo = connect_to_db();
date_default_timezone_set('Asia/Kolkata');
if (!$pdo) {
    $_SESSION['error'] = "Database connection error";
    header("Location: forgot-password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: forgot-password.php");
        exit();
    }

    try {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users_table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Debug output
            error_log("Generated Token: $token");
            error_log("Expiry: $expiry");
            error_log("User ID: {$user['id']}");
            
            // Store token in database
            $updateStmt = $pdo->prepare("UPDATE users_table 
                                      SET reset_token = :token, 
                                          reset_token_expiry = :expiry 
                                      WHERE id = :id");
            $updateStmt->execute([
                ':token' => $token,
                ':expiry' => $expiry,
                ':id' => $user['id']
            ]);
            
            // Verify the token was stored
            $checkStmt = $pdo->prepare("SELECT reset_token, reset_token_expiry 
                                       FROM users_table 
                                       WHERE id = ?");
            $checkStmt->execute([$user['id']]);
            $result = $checkStmt->fetch();
            
            error_log("Stored Token: {$result['reset_token']}");
            error_log("Stored Expiry: {$result['reset_token_expiry']}");
            
            // For development - redirect directly to reset page
            header("Location: reset-password.php?token=" . urlencode($token));
            exit();
        } else {
            $_SESSION['error'] = "Email not found in our system";
            header("Location: forgot-password.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "Database error occurred";
        header("Location: forgot-password.php");
        exit();
    }
}
?>