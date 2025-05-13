<?php
require_once("db_functions.php");

function execute($params)
{
    // If params is already an array, use it directly
    if (is_array($params)) {
        $data = $params;
    }
    // If params is a JSON string, decode it
    else if (is_string($params)) {
        $data = json_decode($params, true);
        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_encode(['error' => 'Invalid JSON input']);
        }
    }
    // Invalid input type
    else {
        return json_encode(['error' => 'Invalid input type']);
    }

    // Validate the required fields
    if (!isset($data['type'], $data['value'])) {
        return json_encode(['error' => 'Missing required parameters']);
    }

    $pdo = connect_to_db();

    $type = $data['type'];
    $value = $data['value'];

    if (empty($type)) {
        return false;
    }

    if (does_value_exist($pdo, 'users_table', $type, $value)) {
        return true;
    }
    return false;
}
?>