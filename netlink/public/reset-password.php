<?php
session_start();
require_once("../private/db_functions.php");

// Verify token if present in URL
$token = $_GET['token'] ?? '';
if (empty($token)) {
    $_SESSION['error'] = "Invalid password reset link";
    header("Location: forgot-password.php");
    exit();
}

// Initialize database connection
$pdo = connect_to_db();
if (!$pdo) {
    $_SESSION['error'] = "Database connection error";
    header("Location: forgot-password.php");
    exit();
}

try {
    // Check if token is valid and not expired
    $stmt = $pdo->prepare("SELECT id FROM users_table 
                          WHERE reset_token = ? 
                          AND reset_token_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = "Invalid or expired reset link";
        header("Location: forgot-password.php");
        exit();
    }

    // Store user ID in session for verification during password update
    $_SESSION['reset_user_id'] = $user['id'];
    $_SESSION['reset_token'] = $token;

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: forgot-password.php");
    exit();
}

// Display any errors
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password · NetLink</title>
    <!-- Include your standard head content -->
    <link rel="icon" type="image/x-icon" href="logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    </script>

<script src="https://unpkg.com/just-validate@latest/dist/just-validate.production.min.js"></script>

<link rel="stylesheet" href="css/style.css">
    <style>
        .password-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background: #eee;
        }
        .password-strength span {
            display: block;
            height: 100%;
            transition: width 0.3s, background 0.3s;
        }
    </style>
</head>
<body>
<?php include('header.php'); ?>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-sm password-container">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Reset Password</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form id="reset-password-form" action="execute_file.php?filename=process-reset-password.php" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="password-strength mt-2">
                            <span id="password-strength-bar"></span>
                        </div>
                        <div class="form-text">Must be at least 8 characters with uppercase, lowercase, and number</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength-bar');
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            const width = (strength / 5) * 100;
            let color;
            
            if (strength <= 1) color = '#ff0000';
            else if (strength <= 3) color = '#ff9900';
            else color = '#00cc00';
            
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = color;
        });

        // Form validation
        document.getElementById('reset-password-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }
            
            if (password.length < 8 || 
                !/[A-Z]/.test(password) || 
                !/[a-z]/.test(password) || 
                !/[0-9]/.test(password)) {
                e.preventDefault();
                alert('Password must be at least 8 characters with uppercase, lowercase, and number');
            }
        });
    </script>
</body>
</html>