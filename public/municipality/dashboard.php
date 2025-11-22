<?php
/**
 * Municipality Dashboard
 */
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../app/url_helper.php';
require_once __DIR__ . '/../../app/reports.php';
require_once __DIR__ . '/../../app/ai.php';
require_once __DIR__ . '/../../app/authorities.php';

requireRole('Municipality Head');

$user = getCurrentUser();
$reports = getAllActiveReports(1000, 30);

// Convert images to base64 for JavaScript (remove large BLOBs)
foreach ($reports as &$report) {
    if ($report['image']) {
        $report['image_base64'] = base64_encode($report['image']);
    }
    unset($report['image']); // Remove large BLOB from JSON
}
unset($report);

$authorities = getAllAuthorities();

// Get AI priorities for all reports
$pdo = getDB();
$aiPriorities = [];
if (!empty($reports)) {
    $reportIds = array_map('intval', array_column($reports, 'report_id'));
    $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
    $stmt = $pdo->prepare("
        SELECT al.report_id, al.priority, al.reason, al.created_at
        FROM ai_logs al
        WHERE al.report_id IN ($placeholders)
        ORDER BY al.created_at DESC
    ");
    $stmt->execute($reportIds);
    foreach ($stmt->fetchAll() as $row) {
        $aiPriorities[$row['report_id']] = $row;
    }
}

// Sort reports by AI priority (highest first)
usort($reports, function($a, $b) use ($aiPriorities) {
    $priorityA = $aiPriorities[$a['report_id']]['priority'] ?? 1;
    $priorityB = $aiPriorities[$b['report_id']]['priority'] ?? 1;
    return $priorityB <=> $priorityA;
});

// Handle actions
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        $reportId = intval($_POST['report_id'] ?? 0);
        
        if ($action === 'assign') {
            $authorityId = intval($_POST['authority_id'] ?? 0);
            $actionDue = $_POST['action_due'] ?? null;
            if ($authorityId) {
                assignReportToAuthority($reportId, $authorityId, $user['user_id'], $actionDue);
                redirect('/municipality/dashboard.php?success=1');
            }
        } elseif ($action === 'status') {
            $statusId = intval($_POST['status_id'] ?? 0);
            if ($statusId) {
                updateReportStatus($reportId, $statusId, $user['user_id']);
                redirect('/municipality/dashboard.php?success=1');
            }
        } elseif ($action === 'priority') {
            // Trigger AI priority scoring
            $report = getReport($reportId);
            if ($report) {
                callAIPriority($reportId, $report['title'], $report['description'], $report['category']);
                redirect('/municipality/dashboard.php?success=1');
            }
        }
    }
}

$success = isset($_GET['success']);
$csrfToken = generateCSRFToken();

// Get status options
$stmt = $pdo->query("SELECT * FROM report_status ORDER BY status_id");
$statuses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipality Dashboard - CityCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="container-fluid my-4">
        <h2 class="mb-4">Municipality Dashboard</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Action completed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Reports Map</h5>
                        <div id="map" style="height: 600px; width: 100%;"></div>
                        <div class="mt-3 small">
                            <strong>Legend:</strong>
                            <span class="badge bg-warning me-2">Pending</span>
                            <span class="badge bg-info me-2">In-Progress</span>
                            <span class="badge bg-success me-2">Fixed</span>
                            <span class="badge bg-danger me-2">Priority 5</span>
                            <span class="badge bg-warning me-2">Priority 4</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">AI Priority Queue</h5>
                        <p class="text-muted small">Reports sorted by AI-assessed urgency</p>
                        <div class="list-group" style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($reports as $report): ?>
                                <?php 
                                $aiData = $aiPriorities[$report['report_id']] ?? null;
                                $priority = $aiData['priority'] ?? 1;
                                ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= h($report['title']) ?></h6>
                                            <small class="text-muted"><?= formatDate($report['created_at']) ?></small>
                                            <div class="mt-1">
                                                <span class="badge bg-<?= getStatusColor($report['status_name']) ?> me-1">
                                                    <?= h($report['status_name']) ?>
                                                </span>
                                                <span class="badge bg-<?= getPriorityColor($priority) ?>">
                                                    Priority: <?= $priority ?>
                                                </span>
                                            </div>
                                            <?php if ($aiData && $aiData['reason']): ?>
                                                <small class="d-block mt-1 text-muted"><?= h($aiData['reason']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <a href="<?= url('/municipality/report_view.php?id=' . $report['report_id']) ?>" 
                                           class="btn btn-sm btn-outline-primary">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script>
        const reports = <?= json_encode($reports) ?>;
        const aiPriorities = <?= json_encode($aiPriorities) ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Ensure map container exists
                const mapContainer = document.getElementById('map');
                if (!mapContainer) {
                    console.error('Map container not found');
                    return;
                }
                
                // Initialize map centered on Pristina, Kosovo
                const map = L.map('map').setView([42.6026, 20.9030], 13);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                    subdomains: ['a','b','c']
                }).addTo(map);
            
            const markers = L.markerClusterGroup();
            
            reports.forEach(function(report) {
                if (report.latitude && report.longitude) {
                    const lat = parseFloat(report.latitude);
                    const lng = parseFloat(report.longitude);
                    
                    const aiData = aiPriorities[report.report_id] || {};
                    const priority = aiData.priority || 1;
                    
                    let color = 'gray';
                    if (report.status_name === 'Pending') color = 'orange';
                    else if (report.status_name === 'In-Progress') color = 'blue';
                    else if (report.status_name === 'Fixed') color = 'green';
                    
                    const marker = L.marker([lat, lng], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: `<div style="background-color: ${color}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px ${priority >= 4 ? 'red' : 'transparent'};"></div>`,
                            iconSize: [24, 24]
                        })
                    });
                    
                    // Image is already base64 encoded from PHP, or null
                    const imageTag = report.image_base64 ? `<img src="data:image/jpeg;base64,${report.image_base64}" style="max-width: 200px; max-height: 150px;" class="img-thumbnail mb-2">` : '';
                    
                    marker.bindPopup(`
                        <div style="min-width: 250px;">
                            <h6>${report.title}</h6>
                            ${imageTag}
                            <p class="mb-1"><small>${report.description.substring(0, 100)}...</small></p>
                            <p class="mb-1"><strong>Status:</strong> ${report.status_name}</p>
                            <p class="mb-1"><strong>Priority:</strong> ${priority}/5</p>
                            <p class="mb-1"><small>Reporter: ${report.username || 'Anonymous'}</small></p>
                            <p class="mb-2"><small>${new Date(report.created_at).toLocaleString()}</small></p>
                            <a href="<?= url('/municipality/report_view.php') ?>?id=${report.report_id}" class="btn btn-sm btn-primary">View Details</a>
                        </div>
                    `);
                    
                    markers.addLayer(marker);
                }
            });
            
            map.addLayer(markers);
            
                // Fit bounds if we have reports, otherwise keep default view
                if (reports.length > 0) {
                    const bounds = reports
                        .filter(r => r.latitude && r.longitude)
                        .map(r => [parseFloat(r.latitude), parseFloat(r.longitude)]);
                    if (bounds.length > 0) {
                        map.fitBounds(bounds, { padding: [50, 50] });
                    }
                }
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        });
    </script>
</body>
</html>

