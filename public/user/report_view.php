<?php
/**
 * View Report Page (Civilian)
 */
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../app/url_helper.php';
require_once __DIR__ . '/../../app/reports.php';

requireRole('Civilian');

$user = getCurrentUser();
$reportId = intval($_GET['id'] ?? 0);
$report = $reportId ? getReport($reportId) : null;

if (!$report) {
    redirect('/user/dashboard.php');
}

// Check ownership
if ($report['user_id'] != $user['user_id']) {
    redirect('/user/dashboard.php');
}

$error = '';
$success = false;

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } elseif ($report['is_verified']) {
        $error = 'Cannot edit verified report.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        
        $result = updateReport($reportId, $user['user_id'], $title, $description, $category);
        
        if ($result['success']) {
            $success = true;
            $report = getReport($reportId); // Refresh
        } else {
            $error = $result['error'];
        }
    }
}


$csrfToken = generateCSRFToken();
$imageBase64 = $report['image'] ? base64_encode($report['image']) : null;
$imageMime = 'image/jpeg'; // Default, could detect from BLOB
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

    <main class="container my-4 flex-grow-1">
        <div class="mb-3">
            <a href="<?= url('/user/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">← Back to Dashboard</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= h($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">Report updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3 shadow-custom">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><?= h($report['title']) ?></h3>
                            <span class="badge bg-<?= getStatusColor($report['status_name']) ?>">
                                <?= h($report['status_name']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($imageBase64): ?>
                            <div class="mb-3">
                                <img src="data:<?= $imageMime ?>;base64,<?= $imageBase64 ?>" 
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
                            <strong>Created:</strong> <?= formatDate($report['created_at']) ?>
                        </div>
                        
                        <?php if ($report['assigned_to']): ?>
                            <div class="mb-3">
                                <strong>Assigned to:</strong> <?= h($report['authority_name'] ?: 'Unknown') ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$report['is_verified']): ?>
                            <hr class="my-4">
                            <h5 class="fw-bold mb-3">Edit Report</h5>
                            <form method="POST" action="" class="p-3 bg-light rounded">
                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                <input type="hidden" name="action" value="update">
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?= h($report['title']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">Select category</option>
                                        <option value="pothole" <?= $report['category'] === 'pothole' ? 'selected' : '' ?>>Pothole</option>
                                        <option value="lighting" <?= $report['category'] === 'lighting' ? 'selected' : '' ?>>Lighting</option>
                                        <option value="water-leak" <?= $report['category'] === 'water-leak' ? 'selected' : '' ?>>Water Leak</option>
                                        <option value="garbage/dumping" <?= $report['category'] === 'garbage/dumping' ? 'selected' : '' ?>>Garbage/Dumping</option>
                                        <option value="traffic" <?= $report['category'] === 'traffic' ? 'selected' : '' ?>>Traffic</option>
                                        <option value="other" <?= $report['category'] === 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required><?= h($report['description']) ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Update Report</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-custom">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Location</h5>
                    </div>
                    <div class="card-body">
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
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
                subdomains: ['a','b','c']
            }).addTo(map);
            L.marker([lat, lng]).addTo(map);
        });
    </script>
</body>
</html>

