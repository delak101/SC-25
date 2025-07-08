<?php
/**
 * Password Reset Handler
 * Processes password reset requests
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        requireCSRF();
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'request_reset') {
            // Step 1: Request password reset
            $email = sanitizeInput($_POST['email'] ?? '');
            
            if (empty($email)) {
                throw new Exception('البريد الإلكتروني مطلوب');
            }
            
            if (!isValidEmail($email)) {
                throw new Exception('صيغة البريد الإلكتروني غير صحيحة');
            }
            
            // Check if user exists
            $user = new User();
            $user_data = $user->getByEmail($email);
            
            if (!$user_data) {
                // Don't reveal if email exists or not for security
                $success_message = 'إذا كان البريد الإلكتروني موجود في النظام، ستصلك رسالة لإعادة تعيين كلمة المرور';
            } else {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                
                // Store reset token
                $db = Database::getInstance();
                $sql = "INSERT INTO password_resets (email, token, expires_at, created_at) 
                        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                        ON DUPLICATE KEY UPDATE 
                        token = VALUES(token), 
                        expires_at = VALUES(expires_at),
                        created_at = VALUES(created_at)";
                
                $db->prepare($sql);
                $db->execute([$email, hash('sha256', $reset_token), $expires_at]);
                
                // Send reset email
                $reset_link = APP_URL . "/reset.php?token=" . $reset_token . "&email=" . urlencode($email);
                
                $reset_message = "
                    <h2>إعادة تعيين كلمة المرور</h2>
                    <p>عزيزي/عزيزتي {$user_data['name']}،</p>
                    <p>تم طلب إعادة تعيين كلمة المرور لحسابك. اضغط على الرابط أدناه لتعيين كلمة مرور جديدة:</p>
                    <p><a href='$reset_link' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>إعادة تعيين كلمة المرور</a></p>
                    <p>هذا الرابط صالح لمدة ساعة واحدة فقط.</p>
                    <p>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذه الرسالة.</p>
                    <br>
                    <p>فريق " . APP_NAME . "</p>
                ";
                
                sendEmail($email, 'إعادة تعيين كلمة المرور - ' . APP_NAME, $reset_message);
                
                // Log activity
                logActivity($user_data['id'], 'password_reset_requested', 'طلب إعادة تعيين كلمة المرور');
                
                $success_message = 'تم إرسال رسالة إعادة تعيين كلمة المرور إلى بريدك الإلكتروني';
            }
            
            if (isAjaxRequest()) {
                jsonResponse(['success' => true, 'message' => $success_message]);
            }
            
            redirectWithMessage('/forgetbs.php', $success_message);
            
        } elseif ($action === 'reset_password') {
            // Step 2: Actually reset the password
            $email = sanitizeInput($_POST['email'] ?? '');
            $token = sanitizeInput($_POST['token'] ?? '');
            $verification_code = sanitizeInput($_POST['verification_code'] ?? '');
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate required fields
            if (empty($email) || empty($token) || empty($new_password) || empty($confirm_password)) {
                throw new Exception('جميع الحقول مطلوبة');
            }
            
            // Validate password match
            if ($new_password !== $confirm_password) {
                throw new Exception('كلمة المرور وتأكيد كلمة المرور غير متطابقتين');
            }
            
            // Validate password strength
            $password_errors = validatePasswordStrength($new_password);
            if (!empty($password_errors)) {
                throw new Exception(implode(', ', $password_errors));
            }
            
            // Verify reset token
            $db = Database::getInstance();
            $sql = "SELECT * FROM password_resets 
                    WHERE email = ? AND token = ? AND expires_at > CURRENT_TIMESTAMP AND used = 0";
            $db->prepare($sql);
            $db->execute([$email, hash('sha256', $token)]);
            
            $reset_record = $db->fetch();
            if (!$reset_record) {
                throw new Exception('رمز إعادة التعيين غير صحيح أو منتهي الصلاحية');
            }
            
            // Reset password
            if ($auth->resetPassword($email, $new_password)) {
                // Mark token as used
                $sql = "UPDATE password_resets SET used = 1, used_at = CURRENT_TIMESTAMP WHERE id = ?";
                $db->prepare($sql);
                $db->execute([$reset_record['id']]);
                
                // Log activity
                $user = new User();
                $user_data = $user->getByEmail($email);
                if ($user_data) {
                    logActivity($user_data['id'], 'password_reset_completed', 'إكمال إعادة تعيين كلمة المرور');
                }
                
                $success_message = 'تم إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول';
                
                if (isAjaxRequest()) {
                    jsonResponse(['success' => true, 'message' => $success_message, 'redirect' => '/login.php']);
                }
                
                redirectWithMessage('/login.php', $success_message);
            } else {
                throw new Exception('فشل في إعادة تعيين كلمة المرور');
            }
        } else {
            throw new Exception('عملية غير صحيحة');
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Password reset failed: " . $error_message);
        
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $error_message], 400);
        }
        
        setErrorMessage($error_message);
        
        // Redirect based on action
        if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
            $redirect_url = '/reset.php?token=' . urlencode($_POST['token'] ?? '') . '&email=' . urlencode($_POST['email'] ?? '');
        } else {
            $redirect_url = '/forgetbs.php';
        }
        
        header('Location: ' . $redirect_url);
        exit;
    }
}

// If not POST request, redirect appropriately
if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    header('Location: /reset.php');
} else {
    header('Location: /forgetbs.php');
}
exit;
?>
