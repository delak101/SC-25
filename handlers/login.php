<?php
/**
 * Login Handler
 * Processes user login requests
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already authenticated
if (isAuthenticated()) {
    header('Location: /dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        requireCSRF();
        
        // Get and sanitize input
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        // Validate required fields
        if (empty($email) || empty($password)) {
            throw new Exception('جميع الحقول مطلوبة');
        }
        
        // Validate email format
        if (!isValidEmail($email)) {
            throw new Exception('صيغة البريد الإلكتروني غير صحيحة');
        }
        
        // Attempt login
        if ($auth->login($email, $password, $remember_me)) {
            $user = getCurrentUser();
            
            // Log successful login
            logActivity($user['id'], 'login', 'تسجيل دخول ناجح');
            
            // Redirect to intended page or dashboard
            $redirect_url = $_GET['redirect'] ?? '/dashboard.php';
            header('Location: ' . $redirect_url);
            exit;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        
        // Log failed login attempt
        if (isset($email)) {
            error_log("Login failed for email: $email - " . $error_message);
        }
    }
}

// Handle AJAX requests
if (isAjaxRequest()) {
    if (isset($error_message)) {
        jsonResponse(['success' => false, 'message' => $error_message], 400);
    } else {
        jsonResponse(['success' => true, 'redirect' => '/dashboard.php']);
    }
}

// Regular form submission - redirect back to login page with error
if (isset($error_message)) {
    setErrorMessage($error_message);
}

header('Location: /login.php');
exit;
?>
