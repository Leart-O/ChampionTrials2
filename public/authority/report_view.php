<?php
/**
 * Authority Report View
 */
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../app/url_helper.php';
require_once __DIR__ . '/../../app/reports.php';
require_once __DIR__ . '/../../app/ai.php';

requireRole('Authority');

$user = getCurrentUser();
$pdo = getDB();

// Get authority ID for this user
$authorityId = getAuthorityIdForUser($user['user_id']);

if (!$authorityId) {
    // Show error instead of redirecting to avoid loop
    $error = 'Your user account is not linked to an authority. Please contact an administrator.';
    $report = null;
}

if ($authorityId) {
    $reportId = intval($_GET['id'] ?? 0);
    $report = $reportId ? getReport($reportId) : null;
    
    if (!$report) {
        redirect('/authority/dashboard.php');
    }
    
    // Verify this report is assigned to this authority
    if ($report['assigned_to'] != $authorityId) {
        redirect('/authority/dashboard.php');
    }
} else {
    $report = null;
    $reportId = 0;
}

// Get AI priority data and help steps (only if report exists)
$aiData = null;
$helpSteps = null;

if ($report) {
    $stmt = $pdo->prepare("SELECT * FROM ai_logs WHERE report_id = :report_id ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(['report_id' => $reportId]);
    $aiData = $stmt->fetch();
    
    // Check if we have cached help steps in ai_logs (we'll store them in raw_response as JSON)
    if ($aiData && $aiData['raw_response']) {
        $parsed = json_decode($aiData['raw_response'], true);
        if (isset($parsed['help_steps'])) {
            $helpSteps = $parsed['help_steps'];
        }
    }
    
    // Generate help steps if not cached
    if (!$helpSteps && $report) {
        $helpSteps = callAIHelpSteps($reportId, $report['title'], $report['description'], $report['category']);
        
        // Cache the help steps in ai_logs if we have an existing entry
        if ($helpSteps && $aiData) {
            $parsed = json_decode($aiData['raw_response'], true);
            if (!$parsed) $parsed = [];
            $parsed['help_steps'] = $helpSteps;
            
            $updateStmt = $pdo->prepare("UPDATE ai_logs SET raw_response = :raw_response WHERE log_id = :log_id");
            $updateStmt->execute([
                'raw_response' => json_encode($parsed),
                'log_id' => $aiData['log_id']
            ]);
        }
    }
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_status') {
            $statusId = intval($_POST['status_id'] ?? 0);
            if ($statusId) {
                updateReportStatus($reportId, $statusId, $user['user_id'], $_POST['note'] ?? '');
                $success = true;
                $report = getReport($reportId); // Refresh
            }
        } elseif ($action === 'regenerate_help') {
            // Regenerate help steps
            $helpSteps = callAIHelpSteps($reportId, $report['title'], $report['description'], $report['category']);
            if ($helpSteps && $aiData) {
                $parsed = json_decode($aiData['raw_response'], true);
                if (!$parsed) $parsed = [];
                $parsed['help_steps'] = $helpSteps;
                
                $updateStmt = $pdo->prepare("UPDATE ai_logs SET raw_response = :raw_response WHERE log_id = :log_id");
                $updateStmt->execute([
                    'raw_response' => json_encode($parsed),
                    'log_id' => $aiData['log_id']
                ]);
            }
            $success = true;
        }
    }
}

$success = isset($_GET['success']) || $success;
$csrfToken = generateCSRFToken();
$imageBase64 = $report ? ($report['image'] ? base64_encode($report['image']) : null) : null;

// Get status options
$statuses = [];
if ($report) {
    $stmt = $pdo->query("SELECT * FROM report_status ORDER BY status_id");
    $statuses = $stmt->fetchAll();
}
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
            <a href="<?= url('/authority/dashboard.php') ?>" class="text-decoration-none">← Back to Dashboard</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">Action completed successfully!</div>
        <?php endif; ?>
        
        <?php if (!$report): ?>
            <div class="alert alert-warning">
                <p>You cannot view this report. <a href="<?= url('/authority/dashboard.php') ?>">Return to Dashboard</a></p>
            </div>
        <?php else: ?>
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
                        
                        <?php if ($report['action_due']): ?>
                            <div class="mb-3">
                                <strong>Action Due:</strong> <span class="text-danger"><?= formatDate($report['action_due']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- AI Help Steps -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">AI-Generated Help Steps</h5>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            <input type="hidden" name="action" value="regenerate_help">
                            <button type="submit" class="btn btn-sm btn-outline-info">↻ Regenerate</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if ($helpSteps && isset($helpSteps['steps'])): ?>
                            <?php if (isset($helpSteps['summary'])): ?>
                                <div class="alert alert-info mb-3">
                                    <strong>Summary:</strong> <?= h($helpSteps['summary']) ?>
                                </div>
                            <?php endif; ?>
                            <ol class="list-group list-group-numbered">
                                <?php foreach ($helpSteps['steps'] as $index => $step): ?>
                                    <li class="list-group-item">
                                        <?= h($step) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <p class="mb-0">AI help steps are being generated. Please try refreshing the page in a moment.</p>
                                <form method="POST" action="" class="mt-2">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                    <input type="hidden" name="action" value="regenerate_help">
                                    <button type="submit" class="btn btn-sm btn-primary">Generate Help Steps</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Update Status -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Update Status</h5>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            <input type="hidden" name="action" value="update_status">
                            
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
                                <input type="text" class="form-control" name="note" placeholder="Optional note about progress">
                            </div>
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
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($report): ?>
            const lat = <?= floatval($report['latitude']) ?>;
            const lng = <?= floatval($report['longitude']) ?>;
            
            const map = L.map('map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
                subdomains: ['a','b','c']
            }).addTo(map);
            L.marker([lat, lng]).addTo(map);
            <?php else: ?>
            const map = L.map('map').setView([42.6026, 20.9030], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
                subdomains: ['a','b','c']
            }).addTo(map);
            <?php endif; ?>
        });
    </script>
</body>
</html>

