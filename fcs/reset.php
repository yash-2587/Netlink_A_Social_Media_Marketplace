<?php
// Database connection
$conn = new mysqli("localhost", "root", "2629", "netlink_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $contact = $_POST['contact']; // Captures the contact number passed in the hidden input field
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['cpassword'];

    // Validate the passwords
    if ($newPassword === $confirmPassword) {
        // Hash the new password for security
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Prepare the SQL query to update the password
        $sql = "UPDATE users SET password = ? WHERE contact = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashedPassword, $contact);

        if ($stmt->execute()) {
            echo "<script>alert('Password updated successfully!'); window.location.href = 'index.html';</script>";
        } else {
            echo "<script>alert('Error updating password: " . $conn->error . "'); window.history.back();</script>";
        }

        // Close statement
        $stmt->close();
    } else {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
    }
} else {
    // If the form is not submitted via POST
    echo "<script>alert('Invalid request method!'); window.history.back();</script>";
}

// Close the database connection
$conn->close();
?>
