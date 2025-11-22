<?php
/**
 * Authentication and Authorization Helpers
 * Session management and role-based access control
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/url_helper.php';

/**
 * Start secure session
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Require user to be logged in, redirect if not
 */
function requireLogin() {
    if (!isLoggedIn()) {
        require_once __DIR__ . '/url_helper.php';
        redirect('/login.php');
    }
}

/**
 * Require specific role, redirect if not authorized
 */
function requireRole($requiredRole) {
    requireLogin();
    startSecureSession();
    
    $userRole = $_SESSION['role_name'] ?? null;
    
    if ($userRole !== $requiredRole) {
        require_once __DIR__ . '/url_helper.php';
        redirect('/index.php');
    }
}

/**
 * Login user with username and password
 * Returns array with 'success' boolean and 'user' data or 'error' message
 */
function loginUser($username, $password) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.email, u.password_hash, u.role_id, r.role_name
        FROM users u
        JOIN user_roles r ON u.role_id = r.role_id
        WHERE u.username = :username OR u.email = :email
    ");
    
    $stmt->execute(['username' => $username, 'email' => $username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    // Regenerate session ID for security
    startSecureSession();
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    
    return ['success' => true, 'user' => $user];
}

/**
 * Register new user
 * Returns array with 'success' boolean and 'user_id' or 'error' message
 */
function registerUser($username, $email, $password, $roleId = 1) {
    $pdo = getDB();
    
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
    $stmt->execute(['username' => $username, 'email' => $email]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Username or email already exists'];
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, role_id)
        VALUES (:username, :email, :password_hash, :role_id)
    ");
    
    try {
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role_id' => $roleId
        ]);
        
        $userId = $pdo->lastInsertId();
        return ['success' => true, 'user_id' => $userId];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    startSecureSession();
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    startSecureSession();
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role_id' => $_SESSION['role_id'],
        'role_name' => $_SESSION['role_name']
    ];
}

