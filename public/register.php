<?php
/**
 * Registration Page
 */
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/url_helper.php';
require_once __DIR__ . '/../app/helpers.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/user/dashboard.php');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            // Register as Civilian (role_id = 1)
            $result = registerUser($username, $email, $password, 1);
            
            if ($result['success']) {
                $success = true;
                // Auto-login after registration
                loginUser($username, $password);
                redirect('/user/dashboard.php');
            } else {
                $error = $result['error'];
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CityCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="<?= url('/index.php') ?>">CityCare</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="<?= url('/login.php') ?>">Login</a>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Create Account</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= h($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">Registration successful! Redirecting...</div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-3">Register</button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account? <a href="<?= url('/login.php') ?>">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

