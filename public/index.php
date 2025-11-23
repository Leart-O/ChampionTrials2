<?php
// --- Static asset shortcut: serve files from public/ directly (place at top of index.php) ---
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

// Remove /ChampionTrials2/public prefix if it exists (for subdirectory installs)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/ChampionTrials2/public/index.php';
$baseUrl = dirname($scriptName); // e.g., /ChampionTrials2/public

if (strpos($path, $baseUrl) === 0) {
    $path = substr($path, strlen($baseUrl));
    if (!$path || $path[0] !== '/') {
        $path = '/' . $path;
    }
}

// Try to serve static file from public directory
$baseDir = __DIR__;
$localFile = realpath($baseDir . $path);

if ($localFile !== false && strpos($localFile, $baseDir) === 0 &&
    preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|map|woff2?|ttf)$/i', $localFile) &&
    is_file($localFile)
) {
    $mime = @mime_content_type($localFile) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=0'); // dev-friendly
    readfile($localFile);
    exit;
}
// --- end static asset shortcut ---

/**
 * CityCare Landing Page
 */
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/url_helper.php';

startSecureSession();

$error = $_GET['error'] ?? '';

// Redirect if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role_name'] === 'Municipality Head') {
        redirect('/municipality/dashboard.php');
    } elseif ($user['role_name'] === 'Admin') {
        redirect('/admin/panel.php');
    } elseif ($user['role_name'] === 'Authority') {
        // Check if authority is linked before redirecting
        require_once __DIR__ . '/../app/reports.php';
        $authorityId = getAuthorityIdForUser($user['user_id']);
        if ($authorityId) {
            redirect('/authority/dashboard.php');
        }
        // If no authority linked, stay on index page to show error
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
    <link rel="stylesheet" href="<?= url('/assets/css/tours.css') ?>">>
    <style>
        html {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
        }
        main {
            flex: 1;
        }
        footer {
            flex-shrink: 0;
            margin-top: auto;
        }
        /* Force navbar and footer styling */
        .navbar, .navbar.navbar-light, .navbar.navbar-dark {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%) !important;
            background-color: #2563eb !important;
        }
        footer, footer.bg-dark {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #1e40af 100%) !important;
            background-color: #1e40af !important;
            color: #ffffff !important;
        }
        footer p {
            color: #ffffff !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg modern-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= url('/index.php') ?>">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="me-2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <span class="brand-text">CityCare</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?= url('/login.php') ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                <polyline points="10 17 15 12 10 7"></polyline>
                                <line x1="15" y1="12" x2="3" y2="12"></line>
                            </svg>
                            Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-nav-register" href="<?= url('/register.php') ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1">
        <?php if ($error === 'no_authority'): ?>
            <div class="container mt-4">
                <div class="alert alert-warning alert-dismissible fade show">
                    <strong>Notice:</strong> Your Authority account is not linked to an authority record. Please contact an administrator.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 fade-in">
                        <h1 class="display-3 fw-bold mb-4">CityCare</h1>
                        <p class="lead mb-4">Smart Reporting Platform for Better City Management</p>
                        <p class="mb-4">Connect citizens with city management through AI-powered issue reporting and intelligent prioritization.</p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="<?= url('/register.php') ?>" class="btn btn-primary btn-lg">Get Started</a>
                            <a href="<?= url('/login.php') ?>" class="btn btn-outline-primary btn-lg">Login</a>
                        </div>
                    </div>
                    <div class="col-lg-6 text-center fade-in">
                        <div class="hero-image-placeholder">
                            <svg width="300" height="300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- House Tours Section -->
        <section class="py-5" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(147, 51, 234, 0.05));">
            <div class="container">
                <h2 class="text-center mb-3 fw-bold text-gradient">Meet Your Guide</h2>
                <p class="text-center text-muted mb-5" style="max-width: 600px; margin-left: auto; margin-right: auto;">
                    New to CityCare? Choose one of our four house guides to take a personalized tour of the platform. Each guide has their own unique style and perspective!
                </p>
                <div class="row g-4 justify-content-center">
                    <?php
                    require_once __DIR__ . '/../app/tours.php';
                    $tours = getAllTours();
                    foreach ($tours as $tour):
                        $tourData = getTourData($tour);
                    ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="card h-100 tour-card" style="border: 2px solid <?= htmlspecialchars($tourData['color']) ?>; cursor: pointer; transition: all 0.3s ease;" onclick="startTour('<?= htmlspecialchars($tour) ?>')" role="button">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <div style="width: 80px; height: 80px; border-radius: 12px; background: linear-gradient(135deg, <?= htmlspecialchars($tourData['color']) ?>20, <?= htmlspecialchars($tourData['color']) ?>40); margin: 0 auto; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 32px; color: <?= htmlspecialchars($tourData['color']) ?>;">
                                        <?= htmlspecialchars(strtoupper($tourData['name'][0])) ?>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-2" style="color: <?= htmlspecialchars($tourData['color']) ?>;"><?= htmlspecialchars($tourData['title']) ?></h5>
                                <p class="text-muted small mb-3"><?= htmlspecialchars($tourData['description']) ?></p>
                                <button class="btn btn-sm" style="background-color: <?= htmlspecialchars($tourData['color']) ?>; color: white; border: none;">
                                    Start Tour
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Problem Section -->
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-5 fw-bold text-gradient">The Problem</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center p-4">
                                <div class="mb-4">
                                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <svg width="48" height="48" fill="currentColor" style="color: #f59e0b;">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-3">Inefficient Reporting</h5>
                                <p class="text-muted">Citizens struggle to report city issues through traditional channels, leading to delayed responses.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center p-4">
                                <div class="mb-4">
                                    <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <svg width="48" height="48" fill="currentColor" style="color: #ef4444;">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-3">Poor Prioritization</h5>
                                <p class="text-muted">Municipalities lack tools to intelligently prioritize urgent issues over routine maintenance.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center p-4">
                                <div class="mb-4">
                                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <svg width="48" height="48" fill="currentColor" style="color: #06b6d4;">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-3">Lack of Transparency</h5>
                                <p class="text-muted">No visibility into report status, resolution progress, or cluster detection for recurring issues.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Solution Section -->
        <section class="py-5" style="background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);">
            <div class="container">
                <h2 class="text-center mb-5 fw-bold text-gradient">The Solution</h2>
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4">
                        <h3 class="fw-bold mb-4">CityCare Platform</h3>
                        <p class="lead mb-4">A modern, AI-powered platform that connects citizens with city management for faster, smarter issue resolution.</p>
                        <ul class="list-unstyled">
                            <li class="mb-3 d-flex align-items-start">
                                <span class="badge bg-success me-3 mt-1">✓</span>
                                <span><strong>Easy mobile-first reporting</strong> with map integration</span>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <span class="badge bg-success me-3 mt-1">✓</span>
                                <span><strong>AI-assisted report creation</strong> and prioritization</span>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <span class="badge bg-success me-3 mt-1">✓</span>
                                <span><strong>Real-time cluster detection</strong> for recurring issues</span>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <span class="badge bg-success me-3 mt-1">✓</span>
                                <span><strong>Transparent status tracking</strong> and updates</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body p-4">
                                <h5 class="card-title fw-bold mb-4">How It Works</h5>
                                <div class="step">
                                    <strong class="text-primary">Step 1:</strong> Citizens report issues with photos and location
                                </div>
                                <div class="step">
                                    <strong class="text-primary">Step 2:</strong> AI analyzes and prioritizes reports automatically
                                </div>
                                <div class="step">
                                    <strong class="text-primary">Step 3:</strong> Municipality assigns and tracks resolution
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-5" style="background: linear-gradient(135deg, var(--white) 0%, var(--gray-50) 100%);">
            <div class="container">
                <h2 class="text-center mb-5 fw-bold text-gradient">Key Features</h2>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center feature-card">
                            <div class="card-body p-4">
                                <div class="feature-icon mb-3">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <svg width="40" height="40" fill="currentColor" style="color: var(--primary-blue);">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-3">AI-Powered</h5>
                                <p class="text-muted mb-0">Intelligent prioritization and automated report analysis using advanced AI technology.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center feature-card">
                            <div class="card-body p-4">
                                <div class="feature-icon mb-3">
                                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <svg width="40" height="40" fill="currentColor" style="color: var(--accent-yellow);">
                                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-3">Real-Time Tracking</h5>
                                <p class="text-muted mb-0">Monitor report status and resolution progress in real-time with live updates.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center feature-card">
                            <div class="card-body p-4">
                                <div class="feature-icon mb-3">
                                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <svg width="40" height="40" fill="currentColor" style="color: var(--success-green);">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-3">Location-Based</h5>
                                <p class="text-muted mb-0">Interactive maps with precise location tracking for accurate issue reporting.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center feature-card">
                            <div class="card-body p-4">
                                <div class="feature-icon mb-3">
                                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <svg width="40" height="40" fill="currentColor" style="color: #06b6d4;">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-3">Community Driven</h5>
                                <p class="text-muted mb-0">Citizens and authorities work together to build better cities.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="py-5" style="background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%); color: white;">
            <div class="container">
                <div class="row g-4 text-center">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h2 class="display-4 fw-bold mb-2" style="color: var(--accent-yellow);">1000+</h2>
                            <p class="mb-0 opacity-90">Reports Resolved</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h2 class="display-4 fw-bold mb-2" style="color: var(--accent-yellow);">500+</h2>
                            <p class="mb-0 opacity-90">Active Users</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h2 class="display-4 fw-bold mb-2" style="color: var(--accent-yellow);">50+</h2>
                            <p class="mb-0 opacity-90">Authorities</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h2 class="display-4 fw-bold mb-2" style="color: var(--accent-yellow);">24/7</h2>
                            <p class="mb-0 opacity-90">Support Available</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-5 fw-bold text-gradient">Why Choose CityCare?</h2>
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6">
                        <div class="benefit-item mb-4 d-flex">
                            <div class="benefit-icon me-4">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; min-width: 60px;">
                                    <svg width="30" height="30" fill="currentColor" style="color: var(--primary-blue);">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Lightning Fast</h5>
                                <p class="text-muted mb-0">Report issues in seconds with our streamlined mobile-first interface.</p>
                            </div>
                        </div>
                        <div class="benefit-item mb-4 d-flex">
                            <div class="benefit-icon me-4">
                                <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; min-width: 60px;">
                                    <svg width="30" height="30" fill="currentColor" style="color: var(--success-green);">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Secure & Reliable</h5>
                                <p class="text-muted mb-0">Your data is protected with enterprise-grade security measures.</p>
                            </div>
                        </div>
                        <div class="benefit-item mb-4 d-flex">
                            <div class="benefit-icon me-4">
                                <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; min-width: 60px;">
                                    <svg width="30" height="30" fill="currentColor" style="color: var(--accent-yellow);">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Always Improving</h5>
                                <p class="text-muted mb-0">Continuous updates and improvements based on user feedback.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-custom">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-4 text-center">Get Started Today</h5>
                                <p class="text-center text-muted mb-4">Join thousands of citizens making their cities better</p>
                                <div class="d-grid gap-2">
                                    <a href="<?= url('/register.php') ?>" class="btn btn-primary btn-lg">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="8.5" cy="7" r="4"></circle>
                                            <line x1="20" y1="8" x2="20" y2="14"></line>
                                            <line x1="23" y1="11" x2="17" y2="11"></line>
                                        </svg>
                                        Create Free Account
                                    </a>
                                    <a href="<?= url('/login.php') ?>" class="btn btn-outline-primary">
                                        Already have an account? Login
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="row mb-3">
                <div class="col-md-6 text-md-start text-center mb-3 mb-md-0">
                    <h6 style="color: var(--accent-yellow); font-weight: 700; letter-spacing: 1px; margin-bottom: 0.5rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; vertical-align: middle;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>CityCare
                    </h6>
                    <p style="font-size: 0.95rem; margin-bottom: 0;">Smart City Reporting Platform</p>
                </div>
                <div class="col-md-6 text-md-end text-center">
                    <p style="margin: 0; font-size: 0.9rem;">
                        <a href="#" style="margin: 0 0.75rem;">About</a> •
                        <a href="#" style="margin: 0 0.75rem;">Privacy</a> •
                        <a href="#" style="margin: 0 0.75rem;">Contact</a>
                    </p>
                </div>
            </div>
            <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 1.5rem 0;">
            <p class="mb-0" style="text-align: center; font-size: 0.9rem; opacity: 0.85;">&copy; 2024 CityCare. Smart City Reporting Platform. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= url('/assets/js/tours.js') ?>"></script>
</body>
</html>

