<?php
/**
 * User Dashboard (Civilian)
 */
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../app/url_helper.php';
require_once __DIR__ . '/../../app/reports.php';

requireRole('Civilian');

// Handle AJAX request for getting updated reports
if (isset($_GET['get_reports'])) {
    header('Content-Type: application/json');
    $reports = getAllActiveReports(1000, 90);
    
    // Convert images to base64 for JavaScript (remove large BLOBs)
    foreach ($reports as &$report) {
        if ($report['image']) {
            $report['image_base64'] = base64_encode($report['image']);
        }
        unset($report['image']); // Remove large BLOB from JSON
    }
    unset($report);
    
    echo json_encode(['reports' => $reports]);
    exit;
}

$user = getCurrentUser();
$userReports = getUserReports($user['user_id']);

// Get ALL active reports for the map (users can see all reports, not just their own)
$allReports = getAllActiveReports(1000, 90);

// Convert images to base64 for JavaScript (remove large BLOBs)
foreach ($allReports as &$report) {
    if ($report['image']) {
        $report['image_base64'] = base64_encode($report['image']);
    }
    unset($report['image']); // Remove large BLOB from JSON
}
unset($report);

$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - CityCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <style>
        /* Force navbar and footer blue - highest priority */
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
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h2 class="fw-bold text-gradient mb-0">My Reports</h2>
            <a href="<?= url('/user/submit_report.php') ?>" class="btn btn-primary">Submit New Report</a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Report submitted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <?php if (empty($userReports)): ?>
                    <div class="card shadow-custom">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <svg width="80" height="80" fill="currentColor" class="text-primary opacity-50">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
                                </svg>
                            </div>
                            <p class="text-muted mb-3 fs-5">You haven't submitted any reports yet.</p>
                            <a href="<?= url('/user/submit_report.php') ?>" class="btn btn-primary btn-lg">Submit Your First Report</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive shadow-custom" style="border-radius: var(--radius-lg);">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userReports as $report): ?>
                                    <tr>
                                        <td><?= h($report['title']) ?></td>
                                        <td><?= h($report['category'] ?: 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusColor($report['status_name']) ?>">
                                                <?= h($report['status_name']) ?>
                                            </span>
                                        </td>
                                        <td><?= formatDate($report['created_at']) ?></td>
                                        <td>
                                            <a href="<?= url('/user/report_view.php?id=' . $report['report_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-custom">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Reports Map</h5>
                    </div>
                    <div class="card-body">
                        <div id="map" style="height: 400px; width: 100%;"></div>
                        <div class="mt-3 small">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="badge bg-warning me-2">Pending</span>
                                <span class="badge bg-info me-2">In-Progress</span>
                                <span class="badge bg-success me-2">Fixed</span>
                                <span class="badge bg-success" style="border: 2px solid #10b981;">Your Reports</span>
                            </div>
                            <p class="text-muted small mb-0">Showing all active reports. Your reports are highlighted with a green border.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

        <!-- AI / Create Modal -->
        <!-- <div class="modal fade" id="createAiModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                         <div class="d-flex gap-3 justify-content-center mb-3">
                            <button id="createBlankBtn" class="btn btn-outline-primary">Create Blank Report</button>
                            <button id="useAiBtn" class="btn btn-primary">Use AI Assistance</button>
                        </div> 

                        <div id="aiFormWrapper" style="display:none;">
                            <form id="aiAssistForm">
                                <div class="mb-3">
                                    <label for="aiDescription" class="form-label">Description</label>
                                    <textarea id="aiDescription" name="description" class="form-control" rows="4" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="aiLat" class="form-label">Latitude (optional)</label>
                                    <input id="aiLat" name="lat" class="form-control" type="text" />
                                </div>
                                <div class="mb-3">
                                    <label for="aiLng" class="form-label">Longitude (optional)</label>
                                    <input id="aiLng" name="lng" class="form-control" type="text" />
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success">Get AI Suggestions</button>
                                </div>
                            </form>
                        </div>

                        <div id="aiLoading" style="display:none; text-align:center;">
                            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                            <div>Contacting AI...</div>
                        </div>

                        <div id="aiError" class="alert alert-danger" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div> -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const initialReports = <?= json_encode($allReports) ?>;
        const userReportIds = new Set(<?= json_encode(array_column($userReports, 'report_id')) ?>);
        let map;
        let currentMarkers = [];
        let currentReports = initialReports;
        
        function clearMarkers() {
            currentMarkers.forEach(marker => marker.remove());
            currentMarkers = [];
        }
        
        function addMarkersToMap(reports) {
            clearMarkers();
            
            reports.forEach(function(report) {
                if (report.latitude && report.longitude) {
                    const lat = parseFloat(report.latitude);
                    const lng = parseFloat(report.longitude);
                    
                    // Status-based base color
                    let color = 'gray';
                    if (report.status_name === 'Pending') color = '#ffc107';
                    else if (report.status_name === 'In-Progress') color = '#0dcaf0';
                    else if (report.status_name === 'Fixed') color = '#198754';
                    
                    const marker = L.marker([lat, lng], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: `<div style="background-color: ${color}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px rgba(0,0,0,0.1);"></div>`,
                            iconSize: [24, 24]
                        })
                    });
                    
                    // Image is already base64 encoded from PHP, or null
                    const imageTag = report.image_base64 ? `<img src="data:image/jpeg;base64,${report.image_base64}" style="max-width: 200px; max-height: 150px;" class="img-thumbnail mb-2">` : '';
                    
                    // Check if this is user's own report
                    const isUserReport = userReportIds.has(report.report_id);
                    const userBadge = isUserReport ? '<span class="badge bg-success me-2">Your Report</span>' : '';
                    
                    marker.bindPopup(`
                        <div style="min-width: 250px;">
                            ${userBadge}
                            <h6>${report.title}</h6>
                            ${imageTag}
                            <p class="mb-1"><small>${report.description.substring(0, 100)}...</small></p>
                            <p class="mb-1"><strong>Status:</strong> ${report.status_name}</p>
                            <p class="mb-1"><small>Reporter: ${report.username || 'Anonymous'}</small></p>
                            <p class="mb-2"><small>${new Date(report.created_at).toLocaleString()}</small></p>
                            <a href="<?= url('/user/report_view.php') ?>?id=${report.report_id}" class="btn btn-sm btn-primary" style="text-decoration: none; color: white;">View Details</a>
                        </div>
                    `);
                    
                    marker.addTo(map);
                    currentMarkers.push(marker);
                }
            });
        }
        
        function refreshMapData() {
            fetch('<?= url('/user/dashboard.php') ?>?get_reports=1')
                .then(response => response.json())
                .then(data => {
                    if (data && data.reports) {
                        currentReports = data.reports;
                        addMarkersToMap(currentReports);
                        
                        // Update user report IDs in case new reports were created
                        // (though we'll keep the original for this user)
                    }
                })
                .catch(error => {
                    console.error('Error refreshing map:', error);
                });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Ensure map container exists
                const mapContainer = document.getElementById('map');
                if (!mapContainer) {
                    console.error('Map container not found');
                    return;
                }
                
                // Initialize map centered on Pristina, Kosovo
                map = L.map('map').setView([42.6026, 20.9030], 13);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                    subdomains: ['a','b','c']
                }).addTo(map);
            
                // Add initial markers
                addMarkersToMap(currentReports);
                
                // Fit bounds if we have reports, otherwise keep default view
                if (currentReports.length > 0) {
                    const bounds = currentReports
                        .filter(r => r.latitude && r.longitude)
                        .map(r => [parseFloat(r.latitude), parseFloat(r.longitude)]);
                    if (bounds.length > 0) {
                        map.fitBounds(bounds, { padding: [50, 50] });
                    }
                }
                
                // Refresh map data every 10 seconds
                setInterval(refreshMapData, 10000);
                
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        });
    </script>
</body>
</html>

