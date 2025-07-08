<?php
/**
 * Video Handler
 * Processes video-related requests
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Video.php';

// Require authentication
requireAuth();

$video = new Video();
$current_user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        requireCSRF();
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                // Check permission
                requirePermission('videos', 'create');
                
                $title = sanitizeInput($_POST['title'] ?? '');
                $description = sanitizeInput($_POST['description'] ?? '');
                $video_url = sanitizeInput($_POST['video_url'] ?? '');
                $category = !empty($_POST['category']) ? (int)$_POST['category'] : null;
                $target_audience = sanitizeInput($_POST['target_audience'] ?? 'all');
                $clinic_id = !empty($_POST['clinic_id']) ? (int)$_POST['clinic_id'] : null;
                
                if (empty($title)) {
                    throw new Exception('عنوان الفيديو مطلوب');
                }
                
                // Validate target audience
                $valid_audiences = array_keys(USER_ROLES);
                $valid_audiences[] = 'all';
                if (!in_array($target_audience, $valid_audiences)) {
                    $target_audience = 'all';
                }
                
                // Handle video upload if provided
                $video_path = '';
                if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = $video->uploadFile($_FILES['video_file'], $title);
                    $video_path = $upload_result['path'];
                }
                
                // Require either video file or video URL
                if (empty($video_url) && empty($video_path)) {
                    throw new Exception('يجب توفير ملف فيديو أو رابط فيديو');
                }
                
                $video_data = [
                    'title' => $title,
                    'description' => $description,
                    'video_url' => $video_url,
                    'video_path' => $video_path,
                    'category' => $category,
                    'target_audience' => $target_audience,
                    'clinic_id' => $clinic_id,
                    'created_by' => $current_user['id']
                ];
                
                $video_id = $video->create($video_data);
                
                // Log activity
                logActivity($current_user['id'], 'video_created', "إنشاء فيديو: $title", 'videos', $video_id);
                
                $success_message = 'تم إنشاء الفيديو بنجاح';
                break;
                
            case 'update':
                // Check permission
                requirePermission('videos', 'update');
                
                $video_id = (int)($_POST['video_id'] ?? 0);
                if (!$video_id) {
                    throw new Exception('معرف الفيديو مطلوب');
                }
                
                $update_data = [];
                
                if (isset($_POST['title'])) {
                    $update_data['title'] = sanitizeInput($_POST['title']);
                }
                
                if (isset($_POST['description'])) {
                    $update_data['description'] = sanitizeInput($_POST['description']);
                }
                
                if (isset($_POST['video_url'])) {
                    $update_data['video_url'] = sanitizeInput($_POST['video_url']);
                }
                
                if (isset($_POST['category'])) {
                    $update_data['category'] = !empty($_POST['category']) ? (int)$_POST['category'] : null;
                }
                
                if (isset($_POST['target_audience'])) {
                    $target_audience = sanitizeInput($_POST['target_audience']);
                    $valid_audiences = array_keys(USER_ROLES);
                    $valid_audiences[] = 'all';
                    if (in_array($target_audience, $valid_audiences)) {
                        $update_data['target_audience'] = $target_audience;
                    }
                }
                
                if (isset($_POST['status'])) {
                    $update_data['status'] = sanitizeInput($_POST['status']);
                }
                
                // Handle video upload if provided
                if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = $video->uploadFile($_FILES['video_file'], $update_data['title'] ?? 'فيديو محدث');
                    $update_data['video_path'] = $upload_result['path'];
                }
                
                if (empty($update_data)) {
                    throw new Exception('لا توجد بيانات للتحديث');
                }
                
                if ($video->update($video_id, $update_data)) {
                    // Log activity
                    logActivity($current_user['id'], 'video_updated', "تحديث فيديو", 'videos', $video_id);
                    $success_message = 'تم تحديث الفيديو بنجاح';
                } else {
                    throw new Exception('فشل في تحديث الفيديو');
                }
                break;
                
            case 'delete':
                // Check permission
                requirePermission('videos', 'delete');
                
                $video_id = (int)($_POST['video_id'] ?? 0);
                if (!$video_id) {
                    throw new Exception('معرف الفيديو مطلوب');
                }
                
                // Get video data to delete file
                $video_data = $video->getById($video_id);
                
                if ($video->delete($video_id)) {
                    // Delete associated file if exists
                    if ($video_data && $video_data['video_path']) {
                        $video->deleteFile($video_data['video_path']);
                    }
                    
                    // Log activity
                    logActivity($current_user['id'], 'video_deleted', "حذف فيديو", 'videos', $video_id);
                    $success_message = 'تم حذف الفيديو بنجاح';
                } else {
                    throw new Exception('فشل في حذف الفيديو');
                }
                break;
                
            case 'create_category':
                // Check permission
                requirePermission('video_categories', 'create');
                
                $name = sanitizeInput($_POST['name'] ?? '');
                $slug = sanitizeInput($_POST['slug'] ?? '');
                $description = sanitizeInput($_POST['description'] ?? '');
                $icon = sanitizeInput($_POST['icon'] ?? 'video');
                $color = sanitizeInput($_POST['color'] ?? 'primary');
                
                if (empty($name) || empty($slug)) {
                    throw new Exception('اسم الفئة واسم الرابط مطلوبان');
                }
                
                $category_data = [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'icon' => $icon,
                    'color' => $color,
                    'created_by' => $current_user['id']
                ];
                
                $category_id = $video->createCategory($category_data);
                
                // Log activity
                logActivity($current_user['id'], 'video_category_created', "إنشاء فئة فيديو: $name", 'video_categories', $category_id);
                
                $success_message = 'تم إنشاء الفئة بنجاح';
                break;
                
            case 'update_category':
                // Check permission
                requirePermission('video_categories', 'update');
                
                $category_id = (int)($_POST['category_id'] ?? 0);
                if (!$category_id) {
                    throw new Exception('معرف الفئة مطلوب');
                }
                
                $update_data = [];
                
                if (isset($_POST['name'])) {
                    $update_data['name'] = sanitizeInput($_POST['name']);
                }
                
                if (isset($_POST['slug'])) {
                    $update_data['slug'] = sanitizeInput($_POST['slug']);
                }
                
                if (isset($_POST['description'])) {
                    $update_data['description'] = sanitizeInput($_POST['description']);
                }
                
                if (isset($_POST['icon'])) {
                    $update_data['icon'] = sanitizeInput($_POST['icon']);
                }
                
                if (isset($_POST['color'])) {
                    $update_data['color'] = sanitizeInput($_POST['color']);
                }
                
                if (empty($update_data)) {
                    throw new Exception('لا توجد بيانات للتحديث');
                }
                
                if ($video->updateCategory($category_id, $update_data)) {
                    // Log activity
                    logActivity($current_user['id'], 'video_category_updated', "تحديث فئة فيديو", 'video_categories', $category_id);
                    $success_message = 'تم تحديث الفئة بنجاح';
                } else {
                    throw new Exception('فشل في تحديث الفئة');
                }
                break;
                
            case 'delete_category':
                // Check permission
                requirePermission('video_categories', 'delete');
                
                $category_id = (int)($_POST['category_id'] ?? 0);
                if (!$category_id) {
                    throw new Exception('معرف الفئة مطلوب');
                }
                
                if ($video->deleteCategory($category_id)) {
                    // Log activity
                    logActivity($current_user['id'], 'video_category_deleted', "حذف فئة فيديو", 'video_categories', $category_id);
                    $success_message = 'تم حذف الفئة بنجاح';
                } else {
                    throw new Exception('فشل في حذف الفئة');
                }
                break;
                
            default:
                throw new Exception('عملية غير صحيحة');
        }
        
        // Handle AJAX requests
        if (isAjaxRequest()) {
            $response = ['success' => true, 'message' => $success_message];
            
            // Include updated data for certain actions
            if ($action === 'create' && isset($video_id)) {
                $response['video'] = $video->getById($video_id);
            } elseif ($action === 'create_category' && isset($category_id)) {
                $response['category'] = $video->getCategoryById($category_id);
            }
            
            jsonResponse($response);
        }
        
        // Regular form submission
        $redirect_url = $_POST['redirect'] ?? '/videos/index.php';
        redirectWithMessage($redirect_url, $success_message);
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Video operation failed: " . $error_message);
        
        // Handle AJAX requests
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $error_message], 400);
        }
        
        // Regular form submission
        setErrorMessage($error_message);
        $redirect_url = $_POST['redirect'] ?? '/videos/index.php';
        header('Location: ' . $redirect_url);
        exit;
    }
}

// Handle GET requests for video data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get':
            requirePermission('videos', 'read');
            
            $video_id = (int)($_GET['id'] ?? 0);
            if (!$video_id) {
                jsonResponse(['success' => false, 'message' => 'معرف الفيديو مطلوب'], 400);
            }
            
            $video_data = $video->getById($video_id);
            if (!$video_data) {
                jsonResponse(['success' => false, 'message' => 'الفيديو غير موجود'], 404);
            }
            
            jsonResponse(['success' => true, 'video' => $video_data]);
            break;
            
        case 'search':
            requirePermission('videos', 'read');
            
            $query = sanitizeInput($_GET['q'] ?? '');
            $limit = min((int)($_GET['limit'] ?? 10), 50);
            
            $results = $video->search($query, $limit);
            jsonResponse(['success' => true, 'videos' => $results]);
            break;
            
        case 'by_category':
            requirePermission('videos', 'read');
            
            $category_id = (int)($_GET['category_id'] ?? 0);
            $limit = min((int)($_GET['limit'] ?? 20), 50);
            
            if (!$category_id) {
                jsonResponse(['success' => false, 'message' => 'معرف الفئة مطلوب'], 400);
            }
            
            $videos = $video->getByCategory($category_id, $limit);
            jsonResponse(['success' => true, 'videos' => $videos]);
            break;
            
        case 'by_audience':
            requirePermission('videos', 'read');
            
            $audience = sanitizeInput($_GET['audience'] ?? $current_user['role']);
            $limit = min((int)($_GET['limit'] ?? 20), 50);
            
            // $videos = $video->getByTargetAudience($audience, $limit);
            jsonResponse(['success' => true, 'videos' => $videos]);
            break;
            
        case 'categories':
            requirePermission('videos', 'read');
            
            $categories = $video->getCategories();
            jsonResponse(['success' => true, 'categories' => $categories]);
            break;
            
        case 'category':
            requirePermission('videos', 'read');
            
            $category_id = (int)($_GET['id'] ?? 0);
            if (!$category_id) {
                jsonResponse(['success' => false, 'message' => 'معرف الفئة مطلوب'], 400);
            }
            
            $category = $video->getCategoryById($category_id);
            if (!$category) {
                jsonResponse(['success' => false, 'message' => 'الفئة غير موجودة'], 404);
            }
            
            jsonResponse(['success' => true, 'category' => $category]);
            break;
            
        case 'statistics':
            requirePermission('videos', 'read');
            
            $user_filter = hasPermission('videos', 'manage') ? null : $current_user['id'];
            $stats = $video->getStatistics($user_filter);
            jsonResponse(['success' => true, 'statistics' => $stats]);
            break;
            
        case 'recent':
            requirePermission('videos', 'read');
            
            $limit = min((int)($_GET['limit'] ?? 10), 50);
            $user_role = $current_user['role'];
            
            $recent_videos = $video->getRecent($limit, $user_role);
            jsonResponse(['success' => true, 'videos' => $recent_videos]);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'عملية غير صحيحة'], 400);
    }
}

// If no valid action, redirect to videos list
header('Location: /videos/index.php');
exit;
?>