<?php
require_once("db_functions.php");

function execute($params) {
    $pdo = connect_to_db();
    $username = $params['username'] ?? '';
    $password = $params['password'] ?? '';

    return get_user_by_credentials($pdo, $username, $password);
}
?>