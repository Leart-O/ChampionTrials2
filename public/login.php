<?php
/**
 * Login Page
 */
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/url_helper.php';
require_once __DIR__ . '/../app/helpers.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role_name'] === 'Municipality Head') {
        redirect('/municipality/dashboard.php');
    } elseif ($user['role_name'] === 'Admin') {
        redirect('/admin/panel.php');
    } else {
        redirect('/user/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $result = loginUser($username, $password);
            
            if ($result['success']) {
                $user = getCurrentUser();
                // Redirect based on role
                if ($user['role_name'] === 'Municipality Head') {
                    redirect('/municipality/dashboard.php');
                } elseif ($user['role_name'] === 'Admin') {
                    redirect('/admin/panel.php');
                } elseif ($user['role_name'] === 'Authority') {
                    redirect('/authority/dashboard.php');
                } else {
                    redirect('/user/dashboard.php');
                }
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
    <title>Login - CityCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <style>
        .navbar, .navbar.navbar-light, .navbar.navbar-dark {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%) !important;
            background-color: #2563eb !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg modern-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= url('/index.php') ?>">CityCare</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="<?= url('/register.php') ?>">Register</a>
            </div>
        </div>
    </nav>

    <main class="container my-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-custom">
                    <div class="card-header">
                        <h2 class="card-title text-center mb-0">Login</h2>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= h($error) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <input type="text" class="form-control" id="username" name="username" required autofocus>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-0">Don't have an account? <a href="<?= url('/register.php') ?>">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

