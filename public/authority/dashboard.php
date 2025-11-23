<?php
/**
 * Authority Dashboard
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
    // Show error message instead of redirecting to avoid loop
    $error = 'Your user account is not linked to an authority. Please contact an administrator.';
    $reports = [];
    $aiPriorities = [];
} else {
    // Get reports assigned to this authority
    $reports = getAuthorityReports($authorityId);
    
    // Convert images to base64 for JavaScript (remove large BLOBs)
    foreach ($reports as &$report) {
        if ($report['image']) {
            $report['image_base64'] = base64_encode($report['image']);
        }
        unset($report['image']); // Remove large BLOB from JSON
    }
    unset($report);
    
    // Get AI priorities for all reports
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
}

$error = $error ?? '';

// Get reports assigned to this authority
$reports = getAuthorityReports($authorityId);

// Convert images to base64 for JavaScript (remove large BLOBs)
foreach ($reports as &$report) {
    if ($report['image']) {
        $report['image_base64'] = base64_encode($report['image']);
    }
    unset($report['image']); // Remove large BLOB from JSON
}
unset($report);

// Get AI priorities for all reports
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

$success = isset($_GET['success']);
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authority Dashboard - CityCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="container-fluid my-4">
        <h2 class="mb-4">Authority Dashboard</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= h($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
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
                        <h5 class="card-title">Assigned Reports Map</h5>
                        <div id="map" style="height: 600px; width: 100%; min-height: 600px; position: relative; z-index: 0;"></div>
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Status Colors:</strong>
                                    <div class="mt-1">
                                        <span class="badge bg-warning me-2">Pending</span>
                                        <span class="badge bg-info me-2">In-Progress</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Priority Levels (Border Color):</strong>
                                    <div class="mt-1">
                                        <span class="badge bg-danger me-2">Priority 5 (Red Border)</span>
                                        <span class="badge bg-warning me-2">Priority 4 (Orange Border)</span>
                                        <span class="badge bg-info me-2">Priority 3 (Cyan Border)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Assigned Reports</h5>
                        <p class="text-muted small">Reports assigned to your authority</p>
                        <div class="list-group" style="max-height: 600px; overflow-y: auto;">
                            <?php if (empty($reports)): ?>
                                <div class="list-group-item text-center text-muted">
                                    <p class="mb-0">No reports assigned yet.</p>
                                </div>
                            <?php else: ?>
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
                                                <?php if ($report['action_due']): ?>
                                                    <small class="d-block mt-1 text-danger">
                                                        <strong>Due:</strong> <?= formatDate($report['action_due']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <a href="<?= url('/authority/report_view.php?id=' . $report['report_id']) ?>" 
                                               class="btn btn-sm btn-outline-primary">View & Get Help</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
            setTimeout(function() {
                try {
                    const mapContainer = document.getElementById('map');
                    if (!mapContainer) {
                        console.error('Map container not found');
                        return;
                    }
                    
                    if (mapContainer.offsetHeight === 0 || mapContainer.offsetWidth === 0) {
                        mapContainer.style.display = 'block';
                        mapContainer.style.height = '600px';
                        mapContainer.style.width = '100%';
                    }
                    
                    if (typeof L === 'undefined') {
                        console.error('Leaflet library not loaded');
                        return;
                    }
                    
                    const map = L.map('map', {
                        preferCanvas: false
                    }).setView([42.6026, 20.9030], 13);
                    
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors',
                        maxZoom: 19,
                        subdomains: ['a','b','c']
                    }).addTo(map);
                    
                    setTimeout(function() {
                        map.invalidateSize();
                    }, 200);
                    
                    const markers = L.markerClusterGroup();
                    const bounds = [];
                    
                    reports.forEach(function(report) {
                        if (report.latitude && report.longitude) {
                            const lat = parseFloat(report.latitude);
                            const lng = parseFloat(report.longitude);
                            bounds.push([lat, lng]);
                            
                            const aiData = aiPriorities[report.report_id] || {};
                            const priority = aiData.priority || 1;
                            
                            let color = 'gray';
                            if (report.status_name === 'Pending') color = '#ffc107';
                            else if (report.status_name === 'In-Progress') color = '#0dcaf0';
                            
                            let borderColor = 'transparent';
                            let borderWidth = '2px';
                            if (priority >= 5) {
                                borderColor = '#dc3545';
                                borderWidth = '4px';
                            } else if (priority >= 4) {
                                borderColor = '#fd7e14';
                                borderWidth = '3px';
                            } else if (priority >= 3) {
                                borderColor = '#0dcaf0';
                                borderWidth = '2px';
                            }
                            
                            const marker = L.marker([lat, lng], {
                                icon: L.divIcon({
                                    className: 'custom-marker',
                                    html: `<div style="background-color: ${color}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 ${borderWidth} ${borderColor};"></div>`,
                                    iconSize: [24, 24]
                                })
                            });
                            
                            const imageTag = report.image_base64 ? `<img src="data:image/jpeg;base64,${report.image_base64}" style="max-width: 200px; max-height: 150px;" class="img-thumbnail mb-2">` : '';
                            
                            marker.bindPopup(`
                                <div style="min-width: 250px;">
                                    <h6>${report.title}</h6>
                                    ${imageTag}
                                    <p class="mb-1"><small>${report.description.substring(0, 100)}...</small></p>
                                    <p class="mb-1"><strong>Status:</strong> ${report.status_name}</p>
                                    <p class="mb-1"><strong>Priority:</strong> ${priority}/5</p>
                                    <p class="mb-2"><small>${new Date(report.created_at).toLocaleString()}</small></p>
                                    <a href="<?= url('/authority/report_view.php') ?>?id=${report.report_id}" class="btn btn-sm btn-primary" style="text-decoration: none; color: white;">View Details</a>
                                </div>
                            `);
                            
                            markers.addLayer(marker);
                        }
                    });
                    
                    map.addLayer(markers);
                    
                    if (bounds.length > 0) {
                        map.fitBounds(bounds, { padding: [50, 50] });
                    }
                } catch (error) {
                    console.error('Error initializing map:', error);
                }
            }, 100);
        });
    </script>
</body>
</html>

