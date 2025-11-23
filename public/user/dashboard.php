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
// Get all active reports for the map (so users can see all reports, not just their own)
// Use a longer time period to ensure we get reports
$allReportsForMap = getAllActiveReports(1000, 365); // 1 year instead of 90 days
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
        /* Ensure map container is always visible */
        #map {
            height: 400px !important;
            width: 100% !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
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
                <?php if (empty($reports)): ?>
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
                <div class="card shadow-custom">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Reports Map</h5>
                    </div>
                    <div class="card-body">
                        <div id="map" style="height: 400px !important; width: 100% !important; min-height: 400px !important; position: relative !important; z-index: 0 !important; display: block !important; visibility: visible !important; opacity: 1 !important;"></div>
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
        // Initialize map with ALL active reports (so users can see all reports)
        const allReports = <?= json_encode($allReportsForMap) ?>;
        const userReports = <?= json_encode($reports) ?>;
        
        // Initialize map immediately when script loads
        let mapInstance = null;
        
        function initializeMap() {
            try {
                const mapContainer = document.getElementById('map');
                if (!mapContainer) {
                    console.error('Map container not found');
                    return;
                }
                
                // Force container to be visible with all styles
                mapContainer.style.cssText = 'height: 400px !important; width: 100% !important; min-height: 400px !important; position: relative !important; z-index: 0 !important; display: block !important; visibility: visible !important; opacity: 1 !important;';
                
                // Check if Leaflet is loaded
                if (typeof L === 'undefined') {
                    console.error('Leaflet library not loaded');
                    setTimeout(initializeMap, 100);
                    return;
                }
                
                // Destroy existing map if it exists
                if (mapInstance) {
                    mapInstance.remove();
                }
                
                // Initialize map
                mapInstance = L.map('map', {
                    preferCanvas: false
                }).setView([42.6026, 20.9030], 13);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                    subdomains: ['a','b','c'],
                    errorTileUrl: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
                }).addTo(mapInstance);
                
                // Force multiple invalidateSize calls
                setTimeout(function() {
                    mapInstance.invalidateSize();
                    setTimeout(function() {
                        mapInstance.invalidateSize();
                        setTimeout(function() {
                            mapInstance.invalidateSize();
                        }, 200);
                    }, 200);
                }, 100);
                
                // Window resize handler
                window.addEventListener('resize', function() {
                    if (mapInstance) {
                        setTimeout(function() {
                            mapInstance.invalidateSize();
                        }, 100);
                    }
                });
                
                const bounds = [];
                
                // Process reports for map
                const mapReports = Array.isArray(allReports) ? allReports.filter(function(report) {
                    return report && report.status_name && report.status_name !== 'Fixed' && report.status_name !== 'Rejected';
                }) : [];
                
                const userReportIds = new Set(Array.isArray(userReports) ? userReports.map(r => r.report_id) : []);
                
                // Add markers
                mapReports.forEach(function(report) {
                    if (report && report.latitude && report.longitude) {
                        const lat = parseFloat(report.latitude);
                        const lng = parseFloat(report.longitude);
                        
                        if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                            bounds.push([lat, lng]);
                            
                            let color = 'gray';
                            if (report.status_name === 'Pending') color = 'orange';
                            else if (report.status_name === 'In-Progress') color = 'blue';
                            
                            const isUserReport = userReportIds.has(report.report_id);
                            const borderColor = isUserReport ? '#10b981' : 'white';
                            const borderWidth = isUserReport ? '3px' : '2px';
                            
                            const marker = L.marker([lat, lng], {
                                icon: L.divIcon({
                                    className: 'custom-marker',
                                    html: `<div style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: ${borderWidth} solid ${borderColor}; box-shadow: 0 0 0 2px rgba(0,0,0,0.1);"></div>`,
                                    iconSize: [20, 20]
                                })
                            });
                            
                            const reportType = isUserReport ? '<strong>(Your Report)</strong><br>' : '';
                            marker.bindPopup(`
                                ${reportType}
                                <strong>${report.title || 'Untitled'}</strong><br>
                                Status: ${report.status_name || 'Unknown'}<br>
                                Reporter: ${report.username || 'Anonymous'}<br>
                                ${isUserReport ? '<a href="<?= url('/user/report_view.php?id=') ?>' + report.report_id + '">View Details</a>' : ''}
                            `);
                            
                            marker.addTo(mapInstance);
                        }
                    }
                });
                
                // Fit bounds or keep default view
                if (bounds.length > 0) {
                    setTimeout(function() {
                        if (mapInstance) {
                            mapInstance.fitBounds(bounds, { padding: [50, 50] });
                            mapInstance.invalidateSize();
                        }
                    }, 600);
                } else {
                    setTimeout(function() {
                        if (mapInstance) {
                            mapInstance.invalidateSize();
                        }
                    }, 600);
                }
                
                console.log('Map initialized with', mapReports.length, 'reports');
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        }
        
        // Try multiple times to ensure map loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            setTimeout(initializeMap, 200);
            setTimeout(initializeMap, 500);
        });
        
        // Also try when window loads
        window.addEventListener('load', function() {
            setTimeout(initializeMap, 100);
        });
            
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

