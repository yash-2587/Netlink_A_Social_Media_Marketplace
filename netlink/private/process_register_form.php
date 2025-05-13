<?php
require_once("db_functions.php");
$conn = connect_to_db();

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

function validateField($field, $name, $minLength, $maxLength = false, $numeric = false, $email = false)
{
    global $errors, $conn;

    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $errors[] = "$name is required.";
        return;
    }

    $value = trim($_POST[$field]);

    if ($field === 'csrf_token' && !validateCSRFToken($value)) {
        $errors[] = "Invalid form submission.";
        return;
    }

    if (in_array($field, ['email', 'phone_number', 'username']) && does_value_exist($conn, 'users_table', $field, $value)) {
        $errors[] = "$name already exists. Please choose a different $name.";
        return;
    }

    if ($numeric) {
        if (!is_numeric($value) || strlen($value) < $minLength || strlen($value) > $maxLength) {
            if (!is_numeric($value)) {
                $errors[] = "$name must be numeric.";
            } else {
                $errors[] = "$name must be between $minLength and $maxLength digits.";
            }
            return;
        }
    }

    if ($email && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid $name format.";
        return;
    }

    if ($field === 'username' && !preg_match('/^[a-zA-Z0-9._]+$/', $value)) {
        $errors[] = "$name may only contain letters, numbers, periods, and underscores.";
        return;
    }

    if ($maxLength) {
        if (strlen($value) < $minLength || strlen($value) > $maxLength) {
            $errors[] = "$name must be between $minLength and $maxLength characters.";
            return;
        }
    } else {
        if (strlen($value) < $minLength) {
            $errors[] = "$name must be at least $minLength characters long.";
            return;
        }
    }

    if ($field === 'password') {
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/', $value)) {
            $errors[] = "Password must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, and one number.";
            return;
        }
    }
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$errors = [];

validateField('email', 'Email', 1, 255, false, true);
validateField('phone_number', 'Phone number', 3, 15, true);
validateField('full_name', 'Full name', 3, 50);
validateField('username', 'Username', 1, 15);
validateField('password', 'Password', 8, 255);

if (!empty($errors)) {
    echo json_encode($errors);
    return;
}

$_SESSION['email'] = strtolower($_POST['email']);
$_SESSION['phone_number'] = $_POST['phone_number'];
$_SESSION['full_name'] = $_POST['full_name'];
$_SESSION['username'] = strtolower($_POST['username']);
$_SESSION['hashed_password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
$_SESSION['registration_complete'] = true;

header('Location: profile_setup.php');
exit;

unset($_SESSION['email']);
unset($_SESSION['phone_number']);
unset($_SESSION['hashed_password']);
?>