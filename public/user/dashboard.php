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
                        <div id="map" style="height: 400px; width: 100%; min-height: 400px; position: relative; z-index: 0;"></div>
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
        // Initialize map with user reports
        const reports = <?= json_encode($reports) ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit to ensure container is fully rendered
            setTimeout(function() {
                try {
                    // Ensure map container exists and is visible
                    const mapContainer = document.getElementById('map');
                    if (!mapContainer) {
                        console.error('Map container not found');
                        return;
                    }
                    
                    // Ensure container is visible and has dimensions
                    if (mapContainer.offsetHeight === 0 || mapContainer.offsetWidth === 0) {
                        console.warn('Map container has no dimensions, forcing display');
                        mapContainer.style.display = 'block';
                        mapContainer.style.height = '400px';
                        mapContainer.style.width = '100%';
                    }
                    
                    // Check if Leaflet is loaded
                    if (typeof L === 'undefined') {
                        console.error('Leaflet library not loaded');
                        return;
                    }
                    
                    // Initialize map centered on Pristina, Kosovo
                    const map = L.map('map', {
                        preferCanvas: false
                    }).setView([42.6026, 20.9030], 13);
                    
                    // Add OpenStreetMap tiles
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors',
                        maxZoom: 19,
                        subdomains: ['a','b','c']
                    }).addTo(map);
                    
                    // Force map to recalculate size after initialization
                    setTimeout(function() {
                        map.invalidateSize();
                        console.log('Map initialized successfully');
                    }, 200);
                    
                    const bounds = [];
                    
                    // Filter out Fixed and Rejected reports from map (but keep in table)
                    const mapReports = reports.filter(function(report) {
                        return report.status_name !== 'Fixed' && report.status_name !== 'Rejected';
                    });
                    
                    // Add markers for each active report
                    mapReports.forEach(function(report) {
                        if (report.latitude && report.longitude) {
                            const lat = parseFloat(report.latitude);
                            const lng = parseFloat(report.longitude);
                            bounds.push([lat, lng]);
                            
                            let color = 'gray';
                            if (report.status_name === 'Pending') color = 'orange';
                            else if (report.status_name === 'In-Progress') color = 'blue';
                            
                            const marker = L.marker([lat, lng], {
                                icon: L.divIcon({
                                    className: 'custom-marker',
                                    html: `<div style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white;"></div>`,
                                    iconSize: [20, 20]
                                })
                            });
                            
                            marker.bindPopup(`
                                <strong>${report.title}</strong><br>
                                Status: ${report.status_name}<br>
                                <a href="<?= url('/user/report_view.php?id=') ?>${report.report_id}">View Details</a>
                            `);
                            
                            marker.addTo(map);
                        }
                    });
                    
                    // Fit bounds if we have active reports on map, otherwise keep default view
                    if (bounds.length > 0) {
                        map.fitBounds(bounds, { padding: [50, 50] });
                    } else {
                        // Ensure map is visible even with no reports
                        map.invalidateSize();
                    }
                } catch (error) {
                    console.error('Error initializing map:', error);
                }
            }, 100); // Small delay to ensure container is rendered
            
            // AI modal behavior (initialize once, not per marker)
            (function(){
                    const createAiModalEl = document.getElementById('createAiModal');
                    if (!createAiModalEl) return;
                    const createAiModal = new bootstrap.Modal(createAiModalEl);
                    // floating button to open create modal
                    const floatBtn = document.createElement('button');
                    floatBtn.className = 'btn btn-primary rounded-circle';
                    floatBtn.style.position = 'fixed';
                    floatBtn.style.right = '20px';
                    floatBtn.style.bottom = '20px';
                    floatBtn.style.width = '56px';
                    floatBtn.style.height = '56px';
                    floatBtn.style.zIndex = '2000';
                    floatBtn.innerHTML = '+';
                    floatBtn.title = 'Create Report';
                    floatBtn.addEventListener('click', function(){ createAiModal.show(); });
                    document.body.appendChild(floatBtn);

                    // also hook the top 'Submit New Report' button to open modal
                    const openCreateBtn = document.getElementById('openCreateModal');
                    if (openCreateBtn) {
                        openCreateBtn.addEventListener('click', function(e){
                            e.preventDefault();
                            createAiModal.show();
                        });
                    }

                    // Auto-show modal on page load (per user request)
                    createAiModal.show();

                    const createBlankBtn = document.getElementById('createBlankBtn');
                    if (createBlankBtn) createBlankBtn.addEventListener('click', function(){
                        window.location = '<?= url('/user/submit_report.php') ?>';
                    });

                    const useAiBtn = document.getElementById('useAiBtn');
                    if (useAiBtn) useAiBtn.addEventListener('click', function(){
                        const wrapper = document.getElementById('aiFormWrapper');
                        if (wrapper) wrapper.style.display = 'block';
                    });

                    const aiForm = document.getElementById('aiAssistForm');
                    if (!aiForm) return;
                    aiForm.addEventListener('submit', function(e){
                        e.preventDefault();
                        const errEl = document.getElementById('aiError'); if (errEl) errEl.style.display = 'none';
                        const loadEl = document.getElementById('aiLoading'); if (loadEl) loadEl.style.display = 'block';

                        const fd = new FormData(aiForm);
                        fd.append('action', 'ai_assist');

                        fetch('<?= url('/user/submit_report.php') ?>', {
                            method: 'POST',
                            body: fd,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        }).then(r => r.json()).then(data => {
                            if (loadEl) loadEl.style.display = 'none';
                            if (!data) {
                                if (errEl) { errEl.innerText = 'No response from server'; errEl.style.display = 'block'; }
                                return;
                            }
                            if (data.error) {
                                if (errEl) { errEl.innerText = data.message || data.error; errEl.style.display = 'block'; }
                                return;
                            }

                            // Build query string to pass suggestions to submit page for editing/final submit
                            const params = new URLSearchParams();
                            if (data.title_suggestion) params.set('ai_title', data.title_suggestion);
                            if (data.category_suggestion) params.set('ai_category', data.category_suggestion);
                            if (data.summary) params.set('ai_summary', data.summary);
                            if (data.suggested_lat) params.set('ai_lat', data.suggested_lat);
                            if (data.suggested_lng) params.set('ai_lng', data.suggested_lng);

                            // close modal and redirect to submit page with prefill params
                            createAiModal.hide();
                            window.location = '<?= url('/user/submit_report.php') ?>' + (params.toString() ? ('?' + params.toString()) : '');
                        }).catch(err => {
                            if (loadEl) loadEl.style.display = 'none';
                            if (errEl) { errEl.innerText = err.message || 'Unknown error'; errEl.style.display = 'block'; }
                        });
                    });
                })();
        });
    </script>
</body>
</html>

