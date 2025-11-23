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

// Handle AJAX request for getting updated AI priorities
if (isset($_GET['get_ai_priorities'])) {
    header('Content-Type: application/json');
    $reportId = intval($_GET['report_id'] ?? 0);
    
    if ($reportId) {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT al.report_id, al.priority, al.reason, al.created_at
            FROM ai_logs al
            WHERE al.report_id = :report_id
            ORDER BY al.created_at DESC
            LIMIT 1
        ");
        $stmt->execute(['report_id' => $reportId]);
        $row = $stmt->fetch();
        
        if ($row) {
            echo json_encode(['aiPriorities' => [$row['report_id'] => $row]]);
        } else {
            echo json_encode(['aiPriorities' => []]);
        }
    }
    exit;
}

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
        } elseif ($action === 'create_authority') {
            require_once __DIR__ . '/../../app/auth.php';
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $contactEmail = trim($_POST['contact_email'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($name) || empty($type) || empty($contactEmail)) {
                $error = 'Please fill in authority name, type, and email.';
            } elseif (empty($username) || empty($email) || empty($password)) {
                $error = 'Please fill in username, email, and password for the authority user account.';
            } else {
                // Create user account for authority first
                $userResult = registerUser($username, $email, $password, 4); // Role 4 = Authority
                if ($userResult['success']) {
                    // Create authority and link to user
                    $result = createAuthority($name, $type, $contactEmail, $notes, $userResult['user_id']);
                    if ($result['success']) {
                        redirect('/municipality/dashboard.php?success=1');
                    } else {
                        $error = 'User account created but authority creation failed: ' . $result['error'];
                        // Try to clean up the user account
                        try {
                            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$userResult['user_id']]);
                        } catch (PDOException $e) {
                            error_log("Failed to clean up user account: " . $e->getMessage());
                        }
                    }
                } else {
                    $error = 'User account creation failed: ' . $userResult['error'];
                }
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
    <style>
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

    <main class="container-fluid my-4 flex-grow-1">
        <h2 class="mb-4 fw-bold text-gradient">Municipality Dashboard</h2>
        
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
                <div class="card mb-3 shadow-custom">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Reports Map</h5>
                    </div>
                    <div class="card-body">
                        <div id="map" style="height: 600px; width: 100%;"></div>
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Status Colors:</strong>
                                    <div class="mt-1">
                                        <span class="badge bg-warning me-2">Pending</span>
                                        <span class="badge bg-info me-2">In-Progress</span>
                                        <span class="badge bg-success me-2">Fixed</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Priority Levels (Border Color):</strong>
                                    <div class="mt-1">
                                        <span class="badge bg-danger me-2">Priority 5 (Red Border)</span>
                                        <span class="badge bg-warning me-2">Priority 4 (Orange Border)</span>
                                        <span class="badge bg-info me-2">Priority 3 (Cyan Border)</span>
                                        <span class="badge bg-primary me-2">Priority 2</span>
                                        <span class="badge bg-dark me-2">Priority 1</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">Marker border thickness indicates priority level</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-3 shadow-custom">
                    <div class="card-header">
                        <h5 class="card-title mb-0">AI Priority Queue</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Reports sorted by AI-assessed urgency</p>
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
                                    <div class="mt-2 d-flex gap-2">
                                        <a href="<?= url('/municipality/report_view.php?id=' . $report['report_id']) ?>" 
                                           class="btn btn-sm btn-outline-primary">View</a>
                                        <button type="button" class="btn btn-sm btn-outline-info rerun-ai-btn" 
                                                data-report-id="<?= $report['report_id'] ?>"
                                                data-csrf-token="<?= h($csrfToken) ?>"
                                                title="Re-run AI Priority Analysis">
                                            ↻ Re-run
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Create Authority -->
                <!-- <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Create New Authority</h5>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#createAuthorityForm" aria-expanded="false" aria-controls="createAuthorityForm">
                            + Add Authority
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="collapse" id="createAuthorityForm">
                            <p class="text-muted small mb-3">Create a new authority and user account. The user will log in with the username and password you provide below.</p>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                <input type="hidden" name="action" value="create_authority">
                                
                                <div class="mb-2">
                                    <label class="form-label small">Authority Name</label>
                                    <input type="text" class="form-control form-control-sm" name="name" placeholder="Authority Name" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Type</label>
                                    <input type="text" class="form-control form-control-sm" name="type" placeholder="Type (e.g., Road Maintenance)" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Contact Email</label>
                                    <input type="email" class="form-control form-control-sm" name="contact_email" placeholder="Contact Email" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Notes (optional)</label>
                                    <input type="text" class="form-control form-control-sm" name="notes" placeholder="Notes">
                                </div>
                                <hr class="my-2">
                                <small class="text-muted">User Account Details (for login):</small>
                                <div class="mb-2">
                                    <label class="form-label small">Username</label>
                                    <input type="text" class="form-control form-control-sm" name="username" placeholder="Username" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">User Email</label>
                                    <input type="email" class="form-control form-control-sm" name="email" placeholder="User Email" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Password</label>
                                    <input type="password" class="form-control form-control-sm" name="password" placeholder="Password (min 8 chars)" required minlength="8">
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-success">Create Authority</button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="collapse" data-bs-target="#createAuthorityForm">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script>
        const reports = <?= json_encode($reports) ?>;
        let aiPriorities = <?= json_encode($aiPriorities) ?>;
        let map;
        let markersByReportId = {};
        
        function getPriorityBorder(priority) {
            let borderColor = 'transparent';
            let borderWidth = '2px';
            if (priority >= 5) {
                borderColor = '#dc3545'; // Red for highest priority
                borderWidth = '4px';
            } else if (priority >= 4) {
                borderColor = '#fd7e14'; // Orange for high priority (different from pending)
                borderWidth = '3px';
            } else if (priority >= 3) {
                borderColor = '#0dcaf0'; // Cyan for medium priority
                borderWidth = '2px';
            }
            return { borderColor, borderWidth };
        }
        
        function updateMarkerIcon(reportId, report) {
            if (!markersByReportId[reportId]) return;
            
            const marker = markersByReportId[reportId];
            const aiData = aiPriorities[reportId] || {};
            const priority = aiData.priority || 1;
            
            // Status-based base color
            let color = 'gray';
            if (report.status_name === 'Pending') color = '#ffc107';
            else if (report.status_name === 'In-Progress') color = '#0dcaf0';
            else if (report.status_name === 'Fixed') color = '#198754';
            
            const { borderColor, borderWidth } = getPriorityBorder(priority);
            
            marker.setIcon(L.divIcon({
                className: 'custom-marker',
                html: `<div style="background-color: ${color}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 ${borderWidth} ${borderColor};"></div>`,
                iconSize: [24, 24]
            }));
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
            
                const markers = L.markerClusterGroup();
                
                reports.forEach(function(report) {
                    if (report.latitude && report.longitude) {
                        const lat = parseFloat(report.latitude);
                        const lng = parseFloat(report.longitude);
                        
                        const aiData = aiPriorities[report.report_id] || {};
                        const priority = aiData.priority || 1;
                        
                        // Status-based base color
                        let color = 'gray';
                        if (report.status_name === 'Pending') color = '#ffc107';
                        else if (report.status_name === 'In-Progress') color = '#0dcaf0';
                        else if (report.status_name === 'Fixed') color = '#198754';
                        
                        const { borderColor, borderWidth } = getPriorityBorder(priority);
                        
                        const marker = L.marker([lat, lng], {
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: `<div style="background-color: ${color}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 ${borderWidth} ${borderColor};"></div>`,
                                iconSize: [24, 24]
                            })
                        });
                        
                        // Store marker reference for updates
                        markersByReportId[report.report_id] = marker;
                        
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
                                <a href="<?= url('/municipality/report_view.php') ?>?id=${report.report_id}" class="btn btn-sm btn-primary" style="text-decoration: none; color: white;">View Details</a>
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
                
                // Handle Re-run AI button clicks
                document.querySelectorAll('.rerun-ai-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        if (!confirm('Re-run AI priority analysis for this report?')) {
                            return;
                        }
                        
                        const reportId = this.getAttribute('data-report-id');
                        const csrfToken = this.getAttribute('data-csrf-token');
                        const btn = this;
                        
                        // Disable button during request
                        btn.disabled = true;
                        btn.innerHTML = '⏳ Analyzing...';
                        
                        fetch('<?= url('/municipality/dashboard.php') ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new URLSearchParams({
                                'action': 'priority',
                                'report_id': reportId,
                                'csrf_token': csrfToken
                            })
                        })
                        .then(response => response.json())
                        .catch(() => {
                            // Server may not return JSON, that's ok - reload page
                            location.reload();
                        })
                        .then(data => {
                            // Fetch updated AI priorities
                            return fetch('<?= url('/municipality/dashboard.php') ?>?get_ai_priorities=1&report_id=' + reportId, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.aiPriorities) {
                                // Update aiPriorities object
                                aiPriorities = { ...aiPriorities, ...data.aiPriorities };
                                
                                // Find the report and update marker
                                const report = reports.find(r => r.report_id == reportId);
                                if (report) {
                                    updateMarkerIcon(reportId, report);
                                }
                                
                                // Reload page to update the priority queue list
                                location.reload();
                            }
                        })
                        .catch(error => {
                            console.log('Error:', error);
                            // Fallback: reload page
                            location.reload();
                        });
                    });
                });
                
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        });
    </script>
</body>
</html>

