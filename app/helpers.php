<?php
/**
 * Helper Functions
 * Security, CSRF, file upload, and utility functions
 */

/**
 * Escape output for HTML (XSS protection)
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token and store in session
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Upload and validate image, return as binary data for BLOB storage
 * Returns array with 'success' boolean and 'data' (binary) or 'error' message
 */
function uploadImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error'];
    }

    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB'];
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPEG and PNG allowed.'];
    }

    // Additional validation using getimagesize
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'File is not a valid image'];
    }

    // Read file as binary
    $imageData = file_get_contents($file['tmp_name']);
    if ($imageData === false) {
        return ['success' => false, 'error' => 'Failed to read image file'];
    }

    return [
        'success' => true,
        'data' => $imageData,
        'mime_type' => $mimeType
    ];
}

/**
 * Format date for display
 */
function formatDate($timestamp) {
    return date('M d, Y H:i', strtotime($timestamp));
}

/**
 * Calculate distance between two coordinates using Haversine formula (in meters)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Earth radius in meters

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

/**
 * Get status color for UI
 */
function getStatusColor($statusName) {
    $colors = [
        'Pending' => 'warning',
        'In-Progress' => 'info',
        'Fixed' => 'success',
        'Rejected' => 'danger'
    ];
    return $colors[$statusName] ?? 'secondary';
}

/**
 * Get priority color for AI priority display
 */
function getPriorityColor($priority) {
    if ($priority >= 5) return 'danger';
    if ($priority >= 4) return 'warning';
    if ($priority >= 3) return 'info';
    if ($priority >= 2) return 'secondary';
    return 'light';
}

/**
 * Check if user has required role
 */
function hasRole($requiredRole) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userRole = $_SESSION['role_name'] ?? null;
    return $userRole === $requiredRole;
}

