<?php
session_start();
require_once('utility_functions.php');
redirect_if_not_logged_in();

require_once('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);
    $item_description = htmlspecialchars($_POST['item_description']);
    $item_price = floatval($_POST['item_price']);
    $user_id = $_SESSION['user_id'];

    // Check if the item belongs to the logged-in user
    $stmt = $conn->prepare("SELECT user_id FROM items_table WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->bind_result($item_user_id);
    $stmt->fetch();
    $stmt->close();

    if ($item_user_id === $user_id) {
        // Update the item
        $stmt = $conn->prepare("UPDATE items_table SET description = ?, price = ? WHERE id = ?");
        $stmt->bind_param("sdi", $item_description, $item_price, $item_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Item updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update item.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "You do not have permission to edit this item.";
    }

    header("Location: ../public/user_profile.php?user_id=$user_id");
    exit();
}
?>