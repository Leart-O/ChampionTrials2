<?php
/**
 * Authority Management Functions
 */

require_once __DIR__ . '/db.php';

/**
 * Get all authorities
 */
function getAllAuthorities() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM authorities ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get authority by ID
 */
function getAuthority($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM authorities WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

/**
 * Create authority
 */
function createAuthority($name, $type, $contactEmail, $notes = '', $userId = null) {
    $pdo = getDB();
    
    // Check if user_id column exists
    $columns = $pdo->query("SHOW COLUMNS FROM authorities LIKE 'user_id'")->fetch();
    $hasUserIdColumn = $columns !== false;
    
    if ($hasUserIdColumn) {
        $stmt = $pdo->prepare("
            INSERT INTO authorities (name, type, contact_email, notes, user_id)
            VALUES (:name, :type, :contact_email, :notes, :user_id)
        ");
        
        try {
            $stmt->execute([
                'name' => $name,
                'type' => $type,
                'contact_email' => $contactEmail,
                'notes' => $notes,
                'user_id' => $userId
            ]);
            return ['success' => true, 'id' => $pdo->lastInsertId()];
        } catch (PDOException $e) {
            error_log("Authority creation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create authority: ' . $e->getMessage()];
        }
    } else {
        // Fallback if user_id column doesn't exist yet
        $stmt = $pdo->prepare("
            INSERT INTO authorities (name, type, contact_email, notes)
            VALUES (:name, :type, :contact_email, :notes)
        ");
        
        try {
            $stmt->execute([
                'name' => $name,
                'type' => $type,
                'contact_email' => $contactEmail,
                'notes' => $notes
            ]);
            $authorityId = $pdo->lastInsertId();
            
            // If user_id was provided, try to update it (column might be added later)
            if ($userId) {
                try {
                    $pdo->prepare("UPDATE authorities SET user_id = ? WHERE id = ?")
                        ->execute([$userId, $authorityId]);
                } catch (PDOException $e) {
                    // Column doesn't exist yet, that's okay
                    error_log("Note: user_id column not found, authority created without user link");
                }
            }
            
            return ['success' => true, 'id' => $authorityId];
        } catch (PDOException $e) {
            error_log("Authority creation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create authority: ' . $e->getMessage()];
        }
    }
}

/**
 * Update authority
 */
function updateAuthority($id, $name, $type, $contactEmail, $notes = '') {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        UPDATE authorities 
        SET name = :name, type = :type, contact_email = :contact_email, notes = :notes
        WHERE id = :id
    ");
    
    try {
        $stmt->execute([
            'name' => $name,
            'type' => $type,
            'contact_email' => $contactEmail,
            'notes' => $notes,
            'id' => $id
        ]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Authority update error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update authority'];
    }
}

/**
 * Delete authority
 */
function deleteAuthority($id) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("DELETE FROM authorities WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    return $stmt->rowCount() > 0;
}

