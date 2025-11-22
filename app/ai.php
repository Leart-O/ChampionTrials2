<?php
/**
 * AI Integration with Google AI Studio
 * Handles priority scoring and user assistance features
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config.php';

/**
 * Call Google AI Studio API for chat completions
 * Returns response in OpenRouter-compatible format or false on error
 */
function callOpenRouterAPI($messages, $model = null) {
    // Keep function name for compatibility, but it now calls Google AI Studio
    
    if ($model === null) {
        $model = defined('GOOGLE_AI_MODEL') ? GOOGLE_AI_MODEL : 'gemini-pro';
    }
    
    $apiKey = defined('GOOGLE_AI_API_KEY') ? GOOGLE_AI_API_KEY : '';
    if (empty($apiKey) || $apiKey === 'your-google-ai-api-key-here') {
        error_log("Google AI Studio API key not configured");
        return false;
    }
    
    // Convert messages format to Google AI format
    // Google AI uses contents array with parts containing text
    // System messages are prepended to the first user message
    $contents = [];
    $systemInstruction = '';
    $firstUserMessage = true;
    
    foreach ($messages as $msg) {
        if ($msg['role'] === 'system') {
            $systemInstruction = $msg['content'];
        } elseif ($msg['role'] === 'user') {
            // Prepend system instruction to first user message if present
            $userText = $msg['content'];
            if ($firstUserMessage && !empty($systemInstruction)) {
                $userText = $systemInstruction . "\n\n" . $userText;
                $firstUserMessage = false;
            }
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userText]]
            ];
        } elseif ($msg['role'] === 'assistant') {
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => $msg['content']]]
            ];
        }
    }
    
    // Build request data
    $data = [
        'contents' => $contents
    ];
    
    // Google AI Studio endpoint (use v1beta for better compatibility)
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    // Set timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Google AI Studio API curl error: " . $error);
        return ['error' => 'curl_error', 'message' => $error];
    }
    
    if ($httpCode !== 200) {
        $errorResponse = json_decode($response, true);
        $errorMsg = $errorResponse['error']['message'] ?? $response;
        error_log("Google AI Studio API HTTP error: " . $httpCode . " - " . $errorMsg);
        return ['error' => 'http_error', 'code' => $httpCode, 'message' => $errorMsg];
    }
    
    $decoded = json_decode($response, true);
    if (!$decoded) {
        error_log("Google AI Studio API JSON decode error. Response: " . substr($response, 0, 500));
        return ['error' => 'json_error', 'message' => 'Invalid JSON response'];
    }
    
    // Convert Google AI response to OpenRouter-compatible format
    if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $decoded['candidates'][0]['content']['parts'][0]['text'];
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => $text,
                        'role' => 'assistant'
                    ]
                ]
            ]
        ];
    }
    
    error_log("Google AI Studio API: Unexpected response format");
    return ['error' => 'format_error', 'message' => 'Unexpected response format'];
}

/**
 * Check cache for AI response (simple in-memory cache, can be enhanced with Redis/Memcached)
 * Returns cached response or false
 */
function getAICache($cacheKey) {
    $pdo = getDB();
    
    // Create cache table if it doesn't exist
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ai_cache (
                cache_key VARCHAR(255) PRIMARY KEY,
                response TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (PDOException $e) {
        // Table might already exist, ignore
    }
    
    $stmt = $pdo->prepare("
        SELECT response, created_at FROM ai_cache 
        WHERE cache_key = :key AND created_at > DATE_SUB(NOW(), INTERVAL :ttl SECOND)
    ");
    $stmt->execute(['key' => $cacheKey, 'ttl' => AI_CACHE_TTL]);
    $result = $stmt->fetch();
    
    if ($result) {
        return json_decode($result['response'], true);
    }
    
    return false;
}

/**
 * Store AI response in cache
 */
function setAICache($cacheKey, $response) {
    $pdo = getDB();
    
    // Create cache table if it doesn't exist (simple approach)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ai_cache (
                cache_key VARCHAR(255) PRIMARY KEY,
                response TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (PDOException $e) {
        // Table might already exist, ignore
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO ai_cache (cache_key, response) 
        VALUES (:key, :response)
        ON DUPLICATE KEY UPDATE response = :response, created_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        'key' => $cacheKey,
        'response' => json_encode($response)
    ]);
}

/**
 * Call AI for priority scoring
 * Returns array with priority, reason, confidence or false on error
 */
function callAIPriority($reportId, $title, $description, $category = null, $additionalContext = '') {
    // Create cache key from content
    $cacheKey = 'priority_' . md5($title . $description . $category . $additionalContext);
    
    // Check cache first
    $cached = getAICache($cacheKey);
    if ($cached !== false) {
        return $cached;
    }
    
    // Build prompt
    $prompt = "You are a city management AI assistant. Analyze the following city report and assign a priority score from 1-5 (5 = most urgent, 1 = least urgent).\n\n";
    $prompt .= "Title: {$title}\n";
    $prompt .= "Description: {$description}\n";
    if ($category) {
        $prompt .= "Category: {$category}\n";
    }
    if ($additionalContext) {
        $prompt .= "Additional Context: {$additionalContext}\n";
    }
    $prompt .= "\nPriority Guidelines:\n";
    $prompt .= "- Priority 5: Immediate danger to people or property (e.g., gas leaks, structural damage, electrical hazards near schools)\n";
    $prompt .= "- Priority 4: Major road-blocking issues, frequent reports from same location, safety concerns\n";
    $prompt .= "- Priority 3: Significant issues requiring attention but not immediately dangerous\n";
    $prompt .= "- Priority 2: Normal city repair needed, moderate urgency\n";
    $prompt .= "- Priority 1: Purely aesthetic or minor issues\n\n";
    $prompt .= "Consider keywords like 'dangerous', 'risk to life', 'school', 'bridge', 'gas', 'electrical', 'many reports', 'traffic'.\n\n";
    $prompt .= "Return ONLY valid JSON in this exact format:\n";
    $prompt .= "{\n";
    $prompt .= '  "priority": <int 1-5>,\n';
    $prompt .= '  "reason": "<short human-readable reason, 10-40 words>",\n';
    $prompt .= '  "confidence": <0.0-1.0 numeric or "low"/"med"/"high">\n';
    $prompt .= "}\n";
    $prompt .= "Do not include any other text, only the JSON object.";
    
    $messages = [
        ['role' => 'system', 'content' => 'You are a helpful assistant that returns only valid JSON.'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    $response = callOpenRouterAPI($messages);
    
    // Check for API errors
    if (isset($response['error'])) {
        error_log("AI Priority API error: " . json_encode($response));
        // Fallback to default low priority
        $result = [
            'priority' => 1,
            'reason' => 'Unable to analyze - API error: ' . ($response['message'] ?? 'Unknown error'),
            'confidence' => 'low'
        ];
    } elseif (!$response || !isset($response['choices'][0]['message']['content'])) {
        error_log("AI Priority: Invalid response structure");
        // Fallback to default low priority
        $result = [
            'priority' => 1,
            'reason' => 'Unable to analyze - invalid API response',
            'confidence' => 'low'
        ];
    } else {
        $content = $response['choices'][0]['message']['content'];
        $result = parseAIPriority($content);
        
        // Cache the result
        setAICache($cacheKey, $result);
    }
    
    // Log to database
    logAIPriority($reportId, $result, $response ? json_encode($response) : null);
    
    return $result;
}

/**
 * Parse AI priority response, handling various JSON formats
 * Returns array with priority, reason, confidence
 */
function parseAIPriority($content) {
    // Try to extract JSON from response (in case model adds extra text)
    if (preg_match('/\{[^}]*"priority"[^}]*\}/s', $content, $matches)) {
        $content = $matches[0];
    }
    
    $decoded = json_decode($content, true);
    
    if ($decoded && isset($decoded['priority'])) {
        return [
            'priority' => (int) $decoded['priority'],
            'reason' => $decoded['reason'] ?? 'No reason provided',
            'confidence' => $decoded['confidence'] ?? 'med'
        ];
    }
    
    // Fallback parsing
    if (preg_match('/priority["\']?\s*[:=]\s*(\d)/i', $content, $matches)) {
        $priority = (int) $matches[1];
        $reason = 'Parsed from response';
        if (preg_match('/reason["\']?\s*[:=]\s*["\']?([^"\']+)/i', $content, $reasonMatch)) {
            $reason = $reasonMatch[1];
        }
        
        return [
            'priority' => max(1, min(5, $priority)),
            'reason' => $reason,
            'confidence' => 'low'
        ];
    }
    
    // Ultimate fallback
    return [
        'priority' => 1,
        'reason' => 'Unable to parse AI response - default priority',
        'confidence' => 'low'
    ];
}

/**
 * Log AI priority result to database
 */
function logAIPriority($reportId, $result, $rawResponse = null) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        INSERT INTO ai_logs (report_id, priority, reason, raw_response)
        VALUES (:report_id, :priority, :reason, :raw_response)
    ");
    
    $stmt->execute([
        'report_id' => $reportId,
        'priority' => $result['priority'],
        'reason' => $result['reason'],
        'raw_response' => $rawResponse
    ]);
}

/**
 * Call AI for user assistance (title, category, summary suggestions)
 * Returns array with suggestions or false on error
 */
function callAIAssistant($description) {
    // Create cache key
    $cacheKey = 'assistant_' . md5($description);
    
    // Check cache
    $cached = getAICache($cacheKey);
    if ($cached !== false) {
        return $cached;
    }
    
    $prompt = "You are a helpful assistant for a city reporting platform. A user has written the following description of a city issue:\n\n";
    $prompt .= "{$description}\n\n";
    $prompt .= "Please analyze this and provide:\n";
    $prompt .= "1. A concise, clear title (max 60 characters)\n";
    $prompt .= "2. A category from this list: pothole, lighting, water-leak, garbage/dumping, traffic, other\n";
    $prompt .= "3. A 1-2 sentence summary\n";
    $prompt .= "4. If you can identify a recognizable place name or location, suggest approximate latitude and longitude (nullable if unknown)\n\n";
    $prompt .= "Return ONLY valid JSON in this exact format:\n";
    $prompt .= "{\n";
    $prompt .= '  "title_suggestion": "<suggested title>",\n';
    $prompt .= '  "category_suggestion": "<category from list>",\n';
    $prompt .= '  "summary": "<1-2 sentence summary>",\n';
    $prompt .= '  "suggested_lat": <decimal or null>,\n';
    $prompt .= '  "suggested_lng": <decimal or null>\n';
    $prompt .= "}\n";
    $prompt .= "If location cannot be inferred, use null for lat/lng. Do not include any other text.";
    
    $messages = [
        ['role' => 'system', 'content' => 'You are a helpful assistant that returns only valid JSON.'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    $response = callOpenRouterAPI($messages);
    
    // Check for API errors (new error format)
    if (is_array($response) && isset($response['error'])) {
        $errorMsg = $response['message'] ?? 'Unknown API error';
        error_log("AI Assistant API error: " . json_encode($response));
        return false;
    }
    
    // Check if response is false (old format)
    if ($response === false) {
        error_log("AI Assistant: API call returned false");
        return false;
    }
    
    if (!is_array($response) || !isset($response['choices'][0]['message']['content'])) {
        error_log("AI Assistant: Invalid response structure. Response type: " . gettype($response));
        if (is_array($response)) {
            error_log("AI Assistant: Response content: " . json_encode($response));
        }
        return false;
    }
    
    $content = $response['choices'][0]['message']['content'];
    
    // Try to extract JSON (handle cases where model adds extra text)
    if (preg_match('/\{[^}]*"title_suggestion"[^}]*\}/s', $content, $matches)) {
        $content = $matches[0];
    }
    
    $decoded = json_decode($content, true);
    
    if ($decoded && isset($decoded['title_suggestion'])) {
        // Cache the result
        setAICache($cacheKey, $decoded);
        return $decoded;
    }
    
    error_log("AI Assistant: Failed to parse JSON. Content: " . substr($content, 0, 500));
    return false;
}

