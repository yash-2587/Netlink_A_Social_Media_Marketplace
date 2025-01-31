<?php
// Database connection
$conn = new mysqli("localhost", "root", "2629", "netlink_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['adsub'])) {
    $username = $conn->real_escape_string($_POST['username1']);
    $password = $_POST['password2'];

    // Fetch user from the database
    $sql = "SELECT * FROM users WHERE email = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            echo "<script>alert('Login successful!'); window.location.href = 'otp.html';</script>";
        } else {
            echo "<script>alert('Invalid password!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('No user found with this email!'); window.history.back();</script>";
    }
}

$conn->close();
?>
