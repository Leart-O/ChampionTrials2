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

    // Build a simple text fallback payload (some endpoints expect `input` or `prompt`)
    $simpleText = '';
    foreach ($gMessages as $m) {
        $simpleText .= strtoupper($m['role']) . ": " . ($m['content'] ?? '') . "\n";
    }

    // Initialize common variables
    $lastError = null;
    $response = null;
    $httpCode = 0;
    // Configured GROQ URL from config.php (may be empty)
    $configuredUrl = defined('GROQ_API_URL') ? rtrim(GROQ_API_URL, '/') : '';

    // If configured URL explicitly targets /outputs, try payload shapes compatible with that endpoint only
    if ($configuredUrl !== '' && stripos($configuredUrl, '/outputs') !== false) {
        $structuredInput = $gMessages; // array of ['role'=>..., 'content'=>...]
        $fallbackPayloads = [
            ['model' => $model, 'input' => $structuredInput],
            ['model' => $model, 'input' => $simpleText],
            ['model' => $model, 'prompt' => $simpleText],
            ['model' => 'models/' . $model, 'input' => $structuredInput],
        ];

        foreach ($fallbackPayloads as $fp) {
            $ch2 = curl_init($configuredUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($fp));
            curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $groqKey
            ]);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 30);

            $resp2 = curl_exec($ch2);
            $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            $curlErr2 = curl_error($ch2);
            curl_close($ch2);

            if ($curlErr2) {
                $lastError = ['error' => 'curl_error', 'message' => $curlErr2, 'url' => $configuredUrl];
                continue;
            }

            if ($code2 === 200 || $code2 === 201) {
                $response = $resp2;
                $httpCode = $code2;
                break;
            }

            $decodedErr2 = json_decode($resp2, true);
            $errMsg2 = $decodedErr2['error']['message'] ?? $decodedErr2['message'] ?? ($resp2 ?: '');
            $lastError = ['error' => 'http_error', 'code' => $code2, 'message' => $errMsg2, 'url' => $configuredUrl];
        }

        if ($response === null) {
            return is_array($lastError) ? $lastError : ['error' => 'no_response', 'message' => 'No successful response from configured GROQ API URL', 'url' => $configuredUrl];
        }
    } else {
        // Fallback: try a set of common endpoints (legacy behavior)
        $defaultCandidates = [
            'https://api.groq.com/v1/chat/completions',
            'https://api.groq.com/v1/completions',
            'https://api.groq.com/v1/outputs',
            'https://api.groq.com/v1/models/' . $model . '/outputs',
            'https://api.groq.com/v1/engines/' . $model . '/completions',
            'https://api.groq.com/v1/engines/' . $model . '/outputs',
        ];
        $candidates = [];
        if ($configuredUrl !== '') { $candidates[] = $configuredUrl; }
        foreach ($defaultCandidates as $c) { if (!in_array($c, $candidates)) $candidates[] = $c; }

        // Try each candidate URL. For each, attempt message-style payload first, then a fallback input/prompt payload.
        foreach ($candidates as $tryUrl) {
            // try message-style
            $ch = curl_init($tryUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $groqKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                $lastError = ['error' => 'curl_error', 'message' => $curlErr, 'url' => $tryUrl];
                continue;
            }

            if ($code === 200 || $code === 201) {
                $response = $resp;
                $httpCode = $code;
                break;
            }

            $decodedErr = json_decode($resp, true);
            $errMsg = $decodedErr['error']['message'] ?? $decodedErr['message'] ?? ($resp ?: '');
            if ($code === 404 || stripos($errMsg, 'Unknown request URL') !== false) {
                $structuredInput = $gMessages;
                $fallbackPayloads = [
                    ['model' => $model, 'input' => $structuredInput],
                    ['model' => $model, 'input' => $simpleText],
                    ['model' => $model, 'prompt' => $simpleText],
                    ['model' => $model, 'text' => $simpleText]
                ];

                $modelVariant = 'models/' . $model;
                $additionalFallbacks = [
                    ['model' => $modelVariant, 'input' => $structuredInput],
                    ['model' => $modelVariant, 'input' => $simpleText],
                    ['model' => $modelVariant, 'prompt' => $simpleText],
                ];

                $allFallbacks = array_merge($fallbackPayloads, $additionalFallbacks);

                foreach ($allFallbacks as $fp) {
                    $ch2 = curl_init($tryUrl);
                    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch2, CURLOPT_POST, true);
                    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($fp));
                    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $groqKey
                    ]);
                    curl_setopt($ch2, CURLOPT_TIMEOUT, 30);

                    $resp2 = curl_exec($ch2);
                    $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                    $curlErr2 = curl_error($ch2);
                    curl_close($ch2);

                    if ($curlErr2) {
                        $lastError = ['error' => 'curl_error', 'message' => $curlErr2, 'url' => $tryUrl];
                        continue;
                    }

                    if ($code2 === 200 || $code2 === 201) {
                        $response = $resp2;
                        $httpCode = $code2;
                        break 2;
                    }

                    $decodedErr2 = json_decode($resp2, true);
                    $errMsg2 = $decodedErr2['error']['message'] ?? $decodedErr2['message'] ?? ($resp2 ?: '');
                    $lastError = ['error' => 'http_error', 'code' => $code2, 'message' => $errMsg2, 'url' => $tryUrl];
                }
            }

            $lastError = ['error' => 'http_error', 'code' => $code, 'message' => $errMsg, 'url' => $tryUrl];
        }
    }

    if ($response === null) {
        // return the last error we encountered
        if (is_array($lastError)) {
            return $lastError;
        }
        return ['error' => 'no_response', 'message' => 'No successful response from GROQ endpoints'];
    }

    $decoded = json_decode($response, true);
    if (!$decoded) {
        error_log("GROQ API JSON decode error. Response: " . substr($response, 0, 500));
        return ['error' => 'json_error', 'message' => 'Invalid JSON response', 'raw' => $response];
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
/**
 * List available GROQ models for the configured API key
 * Returns array of model names or an array with 'error' on failure
 */
function listAvailableGroqModels() {
    $groqKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    if (empty($groqKey)) {
        return ['error' => 'no_api_key', 'message' => 'GROQ API key not configured'];
    }

    // Derive models endpoint from configured API URL if possible
    $base = 'https://api.groq.com';
    if (defined('GROQ_API_URL') && !empty(GROQ_API_URL)) {
        $u = parse_url(GROQ_API_URL);
        if ($u !== false && isset($u['scheme']) && isset($u['host'])) {
            $base = $u['scheme'] . '://' . $u['host'];
        }
    }

    $modelsUrl = rtrim($base, '/') . '/v1/models';

    $ch = curl_init($modelsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $groqKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['error' => 'curl_error', 'message' => $curlErr];
    }

    if ($code !== 200) {
        $decoded = json_decode($resp, true);
        $msg = $decoded['error']['message'] ?? $decoded['message'] ?? $resp;
        return ['error' => 'http_error', 'code' => $code, 'message' => $msg];
    }

    $decoded = json_decode($resp, true);
    if (!$decoded) {
        return ['error' => 'json_error', 'message' => 'Unable to decode models response', 'raw' => $resp];
    }

    // Attempt to extract model names from common shapes
    $models = [];
    if (isset($decoded['data']) && is_array($decoded['data'])) {
        foreach ($decoded['data'] as $m) {
            if (is_array($m) && isset($m['id'])) $models[] = $m['id'];
            elseif (is_string($m)) $models[] = $m;
        }
    } elseif (isset($decoded['models']) && is_array($decoded['models'])) {
        foreach ($decoded['models'] as $m) {
            if (is_array($m) && isset($m['id'])) $models[] = $m['id'];
            elseif (is_string($m)) $models[] = $m;
        }
    }

    return $models ?: ['error' => 'no_models_found', 'message' => 'No models returned'];
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

