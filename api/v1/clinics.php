<?php
/**
 * Clinics API Endpoints
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

// Debug logging
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Debug the full URL
error_log("Full URL: " . $_SERVER['REQUEST_URI']);
error_log("Parsed Path: " . $uri);

// Match the entire path pattern including potential ID
if (preg_match('#^/api/v1/clinics(/(\d+|search))?/?$#', $uri, $matches)) {
    error_log("URL matches pattern. Matches: " . print_r($matches, true));
    // The ID or 'search' will be in $matches[2] if present
    $firstSegment = isset($matches[2]) ? $matches[2] : '';
    error_log("First segment from matches[2]: " . $firstSegment);
} else {
    error_log("URL does not match pattern");
    $firstSegment = '';
}

error_log("Final first segment value: " . $firstSegment);

error_log("Method: " . $method . ", First segment: " . $firstSegment);

switch ($method) {
    case 'GET':
        if ($firstSegment === 'search') {
            error_log("Handling search");
            handleSearchClinics();
        } else if (is_numeric($firstSegment)) {
            error_log("Handling get clinic by ID: " . $firstSegment);
            error_log("URI path: " . $uri); // Additional debug
            handleGetClinic((int)$firstSegment);
            return; // Exit after handling single clinic
        } else {
            error_log("Handling get all clinics (no ID in URL)");
            handleGetClinics();
        }
        break;
    case 'POST':
        if ($firstSegment === 'search') {
            handleSearchClinics();
        } else {
            handleCreateClinic();
        }
        break;
    case 'PUT':
        if (is_numeric($firstSegment)) {
            handleUpdateClinic((int)$firstSegment);
        } else {
            ApiResponse::error('Clinic ID required for update');
        }
        break;
    case 'DELETE':
        if (is_numeric($firstSegment)) {
            handleDeleteClinic((int)$firstSegment);
        } else {
            ApiResponse::error('Clinic ID required for deletion');
        }
        break;
    default:
        ApiResponse::error('Method not allowed', 405);
}

function handleGetClinics() {
    error_log("Executing handleGetClinics - listing all clinics");
    $user = ApiAuth::authenticate();
    
    $db = Database::getInstance();
    
    // Build query based on user role
    $whereClause = 'WHERE 1=1';  // Always true condition to simplify adding conditions
    $params = [];
    
    // Handle different roles
    if ($user['role'] === 'doctor') {
        // Show clinics where the doctor is assigned
        $whereClause .= ' AND cd.doctor_id = ?';
        $params[] = $user['id'];
    }
    
    // Always add status condition unless admin/secretary viewing specific status
    if (!in_array($user['role'], ['admin', 'secretary'])) {
        $whereClause .= ' AND c.status = "active"';
    }
    
    // Get pagination parameters
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 10), 50);
    $offset = ($page - 1) * $limit;
    
    // Get filters
    $status = $_GET['status'] ?? '';
    
    if ($status && ($user['role'] === 'admin' || $user['role'] === 'secretary')) {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'c.status = ?';
        $params[] = $status;
    }
    
    $query = "SELECT c.*, u.name as creator_name,
                     COUNT(DISTINCT cd.doctor_id) as doctor_count,
                     COUNT(DISTINCT a.id) as appointment_count
              FROM clinics c 
              LEFT JOIN users u ON c.created_by = u.id 
              LEFT JOIN clinic_doctors cd ON c.id = cd.clinic_id
              LEFT JOIN appointments a ON c.id = a.clinic_id 
              $whereClause
              GROUP BY c.id
              ORDER BY c.created_at DESC 
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countQuery = "SELECT COUNT(DISTINCT c.id) FROM clinics c 
                   LEFT JOIN clinic_doctors cd ON c.id = cd.clinic_id 
                   $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute(array_slice($params, 0, -2));
    $totalCount = $stmt->fetchColumn();
    
    ApiResponse::success([
        'clinics' => $clinics,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $limit)
        ]
    ]);
}

function handleGetClinic($clinicId) {
    $user = ApiAuth::authenticate();
    
    if (!is_numeric($clinicId)) {
        ApiResponse::error('Invalid clinic ID');
        return;
    }
    
    error_log("handleGetClinic: Getting single clinic with ID: " . $clinicId);
    error_log("handleGetClinic: Authenticated user role: " . $user['role']);
    $db = Database::getInstance();
    
    // Get clinic details with creator info
    $stmt = $db->prepare("SELECT c.*, 
                                u.name as creator_name,
                                u.email as creator_email,
                                (SELECT COUNT(*) FROM clinic_doctors cd WHERE cd.clinic_id = c.id) as doctor_count,
                                (SELECT COUNT(*) FROM appointments a WHERE a.clinic_id = c.id) as total_appointments
                         FROM clinics c 
                         LEFT JOIN users u ON c.created_by = u.id 
                         WHERE c.id = ? AND c.status != 'deleted'");
    $stmt->execute([$clinicId]);
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$clinic) {
        ApiResponse::notFound('Clinic not found');
        return;
    }
    
    // Get assigned doctors with their schedules
    error_log("handleGetClinic: Fetching assigned doctors for clinic ID: " . $clinicId);
    $stmt = $db->prepare("SELECT u.id, u.name, u.email, u.phone, u.role,
                                ds.day_of_week, ds.start_time, ds.end_time
                         FROM clinic_doctors cd
                         JOIN users u ON cd.doctor_id = u.id
                         LEFT JOIN doctor_schedules ds ON ds.clinic_id = cd.clinic_id 
                            AND ds.doctor_id = cd.doctor_id
                         WHERE cd.clinic_id = ? AND u.status = 'active'
                         ORDER BY u.name, ds.day_of_week, ds.start_time");
    $stmt->execute([$clinicId]);
    $doctorsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("handleGetClinic: Found " . count($doctorsData) . " doctor assignments");
    
    // Organize doctors and their schedules
    $doctors = [];
    $schedules = [];
    foreach ($doctorsData as $row) {
        $doctorId = $row['id'];
        if (!isset($doctors[$doctorId])) {
            $doctors[$doctorId] = [
                'id' => $doctorId,
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'role' => $row['role']
            ];
        }
        if ($row['day_of_week']) {
            $schedules[] = [
                'doctor_id' => $doctorId,
                'day_of_week' => $row['day_of_week'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'appointment_duration' => $row['appointment_duration']
            ];
        }
    }
    $clinic['doctors'] = array_values($doctors);
    $clinic['schedules'] = $schedules;
    
    // Get recent appointments for authorized roles
    if (in_array($user['role'], ['admin', 'doctor', 'secretary'])) {
        $stmt = $db->prepare("SELECT a.id, 
                                    a.patient_id,
                                    a.doctor_id,
                                    a.appointment_date,
                                    a.appointment_time,
                                    a.status,
                                    a.created_at,
                                    p.name as patient_name,
                                    p.phone as patient_phone,
                                    d.name as doctor_name
                             FROM appointments a 
                             JOIN users p ON a.patient_id = p.id 
                             JOIN users d ON a.doctor_id = d.id
                             WHERE a.clinic_id = ? 
                             ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                             LIMIT 10");
        $stmt->execute([$clinicId]);
        $clinic['recent_appointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    error_log("handleGetClinic: Preparing final response for clinic ID: " . $clinicId);
    error_log("handleGetClinic: Response data: " . json_encode($clinic));
    
    // Return just the single clinic object
    ApiResponse::success($clinic);
}

function handleSearchClinics() {
    $user = ApiAuth::authenticate();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $searchTerm = $input['query'] ?? '';
    $specialty = $input['specialty'] ?? '';
    $location = $input['location'] ?? '';
    $availability = $input['availability'] ?? '';
    
    $db = Database::getInstance();
    
    $whereClause = "WHERE c.status = 'active'";
    $params = [];
    
    if ($searchTerm) {
        $whereClause .= " AND (c.name LIKE ? OR c.description LIKE ? OR u.name LIKE ?)";
        $searchPattern = "%$searchTerm%";
        $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern]);
    }
    
    $query = "SELECT c.*, 
                     u.name as creator_name,
                     COUNT(DISTINCT cd.doctor_id) as doctor_count,
                     COUNT(DISTINCT a.id) as appointment_count
              FROM clinics c 
              LEFT JOIN users u ON c.created_by = u.id 
              LEFT JOIN clinic_doctors cd ON c.id = cd.clinic_id
              LEFT JOIN appointments a ON c.id = a.clinic_id
              $whereClause
              GROUP BY c.id
              ORDER BY c.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success($clinics);
}

function handleCreateClinic() {
    $user = ApiAuth::requireRole(['admin', 'doctor']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['name'])) {
        ApiResponse::error('Clinic name is required');
    }
    
    $db = Database::getInstance();
    
    // Validate doctor IDs if provided
    if (!empty($input['doctor_ids']) && is_array($input['doctor_ids'])) {
        $placeholders = str_repeat('?,', count($input['doctor_ids']) - 1) . '?';
        $stmt = $db->prepare("SELECT id, name FROM users 
                             WHERE id IN ($placeholders) 
                             AND role = 'doctor' 
                             AND status = 'active'");
        $stmt->execute($input['doctor_ids']);
        $validDoctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($validDoctors) !== count($input['doctor_ids'])) {
            ApiResponse::error('One or more invalid doctor IDs provided');
        }
    }
    
    try {
        $db->beginTransaction();
        
        // Create clinic
        $stmt = $db->prepare("INSERT INTO clinics (
            name, 
            description, 
            video_url,
            video_path,
            status,
            created_by
        ) VALUES (?, ?, ?, ?, 'active', ?)");
        
        $stmt->execute([
            $input['name'],
            $input['description'] ?? null,
            $input['video_url'] ?? null,
            $input['video_path'] ?? null,
            $user['id']
        ]);
        
        $clinicId = $db->lastInsertId();
        
        // Assign validated doctors
        if (!empty($validDoctors)) {
            $stmt = $db->prepare("INSERT INTO clinic_doctors (clinic_id, doctor_id) VALUES (?, ?)");
            foreach ($validDoctors as $doctor) {
                $stmt->execute([$clinicId, $doctor['id']]);
            }
        }
        
        // Always assign the creating doctor if they're a doctor
        if ($user['role'] === 'doctor') {
            $stmt = $db->prepare("INSERT IGNORE INTO clinic_doctors (clinic_id, doctor_id) VALUES (?, ?)");
            $stmt->execute([$clinicId, $user['id']]);
        }
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                             VALUES (?, 'create_clinic', 'clinics', ?, ?, ?)");
        $stmt->execute([$user['id'], $clinicId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        
        $db->commit();
        
        // Get created clinic with all its details
        $stmt = $db->prepare("SELECT c.*, u.name as creator_name,
                                    GROUP_CONCAT(DISTINCT d.name) as doctor_names,
                                    COUNT(DISTINCT cd.doctor_id) as doctor_count
                             FROM clinics c 
                             LEFT JOIN users u ON c.created_by = u.id 
                             LEFT JOIN clinic_doctors cd ON c.id = cd.clinic_id
                             LEFT JOIN users d ON cd.doctor_id = d.id
                             WHERE c.id = ?
                             GROUP BY c.id");
        $stmt->execute([$clinicId]);
        $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get assigned doctors details
        $stmt = $db->prepare("SELECT u.id, u.name, u.email, u.phone, d.specialization, d.license_number
                             FROM clinic_doctors cd
                             JOIN users u ON cd.doctor_id = u.id
                             LEFT JOIN doctors d ON u.id = d.user_id
                             WHERE cd.clinic_id = ?");
        $stmt->execute([$clinicId]);
        $clinic['doctors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ApiResponse::success($clinic, 'Clinic created successfully', 201);
        
    } catch (Exception $e) {
        $db->rollBack();
        ApiResponse::error('Failed to create clinic: ' . $e->getMessage(), 500);
    }
}

function handleUpdateClinic($clinicId) {
    $user = ApiAuth::requireRole(['admin', 'doctor']);
    
    $db = Database::getInstance();
    
    // Check if clinic exists and verify permissions
    $stmt = $db->prepare("SELECT c.*, cd.doctor_id 
                         FROM clinics c
                         LEFT JOIN clinic_doctors cd ON c.id = cd.clinic_id AND cd.doctor_id = ?
                         WHERE c.id = ?");
    $stmt->execute([$user['id'], $clinicId]);
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$clinic) {
        ApiResponse::notFound('Clinic not found');
    }
    
    // Only admin or assigned doctors can update
    if ($user['role'] !== 'admin' && !$clinic['doctor_id']) {
        ApiResponse::forbidden('Access denied');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $updateFields = [];
    $params = [];
    
    // Only allow updating fields that exist in the schema
    $allowedFields = ['name', 'description', 'video_url', 'video_path', 'status'];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    if (empty($updateFields)) {
        ApiResponse::error('No fields to update');
    }
    
    try {
        $db->beginTransaction();
        
        if (!empty($updateFields)) {
            // Update clinic
            $params[] = $clinicId;
            $query = "UPDATE clinics SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
        }
        
        // Update doctor assignments if provided and user is admin
        if ($user['role'] === 'admin' && isset($input['doctor_ids']) && is_array($input['doctor_ids'])) {
            // Remove existing assignments
            $stmt = $db->prepare("DELETE FROM clinic_doctors WHERE clinic_id = ?");
            $stmt->execute([$clinicId]);
            
            // Add new assignments
            $stmt = $db->prepare("INSERT INTO clinic_doctors (clinic_id, doctor_id) VALUES (?, ?)");
            foreach ($input['doctor_ids'] as $doctorId) {
                $stmt->execute([$clinicId, $doctorId]);
            }
        }
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                             VALUES (?, 'update_clinic', 'clinics', ?, ?, ?)");
        $stmt->execute([$user['id'], $clinicId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        
        $db->commit();
        
        // Get updated clinic
        $stmt = $db->prepare("SELECT c.*, u.name as creator_name
                             FROM clinics c 
                             LEFT JOIN users u ON c.created_by = u.id 
                             WHERE c.id = ?");
        $stmt->execute([$clinicId]);
        $updatedClinic = $stmt->fetch(PDO::FETCH_ASSOC);
        
        ApiResponse::success($updatedClinic, 'Clinic updated successfully');
        
    } catch (Exception $e) {
        $db->rollBack();
        ApiResponse::error('Failed to update clinic: ' . $e->getMessage(), 500);
    }
}

function handleDeleteClinic($clinicId) {
    $user = ApiAuth::requireRole(['admin', 'doctor']);
    
    $db = Database::getInstance();
    
    // Check ownership and existing appointments
    $stmt = $db->prepare("SELECT c.doctor_id, COUNT(a.id) as appointment_count 
                         FROM clinics c 
                         LEFT JOIN appointments a ON c.id = a.clinic_id AND a.status IN ('scheduled', 'confirmed')
                         WHERE c.id = ? 
                         GROUP BY c.id");
    $stmt->execute([$clinicId]);
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$clinic) {
        ApiResponse::notFound('Clinic not found');
    }
    
    if ($user['role'] !== 'admin' && $clinic['doctor_id'] != $user['id']) {
        ApiResponse::forbidden('Access denied');
    }
    
    if ($clinic['appointment_count'] > 0) {
        ApiResponse::error('Cannot delete clinic with scheduled appointments');
    }
    
    try {
        $db->beginTransaction();
        
        // Delete clinic schedules
        $stmt = $db->prepare("DELETE FROM clinic_schedules WHERE clinic_id = ?");
        $stmt->execute([$clinicId]);
        
        // Soft delete clinic
        $stmt = $db->prepare("UPDATE clinics SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$clinicId]);
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                             VALUES (?, 'delete_clinic', 'clinics', ?, ?, ?)");
        $stmt->execute([$user['id'], $clinicId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        
        $db->commit();
        
        ApiResponse::success(null, 'Clinic deleted successfully');
        
    } catch (Exception $e) {
        $db->rollBack();
        ApiResponse::error('Failed to delete clinic: ' . $e->getMessage(), 500);
    }
}
?>