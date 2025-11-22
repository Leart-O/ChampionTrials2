<?php
/**
 * CityCare Configuration File
 * Copy this to config.php and update with your database credentials
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'citycare');
define('DB_USER', 'root');
define('DB_PASS', '');

// GROQ AI API Configuration (single provider)
// Get your API key from https://groq.com/ and set it here.
if (!defined('GROQ_API_KEY')) {
    // Set your GROQ API key here. Leave empty ('') to disable AI features.
    define('GROQ_API_KEY', '');
}
if (!defined('GROQ_MODEL')) {
    define('GROQ_MODEL', 'groq-alpha'); // change to a model you have access to on Groq
}
if (!defined('GROQ_API_URL')) {
    // Default Groq API endpoint (replace if Groq uses a different path)
    define('GROQ_API_URL', 'https://api.groq.com/v1/chat/completions');
}

// Application Settings
define('APP_NAME', 'CityCare');
define('APP_URL', 'http://localhost:8000'); // Update this to your actual URL

// Base Path - set this based on your installation
// For XAMPP with subdirectory: '/ChampionTrials2/public'
// For PHP built-in server from public folder: ''
// For root installation: ''
if (!defined('BASE_PATH')) {
    // Auto-detect: check if we're in ChampionTrials2 subdirectory
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    if (strpos($scriptName, '/ChampionTrials2') !== false || strpos($requestUri, '/ChampionTrials2') !== false) {
        define('BASE_PATH', '/ChampionTrials2/public');
    } else {
        // For PHP built-in server or root installation
        define('BASE_PATH', '');
    }
}

// File Upload Settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Session Settings
define('SESSION_LIFETIME', 3600); // 1 hour

// AI Cache Settings (in seconds)
define('AI_CACHE_TTL', 86400); // 24 hours

// Cluster Detection Settings
define('CLUSTER_DISTANCE_METERS', 500); // Reports within 500m are considered clustered
define('CLUSTER_TIME_HOURS', 48); // Reports within 48 hours

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

