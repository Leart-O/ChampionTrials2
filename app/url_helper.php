<?php
/**
 * URL Helper Functions
 * Generates URLs with correct base path for subdirectory installations
 */

require_once __DIR__ . '/../config.php';

/**
 * Get base path for URLs (handles subdirectory installations)
 */
function basePath() {
    if (defined('BASE_PATH')) {
        return BASE_PATH;
    }
    return '';
}

/**
 * Generate URL with base path
 * Usage: url('/login.php') or url('login.php')
 */
function url($path = '') {
    $base = basePath();
    // Ensure path starts with /
    if ($path && $path[0] !== '/') {
        $path = '/' . $path;
    }
    return $base . $path;
}

/**
 * Redirect to a URL with base path
 */
function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

