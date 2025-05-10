<?php
session_start();
require_once('utility_functions.php');
redirect_if_not_logged_in();

require_once('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);
    $user_id = $_SESSION['user_id'];

    // Check if the item belongs to the logged-in user
    $stmt = $conn->prepare("SELECT user_id FROM items_table WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->bind_result($item_user_id);
    $stmt->fetch();
    $stmt->close();

    if ($item_user_id === $user_id) {
        // Delete the item
        $stmt = $conn->prepare("DELETE FROM items_table WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Item deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete item.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "You do not have permission to delete this item.";
    }

    header("Location:  ../user_profile.php?user_id=$user_id");
    exit();
}
?>
