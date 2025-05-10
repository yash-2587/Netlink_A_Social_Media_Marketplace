<?php
session_start();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password · NetLink</title>
    <link rel="icon" type="image/x-icon" href="logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    </script>

<script src="https://unpkg.com/just-validate@latest/dist/just-validate.production.min.js"></script>

<link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include('header.php'); ?>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-sm" style="width: 400px;">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Verify your Email</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form id="forgot-password-form" action="execute_file.php?filename=process-forgot-password.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">RESET PASSWORD</button>
                </form>
                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>