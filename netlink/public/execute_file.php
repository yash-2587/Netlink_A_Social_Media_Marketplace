<?php
if (empty($_SERVER['HTTP_REFERER'])) {
    header('Location: index.php');
    exit;
}

$base_path = dirname(__DIR__) . '/private/';

$file = $_GET['filename'] ?? '';

$file = basename($file); 

$file_to_execute = $base_path . $file;


if (!file_exists($file_to_execute)) {
    die("Error: File not found at path - $file_to_execute");
}

ob_start();
include $file_to_execute;
ob_end_flush();

exit;


?>
