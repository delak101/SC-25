<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';

/**
 * Authentication Class
 * Handles user authentication, session management, and security
 */
class Auth {
    private $db;
    private $user;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->user = new User();
    }

    /**
     * Authenticate user login
     */
    public function login($email, $password, $remember_me = false) {
        try {
            // Check for too many failed attempts
            if ($this->isLockedOut($email)) {
                throw new Exception('تم قفل الحساب مؤقتاً بسبب المحاولات الفاشلة المتكررة. حاول مرة أخرى بعد ' . (LOGIN_LOCKOUT_TIME / 60) . ' دقائق.');
            }

            // Get user by email
            $user = $this->user->getByEmail($email);
            
            if (!$user) {
                $this->recordFailedAttempt($email);
                throw new Exception('البريد الإلكتروني أو كلمة المرور غير صحيحة');
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->recordFailedAttempt($email);
                throw new Exception('البريد الإلكتروني أو كلمة المرور غير صحيحة');
            }

            // Check if user is active
            if ($user['status'] !== 'active') {
                throw new Exception('تم إيقاف هذا الحساب. يرجى التواصل مع الإدارة');
            }

            // Clear failed attempts
            $this->clearFailedAttempts($email);

            // Create session
            $this->createSession($user);

            // Handle remember me
            if ($remember_me) {
                $this->createRememberToken($user['id']);
            }

            // Update last login
            $this->updateLastLogin($user['id']);

            return true;

        } catch (Exception $e) {
            error_log("Login failed for $email: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        // Destroy session
        session_unset();
        session_destroy();

        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
            $this->clearRememberToken($_COOKIE['remember_token']);
        }

        return true;
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        // Check session
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
            return true;
        }

        // Check remember me token
        if (isset($_COOKIE['remember_token'])) {
            return $this->validateRememberToken($_COOKIE['remember_token']);
        }

        return false;
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }

        if (isset($_SESSION['user_data'])) {
            return $_SESSION['user_data'];
        }

        // Reload user data
        $user = $this->user->getById($_SESSION['user_id']);
        if ($user) {
            $_SESSION['user_data'] = $user;
            return $user;
        }

        return null;
    }

    /**
     * Register new user with extended fields
     */
    public function register($data) {
        try {
            // Validate required fields
            $required_fields = [
                'name', 'email', 'password', 'phone', 
                'national_id', 'gender', 'hearing_status',
                'governorate', 'age'
            ];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("الحقل $field مطلوب");
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('صيغة البريد الإلكتروني غير صحيحة');
            }

            // Check if email already exists
            if ($this->user->getByEmail($data['email'])) {
                throw new Exception('البريد الإلكتروني مستخدم بالفعل');
            }

            // Validate password strength
            if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                throw new Exception('كلمة المرور يجب أن تكون ' . PASSWORD_MIN_LENGTH . ' أحرف على الأقل');
            }

            // Validate phone number
            if (!preg_match('/^[0-9+\-\s()]{10,20}$/', $data['phone'])) {
                throw new Exception('رقم الهاتف غير صحيح');
            }

            // Validate age
            if ($data['age'] < 10 || $data['age'] > 100) {
                throw new Exception('السن يجب أن يكون بين 10 و 100 سنة');
            }

            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            // Set default role if not provided
            if (!isset($data['role'])) {
                $data['role'] = 'patient';
            }

            // Create user
            $user_id = $this->user->create($data);

            return $user_id;

        } catch (Exception $e) {
            error_log("Registration failed: " . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Reset password
     */
    public function resetPassword($email, $new_password, $reset_token = null) {
        try {
            $user = $this->user->getByEmail($email);
            if (!$user) {
                throw new Exception('المستخدم غير موجود');
            }

            // If reset token is provided, validate it
            if ($reset_token) {
                // Implement token validation logic here
                // For now, we'll skip token validation
            }

            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $sql = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$hashed_password, $user['id']]);

            return true;

        } catch (Exception $e) {
            error_log("Password reset failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create user session
     */
    private function createSession($user) {
        // Regenerate session ID for security
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_data'] = $user;
        $_SESSION['login_time'] = time();
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($email) {
        $sql = "INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
        $this->db->prepare($sql);
        $this->db->execute([$email, $_SERVER['REMOTE_ADDR']]);
    }

    /**
     * Check if user is locked out
     */
    private function isLockedOut($email) {
        $lockout_time = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_TIME);
        
        $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
                WHERE email = ? AND attempted_at > ? AND ip_address = ?";
        $this->db->prepare($sql);
        $this->db->execute([$email, $lockout_time, $_SERVER['REMOTE_ADDR']]);
        
        $result = $this->db->fetch();
        return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Clear failed login attempts
     */
    private function clearFailedAttempts($email) {
        $sql = "DELETE FROM login_attempts WHERE email = ?";
        $this->db->prepare($sql);
        $this->db->execute([$email]);
    }

    /**
     * Create remember me token
     */
    private function createRememberToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 3600)); // 30 days

        $sql = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
        $this->db->prepare($sql);
        $this->db->execute([$user_id, hash('sha256', $token), $expires]);

        // Set cookie
        setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/');
    }

    /**
     * Validate remember me token
     */
    private function validateRememberToken($token) {
        $hashed_token = hash('sha256', $token);
        
        $sql = "SELECT rt.user_id, u.* FROM remember_tokens rt 
                JOIN users u ON rt.user_id = u.id 
                WHERE rt.token = ? AND rt.expires_at > CURRENT_TIMESTAMP AND u.status = 'active'";
        $this->db->prepare($sql);
        $this->db->execute([$hashed_token]);
        
        $user = $this->db->fetch();
        if ($user) {
            $this->createSession($user);
            return true;
        }

        return false;
    }

    /**
     * Clear remember me token
     */
    private function clearRememberToken($token) {
        $hashed_token = hash('sha256', $token);
        
        $sql = "DELETE FROM remember_tokens WHERE token = ?";
        $this->db->prepare($sql);
        $this->db->execute([$hashed_token]);
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($user_id) {
        $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
        $this->db->prepare($sql);
        $this->db->execute([$user_id]);
    }

    /**
     * Check session timeout
     */
    public function checkSessionTimeout() {
        if (isset($_SESSION['login_time'])) {
            $session_lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 28800; // 8 hours default
            if (time() - $_SESSION['login_time'] > $session_lifetime) {
                $this->logout();
                return false;
            }
            // Update login time
            $_SESSION['login_time'] = time();
        }
        return true;
    }
}