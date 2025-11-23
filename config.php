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

// OpenRouter AI API Configuration
// Get your API key from https://openrouter.ai/ and set it here.
if (!defined('OPENROUTER_API_KEY')) {
    // Set your OpenRouter API key here. Leave empty ('') to disable AI features.
    define('OPENROUTER_API_KEY', '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');
}

if (!defined('OPENROUTER_MODEL')) {
    // Default model - you can use any model available on OpenRouter
    // Examples: 'openai/gpt-4', 'anthropic/claude-3-opus', 'google/gemini-pro', 'meta-llama/llama-3.1-70b-instruct'
    define('OPENROUTER_MODEL', 'kwaipilot/kat-coder-pro-v1:free');
}

if (!defined('OPENROUTER_API_URL')) {
    // OpenRouter API endpoint
    define('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions');
}

// Application Settings
define('APP_NAME', 'CityCare');
define('APP_URL', 'http://localhost:8000');

// Base Path - auto-detect installation subdirectory
if (!defined('BASE_PATH')) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    if (strpos($scriptName, '/ChampionTrials2') !== false || strpos($requestUri, '/ChampionTrials2') !== false) {
        define('BASE_PATH', '/ChampionTrials2/public');
    } else {
        define('BASE_PATH', '');
    }
}

// File Upload Settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Session Settings
define('SESSION_LIFETIME', 3600);

// AI Cache Settings
define('AI_CACHE_TTL', 86400);

// Cluster Detection Settings
define('CLUSTER_DISTANCE_METERS', 500);
define('CLUSTER_TIME_HOURS', 48);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

