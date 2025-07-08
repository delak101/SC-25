<?php
/**
 * Users API Endpoints
 */

// Set response headers for JSON API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/v1/', '', $path);  // Remove the api/v1 prefix
error_log('Cleaned path: ' . $path);
$segments = explode('/', trim($path, '/'));
error_log('Path segments: ' . print_r($segments, true));
$action = $segments[0] ?? '';
$userId = $segments[1] ?? null;
error_log('Action: ' . $action . ', UserID: ' . $userId);

switch ($method) {
    case 'GET':
        if ($userId) {
            handleGetUser($userId);
        } else {
            handleGetUsers();
        }
        break;
    case 'POST':
        if ($action === 'search') {
            handleSearchUsers();
        } else {
            handleCreateUser();
        }
        break;
    case 'PUT':
        if ($userId) {
            handleUpdateUser($userId);
        } else {
            ApiResponse::error('User ID required for update');
        }
        break;
    case 'DELETE':
        if ($userId) {
            handleDeleteUser($userId);
        } else {
            ApiResponse::error('User ID required for deletion');
        }
        break;
    default:
        ApiResponse::error('Method not allowed', 405);
}

function handleGetUsers() {
    $user = ApiAuth::requireRole(['admin', 'secretary']);
    
    $db = Database::getInstance();
    
    // Get filters
    $role = $_GET['role'] ?? '';
    $status = $_GET['status'] ?? '';
    
    // Get pagination parameters
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 10), 50);
    $offset = ($page - 1) * $limit;
    
    $whereClause = '';
    $params = [];
    
    if ($role) {
        $whereClause = 'WHERE role = ?';
        $params[] = $role;
    }
    
    if ($status) {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'status = ?';
        $params[] = $status;
    }
    
    $query = "SELECT id, name, email, role, phone, status, created_at, last_login
              FROM users 
              $whereClause
              ORDER BY created_at DESC 
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM users $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute(array_slice($params, 0, -2));
    $totalCount = $stmt->fetchColumn();
    
    ApiResponse::success([
        'users' => $users,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $limit)
        ]
    ]);
}

function handleGetUser($userId) {
    $user = ApiAuth::authenticate();
    
    // Users can only view their own profile unless admin/secretary
    if ($user['role'] !== 'admin' && $user['role'] !== 'secretary' && $user['id'] != $userId) {
        ApiResponse::forbidden('Access denied');
    }
    
    $db = Database::getInstance();
    
    $stmt = $db->prepare("SELECT id, name, email, role, phone, status, created_at, last_login FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        ApiResponse::notFound('User not found');
    }
    
    // Get role-specific profile
    if ($userData['role'] === 'patient') {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        $userData['profile'] = $profile;
    } elseif ($userData['role'] === 'doctor') {
        $stmt = $db->prepare("SELECT * FROM doctor_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        $userData['profile'] = $profile;
        
        // Get doctor's clinics
        $stmt = $db->prepare("SELECT id, name, specialty, status FROM clinics WHERE doctor_id = ?");
        $stmt->execute([$userId]);
        $userData['clinics'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    ApiResponse::success($userData);
}

function handleCreateUser() {
    $user = ApiAuth::requireRole(['admin']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = $input['name'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'patient';
    $phone = $input['phone'] ?? '';
    $status = $input['status'] ?? 'active';
    
    if (empty($name) || empty($email) || empty($password)) {
        ApiResponse::error('All required fields must be provided');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ApiResponse::error('Invalid email format');
    }
    
    if (strlen($password) < 8) {
        ApiResponse::error('Password must be at least 8 characters long');
    }
    
    $allowedRoles = ['admin', 'doctor', 'patient', 'secretary', 'pharmacy', 'reception'];
    if (!in_array($role, $allowedRoles)) {
        ApiResponse::error('Invalid role');
    }
    
    $db = Database::getInstance();
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        ApiResponse::error('Email already registered', 409);
    }
    
    try {
        $db->beginTransaction();
        
        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $role, $phone, $status]);
        
        $newUserId = $db->lastInsertId();
        
        // Create role-specific profile
        if ($role === 'patient') {
            $stmt = $db->prepare("INSERT INTO users (user_id) VALUES (?)");
            $stmt->execute([$newUserId]);
        } elseif ($role === 'doctor') {
            $stmt = $db->prepare("INSERT INTO doctor_profiles (user_id) VALUES (?)");
            $stmt->execute([$newUserId]);
        }
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                             VALUES (?, 'create_user', 'users', ?, ?, ?)");
        $stmt->execute([$user['id'], $newUserId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        
        $db->commit();
        
        // Get created user (without password)
        $stmt = $db->prepare("SELECT id, name, email, role, phone, status, created_at FROM users WHERE id = ?");
        $stmt->execute([$newUserId]);
        $createdUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        ApiResponse::success($createdUser, 'User created successfully', 201);
        
    } catch (Exception $e) {
        $db->rollBack();
        ApiResponse::error('Failed to create user: ' . $e->getMessage(), 500);
    }
}

function handleUpdateUser($userId) {
    $user = ApiAuth::authenticate();
    
    // Users can only update their own profile unless admin
    if ($user['id'] != $userId) {
        ApiResponse::forbidden('Access denied');
    }
    
    $db = Database::getInstance();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        ApiResponse::notFound('User not found');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $updateFields = [];
    $params = [];
    
    // Define allowed fields based on role
    $allowedFields = ['name', 'phone'];
    if ($user['role'] === 'admin') {
        $allowedFields = array_merge($allowedFields, ['email', 'role', 'status']);
    }
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            if ($field === 'email' && !filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
                ApiResponse::error('Invalid email format');
            }
            $updateFields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    // Handle password update
    if (isset($input['password'])) {
        if (strlen($input['password']) < 8) {
            ApiResponse::error('Password must be at least 8 characters long');
        }
        $updateFields[] = "password = ?";
        $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
    }
    
    if (empty($updateFields)) {
        ApiResponse::error('No fields to update');
    }
    
    // Check email uniqueness if updating email
    if (isset($input['email'])) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$input['email'], $userId]);
        if ($stmt->fetch()) {
            ApiResponse::error('Email already exists', 409);
        }
    }
    
    $params[] = $userId;
    
    try {
        $db->beginTransaction();
        
        // Update user
        $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        // Update role-specific profile if provided
        if (isset($input['profile'])) {
            $profile = $input['profile'];
            
            if ($targetUser['role'] === 'patient') {
                $profileFields = [];
                $profileParams = [];
                $allowedProfileFields = ['date_of_birth', 'gender', 'address', 'emergency_contact', 'medical_history', 'allergies'];
                
                foreach ($allowedProfileFields as $field) {
                    if (isset($profile[$field])) {
                        $profileFields[] = "$field = ?";
                        $profileParams[] = $profile[$field];
                    }
                }
                
                if (!empty($profileFields)) {
                    $profileParams[] = $userId;
                    $profileQuery = "UPDATE users SET " . implode(', ', $profileFields) . " WHERE user_id = ?";
                    $stmt = $db->prepare($profileQuery);
                    $stmt->execute($profileParams);
                }
                
            } elseif ($targetUser['role'] === 'doctor') {
                $profileFields = [];
                $profileParams = [];
                $allowedProfileFields = ['specialization', 'license_number', 'experience_years', 'education', 'bio'];
                
                foreach ($allowedProfileFields as $field) {
                    if (isset($profile[$field])) {
                        $profileFields[] = "$field = ?";
                        $profileParams[] = $profile[$field];
                    }
                }
                
                if (!empty($profileFields)) {
                    $profileParams[] = $userId;
                    $profileQuery = "UPDATE doctor_profiles SET " . implode(', ', $profileFields) . " WHERE user_id = ?";
                    $stmt = $db->prepare($profileQuery);
                    $stmt->execute($profileParams);
                }
            }
        }
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                             VALUES (?, 'update_user', 'users', ?, ?, ?)");
        $stmt->execute([$user['id'], $userId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        
        $db->commit();
        
        // Get updated user
        $stmt = $db->prepare("SELECT id, name, email, role, phone, status, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        ApiResponse::success($updatedUser, 'User updated successfully');
        
    } catch (Exception $e) {
        $db->rollBack();
        ApiResponse::error('Failed to update user: ' . $e->getMessage(), 500);
    }
}

function handleDeleteUser($userId) {
    $user = ApiAuth::requireRole(['admin']);
    
    if ($user['id'] == $userId) {
        ApiResponse::error('Cannot delete your own account');
    }
    
    $db = Database::getInstance();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        ApiResponse::notFound('User not found');
    }
    
    // Check if user has dependencies
    $dependencies = [];
    
    if ($targetUser['role'] === 'doctor') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM clinics WHERE doctor_id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() > 0) {
            $dependencies[] = 'active clinics';
        }
    }
    
    if ($targetUser['role'] === 'patient') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status IN ('scheduled', 'confirmed')");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() > 0) {
            $dependencies[] = 'scheduled appointments';
        }
    }
    
    if (!empty($dependencies)) {
        ApiResponse::error('Cannot delete user with ' . implode(', ', $dependencies));
    }
    
    try {
        $db->beginTransaction();
        
        // Soft delete user
        $stmt = $db->prepare("UPDATE users SET status = 'deleted', email = CONCAT(email, '_deleted_', ?) WHERE id = ?");
        $stmt->execute([time(), $userId]);
        
        // Delete user tokens
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                             VALUES (?, 'delete_user', 'users', ?, ?, ?)");
        $stmt->execute([$user['id'], $userId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        
        $db->commit();
        
        ApiResponse::success(null, 'User deleted successfully');
        
    } catch (Exception $e) {
        $db->rollBack();
        ApiResponse::error('Failed to delete user: ' . $e->getMessage(), 500);
    }
}

function handleSearchUsers() {
    $user = ApiAuth::requireRole(['admin', 'secretary']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $searchTerm = $input['query'] ?? '';
    $role = $input['role'] ?? '';
    $status = $input['status'] ?? '';
    
    if (empty($searchTerm)) {
        ApiResponse::error('Search term is required');
    }
    
    $db = Database::getInstance();
    
    $whereClause = "WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];
    
    if ($role) {
        $whereClause .= " AND role = ?";
        $params[] = $role;
    }
    
    if ($status) {
        $whereClause .= " AND status = ?";
        $params[] = $status;
    }
    
    $query = "SELECT id, name, email, role, phone, status, created_at
              FROM users 
              $whereClause
              ORDER BY name
              LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success($users);
}
?>