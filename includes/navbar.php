<?php
/**
 * Navigation Bar Component
 */
require_once __DIR__ . '/../app/url_helper.php';
if (!isset($user)) {
    $user = getCurrentUser();
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="<?= url('/index.php') ?>">CityCare</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($user): ?>
                    <?php if ($user['role_name'] === 'Civilian'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/user/dashboard.php') ?>">My Reports</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/user/submit_report.php') ?>">Submit Report</a>
                        </li>
                    <?php elseif ($user['role_name'] === 'Municipality Head'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/municipality/dashboard.php') ?>">Dashboard</a>
                        </li>
                    <?php elseif ($user['role_name'] === 'Authority'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/authority/dashboard.php') ?>">Dashboard</a>
                        </li>
                    <?php elseif ($user['role_name'] === 'Admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/admin/panel.php') ?>">Admin Panel</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?= h($user['username']) ?> (<?= h($user['role_name']) ?>)
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= url('/logout.php') ?>">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/login.php') ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/register.php') ?>">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

