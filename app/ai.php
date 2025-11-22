<?php
/**
 * AI Integration with GROQ
 * Handles priority scoring and user assistance features
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config.php';

/**
 * Call GROQ API for chat completions
 * Returns response in the application's normalized format (choices[0].message.content)
 * or an array containing an 'error' key on failure.
 */
function callOpenRouterAPI($messages, $model = null) {
    // GROQ-only implementation: use GROQ_API_KEY and GROQ_API_URL
    $groqKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    if (empty($groqKey)) {
        error_log('GROQ API key not configured');
        return ['error' => 'no_api_key', 'message' => 'GROQ API key not configured'];
    }

    // Default to GROQ_MODEL if not provided
    if ($model === null) {
        $model = defined('GROQ_MODEL') ? GROQ_MODEL : 'groq-alpha';
    }

    // Prepare messages for Groq (send as messages array if available)
    $gMessages = [];
    foreach ($messages as $m) {
        $role = $m['role'] ?? 'user';
        $content = $m['content'] ?? '';
        $gMessages[] = ['role' => $role, 'content' => $content];
    }

    $payload = ['model' => $model, 'messages' => $gMessages];

    $url = defined('GROQ_API_URL') ? rtrim(GROQ_API_URL, '/') : 'https://api.groq.com/v1/chat/completions';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $groqKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("GROQ API curl error: {$curlError}");
        return ['error' => 'curl_error', 'message' => $curlError];
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
        $decodedErr = json_decode($response, true);
        $msg = $decodedErr['error']['message'] ?? $decodedErr['message'] ?? $response;
        error_log("GROQ API HTTP error {$httpCode}: " . substr($msg, 0, 1000));
        return ['error' => 'http_error', 'code' => $httpCode, 'message' => $msg];
    }

    $decoded = json_decode($response, true);
    if (!$decoded) {
        error_log("GROQ API JSON decode error. Response: " . substr($response, 0, 500));
        return ['error' => 'json_error', 'message' => 'Invalid JSON response'];
    }

    // Try to extract text from common shapes
    $extracted = null;
    if (isset($decoded['choices'][0]['message']['content']) && is_string($decoded['choices'][0]['message']['content'])) {
        $extracted = $decoded['choices'][0]['message']['content'];
    }
    if ($extracted === null && isset($decoded['choices'][0]['message']['content'][0]['text'])) {
        $extracted = $decoded['choices'][0]['message']['content'][0]['text'];
    }
    if ($extracted === null && isset($decoded['choices'][0]['text'])) {
        $extracted = $decoded['choices'][0]['text'];
    }

    // fallback: search for first non-empty string in response
    if ($extracted === null) {
        try {
            $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($decoded));
            foreach ($it as $v) {
                if (is_string($v) && strlen(trim($v)) > 0) { $extracted = $v; break; }
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    if ($extracted !== null) {
        return ['choices' => [['message' => ['content' => $extracted, 'role' => 'assistant']]]];
    }
    return ['error' => 'format_error', 'message' => 'Unexpected response format', 'raw' => $decoded];
}

/**
 * List available Google Generative models for the configured API key
 * Returns array of model names or an array with 'error' on failure
 */
function listAvailableGoogleModels() {
    // Model listing is not implemented for GROQ in this project.
    // Return a helpful error message so callers can fall back or log diagnostics.
    return ['error' => 'not_supported', 'message' => 'Listing available models is not supported for GROQ in this integration.'];
}

/**
 * List available Google Generative models for the configured API key
 * Returns array of model names or an array with 'error' on failure
 */


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

