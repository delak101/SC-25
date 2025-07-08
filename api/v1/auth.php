<?php
/**
 * Authentication API Endpoints
 */

// Ensure proper headers for JSON API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$segments = explode('/', trim($path, '/'));
$action = $segments[1] ?? '';

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'login':
                handleLogin();
                break;
            case 'register':
                handleRegister();
                break;
            case 'refresh':
                handleRefreshToken();
                break;
            case 'logout':
                handleLogout();
                break;
            case 'forgot-password':
                handleForgotPassword();
                break;
            case 'reset-password':
                handleResetPassword();
                break;
            default:
                ApiResponse::notFound('Auth endpoint not found');
        }
        break;
    case 'GET':
        switch ($action) {
            case 'profile':
                handleGetProfile();
                break;
            case 'verify':
                handleVerifyToken();
                break;
            default:
                ApiResponse::notFound('Auth endpoint not found');
        }
        break;
    default:
        ApiResponse::error('Method not allowed', 405);
}

function handleLogin() {
    // Get and validate JSON input
    $input = ApiValidator::validateJson();
    
    // Fallback to POST for form data
    if (empty($input)) {
        $input = $_POST;
    }
    
    // Validate required fields
    ApiValidator::validateRequiredFields($input, ['email', 'password']);
    
    // Sanitize input
    $input = ApiValidator::sanitizeInput($input);
    
    // Extract and validate specific fields
    $email = $input['email'];
    $password = $input['password'];
    $remember = filter_var($input['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    // Validate email format
    ApiValidator::validateEmail($email);
    
    $db = Database::getInstance();
    
    // Get user
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user['password'])) {
        try {
            // Log failed attempt (minimal columns)
            $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
            $stmt->execute([$email, $_SERVER['REMOTE_ADDR']]);
        } catch (PDOException $e) {
            error_log("Failed to log login attempt: " . $e->getMessage());
        }
        
        ApiResponse::error('Invalid credentials', 401);
    }
    
    // Check if account is locked - using timestamp column if created_at doesn't exist
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND 
                             (created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) OR 
                             timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE))");
        $stmt->execute([$email]);
        $failedAttempts = $stmt->fetchColumn();
        
        if ($failedAttempts >= 5) {
            ApiResponse::error('Account temporarily locked due to multiple failed attempts', 423);
        }
    } catch (PDOException $e) {
        // If the lock check fails, just continue (don't break login)
        error_log("Failed to check login attempts: " . $e->getMessage());
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime($remember ? '+30 days' : '+24 hours'));
    
    // Store token (without token_type)
    try {
        // First try with minimal columns
        $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expiresAt]);
    } catch (PDOException $e) {
        // If that fails, try with created_at if needed
        try {
            $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user['id'], $token, $expiresAt]);
        } catch (PDOException $e) {
            error_log("Failed to store remember token: " . $e->getMessage());
            ApiResponse::error('Could not complete login', 500);
        }
    }
    
    // Clear failed attempts
    try {
        $stmt = $db->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
    } catch (PDOException $e) {
        error_log("Failed to clear login attempts: " . $e->getMessage());
    }
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    try {
        // Log activity (minimal columns)
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'login')");
        $stmt->execute([$user['id']]);
    } catch (PDOException $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
    
    // Remove sensitive data
    unset($user['password']);
    
    ApiResponse::success([
        'user' => $user,
        'token' => $token,
        'expires_at' => $expiresAt
    ], 'Login successful');
}

    function handleRegister() {
    // Get and validate JSON input
    $input = ApiValidator::validateJson();
    
    // Fallback to POST for form data
    if (empty($input)) {
        $input = $_POST;
    }

    // Required fields matching the registration form
    $requiredFields = [
        'name', 'email', 'password', 'confirm_password', 'phone', 
        'national_id', 'gender', 'hearing_status', 'governorate', 'age', 'terms'
    ];

    // Validate required fields
    ApiValidator::validateRequiredFields($input, $requiredFields);
    
    // Sanitize input
    $input = ApiValidator::sanitizeInput($input);

    // Validate terms acceptance
    if (!filter_var($input['terms'], FILTER_VALIDATE_BOOLEAN)) {
        ApiResponse::validationError('You must accept the terms and conditions');
    }

    // Validate email format
    ApiValidator::validateEmail($input['email']);

    // Validate password strength and match
    if (strlen($input['password']) < 8) {
        ApiResponse::validationError('Password must be at least 8 characters long');
    }
    
    if ($input['password'] !== $input['confirm_password']) {
        ApiResponse::validationError('Passwords do not match');
    }

    // Validate age
    ApiValidator::validateInteger($input['age'], 'Age', 10, 100);

    // Validate phone number (Egyptian format)
    if (!preg_match('/^01[0-9]{9}$/', $input['phone'])) {
        ApiResponse::validationError('Phone number must be a valid Egyptian mobile number (e.g. 01xxxxxxxxx)');
    }

    // Validate national ID (Egyptian format)
    if (!preg_match('/^[0-9]{14}$/', $input['national_id'])) {
        ApiResponse::validationError('National ID must be 14 digits');
    }

    // Validate gender
    ApiValidator::validateInArray($input['gender'], ['male', 'female'], 'gender');

    // Validate hearing status
    $validHearingStatuses = ['deaf', 'hard_of_hearing', 'hearing'];
    ApiValidator::validateInArray($input['hearing_status'], $validHearingStatuses, 'hearing_status');

    // Validate governorate
    $validGovernorates = ['cairo', 'alexandria', 'giza']; // Add more as needed
    ApiValidator::validateInArray($input['governorate'], $validGovernorates, 'governorate');

    // Validate optional fields
    $validMaritalStatuses = ['single', 'married', 'divorced', 'widowed'];
    if (!empty($input['marital_status'])) {
        ApiValidator::validateInArray($input['marital_status'], $validMaritalStatuses, 'marital_status');
    }

    $validSignLanguageLevels = ['beginner', 'intermediate', 'advanced', 'none'];
    if (!empty($input['sign_language_level'])) {
        ApiValidator::validateInArray($input['sign_language_level'], $validSignLanguageLevels, 'sign_language_level');
    }

    // Set default image path
    $defaultImagePath = 'C:\xampp\htdocs\finalfinalfinal\images\logo.png';

        $db = Database::getInstance();

        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        if ($stmt->fetch()) {
            ApiResponse::error('Email already registered', 409);
        }

        // Check if national ID exists
        $stmt = $db->prepare("SELECT id FROM users WHERE national_id = ?");
        $stmt->execute([$input['national_id']]);
        if ($stmt->fetch()) {
            ApiResponse::error('National ID already registered', 409);
        }

        try {
            $db->beginTransaction();

            // Handle file uploads
            $uploadDir = __DIR__ . '/../../uploads/';
            
            // Ensure upload directories exist
            $nationalIdsDir = $uploadDir . 'national_ids/';
            $serviceCardsDir = $uploadDir . 'service_cards/';
            
            if (!file_exists($nationalIdsDir)) {
                mkdir($nationalIdsDir, 0777, true);
            }
            if (!file_exists($serviceCardsDir)) {
                mkdir($serviceCardsDir, 0777, true);
            }

            // Handle National ID image (use default if not provided)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            $nationalIdFilename = 'logo.png'; // Default to logo.png

            if (isset($_FILES['national_id_image']) && $_FILES['national_id_image']['error'] === UPLOAD_ERR_OK) {
                if (!in_array($_FILES['national_id_image']['type'], $allowedTypes)) {
                    throw new Exception('Invalid file type for national ID image. Only JPEG, PNG, GIF allowed.');
                }
                if ($_FILES['national_id_image']['size'] > $maxSize) {
                    throw new Exception('National ID image file too large. Maximum 2MB allowed.');
                }

                // Upload the provided image
                $nationalIdExt = pathinfo($_FILES['national_id_image']['name'], PATHINFO_EXTENSION);
                $nationalIdFilename = uniqid() . '.' . $nationalIdExt;
                $nationalIdPath = $nationalIdsDir . $nationalIdFilename;
                if (!move_uploaded_file($_FILES['national_id_image']['tmp_name'], $nationalIdPath)) {
                    throw new Exception('Failed to save national ID image');
                }
            }

            // Service card image (optional, use default if not provided)
            $serviceCardFilename = 'logo.png'; // Default to logo.png
            if (isset($_FILES['service_card_image']) && $_FILES['service_card_image']['error'] === UPLOAD_ERR_OK) {
                if (!in_array($_FILES['service_card_image']['type'], $allowedTypes)) {
                    throw new Exception('Invalid file type for service card image. Only JPEG, PNG, GIF allowed.');
                }
                if ($_FILES['service_card_image']['size'] > $maxSize) {
                    throw new Exception('Service card image file too large. Maximum 2MB allowed.');
                }

                $serviceCardExt = pathinfo($_FILES['service_card_image']['name'], PATHINFO_EXTENSION);
                $serviceCardFilename = uniqid() . '.' . $serviceCardExt;
                $serviceCardPath = $serviceCardsDir . $serviceCardFilename;
                if (!move_uploaded_file($_FILES['service_card_image']['tmp_name'], $serviceCardPath)) {
                    throw new Exception('Failed to save service card image');
                }
            }

            // Create user - matching the form fields exactly
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO users (
                    name, email, password, phone, national_id, gender, 
                    hearing_status, marital_status, sign_language_level, 
                    governorate, age, job, national_id_image, service_card_image,
                    role, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'patient', 'active', NOW())
            ");
            
            $stmt->execute([
                $input['name'],
                $input['email'],
                $hashedPassword,
                $input['phone'],
                $input['national_id'],
                $input['gender'],
                $input['hearing_status'],
                $input['marital_status'] ?? null,
                $input['sign_language_level'] ?? null,
                $input['governorate'],
                (int)$input['age'],
                $input['job'] ?? null,
                $nationalIdFilename,
                $serviceCardFilename
            ]);

            $userId = $db->lastInsertId();

            // Log activity
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, 'register', ?, ?)");
            $stmt->execute([$userId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);

            $db->commit();

            // Prepare response data (without sensitive info)
            $responseData = [
                'user' => [
                    'id' => $userId,
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'role' => 'patient',
                    'status' => 'active'
                ]
            ];

            ApiResponse::success($responseData, 'Registration successful. You can now login.', 201);

        } catch (Exception $e) {
            $db->rollBack();
            
            // Clean up uploaded files if transaction fails
            if (isset($nationalIdPath) && file_exists($nationalIdPath)) {
                unlink($nationalIdPath);
            }
            if (isset($serviceCardPath) && file_exists($serviceCardPath)) {
                unlink($serviceCardPath);
            }
            
            ApiResponse::error('Registration failed: ' . $e->getMessage(), 500);
        }
    }
function handleGetProfile() {
    $user = ApiAuth::authenticate();
    
    $db = Database::getInstance();
    
    // Get user with profile data
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        ApiResponse::notFound('User not found');
    }
    
    // Remove sensitive data
    unset($userData['password']);
    
    // Get role-specific profile
    // if ($userData['role'] === 'patient') {
    //     $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    //     $stmt->execute([$user['id']]);
    //     $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    //     $userData['profile'] = $profile;
    // } elseif ($userData['role'] === 'doctor') {
    //     $stmt = $db->prepare("SELECT * FROM doctor_profiles WHERE user_id = ?");
    //     $stmt->execute([$user['id']]);
    //     $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    //     $userData['profile'] = $profile;
    // }
    
    ApiResponse::success($userData);
}

function handleVerifyToken() {
    try {
        $user = ApiAuth::authenticate();
        ApiResponse::success(['valid' => true, 'user_id' => $user['id']], 'Token is valid');
    } catch (Exception $e) {
        ApiResponse::error('Invalid token', 401);
    }
}

function handleRefreshToken() {
    // Get token from Authorization header
    $headers = getallheaders();
    $oldToken = null;

    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $oldToken = $matches[1];
        }
    }

    if (empty($oldToken)) {
        ApiResponse::error('Token required');
    }

    $db = Database::getInstance();

    // Verify old token
    $stmt = $db->prepare("SELECT u.*, ut.expires_at FROM users u 
                         JOIN remember_tokens ut ON u.id = ut.user_id 
                         WHERE ut.token = ? AND ut.expires_at > CURRENT_TIMESTAMP");
    $stmt->execute([$oldToken]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        ApiResponse::error('Invalid or expired token', 401);
    }

    // Generate new token
    $newToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Update token
    $stmt = $db->prepare("UPDATE remember_tokens SET token = ?, expires_at = ? WHERE token = ?");
    $stmt->execute([$newToken, $expiresAt, $oldToken]);

    ApiResponse::success([
        'token' => $newToken,
        'expires_at' => $expiresAt
    ], 'Token refreshed');
}

function handleLogout() {
    $user = ApiAuth::authenticate();
    
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }
    
    if ($token) {
        $db = Database::getInstance();
        
        // Delete token
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$token]);
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, 'logout', ?, ?)");
        $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
    }
    
    ApiResponse::success(null, 'Logged out successfully');
}

function handleForgotPassword() {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    
    if (empty($email)) {
        ApiResponse::error('Email is required');
    }
    
    $db = Database::getInstance();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Don't reveal if email exists
        ApiResponse::success(null, 'If the email exists, a reset link has been sent');
    }
    
    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store reset token
    $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, 'password_reset', ?)");
    $stmt->execute([$user['id'], $resetToken, $expiresAt]);
    
    // In a real implementation, send email here
    
    ApiResponse::success(null, 'If the email exists, a reset link has been sent');
}

function handleResetPassword() {
    // Get token from Authorization header
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $newPassword = $input['password'] ?? '';

    if (empty($token) || empty($newPassword)) {
        ApiResponse::error('Token and new password are required');
    }

    if (strlen($newPassword) < 8) {
        ApiResponse::error('Password must be at least 8 characters long');
    }

    $db = Database::getInstance();

    // Verify reset token
    $stmt = $db->prepare("SELECT user_id FROM remember_tokens WHERE token = ? AND expires_at > CURRENT_TIMESTAMP");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        ApiResponse::error('Invalid or expired reset token', 401);
    }

    try {
        $db->beginTransaction();

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $tokenData['user_id']]);

        // Delete reset token
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$token]);

        // Delete all access tokens for security
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$tokenData['user_id']]);

        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, 'password_reset', ?, ?)");
        $stmt->execute([$tokenData['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);

        $db->commit();

        ApiResponse::success(null, 'Password reset successfully');

    } catch (Exception $e) {
        $db->rollBack();
        ApiResponse::error('Password reset failed', 500);
    }
}
?>