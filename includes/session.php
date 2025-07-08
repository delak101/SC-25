<?php
/**
 * Session Management
 * Initialize and configure session settings
 */

// Start output buffering to prevent header issues
if (!ob_get_level()) {
    ob_start();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    
    // Set session lifetime
    if (defined('SESSION_LIFETIME')) {
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        session_set_cookie_params(SESSION_LIFETIME);
    }
    
    // Start session
    session_start();
}

// Include required classes
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/RBAC.php';

// Initialize auth and RBAC
$auth = new Auth();
$rbac = new RBAC();

// Check session timeout (only if not on excluded pages)
$excluded_pages = ['login.php', 'register.php', 'index.php', 'setup.php'];
if (!in_array(basename($_SERVER['PHP_SELF']), $excluded_pages)) {
    try {
        if (!$auth->checkSessionTimeout()) {
            // Session expired, redirect to login
            header('Location: /login.php?error=session_expired');
            exit;
        }
    } catch (Exception $e) {
        // If session check fails, allow access to continue (for setup purposes)
        error_log("Session timeout check failed: " . $e->getMessage());
    }
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    global $auth;
    return $auth->isAuthenticated();
}

/**
 * Get current user
 */
function getCurrentUser() {
    global $auth;
    return $auth->getCurrentUser();
}

/**
 * Check if user has permission
 */
function hasPermission($feature, $action) {
    global $rbac;
    
    if (!isAuthenticated()) {
        return false;
    }
    
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Admin has all permissions
    if ($user['role'] === 'admin') {
        return true;
    }
    
    return $rbac->hasPermission($user['id'], $feature, $action);
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Require specific permission
 */
function requirePermission($feature, $action) {
    requireAuth();
    
    if (!hasPermission($feature, $action)) {
        http_response_code(403);
        include __DIR__ . '/../error/403.php';
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireAuth();
    
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        include __DIR__ . '/../error/403.php';
        exit;
    }
}

/**
 * Get user roles
 */
function getUserRoles() {
    global $rbac;
    
    if (!isAuthenticated()) {
        return [];
    }
    
    $user = getCurrentUser();
    if (!$user) {
        return [];
    }
    
    return $rbac->getUserRoles($user['id']);
}

/**
 * Check if user has role
 */
function hasRole($role) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    return $user['role'] === $role;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require CSRF token validation
 */
function requireCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }
}

/**
 * Set success message
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Set error message
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear success message
 */
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Get and clear error message
 */
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}
?>
