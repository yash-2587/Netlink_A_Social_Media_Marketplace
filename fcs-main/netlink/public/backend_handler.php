<?php
$fileName = $_GET['file'];

// Decode JSON params to associative array
$rawParams = $_GET['params'] ?? '';
$params = json_decode($rawParams, true);

// Handle JSON decoding errors
if ($rawParams !== '' && $params === null) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid parameters format']));
}

include '../private/' . $fileName;

$result = null;

if (function_exists('execute')) {
    $result = execute($params); // Now passing an array instead of raw string
} else {
    $result = null;
}

header('Content-Type: application/json');
echo json_encode(['result' => $result]);
?>
