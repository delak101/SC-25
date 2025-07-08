<?php
/**
 * Silent Connect API v1
 * Main API entry point and router
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';

class ApiResponse {
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        $response = [
            'success' => true,
            'status' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'), // ISO 8601 format
            'version' => '1.0'
        ];
        
        // Add metadata for collections
        if (is_array($data) && isset($data['pagination'])) {
            $response['meta'] = [
                'pagination' => $data['pagination']
            ];
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    public static function error($message = 'Error', $code = 400, $details = null) {
        http_response_code($code);
        $response = [
            'success' => false,
            'status' => $code,
            'error' => [
                'message' => $message,
                'code' => $code
            ],
            'timestamp' => date('c'), // ISO 8601 format
            'version' => '1.0'
        ];
        
        if ($details !== null) {
            $response['error']['details'] = $details;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }
    
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }
    
    public static function notFound($message = 'Not Found') {
        self::error($message, 404);
    }
    
    public static function validationError($message = 'Validation Error', $errors = []) {
        self::error($message, 422, ['validation_errors' => $errors]);
    }
    
    public static function conflict($message = 'Conflict') {
        self::error($message, 409);
    }
    
    public static function created($data = null, $message = 'Created') {
        self::success($data, $message, 201);
    }
    
    public static function noContent($message = 'No Content') {
        http_response_code(204);
        exit;
    }
}

class ApiAuth {
    public static function authenticate() {
        $headers = getallheaders();
        $token = null;
        
        // Check Authorization header
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        // Check for token in POST data
        if (!$token && isset($_POST['token'])) {
            $token = $_POST['token'];
        }
        
        // Check for token in GET parameters
        if (!$token && isset($_GET['token'])) {
            $token = $_GET['token'];
        }
        
        if (!$token) {
            ApiResponse::unauthorized('Authentication token required');
        }
        
        // Verify token (simple implementation - in production use JWT or similar)
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT u.*, ut.expires_at FROM users u 
                             JOIN remember_tokens ut ON u.id = ut.user_id 
                             WHERE ut.token = ? AND ut.expires_at > CURRENT_TIMESTAMP");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            ApiResponse::unauthorized('Invalid or expired token');
        }
        
        return $user;
    }
    
    public static function requireRole($required_roles) {
        $user = self::authenticate();
        
        if (!in_array($user['role'], (array)$required_roles)) {
            ApiResponse::forbidden('Insufficient permissions');
        }
        
        return $user;
    }
}

class ApiValidator {
    public static function validateJson($input = null) {
        if ($input === null) {
            $input = json_decode(file_get_contents('php://input'), true);
        }
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ApiResponse::error('Invalid JSON format', 400, [
                'json_error' => json_last_error_msg()
            ]);
        }
        
        return $input ?: [];
    }
    
    public static function validateRequiredFields(array $input, array $requiredFields) {
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || 
                (is_string($input[$field]) && trim($input[$field]) === '') ||
                (is_array($input[$field]) && empty($input[$field]))) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            ApiResponse::validationError('Missing required fields', [
                'missing_fields' => $missingFields,
                'required_fields' => $requiredFields
            ]);
        }
        
        return true;
    }
    
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ApiResponse::validationError('Invalid email format');
        }
        return true;
    }
    
    public static function validateInteger($value, $fieldName, $min = null, $max = null) {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($intValue === false) {
            ApiResponse::validationError("$fieldName must be a valid integer");
        }
        
        if ($min !== null && $intValue < $min) {
            ApiResponse::validationError("$fieldName must be at least $min");
        }
        
        if ($max !== null && $intValue > $max) {
            ApiResponse::validationError("$fieldName must be at most $max");
        }
        
        return $intValue;
    }
    
    public static function validateInArray($value, array $allowedValues, $fieldName) {
        if (!in_array($value, $allowedValues)) {
            ApiResponse::validationError("Invalid $fieldName value", [
                'provided' => $value,
                'allowed_values' => $allowedValues
            ]);
        }
        
        return true;
    }
    
    public static function sanitizeInput(array $input, array $allowedFields = null) {
        if ($allowedFields !== null) {
            $input = array_intersect_key($input, array_flip($allowedFields));
        }
        
        return array_map(function($value) {
            if (is_string($value)) {
                return trim($value);
            }
            return $value;
        }, $input);
    }
}

// Router
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/api/v1', '', $path);
$method = $_SERVER['REQUEST_METHOD'];

// Remove leading slash
$path = ltrim($path, '/');

// Split path into segments
$segments = explode('/', $path);
$endpoint = $segments[0] ?? '';

try {
    switch ($endpoint) {
        case '':
        case 'info':
            ApiResponse::success([
                'api' => [
                    'name' => 'Silent Connect API',
                    'version' => '1.0',
                    'description' => 'Medical Management System API for Silent Connect',
                    'base_url' => '/api/v1',
                    'documentation' => '/api/v1/docs'
                ],
                'endpoints' => [
                    'auth' => [
                        'login' => 'POST /api/v1/auth/login',
                        'register' => 'POST /api/v1/auth/register',
                        'logout' => 'POST /api/v1/auth/logout',
                        'profile' => 'GET /api/v1/auth/profile',
                        'refresh' => 'POST /api/v1/auth/refresh',
                        'forgot-password' => 'POST /api/v1/auth/forgot-password',
                        'reset-password' => 'POST /api/v1/auth/reset-password'
                    ],
                    'users' => [
                        'list' => 'GET /api/v1/users',
                        'get' => 'GET /api/v1/users/{id}',
                        'create' => 'POST /api/v1/users',
                        'update' => 'PUT /api/v1/users/{id}',
                        'delete' => 'DELETE /api/v1/users/{id}',
                        'search' => 'POST /api/v1/users/search'
                    ],
                    'clinics' => [
                        'list' => 'GET /api/v1/clinics',
                        'get' => 'GET /api/v1/clinics/{id}',
                        'create' => 'POST /api/v1/clinics',
                        'update' => 'PUT /api/v1/clinics/{id}',
                        'delete' => 'DELETE /api/v1/clinics/{id}',
                        'search' => 'POST /api/v1/clinics/search'
                    ],
                    'videos' => [
                        'list' => 'GET /api/v1/videos',
                        'get' => 'GET /api/v1/videos/{id}',
                        'create' => 'POST /api/v1/videos',
                        'update' => 'PUT /api/v1/videos/{id}',
                        'delete' => 'DELETE /api/v1/videos/{id}',
                        'search' => 'POST /api/v1/videos/search'
                    ],
                    'pharmacy' => [
                        'list' => 'GET /api/v1/pharmacy',
                        'get' => 'GET /api/v1/pharmacy/{id}',
                        'create' => 'POST /api/v1/pharmacy',
                        'update' => 'PUT /api/v1/pharmacy/{id}',
                        'delete' => 'DELETE /api/v1/pharmacy/{id}',
                        'search' => 'POST /api/v1/pharmacy/search'
                    ]
                ],
                'authentication' => [
                    'type' => 'Bearer Token',
                    'header' => 'Authorization: Bearer {token}'
                ],
                'response_format' => [
                    'success' => [
                        'success' => true,
                        'status' => 200,
                        'message' => 'Success message',
                        'data' => 'Response data',
                        'timestamp' => 'ISO 8601 timestamp',
                        'version' => 'API version'
                    ],
                    'error' => [
                        'success' => false,
                        'status' => 'HTTP status code',
                        'error' => [
                            'message' => 'Error message',
                            'code' => 'Error code',
                            'details' => 'Additional error details (optional)'
                        ],
                        'timestamp' => 'ISO 8601 timestamp',
                        'version' => 'API version'
                    ]
                ]
            ], 'Silent Connect API v1.0');
            break;
            
        case 'docs':
        case 'documentation':
            ApiResponse::success([
                'authentication' => [
                    'description' => 'All endpoints except auth/login and auth/register require authentication',
                    'method' => 'Bearer Token',
                    'header' => 'Authorization: Bearer {your_token}',
                    'example' => 'Authorization: Bearer abc123def456...'
                ],
                'common_request_patterns' => [
                    'pagination' => [
                        'page' => 'Page number (default: 1)',
                        'limit' => 'Items per page (default: 10, max: 50)'
                    ],
                    'search' => [
                        'method' => 'POST',
                        'endpoint' => '/{resource}/search',
                        'body' => ['query' => 'search term', 'filters' => 'additional filters']
                    ]
                ],
                'response_codes' => [
                    '200' => 'Success',
                    '201' => 'Created',
                    '204' => 'No Content',
                    '400' => 'Bad Request',
                    '401' => 'Unauthorized',
                    '403' => 'Forbidden',
                    '404' => 'Not Found',
                    '409' => 'Conflict',
                    '422' => 'Validation Error',
                    '500' => 'Internal Server Error'
                ],
                'example_requests' => [
                    'login' => [
                        'method' => 'POST',
                        'url' => '/api/v1/auth/login',
                        'body' => [
                            'email' => 'user@example.com',
                            'password' => 'password123',
                            'remember' => false
                        ]
                    ],
                    'get_prescriptions' => [
                        'method' => 'GET',
                        'url' => '/api/v1/pharmacy?page=1&limit=10',
                        'headers' => ['Authorization: Bearer {token}']
                    ],
                    'create_prescription' => [
                        'method' => 'POST',
                        'url' => '/api/v1/pharmacy',
                        'headers' => ['Authorization: Bearer {token}'],
                        'body' => [
                            'doctor_id' => 1,
                            'patient_id' => 2,
                            'description' => 'Prescription details',
                            'notes' => 'Additional notes'
                        ]
                    ]
                ]
            ], 'API Documentation');
            break;
            
        case 'health':
        case 'status':
            try {
                // Quick database check
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT 1");
                $stmt->execute();
                
                ApiResponse::success([
                    'status' => 'healthy',
                    'database' => 'connected',
                    'timestamp' => date('c'),
                    'version' => '1.0',
                    'environment' => $_ENV['APP_ENV'] ?? 'production'
                ], 'API is healthy');
            } catch (Exception $e) {
                ApiResponse::error('API health check failed', 500, [
                    'database' => 'disconnected',
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        case 'auth':
            require_once __DIR__ . '/auth.php';
            break;
            
        case 'users':
            require_once __DIR__ . '/users.php';
            break;
            
        case 'clinics':
            require_once __DIR__ . '/clinics.php';
            break;
            
        case 'appointments':
            require_once __DIR__ . '/appointments.php';
            break;
            
        case 'patients':
            require_once __DIR__ . '/patients.php';
            break;
            
        case 'doctors':
            require_once __DIR__ . '/doctors.php';
            break;
            
        case 'videos':
            require_once __DIR__ . '/videos.php';
            break;
            
        case 'pharmacy':
            require_once __DIR__ . '/pharmacy.php';
            break;
            
        case 'terms':
            require_once __DIR__ . '/terms.php';
            break;
            
        default:
            ApiResponse::notFound('Endpoint not found');
    }
} catch (Exception $e) {
    ApiResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>