<?php
/**
 * Test AI API Connection
 * Run this file to test if your OpenRouter API key is working
 * Usage: http://localhost/ChampionTrials2/public/test_ai.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/ai.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>AI API Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: white; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>OpenRouter API Test</h1>
    
    <?php
    echo "<div class='info'>";
    $openrouterConfigured = defined('OPENROUTER_API_KEY') && OPENROUTER_API_KEY !== '';
    echo "<strong>API Provider:</strong> OpenRouter<br>";
    echo "<strong>API Key:</strong> " . (defined('OPENROUTER_API_KEY') ? (OPENROUTER_API_KEY !== '' ? substr(OPENROUTER_API_KEY, 0, 20) . '...' : 'EMPTY') : 'NOT SET') . "<br>";
    echo "<strong>API URL:</strong> " . (defined('OPENROUTER_API_URL') ? OPENROUTER_API_URL : "NOT SET") . "<br>";
    echo "<strong>Model:</strong> " . (defined('OPENROUTER_MODEL') ? OPENROUTER_MODEL : "NOT SET") . "<br>";
    echo "</div>";

    if (!$openrouterConfigured) {
        echo "<div class='error'>";
        echo "<strong>ERROR:</strong> OpenRouter API key is not configured in config.php<br>";
        echo "Please set OPENROUTER_API_KEY in your config.php file.";
        echo "</div>";
        exit;
    }
    
    echo "<div class='info'>Testing API connection...</div>";
    
    // Test with a simple message
    $testMessages = [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Say "Hello" if you can read this.']
    ];
    
    $response = callOpenRouterAPI($testMessages);
    
    if (isset($response['error'])) {
        echo "<div class='error'>";
        echo "<strong>API Error:</strong><br>";
        echo "Type: " . ($response['error'] ?? 'unknown') . "<br>";
        if (isset($response['code'])) {
            echo "HTTP Code: " . $response['code'] . "<br>";
        }
        if (isset($response['message'])) {
            echo "Message: " . htmlspecialchars($response['message']) . "<br>";
        }
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<strong>Troubleshooting:</strong><br>";
    echo "1. Check if your API key is valid at <a href='https://openrouter.ai/' target='_blank'>https://openrouter.ai/</a><br>";
    echo "2. Verify you have sufficient credits/permissions in your OpenRouter account<br>";
    echo "3. Check if the configured model is available on OpenRouter<br>";
    echo "4. Review PHP error logs for more details";
        echo "</div>";
    } elseif ($response && isset($response['choices'][0]['message']['content'])) {
        echo "<div class='success'>";
        echo "<strong>SUCCESS!</strong> API connection is working.<br>";
        echo "Response: " . htmlspecialchars($response['choices'][0]['message']['content']);
        echo "</div>";
        
        // Test AI Assistant function
        echo "<div class='info'>Testing AI Assistant function...</div>";
        $testDescription = "There is a large pothole on Main Street that needs to be fixed.";
        $assistantResult = callAIAssistant($testDescription);
        
        if ($assistantResult) {
            echo "<div class='success'>";
            echo "<strong>AI Assistant Test:</strong> SUCCESS<br>";
            echo "<pre>" . json_encode($assistantResult, JSON_PRETTY_PRINT) . "</pre>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<strong>AI Assistant Test:</strong> FAILED<br>";
            echo "The assistant function returned false. Check error logs for details.";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>";
        echo "<strong>Unexpected Response:</strong><br>";
        echo "<pre>" . htmlspecialchars(print_r($response, true)) . "</pre>";
        echo "</div>";
    }
    ?>
    
    <div class='info'>
        <strong>Full API Response:</strong>
        <pre><?= htmlspecialchars(json_encode($response ?? [], JSON_PRETTY_PRINT)) ?></pre>
    </div>
</body>
</html>

