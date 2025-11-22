<?php
/**
 * User Dashboard (Civilian)
 */
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../app/url_helper.php';
require_once __DIR__ . '/../../app/reports.php';

requireRole('Civilian');

$user = getCurrentUser();
$reports = getUserReports($user['user_id']);
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
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Reports</h2>
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
                <?php if (empty($reports)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <p class="text-muted mb-3">You haven't submitted any reports yet.</p>
                            <a href="<?= url('/user/submit_report.php') ?>" class="btn btn-primary">Submit Your First Report</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
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
                                <?php foreach ($reports as $report): ?>
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
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Reports Map</h5>
                        <div id="map" style="height: 400px; width: 100%;"></div>
                        <div class="mt-3 small">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-warning me-2">Pending</span>
                                <span class="badge bg-info me-2">In-Progress</span>
                                <span class="badge bg-success">Fixed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map with user reports
        const reports = <?= json_encode($reports) ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Ensure map container exists and is visible
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
                
                const bounds = [];
                
                // Add markers for each report
                reports.forEach(function(report) {
                    if (report.latitude && report.longitude) {
                        const lat = parseFloat(report.latitude);
                        const lng = parseFloat(report.longitude);
                        bounds.push([lat, lng]);
                        
                        let color = 'gray';
                        if (report.status_name === 'Pending') color = 'orange';
                        else if (report.status_name === 'In-Progress') color = 'blue';
                        else if (report.status_name === 'Fixed') color = 'green';
                        
                        const marker = L.marker([lat, lng], {
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: `<div style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white;"></div>`,
                                iconSize: [20, 20]
                            })
                        }).addTo(map);
                        
                        marker.bindPopup(`
                            <strong>${report.title}</strong><br>
                            Status: ${report.status_name}<br>
                            <a href="<?= url('/user/report_view.php?id=') ?>${report.report_id}">View Details</a>
                        `);
                    }
                });
                
                // Fit bounds if we have reports, otherwise keep default view
                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [50, 50] });
                }
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        });
    </script>
</body>
</html>

