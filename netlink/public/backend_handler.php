<?php
$fileName = $_GET['file'];

$params = $_GET['params'] ?? null;
$csrfToken = $_GET['csrf_token'] ?? null;

// Decode params if they exist
if ($params) {
    $params = json_decode($params, true);
    // Add csrf_token back to params for the execute function
    if ($csrfToken) {
        $params['csrf_token'] = $csrfToken;
    }
}

include '../private/' . $fileName;

$result = null;

if (function_exists('execute')) {
    if ($params) {
        $result = execute($params);
    } else {
        $result = execute();
    }
} else {
    $result = null;
}

$response = [
    'result' => $result
];

header('Content-Type: application/json');
echo json_encode($response);
?>