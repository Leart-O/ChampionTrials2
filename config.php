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

// DeepInfra AI API Configuration
if (!defined('DEEPINFRA_API_KEY')) {
    // Set your DeepInfra API key here. Leave empty ('') to disable AI features.
    define('DEEPINFRA_API_KEY', '0EXwLcZB7lWzTESPtrB4cGP4lEfnD5Rw');
}

if (!defined('DEEPINFRA_MODEL')) {
    // Use DeepSeek V3.2 Experimental model
    define('DEEPINFRA_MODEL', 'mistralai/Mixtral-7B-Instruct-v0.1');
}

if (!defined('DEEPINFRA_API_URL')) {
    define('DEEPINFRA_API_URL', 'https://api.deepinfra.com/v1/openai/chat/completions');
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

/**
 * Example function to call DeepInfra with the new DeepSeek model
 */
function deepinfra_ai_request($prompt) {
    $apiKey = DEEPINFRA_API_KEY;
    $model = DEEPINFRA_MODEL;
    $url = DEEPINFRA_API_URL;

    $data = [
        "model" => $model,
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Example usage:
// $result = deepinfra_ai_request("Hello, DeepSeek!");
// var_dump($result);
