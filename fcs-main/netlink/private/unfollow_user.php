<?php
require_once 'db_functions.php';

function execute($params)
{
    $pdo = connect_to_db();

    // Assuming $params is already an array from backend_handler.php
    $follower_id = $params['follower_id'] ?? '';
    $followed_id = $params['followed_id'] ?? '';

    if (empty($follower_id) || empty($followed_id)) {
        return ['success' => false, 'error' => 'Invalid parameters'];
    }

    $result = unfollow_user($pdo, $follower_id, $followed_id);

    if ($result) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Failed to unfollow user'];
    }
}
?>
