<?php
session_start();

// Required includes
require_once 'db_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Validate POST data
if (!isset($_POST['receiver_id']) || !isset($_POST['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Get and sanitize input
$sender_id = $_SESSION['user_id'];
$receiver_id = filter_var($_POST['receiver_id'], FILTER_VALIDATE_INT);
$message = trim($_POST['message']);

// Validate input
if (!$receiver_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid receiver ID']);
    exit;
}

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

try {
    // Connect to database
    $pdo = connect_to_db();
    
    // Prepare and execute the insert
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, timestamp) VALUES (?, ?, ?, NOW())");
    
    if ($stmt->execute([$sender_id, $receiver_id, $message])) {
        // Get the inserted message details
        $message_id = $pdo->lastInsertId();
        
        // Return success response with message details
        echo json_encode([
            'success' => true,
            'message' => [
                'id' => $message_id,
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Failed to insert message');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error occurred',
        'details' => $e->getMessage()
    ]);
}
?>