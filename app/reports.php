<?php
/**
 * Report CRUD Operations
 * Functions for creating, reading, updating reports
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Create a new report
 */
function createReport($userId, $title, $description, $imageData, $latitude, $longitude, $category = null) {
    $pdo = getDB();
    
    // Default status is "Pending" (status_id = 1)
    $stmt = $pdo->prepare("
        INSERT INTO reports (user_id, title, description, image, latitude, longitude, category, status_id)
        VALUES (:user_id, :title, :description, :image, :latitude, :longitude, :category, 1)
    ");
    
    try {
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':image', $imageData, PDO::PARAM_LOB);
        $stmt->bindParam(':latitude', $latitude, PDO::PARAM_STR);
        $stmt->bindParam(':longitude', $longitude, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        
        $stmt->execute();
        
        $reportId = $pdo->lastInsertId();
        
        // Log audit trail
        logAuditTrail($reportId, $userId, 'created', 'Report created');
        
        return ['success' => true, 'report_id' => $reportId];
    } catch (PDOException $e) {
        error_log("Report creation error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to create report'];
    }
}

/**
 * Get report by ID
 */
function getReport($reportId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT r.*, s.status_name, u.username, u.email, a.name as authority_name
        FROM reports r
        JOIN report_status s ON r.status_id = s.status_id
        JOIN users u ON r.user_id = u.user_id
        LEFT JOIN authorities a ON r.assigned_to = a.id
        WHERE r.report_id = :report_id
    ");
    
    $stmt->execute(['report_id' => $reportId]);
    return $stmt->fetch();
}

/**
 * Get all reports for a user
 */
function getUserReports($userId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT r.*, s.status_name
        FROM reports r
        JOIN report_status s ON r.status_id = s.status_id
        WHERE r.user_id = :user_id
        ORDER BY r.created_at DESC
    ");
    
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Get all active reports (for municipality dashboard)
 */
function getAllActiveReports($limit = 1000, $days = 30) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT r.*, s.status_name, u.username, u.email
        FROM reports r
        JOIN report_status s ON r.status_id = s.status_id
        JOIN users u ON r.user_id = u.user_id
        WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        AND s.status_name != 'Fixed'
        ORDER BY r.created_at DESC
        LIMIT :limit
    ");
    
    $stmt->bindValue(':days', $days, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get reports as JSON for map (lightweight, no images)
 */
function getReportsJSON($limit = 1000) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT r.report_id, r.title, r.latitude, r.longitude, r.category, 
               s.status_name, r.created_at, r.is_verified
        FROM reports r
        JOIN report_status s ON r.status_id = s.status_id
        WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY r.created_at DESC
        LIMIT :limit
    ");
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update report status
 */
function updateReportStatus($reportId, $statusId, $userId, $note = '') {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        UPDATE reports 
        SET status_id = :status_id
        WHERE report_id = :report_id
    ");
    
    $stmt->execute([
        'status_id' => $statusId,
        'report_id' => $reportId
    ]);
    
    // Log audit trail
    $statusName = getStatusName($statusId);
    logAuditTrail($reportId, $userId, 'status_changed', "Status changed to: {$statusName}. {$note}");
    
    return $stmt->rowCount() > 0;
}

/**
 * Assign report to authority
 */
function assignReportToAuthority($reportId, $authorityId, $userId, $actionDue = null) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        UPDATE reports 
        SET assigned_to = :authority_id, action_due = :action_due
        WHERE report_id = :report_id
    ");
    
    $stmt->execute([
        'authority_id' => $authorityId,
        'action_due' => $actionDue,
        'report_id' => $reportId
    ]);
    
    // Log audit trail
    $authorityName = getAuthorityName($authorityId);
    logAuditTrail($reportId, $userId, 'assigned', "Assigned to: {$authorityName}");
    
    return $stmt->rowCount() > 0;
}

/**
 * Update report (user can edit before verification)
 */
function updateReport($reportId, $userId, $title, $description, $category = null) {
    $pdo = getDB();
    
    // Check if report belongs to user and is not verified
    $stmt = $pdo->prepare("
        SELECT user_id, is_verified FROM reports WHERE report_id = :report_id
    ");
    $stmt->execute(['report_id' => $reportId]);
    $report = $stmt->fetch();
    
    if (!$report || $report['user_id'] != $userId) {
        return ['success' => false, 'error' => 'Unauthorized'];
    }
    
    if ($report['is_verified']) {
        return ['success' => false, 'error' => 'Cannot edit verified report'];
    }
    
    $stmt = $pdo->prepare("
        UPDATE reports 
        SET title = :title, description = :description, category = :category
        WHERE report_id = :report_id
    ");
    
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'category' => $category,
        'report_id' => $reportId
    ]);
    
    logAuditTrail($reportId, $userId, 'updated', 'Report updated');
    
    return ['success' => true];
}

/**
 * Mark report as resolved by user
 */
function markReportResolved($reportId, $userId, $resolutionImage = null) {
    $pdo = getDB();
    
    // Check ownership
    $stmt = $pdo->prepare("SELECT user_id FROM reports WHERE report_id = :report_id");
    $stmt->execute(['report_id' => $reportId]);
    $report = $stmt->fetch();
    
    if (!$report || $report['user_id'] != $userId) {
        return ['success' => false, 'error' => 'Unauthorized'];
    }
    
    // Update status to Fixed
    $fixedStatusId = 3; // Fixed status
    return updateReportStatus($reportId, $fixedStatusId, $userId, 'Marked as resolved by user');
}

/**
 * Get status name by ID
 */
function getStatusName($statusId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT status_name FROM report_status WHERE status_id = :id");
    $stmt->execute(['id' => $statusId]);
    $result = $stmt->fetch();
    return $result ? $result['status_name'] : 'Unknown';
}

/**
 * Get authority name by ID
 */
function getAuthorityName($authorityId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT name FROM authorities WHERE id = :id");
    $stmt->execute(['id' => $authorityId]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : 'Unknown';
}

/**
 * Log audit trail entry
 */
function logAuditTrail($reportId, $userId, $action, $note = '') {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        INSERT INTO audit_trail (report_id, action, actor_user_id, note)
        VALUES (:report_id, :action, :actor_user_id, :note)
    ");
    
    $stmt->execute([
        'report_id' => $reportId,
        'action' => $action,
        'actor_user_id' => $userId,
        'note' => $note
    ]);
}

/**
 * Detect clusters of reports (within distance and time window)
 */
function detectClusters($category = null, $distanceMeters = 500, $timeHours = 48) {
    $pdo = getDB();
    
    $categoryFilter = $category ? "AND r.category = :category" : "";
    $params = ['distance' => $distanceMeters, 'hours' => $timeHours];
    if ($category) {
        $params['category'] = $category;
    }
    
    $stmt = $pdo->prepare("
        SELECT r1.report_id, r1.latitude, r1.longitude, r1.category,
               COUNT(r2.report_id) as cluster_size
        FROM reports r1
        JOIN reports r2 ON (
            r1.report_id != r2.report_id
            AND r1.category = r2.category
            AND ABS(r1.latitude - r2.latitude) < 0.005
            AND ABS(r1.longitude - r2.longitude) < 0.005
            AND ABS(TIMESTAMPDIFF(HOUR, r1.created_at, r2.created_at)) <= :hours
        )
        WHERE r1.created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
        {$categoryFilter}
        GROUP BY r1.report_id
        HAVING cluster_size >= 2
        ORDER BY cluster_size DESC
    ");
    
    $stmt->execute($params);
    return $stmt->fetchAll();
}

