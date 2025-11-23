<?php
/**
 * ai.php
 * OpenRouter AI backend
 * 
 * Requirements:
 * - OPENROUTER_API_KEY
 * - OPENROUTER_MODEL
 * - OPENROUTER_API_URL = https://openrouter.ai/api/v1/chat/completions
 */

if (!defined("OPENROUTER_API_KEY")) die("Missing OPENROUTER_API_KEY in config.php");
if (!defined("OPENROUTER_MODEL")) die("Missing OPENROUTER_MODEL in config.php");
if (!defined("OPENROUTER_API_URL")) die("Missing OPENROUTER_API_URL in config.php");

/**
 * Core OpenRouter API call
 */
function openrouterRequest($messages, $model = null) {
    $apiKey = OPENROUTER_API_KEY;
    $model = $model ?? OPENROUTER_MODEL;
    $url   = OPENROUTER_API_URL;

    if (!$apiKey) {
        return [
            "error" => "missing_api_key",
            "message" => "OPENROUTER_API_KEY is empty"
        ];
    }

    $payload = [
        "model" => $model,
        "messages" => $messages
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey",
            "HTTP-Referer: " . (defined('APP_URL') ? APP_URL : 'http://localhost'),
            "X-Title: CityCare"
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    // Handle connection errors
    if ($curlErr) {
        return [
            "error" => "curl_error",
            "message" => $curlErr
        ];
    }

    // Decode response
    $json = json_decode($raw, true);

    // Handle OpenRouter errors
    if ($code !== 200) {
        return [
            "error" => "http_error",
            "code" => $code,
            "message" => $json["error"]["message"] ?? $raw
        ];
    }

    // Validate
    if (!isset($json["choices"][0]["message"]["content"])) {
        return [
            "error" => "invalid_format",
            "raw" => $json
        ];
    }

    return $json;
}

/**
 * HIGH LEVEL HELPER: Simple call used by test_ai.php
 */
function callOpenRouterAPI($messages) {
    return openrouterRequest($messages);
}

/**
 * AI Assistant Function (Used by your Report Analyzer)
 * Returns:
 * [
 *   "category" => "...",
 *   "urgency" => "...",
 *   "reason" => "...",
 * ]
 */
function callAIAssistant($description) {

    $messages = [
        [
            "role" => "system",
            "content" => "You are CityCare AI, an assistant for municipal issue reporting. Return JSON ONLY, no extra text.\nFields to return (use these exact keys):\n- title_suggestion: a short, clear title for the report (max 10 words)\n- category_suggestion: one of ['pothole','lighting','water-leak','garbage/dumping','traffic','other']\n- summary: a rewritten, clearer version of the user's description suitable for the report body\n- suggested_lat: optional latitude hint (decimal) or null\n- suggested_lng: optional longitude hint (decimal) or null\nIf you cannot determine lat/lng return null for them."
        ],
        [
            "role" => "user",
            "content" => "User description:\n" . $description
        ]
    ];

    $result = openrouterRequest($messages);

    if (isset($result["error"])) {
        return false;
    }

    $jsonStr = $result["choices"][0]["message"]["content"];

    // Try decoding returned JSON. Some models may include surrounding text — try to extract the JSON object.
    $parsed = json_decode($jsonStr, true);
    if (!$parsed) {
        // Attempt to find the first JSON object in the string
        $firstBrace = strpos($jsonStr, '{');
        $lastBrace = strrpos($jsonStr, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $maybe = substr($jsonStr, $firstBrace, $lastBrace - $firstBrace + 1);
            $parsed = json_decode($maybe, true);
        }
    }

    if (!$parsed || !is_array($parsed)) {
        return false;
    }

    // Normalize keys to what the frontend expects
    $out = [];
    $out['title_suggestion'] = $parsed['title_suggestion'] ?? $parsed['title'] ?? '';
    $out['category_suggestion'] = $parsed['category_suggestion'] ?? $parsed['category'] ?? ($parsed['category_name'] ?? '');
    $out['summary'] = $parsed['summary'] ?? $parsed['rewrite'] ?? $parsed['description'] ?? '';
    $out['suggested_lat'] = isset($parsed['suggested_lat']) ? $parsed['suggested_lat'] : null;
    $out['suggested_lng'] = isset($parsed['suggested_lng']) ? $parsed['suggested_lng'] : null;

    return $out;
}

/**
 * AI Priority Scoring Function
 * Analyzes a report and assigns a priority score (1-5)
 * Returns: ['priority' => int, 'reason' => string] or false on error
 */
function callAIPriority($reportId, $title, $description, $category = null) {
    require_once __DIR__ . '/db.php';
    
    // Check if API key is configured
    if (!defined('OPENROUTER_API_KEY') || OPENROUTER_API_KEY === '') {
        return false;
    }
    
    $fullDescription = "Title: $title\n";
    if ($category) {
        $fullDescription .= "Category: $category\n";
    }
    $fullDescription .= "Description: $description";
    
    $messages = [
        [
            "role" => "system",
            "content" => "You are CityCare AI, an assistant for prioritizing municipal issues.
                Return JSON ONLY, no extra text.
                Fields:
                - priority: integer from 1 to 5 (1=lowest, 5=highest urgency)
                - reason: brief explanation of the priority level.
                
                Consider factors like:
                - Safety hazards (higher priority)
                - Impact on public (more people affected = higher priority)
                - Urgency (immediate danger = higher priority)
                - Infrastructure damage severity"
        ],
        [
            "role" => "user",
            "content" => $fullDescription
        ]
    ];
    
    $result = openrouterRequest($messages);
    
    if (isset($result["error"])) {
        error_log("AI Priority Error: " . ($result["message"] ?? "Unknown error"));
        return false;
    }
    
    $jsonStr = $result["choices"][0]["message"]["content"];
    $parsed = json_decode($jsonStr, true);
    
    if (!$parsed || !isset($parsed["priority"])) {
        error_log("AI Priority: Invalid response format");
        return false;
    }
    
    // Ensure priority is between 1 and 5
    $priority = max(1, min(5, intval($parsed["priority"])));
    $reason = $parsed["reason"] ?? "AI analysis completed";
    
    // Store in database
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO ai_logs (report_id, priority, reason, raw_response)
            VALUES (:report_id, :priority, :reason, :raw_response)
        ");
        
        $stmt->execute([
            'report_id' => $reportId,
            'priority' => $priority,
            'reason' => $reason,
            'raw_response' => $jsonStr
        ]);
        
        return [
            'priority' => $priority,
            'reason' => $reason
        ];
    } catch (PDOException $e) {
        error_log("AI Priority DB Error: " . $e->getMessage());
        return false;
    }
}

/**
 * AI Help Steps Function
 * Generates step-by-step instructions for authorities to resolve a report
 * Returns: ['steps' => array, 'summary' => string] or false on error
 */
function callAIHelpSteps($reportId, $title, $description, $category = null) {
    require_once __DIR__ . '/db.php';
    
    // Check if API key is configured
    if (!defined('OPENROUTER_API_KEY') || OPENROUTER_API_KEY === '') {
        return false;
    }
    
    $fullDescription = "Title: $title\n";
    if ($category) {
        $fullDescription .= "Category: $category\n";
    }
    $fullDescription .= "Description: $description";
    
    $messages = [
        [
            "role" => "system",
            "content" => "You are CityCare AI, an assistant helping municipal authorities resolve issues.
                Return JSON ONLY, no extra text.
                Fields:
                - steps: array of 3-7 step-by-step instructions to resolve this issue. Each step should be clear and actionable.
                - summary: brief summary (1-2 sentences) of the recommended approach.
                
                Provide practical, actionable steps that a field worker or authority can follow to resolve the reported issue.
                Consider safety, efficiency, and best practices for municipal work."
        ],
        [
            "role" => "user",
            "content" => "Generate step-by-step instructions to resolve this issue:\n\n$fullDescription"
        ]
    ];
    
    $result = openrouterRequest($messages);
    
    if (isset($result["error"])) {
        error_log("AI Help Steps Error: " . ($result["message"] ?? "Unknown error"));
        return false;
    }
    
    $jsonStr = $result["choices"][0]["message"]["content"];
    $parsed = json_decode($jsonStr, true);
    
    if (!$parsed || !isset($parsed["steps"])) {
        error_log("AI Help Steps: Invalid response format");
        return false;
    }
    
    // Ensure steps is an array
    if (!is_array($parsed["steps"])) {
        $parsed["steps"] = [$parsed["steps"]];
    }
    
    $steps = $parsed["steps"];
    $summary = $parsed["summary"] ?? "Follow the steps below to resolve this issue.";
    
    return [
        'steps' => $steps,
        'summary' => $summary
    ];
}