<?php
/**
 * Submit Report Page (Civilian)
 */
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../app/url_helper.php';
require_once __DIR__ . '/../../app/reports.php';
require_once __DIR__ . '/../../app/ai.php';

requireRole('Civilian');

$user = getCurrentUser();
$error = '';
$success = false;
$aiSuggestions = null;

// Handle AI assistant request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ai_assist') {
    $description = trim($_POST['description'] ?? '');
    if (!empty($description)) {
        $aiSuggestions = callAIAssistant($description);
        if (!$aiSuggestions) {
            // Check if OpenRouter API key is configured
            if (!defined('OPENROUTER_API_KEY') || OPENROUTER_API_KEY === '') {
                $error = 'AI assistant requires an OpenRouter API key. Please configure OPENROUTER_API_KEY in config.php';
            } else {
                $error = 'AI assistant is currently unavailable. Please check your OpenRouter API key and try again, or fill in the form manually.';
            }
        }
    }
    // If this was an AJAX request, return JSON immediately
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        if ($aiSuggestions && is_array($aiSuggestions)) {
            echo json_encode($aiSuggestions);
        } else {
            echo json_encode(['error' => 'ai_unavailable', 'message' => $error ?: 'AI assistant returned no suggestions']);
        }
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $latitude = floatval($_POST['latitude'] ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);
        
        if (empty($title) || empty($description)) {
            $error = 'Please fill in title and description.';
        } elseif ($latitude == 0 && $longitude == 0) {
            $error = 'Please select a location on the map.';
        } else {
            // Handle image upload
            $imageData = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadImage($_FILES['image']);
                if ($uploadResult['success']) {
                    $imageData = $uploadResult['data'];
                } else {
                    $error = $uploadResult['error'];
                }
            }
            
            if (empty($error)) {
                $result = createReport($user['user_id'], $title, $description, $imageData, $latitude, $longitude, $category);
                
                if ($result['success']) {
                    // Trigger AI priority scoring in background (async would be better, but simple call for now)
                    $report = getReport($result['report_id']);
                    if ($report) {
                        callAIPriority($result['report_id'], $title, $description, $category);
                    }
                    
                    redirect('/user/dashboard.php?success=1');
                } else {
                    $error = $result['error'];
                }
            }
        }
    }
}

$csrfToken = generateCSRFToken();

// Prefill from query parameters (used when redirected after AI assist)
$prefill_title = trim($_GET['ai_title'] ?? ($aiSuggestions['title_suggestion'] ?? ''));
$prefill_category = trim($_GET['ai_category'] ?? ($aiSuggestions['category_suggestion'] ?? ''));
$prefill_summary = trim($_GET['ai_summary'] ?? ($aiSuggestions['summary'] ?? ''));
$prefill_lat = trim($_GET['ai_lat'] ?? ($aiSuggestions['suggested_lat'] ?? ''));
$prefill_lng = trim($_GET['ai_lng'] ?? ($aiSuggestions['suggested_lng'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Report - CityCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="container my-4">
        <h2 class="mb-4">Submit New Report</h2>

        <!-- Choice: Manual or AI assisted -->
        <div class="row mb-4" id="submissionChoice">
            <div class="col-md-6 mb-2">
                <div class="card h-100 choice-card" id="manualChoice" style="cursor:pointer;">
                    <div class="card-body d-flex flex-column justify-content-center align-items-start">
                        <h5 class="card-title">Submit Manually</h5>
                        <p class="card-text">Fill out all fields manually (title, category, description, location, image).</p>
                        <button class="btn btn-outline-primary mt-2" id="chooseManualBtn">Choose Manual</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-2">
                <div class="card h-100 choice-card border-primary" id="aiChoice" style="cursor:pointer;">
                    <div class="card-body d-flex flex-column justify-content-center align-items-start">
                        <h5 class="card-title">Use AI Assistance</h5>
                        <p class="card-text">Enter only description, location and image. AI will suggest title, category and rewrite the description for you to review.</p>
                        <button class="btn btn-primary mt-2" id="chooseAiBtn">Use AI</button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        
        <div id="reportFormContainer" class="d-none">
        <form method="POST" action="" enctype="multipart/form-data" id="reportForm">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <input type="hidden" name="action" value="submit">
            <input type="hidden" name="latitude" id="latitude" value="<?= h($prefill_lat) ?>">
            <input type="hidden" name="longitude" id="longitude" value="<?= h($prefill_lng) ?>">
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3 manual-field" id="titleField">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                        value="<?= h($prefill_title ?: ($_POST['title'] ?? '')) ?>">
                            </div>
                            
                            <div class="mb-3 manual-field" id="categoryField">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Select category</option>
                                    <option value="pothole" <?= ($prefill_category === 'pothole' || ($_POST['category'] ?? '') === 'pothole') ? 'selected' : '' ?>>Pothole</option>
                                    <option value="lighting" <?= ($prefill_category === 'lighting' || ($_POST['category'] ?? '') === 'lighting') ? 'selected' : '' ?>>Lighting</option>
                                    <option value="water-leak" <?= ($prefill_category === 'water-leak' || ($_POST['category'] ?? '') === 'water-leak') ? 'selected' : '' ?>>Water Leak</option>
                                    <option value="garbage/dumping" <?= ($prefill_category === 'garbage/dumping' || ($_POST['category'] ?? '') === 'garbage/dumping') ? 'selected' : '' ?>>Garbage/Dumping</option>
                                    <option value="traffic" <?= ($prefill_category === 'traffic' || ($_POST['category'] ?? '') === 'traffic') ? 'selected' : '' ?>>Traffic</option>
                                    <option value="other" <?= ($prefill_category === 'other' || ($_POST['category'] ?? '') === 'other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?= h($_POST['description'] ?? $prefill_summary) ?></textarea>
                                <small class="text-muted">Describe the issue in detail. Click "Get AI Assistance" for help.</small>
                            </div>
                            
                            <!-- Inline AI button removed - use the selection above to choose AI workflow -->
                            
                            <?php if ($aiSuggestions || $prefill_title || $prefill_category || $prefill_summary): ?>
                                <div class="alert alert-info">
                                    <strong>AI Suggestions:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li><strong>Title:</strong> <?= h($prefill_title ?: ($aiSuggestions['title_suggestion'] ?? 'N/A')) ?></li>
                                        <li><strong>Category:</strong> <?= h($prefill_category ?: ($aiSuggestions['category_suggestion'] ?? 'N/A')) ?></li>
                                        <li><strong>Summary:</strong> <?= h($prefill_summary ?: ($aiSuggestions['summary'] ?? 'N/A')) ?></li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Photo (JPEG/PNG, max 5MB)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/jpg">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Location</h5>
                            <p class="text-muted small">Click on the map to set the location, or use the geolocate button.</p>
                            <div id="map" style="height: 300px; width: 100%;" class="mb-3"></div>
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" id="geolocateBtn">
                                Use My Location
                            </button>
                            <div class="mt-2 small text-muted">
                                <div>Lat: <span id="latDisplay">-</span></div>
                                <div>Lng: <span id="lngDisplay">-</span></div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Submit Report</button>
                    <a href="<?= url('/user/dashboard.php') ?>" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                </div>
            </div>
        </form>
        </div>
    </main>

    <!-- AI Review Modal -->
    <div class="modal fade" id="aiReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review AI Suggestions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" id="aiTitleInput" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select id="aiCategoryInput" class="form-select">
                            <option value="">Select category</option>
                            <option value="pothole">Pothole</option>
                            <option value="lighting">Lighting</option>
                            <option value="water-leak">Water Leak</option>
                            <option value="garbage/dumping">Garbage/Dumping</option>
                            <option value="traffic">Traffic</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (edited)</label>
                        <textarea id="aiDescriptionInput" class="form-control" rows="6"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="aiConfirmBtn" class="btn btn-primary">Confirm & Submit</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="/assets/js/map.js"></script>
    <script>
        // Initialize map for report submission
        let map, marker;
        
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
                
                // Add OpenStreetMap tiles (correct template)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                    subdomains: ['a','b','c']
                }).addTo(map);
            
            // Click handler to set location
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;
                document.getElementById('latDisplay').textContent = lat.toFixed(7);
                document.getElementById('lngDisplay').textContent = lng.toFixed(7);
                
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker([lat, lng]).addTo(map);
            });

            // If AI provided lat/lng (via prefill), set marker
            const prefillLat = <?= $prefill_lat !== '' ? json_encode((float)$prefill_lat) : 'null' ?>;
            const prefillLng = <?= $prefill_lng !== '' ? json_encode((float)$prefill_lng) : 'null' ?>;
            if (prefillLat !== null && prefillLng !== null) {
                document.getElementById('latitude').value = prefillLat;
                document.getElementById('longitude').value = prefillLng;
                document.getElementById('latDisplay').textContent = parseFloat(prefillLat).toFixed(7);
                document.getElementById('lngDisplay').textContent = parseFloat(prefillLng).toFixed(7);
                if (marker) { map.removeLayer(marker); }
                marker = L.marker([prefillLat, prefillLng]).addTo(map);
                map.setView([prefillLat, prefillLng], 15);
            }
            
            // Geolocate button
            document.getElementById('geolocateBtn').addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        map.setView([lat, lng], 15);

                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                        document.getElementById('latDisplay').textContent = lat.toFixed(7);
                        document.getElementById('lngDisplay').textContent = lng.toFixed(7);

                        if (marker) {
                            map.removeLayer(marker);
                        }
                        marker = L.marker([lat, lng]).addTo(map);
                    }, function(err) {
                        console.error('Geolocation error', err);
                        if (err.code === err.PERMISSION_DENIED) {
                            alert('Permission denied. Please allow location access in your browser.');
                        } else {
                            alert('Unable to retrieve your location.');
                        }
                    }, { enableHighAccuracy: true, timeout: 10000 });
                } else {
                    alert('Geolocation is not supported by your browser.');
                }
            });
            
            // Choice buttons and AI flow
            const csrfToken = <?= json_encode($csrfToken) ?>;
            let submissionMode = null; // 'manual' or 'ai'

            const submissionChoiceEl = document.getElementById('submissionChoice');
            const reportFormContainer = document.getElementById('reportFormContainer');
            const chooseManualBtn = document.getElementById('chooseManualBtn');
            const chooseAiBtn = document.getElementById('chooseAiBtn');
            const titleField = document.getElementById('titleField');
            const categoryField = document.getElementById('categoryField');
            const titleInput = document.getElementById('title');
            const categorySelect = document.getElementById('category');
            const descriptionInput = document.getElementById('description');

            function setMode(mode) {
                submissionMode = mode;
                // show form, hide choice
                submissionChoiceEl.classList.add('d-none');
                reportFormContainer.classList.remove('d-none');

                if (mode === 'manual') {
                    // show manual fields
                    titleField.classList.remove('d-none');
                    categoryField.classList.remove('d-none');
                    titleInput.required = true;
                } else if (mode === 'ai') {
                    // hide manual title/category (AI will fill)
                    titleField.classList.add('d-none');
                    categoryField.classList.add('d-none');
                    titleInput.required = false;
                }
            }

            chooseManualBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setMode('manual');
            });

            chooseAiBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setMode('ai');
            });

            // Intercept form submit when AI mode to request suggestions and show review modal
            document.getElementById('reportForm').addEventListener('submit', async function(e) {
                if (submissionMode !== 'ai') {
                    return; // allow normal submit for manual
                }
                e.preventDefault();

                const desc = descriptionInput.value.trim();
                const lat = document.getElementById('latitude').value;
                const lng = document.getElementById('longitude').value;

                if (!desc) {
                    alert('Please enter a description before using AI assistance.');
                    return;
                }
                if (!lat || !lng || parseFloat(lat) === 0 && parseFloat(lng) === 0) {
                    alert('Please select a location on the map before using AI assistance.');
                    return;
                }

                // Prepare AI request
                const fd = new FormData();
                fd.append('csrf_token', csrfToken);
                fd.append('action', 'ai_assist');
                fd.append('description', desc);

                try {
                    const resp = await fetch(window.location.pathname + window.location.search, {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await resp.json();
                    if (data.error) {
                        alert('AI assistant error: ' + (data.message || data.error));
                        return;
                    }

                    // Populate review modal fields
                    document.getElementById('aiTitleInput').value = data.title_suggestion || data.title || '';
                    document.getElementById('aiCategoryInput').value = data.category_suggestion || data.category || '';
                    document.getElementById('aiDescriptionInput').value = data.summary || data.rewrite || data.summary_suggestion || desc;

                    // Show review modal
                    const aiModalEl = document.getElementById('aiReviewModal');
                    const aiModal = new bootstrap.Modal(aiModalEl);
                    aiModal.show();

                    // Confirm handler
                    document.getElementById('aiConfirmBtn').onclick = function() {
                        // Copy AI values back to the (hidden) form fields then submit
                        titleInput.value = document.getElementById('aiTitleInput').value;
                        categorySelect.value = document.getElementById('aiCategoryInput').value;
                        descriptionInput.value = document.getElementById('aiDescriptionInput').value;

                        // Reveal manual fields briefly so values get submitted (they are in the form regardless)
                        titleField.classList.remove('d-none');
                        categoryField.classList.remove('d-none');

                        aiModal.hide();
                        // Submit the form normally now that fields are populated
                        document.getElementById('reportForm').submit();
                    };

                } catch (err) {
                    console.error('AI request failed', err);
                    alert('Failed to contact AI assistant. Please try again or submit manually.');
                }
            });
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        });
    </script>
</body>
</html>

