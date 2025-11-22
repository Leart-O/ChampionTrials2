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
            // Check if GROQ API key is configured
            if (!defined('GROQ_API_KEY') || GROQ_API_KEY === '') {
                $error = 'AI assistant requires a GROQ API key. Please configure GROQ_API_KEY in config.php';
            } else {
                $error = 'AI assistant is currently unavailable. Please check your GROQ API key and try again, or fill in the form manually.';
            }
        }
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
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" id="reportForm">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <input type="hidden" name="action" value="submit">
            <input type="hidden" name="latitude" id="latitude" value="">
            <input type="hidden" name="longitude" id="longitude" value="">
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       value="<?= h($aiSuggestions['title_suggestion'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Select category</option>
                                    <option value="pothole" <?= ($aiSuggestions['category_suggestion'] ?? '') === 'pothole' ? 'selected' : '' ?>>Pothole</option>
                                    <option value="lighting" <?= ($aiSuggestions['category_suggestion'] ?? '') === 'lighting' ? 'selected' : '' ?>>Lighting</option>
                                    <option value="water-leak" <?= ($aiSuggestions['category_suggestion'] ?? '') === 'water-leak' ? 'selected' : '' ?>>Water Leak</option>
                                    <option value="garbage/dumping" <?= ($aiSuggestions['category_suggestion'] ?? '') === 'garbage/dumping' ? 'selected' : '' ?>>Garbage/Dumping</option>
                                    <option value="traffic" <?= ($aiSuggestions['category_suggestion'] ?? '') === 'traffic' ? 'selected' : '' ?>>Traffic</option>
                                    <option value="other" <?= ($aiSuggestions['category_suggestion'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?= h($_POST['description'] ?? '') ?></textarea>
                                <small class="text-muted">Describe the issue in detail. Click "Get AI Assistance" for help.</small>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="aiAssistBtn">
                                    <svg width="16" height="16" fill="currentColor" class="me-1">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    Get AI Assistance
                                </button>
                            </div>
                            
                            <?php if ($aiSuggestions): ?>
                                <div class="alert alert-info">
                                    <strong>AI Suggestions:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li><strong>Title:</strong> <?= h($aiSuggestions['title_suggestion'] ?? 'N/A') ?></li>
                                        <li><strong>Category:</strong> <?= h($aiSuggestions['category_suggestion'] ?? 'N/A') ?></li>
                                        <li><strong>Summary:</strong> <?= h($aiSuggestions['summary'] ?? 'N/A') ?></li>
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
    </main>

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
            
                // AI Assist button
                document.getElementById('aiAssistBtn').addEventListener('click', function() {
                    const description = document.getElementById('description').value;
                    if (!description.trim()) {
                        alert('Please enter a description first.');
                        return;
                    }
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        <input type="hidden" name="action" value="ai_assist">
                        <input type="hidden" name="description" value="${description}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                });
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        });
    </script>
</body>
</html>

