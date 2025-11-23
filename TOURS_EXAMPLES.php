<?php
/**
 * House Tour System - Example Usage Patterns
 * 
 * This file demonstrates how to integrate and use the tour system
 * in various scenarios throughout your application.
 * 
 * You can reference these patterns when adding tours to other pages
 * or customizing the tour behavior.
 */

// ============================================================================
// EXAMPLE 1: Adding a Tour Button to a Dashboard Page
// ============================================================================
// Location: Any PHP page header, like public/user/dashboard.php
// ============================================================================

?>
<!-- Add this button somewhere in your dashboard -->
<button class="btn btn-outline-primary" onclick="startTour('engineers')">
    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
    </svg>
    Learn More
</button>

<?php
// ============================================================================
// EXAMPLE 2: Different Tours for Different User Roles
// ============================================================================

// In dashboard.php, customize tour based on user type:
$user = getCurrentUser();
$tourHouse = 'engineers'; // default

switch($user['role_name']) {
    case 'Civilian':
        $tourHouse = 'hipsters';      // Creative, user-friendly
        break;
    case 'Municipality Head':
        $tourHouse = 'engineers';     // Technical, structured
        break;
    case 'Authority':
        $tourHouse = 'speedsters';    // Fast-paced, action-oriented
        break;
    case 'Admin':
        $tourHouse = 'shadows';       // Strategic, comprehensive
        break;
}
?>

<button class="btn btn-sm btn-outline-secondary" 
        onclick="startTour('<?= htmlspecialchars($tourHouse) ?>')">
    Tour Guide: <?= htmlspecialchars(ucfirst($tourHouse)) ?>
</button>

<?php
// ============================================================================
// EXAMPLE 3: Auto-start Tour for First-Time Users
// ============================================================================
// Add this to your page <head> section:
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if user has taken any tour before
        const hasSeenTour = localStorage.getItem('citycare_tour_seen');
        
        if (!hasSeenTour) {
            // Auto-start tour for first-time visitors
            setTimeout(() => {
                startTour('hipsters'); // Friendly introduction
                localStorage.setItem('citycare_tour_seen', 'true');
            }, 1000); // Start after 1 second
        }
    });
</script>

<?php
// ============================================================================
// EXAMPLE 4: Tour Progress Tracking
// ============================================================================
// Add this to track which tours users have taken:
?>

<script>
    // Extend TourManager to track completion
    class TrackedTourManager extends TourManager {
        endTour() {
            // Call parent endTour
            super.endTour();
            
            // Track completion
            if (this.currentTour) {
                const key = `tour_completed_${this.currentTour.name}`;
                localStorage.setItem(key, JSON.stringify({
                    completed: true,
                    date: new Date().toISOString(),
                    steps: this.currentStep
                }));
                
                // Optional: Send to server for analytics
                fetch('/api/track-tour', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        house: this.currentTour.name,
                        completed: true,
                        completedAt: new Date().toISOString()
                    })
                }).catch(() => {}); // Silently fail
            }
        }
    }
</script>

<?php
// ============================================================================
// EXAMPLE 5: Conditional Tour Based on User Preferences
// ============================================================================

// In user dashboard, show tour option if user wants help:
function displayConditionalTourButton($userId) {
    // Could check database for user preference
    $showTour = true; // from database
    
    if ($showTour) {
        ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <div>
                <strong>New to CityCare?</strong> 
                Let a guide walk you through the platform.
            </div>
            <button class="btn btn-sm btn-info" onclick="startTour('hipsters')">
                Start Tour
            </button>
        </div>
        <?php
    }
}
?>

<?php
// ============================================================================
// EXAMPLE 6: Custom Tour Content for Specific Pages
// ============================================================================

// Create a custom tour that focuses on a specific feature:
// (Add this as a new entry in app/tours.php)

'report_submission_tour' => [
    'name' => 'Report Master',
    'title' => 'Master Report Submission',
    'color' => '#7c3aed',
    'personality' => 'guide',
    'steps' => [
        [
            'target' => '#report-form',
            'title' => 'Create Your Report',
            'message' => 'This is where you tell us about an issue in your city.',
            'dialogStyle' => 'Every report makes a difference.'
        ],
        [
            'target' => '#location-picker',
            'title' => 'Mark the Location',
            'message' => 'Click on the map to pinpoint exactly where the issue is.',
            'dialogStyle' => 'Accurate location helps faster response.'
        ],
        [
            'target' => '#category-select',
            'title' => 'Choose a Category',
            'message' => 'Select the type of issue to help authorities prioritize.',
            'dialogStyle' => 'Proper categorization = faster fixes.'
        ],
        [
            'target' => '#submit-btn',
            'title' => 'Submit Your Report',
            'message' => 'Click submit and watch your report get processed instantly!',
            'dialogStyle' => 'Your voice will be heard!'
        ],
    ],
]

// Then in your report submission page:
<button onclick="startTour('report_submission_tour')">
    New to reporting? Get help!
</button>

<?php
// ============================================================================
// EXAMPLE 7: Hide Tour on Specific Pages
// ============================================================================

// In pages where tour might be intrusive (like dashboards with data):
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Disable tour on data-heavy pages
        if (window.location.pathname.includes('/dashboard/') || 
            window.location.pathname.includes('/admin/')) {
            window.tourDisabled = true;
        }
    });
    
    // Check before starting tour
    const originalStartTour = window.startTour;
    window.startTour = function(houseName) {
        if (!window.tourDisabled) {
            originalStartTour(houseName);
        }
    };
</script>

<?php
// ============================================================================
// EXAMPLE 8: Mobile-Optimized Tour
// ============================================================================
// Create shorter tours for mobile users:

// In app/tours.php, add:
'speedsters_mobile' => [
    'name' => 'Quick Start',
    'title' => 'Quick Start Guide',
    'color' => '#dc2626',
    'personality' => 'fast',
    'intro' => ['Quick tour!', 'Let\'s go!'],
    'steps' => [
        // Fewer, simpler steps for mobile
        [
            'target' => '.navbar',
            'title' => 'Navigation',
            'message' => 'Tap here to navigate.',
        ],
        [
            'target' => 'body',
            'title' => 'Submit Reports',
            'message' => 'Report issues here!',
        ],
    ],
    'outro' => ['You\'re all set!', 'Get reporting!'],
]

// JavaScript:
<script>
    // Auto-detect and use mobile tour
    if (window.innerWidth < 768) {
        startTour('speedsters_mobile');
    } else {
        startTour('speedsters');
    }
</script>

<?php
// ============================================================================
// EXAMPLE 9: Tour with Video Integration
// ============================================================================
// Enhance tours with embedded videos:

// In tours.js, modify dialog creation:
/*
if (step.videoUrl) {
    dialogContent += `<iframe width="100%" height="200" 
        src="${step.videoUrl}" 
        frameborder="0" allow="accelerometer" 
        allowfullscreen></iframe>`;
}
*/

// Then in tours.php:
'engineers' => [
    // ... existing config ...
    'steps' => [
        [
            'target' => '#report-form',
            'title' => 'Video Tutorial',
            'message' => 'Watch how to submit a report',
            'videoUrl' => 'https://youtube.com/embed/your-video-id',
        ],
    ],
]

<?php
// ============================================================================
// EXAMPLE 10: API Endpoint for Tour Analytics
// ============================================================================
// Create api/track-tour.php to track tour usage:

/*
<?php
// api/track-tour.php

require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';

header('Content-Type: application/json');
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'] ?? null;
$house = $data['house'] ?? null;

if (!$userId || !$house) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO tour_analytics (user_id, house, completed_at)
        VALUES (:user_id, :house, NOW())
    ");
    $stmt->execute(['user_id' => $userId, 'house' => $house]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
*/

<?php
// ============================================================================
// EXAMPLE 11: Language-Specific Tours
// ============================================================================

// Add language support by creating tour variants:

$translations = [
    'en' => 'English',
    'es' => 'Spanish',
    'fr' => 'French',
];

$userLanguage = $_SESSION['language'] ?? 'en';

// Create multiple tour configurations:
// tours_en.php, tours_es.php, tours_fr.php
// Then load dynamically:

function getLocalizedTour($houseName, $language = 'en') {
    $file = __DIR__ . "/tours_{$language}.php";
    if (file_exists($file)) {
        require_once $file;
        global $TOURS;
        return $TOURS[$houseName] ?? null;
    }
    return null;
}

<?php
// ============================================================================
// EXAMPLE 12: Tour Command in Admin Panel
// ============================================================================

// In admin panel, allow admins to test tours:
?>

<div class="card">
    <div class="card-header">
        <h5>Test Tours</h5>
    </div>
    <div class="card-body">
        <p>Click to preview each house tour:</p>
        <div class="btn-group" role="group">
            <button class="btn btn-outline-dark" onclick="startTour('shadows')">Shadows</button>
            <button class="btn btn-outline-danger" onclick="startTour('hipsters')">Hipsters</button>
            <button class="btn btn-outline-success" onclick="startTour('engineers')">Engineers</button>
            <button class="btn btn-outline-warning" onclick="startTour('speedsters')">Speedsters</button>
        </div>
    </div>
</div>

<?php
// ============================================================================
// EXAMPLE 13: Keyboard Navigation for Tours
// ============================================================================

// Extend tours.js with keyboard support:
/*
document.addEventListener('keydown', function(e) {
    if (!tourManager || !tourManager.isActive) return;
    
    switch(e.key) {
        case 'ArrowRight':
        case ' ':
            tourManager.nextStep();
            break;
        case 'ArrowLeft':
            tourManager.previousStep();
            break;
        case 'Escape':
            tourManager.endTour();
            break;
    }
});
*/

<?php
// ============================================================================
// EXAMPLE 14: Mobile Touch Gestures
// ============================================================================

// Add swipe support to tours:
/*
let touchStartX = 0;

document.addEventListener('touchstart', (e) => {
    if (!tourManager?.isActive) return;
    touchStartX = e.changedTouches[0].clientX;
});

document.addEventListener('touchend', (e) => {
    if (!tourManager?.isActive) return;
    const touchEndX = e.changedTouches[0].clientX;
    const diff = touchStartX - touchEndX;
    
    if (diff > 50) tourManager.nextStep();    // Swipe left
    if (diff < -50) tourManager.previousStep(); // Swipe right
});
*/

<?php
// ============================================================================
// EXAMPLE 15: Integration with User Onboarding Flow
// ============================================================================

// In your registration/first-login flow:
// Add a check_tour_status.php endpoint

// After registration, show:
?>

<div class="onboarding-steps">
    <h3>Welcome to CityCare!</h3>
    <p>Choose how you'd like to get started:</p>
    <button onclick="startTour('hipsters')">Take a Quick Tour</button>
    <button onclick="window.location.href='/user/dashboard.php'">Skip for Now</button>
</div>

<?php
// ============================================================================
// END OF EXAMPLES
// ============================================================================
// 
// These examples demonstrate:
// 1. Basic tour button integration
// 2. Role-based tour selection
// 3. First-time user detection
// 4. Tour completion tracking
// 5. Conditional tour displays
// 6. Custom feature-specific tours
// 7. Page-specific tour disabling
// 8. Mobile optimization
// 9. Video integration
// 10. Backend analytics
// 11. Multi-language support
// 12. Admin testing tools
// 13. Keyboard navigation
// 14. Touch gestures
// 15. Onboarding integration
//
// Mix and match these patterns for your specific needs!
?>
