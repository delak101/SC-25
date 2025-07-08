<?php
/**
 * Logout Handler
 * Handles user logout and session cleanup
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

// Get current user before logout for logging
$current_user = getCurrentUser();

try {
    // Log the logout activity
    if ($current_user) {
        logActivity($current_user['id'], 'logout', 'تسجيل خروج من النظام');
    }
    
    // Perform logout
    if ($auth->logout()) {
        // Set success message for next page
        setSuccessMessage('تم تسجيل الخروج بنجاح');
        
        // Redirect to login page
        header('Location: /login.php');
        exit;
    } else {
        throw new Exception('فشل في تسجيل الخروج');
    }
    
} catch (Exception $e) {
    error_log("Logout failed: " . $e->getMessage());
    
    // Force session destruction even if logout method fails
    session_unset();
    session_destroy();
    
    // Clear remember me cookie if exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Set error message and redirect
    setErrorMessage('حدث خطأ أثناء تسجيل الخروج، ولكن تم تسجيل خروجك بنجاح');
    header('Location: /login.php');
    exit;
}
?>
