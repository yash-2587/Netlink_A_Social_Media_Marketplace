<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_CONFIG', [
    'host' => 'localhost',
    'db'   => 'netlink',
    'user' => 'root',
    'pass' => '2629',
]);

function getDbConnection() {
    $conn = new mysqli(DB_CONFIG['host'], DB_CONFIG['user'], DB_CONFIG['pass'], DB_CONFIG['db']);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

$conn = getDbConnection();
?>

