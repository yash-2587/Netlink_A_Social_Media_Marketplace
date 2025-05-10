<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if OTP is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $entered_otp = implode("", $_POST['otp']); // Combine OTP digits

    // Validate OTP
    if (isset($_SESSION['otp']) && $entered_otp == $_SESSION['otp'] && time() <= $_SESSION['otp_expiry']) {
        // OTP is correct
        unset($_SESSION['otp']); // Clear OTP from session
        unset($_SESSION['otp_expiry']); // Clear OTP expiry time
        $_SESSION['otp_verified'] = true; // Mark OTP as verified

        // Redirect to index.php
        header('Location: index.php');
        exit();
    } else {
        // OTP is incorrect or expired
        $_SESSION['error'] = "Invalid OTP. Please try again.";
        header('Location: otp.php');
        exit();
    }
} else {
    // Invalid request
    header('Location: login.php');
    exit();
}
?>