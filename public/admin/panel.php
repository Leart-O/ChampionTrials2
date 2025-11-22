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
            $result = createAuthority(
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
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="container my-4">
        <h2 class="mb-4">Admin Panel</h2>
        
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
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary"><?= $stats['total_reports'] ?></h3>
                        <p class="text-muted mb-0">Total Reports</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning"><?= $stats['pending_reports'] ?></h3>
                        <p class="text-muted mb-0">Pending Reports</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-info"><?= $stats['total_users'] ?></h3>
                        <p class="text-muted mb-0">Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success"><?= $stats['total_authorities'] ?></h3>
                        <p class="text-muted mb-0">Authorities</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Reports -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Reports</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
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
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
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
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Manage Authorities</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <input type="hidden" name="action" value="create_authority">
                    
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <input type="text" class="form-control" name="name" placeholder="Name" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="text" class="form-control" name="type" placeholder="Type" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="email" class="form-control" name="contact_email" placeholder="Email" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="text" class="form-control" name="notes" placeholder="Notes">
                        </div>
                        <div class="col-md-1 mb-2">
                            <button type="submit" class="btn btn-primary w-100">Add</button>
                        </div>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table">
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

