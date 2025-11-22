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
function createAuthority($name, $type, $contactEmail, $notes = '') {
    $pdo = getDB();
    
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
        return ['success' => true, 'id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        error_log("Authority creation error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to create authority'];
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

