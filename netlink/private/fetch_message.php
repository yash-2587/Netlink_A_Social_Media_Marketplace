<?php
session_start();
require_once('db_functions.php');
require_once('config.php');
 // Add this file for encryption/decryption

// Ensure the script is accessed via execute_file.php
if (empty($_SERVER['HTTP_REFERER']) || !str_contains($_SERVER['HTTP_REFERER'], 'execute_file.php')) {
    header('Location: index.php');
    exit;
}

// Validate receiver_id
if (!isset($_GET['receiver_id'])) {
    echo json_encode(['error' => 'Receiver ID is required']);
    exit;
}

$receiver_id = (int)$_GET['receiver_id'];

if (!$receiver_id) {
    echo json_encode(['error' => 'Invalid Receiver ID']);
    exit;
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$logged_in_user_id = $_SESSION['user_id'];

// Fetch messages sent or received between logged-in user and receiver
try {
    $conn = connect_to_db();

    $sql = "SELECT id, sender_id, receiver_id, message, timestamp 
            FROM messages
            WHERE (sender_id = :logged_in_user AND receiver_id = :receiver)
               OR (sender_id = :receiver AND receiver_id = :logged_in_user)
            ORDER BY timestamp ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':logged_in_user' => $logged_in_user_id,
        ':receiver' => $receiver_id
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decrypt messages
    foreach ($messages as &$message) {
        $message['message'] = decrypt_message($message['message']); // Decrypt the message
    }

    // Return messages as JSON
    header('Content-Type: application/json');
    echo json_encode($messages ? $messages : []);
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Handle other errors
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>