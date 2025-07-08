<?php
/**
 * Pharmacy API Endpoints for Prescriptions
 * Handles prescription-related operations through the medical_records table
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

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

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

error_log("Pharmacy API - Full URL: " . $_SERVER['REQUEST_URI']);
error_log("Pharmacy API - Parsed Path: " . $uri);

// Match the entire path pattern including potential ID
if (preg_match('#^/api/v1/pharmacy(?:/(\d+|search)?)?/?$#', $uri, $matches)) {
    error_log("URL matches pattern. Matches: " . print_r($matches, true));
    // The ID or 'search' will be in $matches[1] if present
    $firstSegment = isset($matches[1]) ? $matches[1] : '';
    error_log("First segment from matches[1]: " . $firstSegment);
} else {
    error_log("URL does not match pattern");
    $firstSegment = '';
}

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Get a single prescription by ID
 */
function handleGetPrescription($id) {
    $user = ApiAuth::authenticate();
    $id = filter_var($id, FILTER_VALIDATE_INT);
    
    if (!$id) {
        ApiResponse::error('Invalid prescription ID', 400);
    }

    $db = Database::getInstance();
    $query = "SELECT mr.*, 
                     u1.name as doctor_name,
                     u2.name as patient_name
              FROM medical_records mr
              LEFT JOIN users u1 ON mr.doctor_id = u1.id
              LEFT JOIN users u2 ON mr.patient_id = u2.id
              WHERE mr.id = ? AND mr.prescription IS NOT NULL";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $prescription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prescription) {
        ApiResponse::notFound('Prescription not found');
    }
    
    ApiResponse::success($prescription);
}

/**
 * Get all prescriptions with optional filtering
 */
function handleGetPrescriptions() {
    $user = ApiAuth::authenticate();
    
    $db = Database::getInstance();
    
    // Get pagination parameters
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 10), 50);
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT mr.*, 
                     u1.name as doctor_name,
                     u2.name as patient_name
              FROM medical_records mr
              LEFT JOIN users u1 ON mr.doctor_id = u1.id
              LEFT JOIN users u2 ON mr.patient_id = u2.id
              WHERE mr.prescription IS NOT NULL
              ORDER BY mr.created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$limit, $offset]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM medical_records WHERE prescription IS NOT NULL";
    $stmt = $db->prepare($countQuery);
    $stmt->execute();
    $totalCount = $stmt->fetchColumn();
    
    ApiResponse::success([
        'prescriptions' => $prescriptions,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $limit)
        ]
    ]);
}

/**
 * Search prescriptions by various criteria
 */
function handleSearchPrescriptions() {
    $user = ApiAuth::authenticate();
    
    // Get and validate JSON input
    $input = ApiValidator::validateJson();
    
    $db = Database::getInstance();
    $where = ["mr.prescription IS NOT NULL"];
    $params = [];
    
    if (!empty($input['doctor_id'])) {
        $where[] = "mr.doctor_id = ?";
        $params[] = ApiValidator::validateInteger($input['doctor_id'], 'doctor_id', 1);
    }
    
    if (!empty($input['patient_id'])) {
        $where[] = "mr.patient_id = ?";
        $params[] = ApiValidator::validateInteger($input['patient_id'], 'patient_id', 1);
    }
    
    if (!empty($input['date_from'])) {
        $where[] = "mr.created_at >= ?";
        $params[] = $input['date_from'];
    }
    
    if (!empty($input['date_to'])) {
        $where[] = "mr.created_at <= ?";
        $params[] = $input['date_to'];
    }
    
    $whereClause = implode(" AND ", $where);
    $query = "SELECT mr.*, 
                     u1.name as doctor_name,
                     u2.name as patient_name
              FROM medical_records mr
              LEFT JOIN users u1 ON mr.doctor_id = u1.id
              LEFT JOIN users u2 ON mr.patient_id = u2.id
              WHERE $whereClause
              ORDER BY mr.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success([
        'prescriptions' => $prescriptions,
        'search_criteria' => ApiValidator::sanitizeInput($input),
        'count' => count($prescriptions)
    ]);
}

/**
 * Create a new prescription
 */
function handleCreatePrescription() {
    $user = ApiAuth::requireRole(['doctor', 'admin']);
    
    // Get and validate JSON input
    $input = ApiValidator::validateJson();
    
    // Validate required fields
    $requiredFields = ['doctor_id', 'patient_id', 'description'];
    ApiValidator::validateRequiredFields($input, $requiredFields);
    
    // Sanitize input
    $input = ApiValidator::sanitizeInput($input);
    
    // Validate field types
    $doctorId = ApiValidator::validateInteger($input['doctor_id'], 'doctor_id', 1);
    $patientId = ApiValidator::validateInteger($input['patient_id'], 'patient_id', 1);
    
    $db = Database::getInstance();
    
    try {
        $query = "INSERT INTO medical_records (doctor_id, patient_id, prescription, notes, created_at)
                  VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($query);
        $notes = $input['notes'] ?? null;
        $stmt->execute([$doctorId, $patientId, $input['description'], $notes]);
        
        $prescriptionId = $db->lastInsertId();
        
        ApiResponse::created([
            'id' => $prescriptionId,
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'description' => $input['description'],
            'notes' => $notes
        ], 'Prescription created successfully');
        
    } catch (Exception $e) {
        ApiResponse::error('Failed to create prescription: ' . $e->getMessage(), 500);
    }
}

/**
 * Update an existing prescription
 */
function handleUpdatePrescription($id) {
    $user = ApiAuth::requireRole(['doctor', 'admin']);
    
    // Get and validate JSON input
    $input = ApiValidator::validateJson();
    
    // Validate and sanitize ID
    $id = ApiValidator::validateInteger($id, 'prescription ID', 1);
    
    // Sanitize input
    $input = ApiValidator::sanitizeInput($input, ['description', 'notes']);
    
    $db = Database::getInstance();
    
    // Verify prescription exists
    $stmt = $db->prepare("SELECT id FROM medical_records WHERE id = ? AND prescription IS NOT NULL");
    $stmt->execute([$id]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        ApiResponse::notFound('Prescription not found');
    }
    
    $updates = [];
    $params = [];
    
    if (isset($input['description']) && !empty(trim($input['description']))) {
        $updates[] = "prescription = ?";
        $params[] = $input['description'];
    }
    
    if (isset($input['notes'])) {
        $updates[] = "notes = ?";
        $params[] = $input['notes'];
    }
    
    if (empty($updates)) {
        ApiResponse::validationError('No valid fields to update');
    }
    
    $params[] = $id;
    
    try {
        $query = "UPDATE medical_records SET " . implode(", ", $updates) . " WHERE id = ? AND prescription IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        ApiResponse::success([
            'id' => $id,
            'updated_fields' => array_keys($input)
        ], 'Prescription updated successfully');
        
    } catch (Exception $e) {
        ApiResponse::error('Failed to update prescription: ' . $e->getMessage(), 500);
    }
}

/**
 * Delete a prescription
 */
function handleDeletePrescription($id) {
    $user = ApiAuth::requireRole(['doctor', 'admin']);
    
    // Validate and sanitize ID
    $id = ApiValidator::validateInteger($id, 'prescription ID', 1);
    
    $db = Database::getInstance();
    
    try {
        $query = "DELETE FROM medical_records WHERE id = ? AND prescription IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            ApiResponse::success([
                'id' => $id,
                'deleted' => true
            ], 'Prescription deleted successfully');
        } else {
            ApiResponse::notFound('Prescription not found');
        }
        
    } catch (Exception $e) {
        ApiResponse::error('Failed to delete prescription: ' . $e->getMessage(), 500);
    }
}

// Handle the request based on method and path
try {
    switch ($method) {
        case 'GET':
            if ($firstSegment === 'search') {
                handleSearchPrescriptions();
            } elseif (is_numeric($firstSegment)) {
                handleGetPrescription($firstSegment);
            } elseif (empty($firstSegment)) {
                handleGetPrescriptions();
            } else {
                ApiResponse::notFound('Endpoint not found');
            }
            break;

        case 'POST':
            if (empty($firstSegment)) {
                handleCreatePrescription();
            } elseif ($firstSegment === 'search') {
                handleSearchPrescriptions();
            } else {
                ApiResponse::notFound('Endpoint not found');
            }
            break;

        case 'PUT':
            if (is_numeric($firstSegment)) {
                handleUpdatePrescription($firstSegment);
            } else {
                ApiResponse::notFound('Endpoint not found');
            }
            break;

        case 'DELETE':
            if (is_numeric($firstSegment)) {
                handleDeletePrescription($firstSegment);
            } else {
                ApiResponse::notFound('Endpoint not found');
            }
            break;

        default:
            ApiResponse::error('Method not allowed', 405);
            break;
    }
} catch (Exception $e) {
    error_log("Pharmacy API Error: " . $e->getMessage());
    ApiResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>