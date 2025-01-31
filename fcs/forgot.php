<?php
header("Content-Type: application/json");

// Database connection
$conn = new mysqli("localhost", "root", "2629", "netlink_db");

// Check connection
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $contact = $_POST['contact'];

    // Validate input
    if (empty($email) || empty($contact)) {
        echo json_encode(["status" => "error", "message" => "Email and contact number are required."]);
        exit();
    }

    // Verify email and contact in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND contact = ?");
    $stmt->bind_param("ss", $email, $contact);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "success", "message" => "User verified."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid email or contact number."]);
    }
}
?>
