<?php
/**
 * Admin Panel
 */
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../app/url_helper.php';
require_once __DIR__ . '/../../app/reports.php';
require_once __DIR__ . '/../../app/authorities.php';

requireRole('Admin');

$user = getCurrentUser();
$pdo = getDB();

// Get statistics
$stats = [
    'total_reports' => $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn(),
    'pending_reports' => $pdo->query("SELECT COUNT(*) FROM reports WHERE status_id = 1")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_authorities' => $pdo->query("SELECT COUNT(*) FROM authorities")->fetchColumn(),
];

// Get recent reports
$recentReports = $pdo->query("
    SELECT r.*, s.status_name, u.username
    FROM reports r
    JOIN report_status s ON r.status_id = s.status_id
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.created_at DESC
    LIMIT 10
")->fetchAll();

// Get all users
$allUsers = $pdo->query("
    SELECT u.*, r.role_name
    FROM users u
    JOIN user_roles r ON u.role_id = r.role_id
    ORDER BY u.created_at DESC
")->fetchAll();

// Get all authorities
$allAuthorities = getAllAuthorities();

// Handle actions
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create_authority') {
            require_once __DIR__ . '/../../app/auth.php';
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $contactEmail = trim($_POST['contact_email'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($name) || empty($type) || empty($contactEmail)) {
                $error = 'Please fill in authority name, type, and email.';
            } elseif (empty($username) || empty($email) || empty($password)) {
                $error = 'Please fill in username, email, and password for the authority user account.';
            } else {
                // Create user account for authority first
                $userResult = registerUser($username, $email, $password, 4); // Role 4 = Authority
                if ($userResult['success']) {
                    // Create authority and link to user
                    $result = createAuthority($name, $type, $contactEmail, $notes, $userResult['user_id']);
                    if ($result['success']) {
                        redirect('/admin/panel.php?success=1');
                    } else {
                        $error = 'User account created but authority creation failed: ' . $result['error'];
                        // Try to clean up the user account
                        try {
                            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$userResult['user_id']]);
                        } catch (PDOException $e) {
                            error_log("Failed to clean up user account: " . $e->getMessage());
                        }
                    }
                } else {
                    $error = 'User account creation failed: ' . $userResult['error'];
                }
            }
        } elseif ($action === 'update_authority') {
            $result = updateAuthority(
                intval($_POST['id'] ?? 0),
                $_POST['name'] ?? '',
                $_POST['type'] ?? '',
                $_POST['contact_email'] ?? '',
                $_POST['notes'] ?? ''
            );
            if ($result['success']) {
                redirect('/admin/panel.php?success=1');
            } else {
                $error = $result['error'];
            }
        } elseif ($action === 'delete_authority') {
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                deleteAuthority($id);
                $success = true;
                header('Location: /admin/panel.php?success=1');
                exit;
            }
        } elseif ($action === 'verify_report') {
            $reportId = intval($_POST['report_id'] ?? 0);
            $pdo->prepare("UPDATE reports SET is_verified = 1 WHERE report_id = ?")->execute([$reportId]);
            redirect('/admin/panel.php?success=1');
        } elseif ($action === 'create_user') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $roleId = intval($_POST['role_id'] ?? 0);
            
            if (empty($username) || empty($email) || empty($password) || !$roleId) {
                $error = 'Please fill in all fields.';
            } else {
                require_once __DIR__ . '/../../app/auth.php';
                $result = registerUser($username, $email, $password, $roleId);
                if ($result['success']) {
                    redirect('/admin/panel.php?success=1');
                } else {
                    $error = $result['error'];
                }
            }
        }
    }
}

$success = isset($_GET['success']);
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CityCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <style>
        .navbar, .navbar.navbar-light, .navbar.navbar-dark {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%) !important;
            background-color: #2563eb !important;
        }
        footer, footer.bg-dark {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;
            background-color: #2563eb !important;
            color: #ffffff !important;
        }
        footer p {
            color: #ffffff !important;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="container my-4 flex-grow-1">
        <h2 class="mb-4 fw-bold text-gradient">Admin Panel</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Action completed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="row mb-4 g-4">
            <div class="col-md-3">
                <div class="stats-card primary text-center">
                    <h3 class="text-primary"><?= $stats['total_reports'] ?></h3>
                    <p class="text-muted mb-0 fw-semibold">Total Reports</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card warning text-center">
                    <h3 style="color: #f59e0b;"><?= $stats['pending_reports'] ?></h3>
                    <p class="text-muted mb-0 fw-semibold">Pending Reports</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card info text-center">
                    <h3 style="color: #06b6d4;"><?= $stats['total_users'] ?></h3>
                    <p class="text-muted mb-0 fw-semibold">Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card success text-center">
                    <h3 class="text-success"><?= $stats['total_authorities'] ?></h3>
                    <p class="text-muted mb-0 fw-semibold">Authorities</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Reports -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-custom">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Reports</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="border-radius: var(--radius-md);">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>User</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentReports as $report): ?>
                                        <tr>
                                            <td><?= h(substr($report['title'], 0, 30)) ?>...</td>
                                            <td><?= h($report['username']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusColor($report['status_name']) ?>">
                                                    <?= h($report['status_name']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= url('/municipality/report_view.php?id=' . $report['report_id']) ?>" 
                                                   class="btn btn-sm btn-outline-primary">View</a>
                                                <?php if (!$report['is_verified']): ?>
                                                    <form method="POST" action="" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="verify_report">
                                                        <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">Verify</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-custom">
                    <div class="card-header">
                        <h5 class="mb-0">Users</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="mb-3 p-3 bg-light rounded">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            <input type="hidden" name="action" value="create_user">
                            
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control form-control-sm" name="username" placeholder="Username" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="email" class="form-control form-control-sm" name="email" placeholder="Email" required>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select form-select-sm" name="role_id" required>
                                        <option value="">Role</option>
                                        <option value="2">Municipality Head</option>
                                        <option value="4">Authority</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary btn-sm w-100" title="Create User">+</button>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <input type="password" class="form-control form-control-sm" name="password" placeholder="Password (min 8 chars)" required minlength="8">
                                </div>
                            </div>
                        </form>
                        
                        <div class="table-responsive" style="border-radius: var(--radius-md);">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allUsers as $u): ?>
                                        <tr>
                                            <td><?= h($u['username']) ?></td>
                                            <td><?= h($u['email']) ?></td>
                                            <td><span class="badge bg-secondary"><?= h($u['role_name']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Authorities Management -->
        <div class="card mb-4 shadow-custom">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Manage Authorities</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#createAuthorityForm" aria-expanded="false" aria-controls="createAuthorityForm">
                    + Add New Authority
                </button>
            </div>
            <div class="card-body">
                <div class="collapse mb-4" id="createAuthorityForm">
                    <div class="card card-body" style="background: linear-gradient(135deg, var(--primary-blue-50) 0%, var(--white) 100%);">
                        <h6>Create New Authority</h6>
                        <p class="text-muted small mb-3">The user will log in with the username and password you provide below.</p>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            <input type="hidden" name="action" value="create_authority">
                            
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Authority Name</label>
                                    <input type="text" class="form-control form-control-sm" name="name" placeholder="Authority Name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Type</label>
                                    <input type="text" class="form-control form-control-sm" name="type" placeholder="Type (e.g., Road Maintenance)" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Contact Email</label>
                                    <input type="email" class="form-control form-control-sm" name="contact_email" placeholder="Contact Email" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Notes (optional)</label>
                                    <input type="text" class="form-control form-control-sm" name="notes" placeholder="Notes">
                                </div>
                            </div>
                            <hr class="my-2">
                            <small class="text-muted">User Account Details (for login):</small>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small">Username</label>
                                    <input type="text" class="form-control form-control-sm" name="username" placeholder="Username" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">User Email</label>
                                    <input type="email" class="form-control form-control-sm" name="email" placeholder="User Email" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Password</label>
                                    <input type="password" class="form-control form-control-sm" name="password" placeholder="Password (min 8 chars)" required minlength="8">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-success">Create Authority</button>
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="collapse" data-bs-target="#createAuthorityForm">Cancel</button>
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive" style="border-radius: var(--radius-md);">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Email</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allAuthorities as $auth): ?>
                                <tr>
                                    <td><?= h($auth['name']) ?></td>
                                    <td><?= h($auth['type']) ?></td>
                                    <td><?= h($auth['contact_email']) ?></td>
                                    <td><?= h($auth['notes'] ?? '') ?></td>
                                    <td>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this authority?');">
                                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete_authority">
                                            <input type="hidden" name="id" value="<?= $auth['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

