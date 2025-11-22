<?php
/**
 * CityCare Landing Page
 */
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/url_helper.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CityCare - Smart Reporting Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="<?= url('/index.php') ?>">CityCare</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/login.php') ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/register.php') ?>">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <!-- Hero Section -->
        <section class="hero-section py-5 bg-light">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <h1 class="display-4 fw-bold mb-4">CityCare</h1>
                        <p class="lead mb-4">Smart Reporting Platform for Better City Management</p>
                        <div class="d-flex gap-3">
                            <a href="<?= url('/register.php') ?>" class="btn btn-primary btn-lg">Get Started</a>
                            <a href="<?= url('/login.php') ?>" class="btn btn-outline-primary btn-lg">Login</a>
                        </div>
                    </div>
                    <div class="col-lg-6 text-center">
                        <div class="hero-image-placeholder bg-primary bg-opacity-10 rounded p-5">
                            <svg width="300" height="300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Problem Section -->
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-5">The Problem</h2>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <svg width="48" height="48" fill="currentColor" class="text-warning">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                                    </svg>
                                </div>
                                <h5>Inefficient Reporting</h5>
                                <p class="text-muted">Citizens struggle to report city issues through traditional channels, leading to delayed responses.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <svg width="48" height="48" fill="currentColor" class="text-danger">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                </div>
                                <h5>Poor Prioritization</h5>
                                <p class="text-muted">Municipalities lack tools to intelligently prioritize urgent issues over routine maintenance.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <svg width="48" height="48" fill="currentColor" class="text-info">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                </div>
                                <h5>Lack of Transparency</h5>
                                <p class="text-muted">No visibility into report status, resolution progress, or cluster detection for recurring issues.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Solution Section -->
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="text-center mb-5">The Solution</h2>
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4">
                        <h3>CityCare Platform</h3>
                        <p class="lead">A modern, AI-powered platform that connects citizens with city management for faster, smarter issue resolution.</p>
                        <ul class="list-unstyled">
                            <li class="mb-2">✓ Easy mobile-first reporting with map integration</li>
                            <li class="mb-2">✓ AI-assisted report creation and prioritization</li>
                            <li class="mb-2">✓ Real-time cluster detection for recurring issues</li>
                            <li class="mb-2">✓ Transparent status tracking and updates</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <div class="bg-white p-4 rounded shadow-sm">
                            <h5>How It Works</h5>
                            <div class="step mb-3">
                                <strong>Step 1:</strong> Citizens report issues with photos and location
                            </div>
                            <div class="step mb-3">
                                <strong>Step 2:</strong> AI analyzes and prioritizes reports automatically
                            </div>
                            <div class="step mb-3">
                                <strong>Step 3:</strong> Municipality assigns and tracks resolution
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Demo Credentials -->
        <section class="py-5">
            <div class="container">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="mb-4">Demo Credentials</h3>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <h5>Admin</h5>
                                <p class="mb-1"><strong>Username:</strong> admin_demo</p>
                                <p class="mb-0"><strong>Password:</strong> DemoPass123!</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <h5>Municipality Head</h5>
                                <p class="mb-1"><strong>Username:</strong> muni_demo</p>
                                <p class="mb-0"><strong>Password:</strong> DemoPass123!</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <h5>Civilian</h5>
                                <p class="mb-1"><strong>Username:</strong> user1</p>
                                <p class="mb-0"><strong>Password:</strong> DemoPass123!</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="<?= url('/login.php') ?>" class="btn btn-primary">Try Demo</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2024 CityCare. Smart City Reporting Platform.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

