<?php
/**
 * Videos API Endpoints
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

$method = $_SERVER['REQUEST_METHOD'];
$segments = explode('/', trim($path, '/'));
$action = $segments[1] ?? '';
$videoId = $segments[2] ?? null;

switch ($method) {
    case 'GET':
        if ($action === 'search' && isset($_GET['q'])) {
            handleSearchVideos($_GET['q']);
        } else if (!empty($action) && is_numeric($action)) {
            handleGetVideo($action);
        } else {
            handleGetVideos();
        }
        break;
    case 'POST':
        if ($action === 'search') {
            handleSearchVideos();
        } else {
            handleCreateVideo();
        }
        break;
    case 'PUT':
        if ($videoId) {
            handleUpdateVideo($videoId);
        } else {
            ApiResponse::error('Video ID required for update');
        }
        break;
    case 'DELETE':
        if ($videoId) {
            handleDeleteVideo($videoId);
        } else {
            ApiResponse::error('Video ID required for deletion');
        }
        break;
    default:
        ApiResponse::error('Method not allowed', 405);
}

function handleGetVideos() {
    $user = ApiAuth::authenticate();
    
    $db = Database::getInstance();
    
    // Get filters
    $category = $_GET['category'] ?? '';
    $level = $_GET['level'] ?? '';
    $language = $_GET['language'] ?? '';
    $target_audience = $_GET['target_audience'] ?? '';
    
    // Get pagination parameters
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 10), 50);
    $offset = ($page - 1) * $limit;
    
    $whereClause = "WHERE v.status = 'active'";
    $params = [];
    
    if ($category) {
        $whereClause .= " AND v.category = ?";
        $params[] = $category;
    }
    
    if ($target_audience && $target_audience !== 'all') {
        $whereClause .= " AND v.target_audience IN ('all', ?)";
        $params[] = $target_audience;
    }
    
    $query = "SELECT v.*, u.name as uploader_name
              FROM videos v
              LEFT JOIN users u ON v.created_by = u.id
              $whereClause
              ORDER BY v.created_at DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM videos v $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute(array_slice($params, 0, -2));
    $totalCount = $stmt->fetchColumn();
    
    ApiResponse::success([
        'videos' => $videos,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $limit)
        ]
    ]);
}

function handleGetVideo($videoId) {
    $user = ApiAuth::authenticate();
    
    $db = Database::getInstance();
    
    // Get video details joined with uploader name
    $stmt = $db->prepare("SELECT v.*, u.name as uploader_name
                          FROM videos v
                          LEFT JOIN users u ON v.created_by = u.id
                          WHERE v.id = ? AND v.status = 'active'");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$video) {
        ApiResponse::notFound('Video not found');
    }
    
    // Get related videos from same category
    $stmt = $db->prepare("SELECT v.*, u.name as uploader_name
                          FROM videos v
                          LEFT JOIN users u ON v.created_by = u.id
                          WHERE v.category = ? AND v.id != ? AND v.status = 'active'
                          ORDER BY v.created_at DESC
                          LIMIT 5");
    $stmt->execute([$video['category'], $videoId]);
    $video['related_videos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success(['video' => $video]);
}

function handleCreateVideo() {
    $user = ApiAuth::authenticate();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $requiredFields = ['title', 'description', 'video_url', 'category'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            ApiResponse::error("$field is required");
        }
    }

    // Validate target audience if provided
    $validAudiences = ['all', 'admin', 'doctor', 'patient', 'secretary', 'pharmacy', 'reception'];
    $targetAudience = $data['target_audience'] ?? 'all';
    if (!in_array($targetAudience, $validAudiences)) {
        ApiResponse::error('Invalid target_audience value');
    }
    
    $db = Database::getInstance();
    
    // Insert the video with correct columns from schema
    $stmt = $db->prepare("INSERT INTO videos (
        title, 
        description, 
        video_url, 
        video_path,
        category,
        target_audience,
        status,
        created_by
    ) VALUES (?, ?, ?, ?, ?, ?, 'active', ?)");
    
    $result = $stmt->execute([
        $data['title'],
        $data['description'],
        $data['video_url'],
        $data['video_path'] ?? null,
        $data['category'],
        $targetAudience,
        $user['id']
    ]);
    
    if (!$result) {
        ApiResponse::error('Failed to create video');
    }
    
    $videoId = $db->lastInsertId();
    
    // Get the newly created video
    $stmt = $db->prepare("SELECT v.*, u.name as uploader_name
                          FROM videos v
                          LEFT JOIN users u ON v.created_by = u.id
                          WHERE v.id = ?");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    ApiResponse::success($video);
}

function handleUpdateVideo($videoId) {
    $user = ApiAuth::authenticate();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $db = Database::getInstance();
    
    // Check if video exists and user has permission
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = ? AND status = 'active'");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$video) {
        ApiResponse::notFound('Video not found');
    }
    
    if ($video['created_by'] != $user['id'] && !$user['is_admin']) {
        ApiResponse::forbidden('You do not have permission to update this video');
    }
    
    // Build update query based on provided fields
    $updates = [];
    $params = [];
    $allowedFields = ['title', 'description', 'video_url', 'thumbnail_url', 'category', 'status'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($updates)) {
        ApiResponse::error('No fields to update');
    }
    
    $params[] = $videoId;
    
    $stmt = $db->prepare("UPDATE videos SET " . implode(', ', $updates) . " WHERE id = ?");
    $result = $stmt->execute($params);
    
    if (!$result) {
        ApiResponse::error('Failed to update video');
    }
    
    // Get updated video
    $stmt = $db->prepare("SELECT v.*, u.name as uploader_name
                          FROM videos v
                          LEFT JOIN users u ON v.created_by = u.id
                          WHERE v.id = ?");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    ApiResponse::success($video);
}

function handleDeleteVideo($videoId) {
    $user = ApiAuth::authenticate();
    
    $db = Database::getInstance();
    
    // Check if video exists and user has permission
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = ? AND status = 'active'");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$video) {
        ApiResponse::notFound('Video not found');
    }
    
    if ($video['created_by'] != $user['id'] && !$user['is_admin']) {
        ApiResponse::forbidden('You do not have permission to delete this video');
    }
    
    // Soft delete by updating status
    $stmt = $db->prepare("UPDATE videos SET status = 'deleted' WHERE id = ?");
    $result = $stmt->execute([$videoId]);
    
    if (!$result) {
        ApiResponse::error('Failed to delete video');
    }
    
    ApiResponse::success(['message' => 'Video deleted successfully']);
}

function handleSearchVideos() {
    $user = ApiAuth::authenticate();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $searchTerm = $input['query'] ?? '';
    $category = $input['category'] ?? '';
    $level = $input['level'] ?? '';
    $language = $input['language'] ?? '';
    
    if (empty($searchTerm)) {
        ApiResponse::error('Search term is required');
    }
    
    $db = Database::getInstance();
    
    $whereClause = "WHERE v.status = 'published' AND (v.title LIKE ? OR v.description LIKE ? OR v.keywords LIKE ?)";
    $params = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];
    
    if ($category) {
        $whereClause .= " AND v.category = ?";
        $params[] = $category;
    }
    
    if ($level) {
        $whereClause .= " AND v.level = ?";
        $params[] = $level;
    }
    
    if ($language) {
        $whereClause .= " AND v.language = ?";
        $params[] = $language;
    }
    
    $query = "SELECT v.*, u.name as uploader_name
              FROM videos v
              LEFT JOIN users u ON v.uploaded_by = u.id
              $whereClause
              ORDER BY v.created_at DESC
              LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success($videos);
}
?>