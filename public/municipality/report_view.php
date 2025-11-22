<?php
/**
 * Municipality Report View
 */
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../app/url_helper.php';
require_once __DIR__ . '/../../app/reports.php';
require_once __DIR__ . '/../../app/ai.php';
require_once __DIR__ . '/../../app/authorities.php';

requireRole('Municipality Head');

$user = getCurrentUser();
$reportId = intval($_GET['id'] ?? 0);
$report = $reportId ? getReport($reportId) : null;

if (!$report) {
    redirect('/municipality/dashboard.php');
}

// Get AI priority data
$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM ai_logs WHERE report_id = :report_id ORDER BY created_at DESC LIMIT 1");
$stmt->execute(['report_id' => $reportId]);
$aiData = $stmt->fetch();

$authorities = getAllAuthorities();
$statuses = $pdo->query("SELECT * FROM report_status ORDER BY status_id")->fetchAll();

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'assign') {
            $authorityId = intval($_POST['authority_id'] ?? 0);
            $actionDue = $_POST['action_due'] ?? null;
            if ($authorityId) {
                assignReportToAuthority($reportId, $authorityId, $user['user_id'], $actionDue);
                $success = true;
                $report = getReport($reportId); // Refresh
            }
        } elseif ($action === 'status') {
            $statusId = intval($_POST['status_id'] ?? 0);
            if ($statusId) {
                updateReportStatus($reportId, $statusId, $user['user_id'], $_POST['note'] ?? '');
                $success = true;
                $report = getReport($reportId); // Refresh
            }
        } elseif ($action === 'priority') {
            // Re-run AI priority
            callAIPriority($reportId, $report['title'], $report['description'], $report['category']);
            redirect('/municipality/report_view.php?id=' . $reportId . '&success=1');
        }
    }
}

$success = isset($_GET['success']);
$csrfToken = generateCSRFToken();
$imageBase64 = $report['image'] ? base64_encode($report['image']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details - CityCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="container my-4">
        <div class="mb-3">
            <a href="<?= url('/municipality/dashboard.php') ?>" class="text-decoration-none">← Back to Dashboard</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">Action completed successfully!</div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h3><?= h($report['title']) ?></h3>
                            <span class="badge bg-<?= getStatusColor($report['status_name']) ?>">
                                <?= h($report['status_name']) ?>
                            </span>
                        </div>
                        
                        <?php if ($aiData): ?>
                            <div class="alert alert-<?= getPriorityColor($aiData['priority']) ?> mb-3">
                                <strong>AI Priority:</strong> <?= $aiData['priority'] ?>/5<br>
                                <small><?= h($aiData['reason']) ?></small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($imageBase64): ?>
                            <div class="mb-3">
                                <img src="data:image/jpeg;base64,<?= $imageBase64 ?>" 
                                     alt="Report image" class="img-fluid rounded">
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <strong>Category:</strong> <?= h($report['category'] ?: 'Not specified') ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p class="mt-2"><?= nl2br(h($report['description'])) ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Reporter:</strong> <?= h($report['username']) ?> (<?= h($report['email']) ?>)
                        </div>
                        
                        <div class="mb-3">
                            <strong>Created:</strong> <?= formatDate($report['created_at']) ?>
                        </div>
                        
                        <?php if ($report['assigned_to']): ?>
                            <div class="mb-3">
                                <strong>Assigned to:</strong> <?= h($report['authority_name'] ?: 'Unknown') ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($report['action_due']): ?>
                            <div class="mb-3">
                                <strong>Action Due:</strong> <?= formatDate($report['action_due']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Actions</h5>
                        
                        <!-- Assign to Authority -->
                        <form method="POST" action="" class="mb-3">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            <input type="hidden" name="action" value="assign">
                            <input type="hidden" name="report_id" value="<?= $reportId ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <select class="form-select" name="authority_id" required>
                                        <option value="">Select Authority</option>
                                        <?php foreach ($authorities as $auth): ?>
                                            <option value="<?= $auth['id'] ?>" 
                                                    <?= $report['assigned_to'] == $auth['id'] ? 'selected' : '' ?>>
                                                <?= h($auth['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <input type="datetime-local" class="form-control" name="action_due" 
                                           value="<?= $report['action_due'] ? date('Y-m-d\TH:i', strtotime($report['action_due'])) : '' ?>">
                                </div>
                                <div class="col-md-2 mb-2">
                                    <button type="submit" class="btn btn-primary w-100">Assign</button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Update Status -->
                        <form method="POST" action="" class="mb-3">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="report_id" value="<?= $reportId ?>">
                            
                            <div class="row">
                                <div class="col-md-8 mb-2">
                                    <select class="form-select" name="status_id" required>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status['status_id'] ?>" 
                                                    <?= $report['status_id'] == $status['status_id'] ? 'selected' : '' ?>>
                                                <?= h($status['status_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <button type="submit" class="btn btn-warning w-100">Update Status</button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <input type="text" class="form-control" name="note" placeholder="Optional note">
                            </div>
                        </form>
                        
                        <!-- Re-run AI Priority -->
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            <input type="hidden" name="action" value="priority">
                            <input type="hidden" name="report_id" value="<?= $reportId ?>">
                            <button type="submit" class="btn btn-outline-info">Re-run AI Priority Analysis</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Location</h5>
                        <div id="map" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lat = <?= floatval($report['latitude']) ?>;
            const lng = <?= floatval($report['longitude']) ?>;
            
            const map = L.map('map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{s}/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            L.marker([lat, lng]).addTo(map);
        });
    </script>
</body>
</html>

