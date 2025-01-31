<?php
// Database connection
$conn = new mysqli("localhost", "root", "2629", "netlink_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['patsub1'])) {
    $fname = $conn->real_escape_string($_POST['fname']);
    $lname = $conn->real_escape_string($_POST['lname']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    $gender = $conn->real_escape_string($_POST['gender']);
    $contact = $conn->real_escape_string($_POST['contact']);

    // Check if passwords match
    if ($password !== $cpassword) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert user into the database
    $sql = "INSERT INTO users (first_name, last_name, email, password, gender, contact) 
            VALUES ('$fname', '$lname', '$email', '$hashed_password', '$gender', '$contact')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Registration successful!'); window.location.href = 'index.html';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "'); window.history.back();</script>";
    }
}

$conn->close();
?>
