<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Log in · NetLink</title>
    <link rel="icon" type="image/x-icon" href="logo.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
        </script>

    <script src="https://unpkg.com/just-validate@latest/dist/just-validate.production.min.js"></script>

    <script type="module" src="scripts/validate-login.js" defer></script>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div>
        <?php include('header.php'); ?>
        <main class="bg-light page-login d-flex flex-column w-100 align-items-center justify-content-center">
            <div class="container p-0 m-0">
                <div class="card login-card border">
                    <div class="login-card-img-container row no-gutters">
                        <div class="col-md-6 p-0">
                            <img class="login-card-img" src="logo.png" alt="">
                        </div>
                        <div class="col-md-6 p-0">
                            <div class="card-body h-100 d-flex flex-column justify-content-center p-0">
                                <form id="login-form" autocomplete="off" novalidate="novalidate"
                                    class="d-flex flex-column w-100 p-5 gap-4" method="POST"
                                    action="execute_file.php?filename=process_login.php">
                                    <div>
                                        <p class="fw-bold fs-1 m-0 p-0">
                                            Hi there!<br>
                                        </p>
                                        <p class="fs-5 m-0 p-0">
                                            Please enter your details to log in.<br>
                                        </p>
                                    </div>
                                    <div class="inputs-container d-flex flex-column gap-4 my-3">
                                        <div class="form-floating">
                                            <input class="form-control bg-light px-3" id="username" name="username"
                                                type="name" autocomplete="off"
                                                placeholder="Phone number, username or email address" />
                                            <label class="w-100 px-0">
                                                <p class="m-0 px-3 w-100 bg-light text-truncate">Phone number,
                                                    username or email address
                                                </p>
                                            </label>
                                        </div>
                                        <div class="form-floating">
                                            <input class="form-control bg-light px-3" id="password" name="password"
                                                placeholder="Password" autocomplete="off" type="password" />
                                            <label class="w-100 px-0">
                                                <p class="m-0 px-3 w-100 bg-light text-truncate">Password</p>
                                            </label>
                                        </div>
                                        <div class="d-flex justify-content-end mt-2">
                                            <a href="forgot-password.php" class="text-decoration-none small">Forgot Password?</a>
                                        </div>
                                        <div>
                                            <button class="btn btn-primary fw-bold w-100" name="submit-button"
                                                type="submit">Log
                                                in</button>
                                        </div>
                                        <div id="login-error" class='alert alert-danger mb-0' role='alert'>Sorry, your
                                            password
                                            was
                                            incorrect. Please double-check your password.
                                        </div>
                                    </div>
                                    <div class="bg-white">
                                        <p class="m-0">
                                            <span class="text-muted">Don't have an account?</span>
                                            <a href="register.php"
                                                class="link-underline link-underline-opacity-0 fw-semibold">Sign up</a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
