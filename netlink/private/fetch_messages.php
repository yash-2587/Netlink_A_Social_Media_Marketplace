<?php
session_start();
require_once('db_functions.php');
require_once('config.php');

function execute($params) {
    if (!isset($_SESSION['user_id'])) {
        return ['error' => 'Unauthorized'];
    }

    $logged_in_user_id = $_SESSION['user_id'];
    $receiver_id = isset($params['receiver_id']) ? (int)$params['receiver_id'] : null;

    if (!$receiver_id) {
        return ['error' => 'Receiver ID is required'];
    }

    $conn = connect_to_db();

    // Fetch messages sent or received between logged-in user and receiver
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
    
    // Return empty array instead of error if no messages found
    return $messages ? $messages : [];
}
?>
