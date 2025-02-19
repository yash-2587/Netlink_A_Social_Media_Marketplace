<?php
session_start();

require_once("db_functions.php");

$conn = connect_to_db();

$username = $_POST['username'];
$password = $_POST['password'];
$result = get_user_by_credentials($conn, $username, $password);

if ($result) {
    $_SESSION['user_id'] = $result['id'];
    $_SESSION['user_username'] = $result['username'];
    $_SESSION['user_full_name'] = $result['full_name'];
    $_SESSION['user_email'] = $result['email'];
    $_SESSION['user_phone_number'] = $result['phone_number'];
    $_SESSION['user_profile_picture_path'] = $result['profile_picture_path'];
    $_SESSION['user_display_name'] = $result['display_name'];
    $_SESSION['user_bio'] = nl2br(stripslashes($result['bio']));

    header('Location: otp.php');
    exit();
} else {
    $_SESSION['error'] = 'Invalid username or password';
    header('Location: login.php');  
    exit();
}


?>



