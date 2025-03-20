<?php
session_start();

require_once 'db_functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

if (!isset($_POST['receiver_id']) || !isset($_POST['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = filter_var($_POST['receiver_id'], FILTER_VALIDATE_INT);
$message = trim($_POST['message']);

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

    $pdo = connect_to_db();

    $encrypted_message = encrypt_message($message); // Use a secure encryption function

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, timestamp) VALUES (?, ?, ?, NOW())");
    
    if ($stmt->execute([$sender_id, $receiver_id, $encrypted_message])) {
        $message_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => [
                'id' => $message_id,
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'message' => $message, // Return decrypted message for immediate display
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