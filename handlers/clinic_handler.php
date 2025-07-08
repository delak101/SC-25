<?php
/**
 * Clinic Handler
 * Processes clinic-related requests
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Clinic.php';

// Require authentication
requireAuth();

$clinic = new Clinic();
$current_user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        requireCSRF();
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                // Check permission
                requirePermission('clinics', 'create');
                
                $name = sanitizeInput($_POST['name'] ?? '');
                $description = sanitizeInput($_POST['description'] ?? '');
                $specialization = sanitizeInput($_POST['specialization'] ?? '');
                $video_url = sanitizeInput($_POST['video_url'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('اسم العيادة مطلوب');
                }
                
                // Handle video upload if provided
                $video_path = '';
                if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = uploadFile(
                        $_FILES['video_file'],
                        ALLOWED_VIDEO_TYPES,
                        UPLOAD_MAX_SIZE,
                        UPLOAD_PATH . 'videos/'
                    );
                    $video_path = $upload_result['path'];
                }
                
                $clinic_data = [
                    'name' => $name,
                    'description' => $description,
                    'specialization' => $specialization,
                    'video_url' => $video_url,
                    'video_path' => $video_path,
                    'created_by' => $current_user['id']
                ];
                
                $clinic_id = $clinic->create($clinic_data);
                
                // Log activity
                logActivity($current_user['id'], 'clinic_created', "إنشاء عيادة: $name", 'clinics', $clinic_id);
                
                $success_message = 'تم إنشاء العيادة بنجاح';
                break;
                
            case 'update':
                // Check permission
                requirePermission('clinics', 'update');
                
                $clinic_id = (int)($_POST['clinic_id'] ?? 0);
                if (!$clinic_id) {
                    throw new Exception('معرف العيادة مطلوب');
                }
                
                $update_data = [];
                
                if (isset($_POST['name'])) {
                    $update_data['name'] = sanitizeInput($_POST['name']);
                }
                
                if (isset($_POST['description'])) {
                    $update_data['description'] = sanitizeInput($_POST['description']);
                }
                
                if (isset($_POST['specialization'])) {
                    $update_data['specialization'] = sanitizeInput($_POST['specialization']);
                }
                
                if (isset($_POST['video_url'])) {
                    $update_data['video_url'] = sanitizeInput($_POST['video_url']);
                }
                
                if (isset($_POST['status'])) {
                    $update_data['status'] = sanitizeInput($_POST['status']);
                }
                
                // Handle video upload if provided
                if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = uploadFile(
                        $_FILES['video_file'],
                        ALLOWED_VIDEO_TYPES,
                        UPLOAD_MAX_SIZE,
                        UPLOAD_PATH . 'videos/'
                    );
                    $update_data['video_path'] = $upload_result['path'];
                }
                
                if (empty($update_data)) {
                    throw new Exception('لا توجد بيانات للتحديث');
                }
                
                if ($clinic->update($clinic_id, $update_data)) {
                    // Log activity
                    logActivity($current_user['id'], 'clinic_updated', "تحديث عيادة", 'clinics', $clinic_id);
                    $success_message = 'تم تحديث العيادة بنجاح';
                } else {
                    throw new Exception('فشل في تحديث العيادة');
                }
                break;
                
            case 'delete':
                // Check permission
                requirePermission('clinics', 'delete');
                
                $clinic_id = (int)($_POST['clinic_id'] ?? 0);
                if (!$clinic_id) {
                    throw new Exception('معرف العيادة مطلوب');
                }
                
                if ($clinic->delete($clinic_id)) {
                    // Log activity
                    logActivity($current_user['id'], 'clinic_deleted', "حذف عيادة", 'clinics', $clinic_id);
                    $success_message = 'تم حذف العيادة بنجاح';
                } else {
                    throw new Exception('فشل في حذف العيادة');
                }
                break;
                
            case 'assign_doctor':
                // Check permission - using 'update' since 'manage' doesn't exist
                requirePermission('clinics', 'update');
                
                $clinic_id = (int)($_POST['clinic_id'] ?? 0);
                $doctor_id = (int)($_POST['doctor_id'] ?? 0);
                
                // Debug logging
                error_log("Assign doctor request - Clinic ID: $clinic_id, Doctor ID: $doctor_id");
                
                if (!$clinic_id || !$doctor_id) {
                    throw new Exception('معرف العيادة والدكتور مطلوبان');
                }
                
                if ($clinic->assignDoctor($clinic_id, $doctor_id)) {
                    // Log activity
                    logActivity($current_user['id'], 'doctor_assigned', "تعيين دكتور للعيادة", 'clinic_doctors');
                    $success_message = 'تم تعيين الدكتور للعيادة بنجاح';
                } else {
                    throw new Exception('فشل في تعيين الدكتور');
                }
                break;
                
            case 'remove_doctor':
                // Check permission - using 'update' since 'manage' doesn't exist
                requirePermission('clinics', 'update');
                
                $clinic_id = (int)($_POST['clinic_id'] ?? 0);
                $doctor_id = (int)($_POST['doctor_id'] ?? 0);
                
                if (!$clinic_id || !$doctor_id) {
                    throw new Exception('معرف العيادة والدكتور مطلوبان');
                }
                
                if ($clinic->removeDoctor($clinic_id, $doctor_id)) {
                    // Log activity
                    logActivity($current_user['id'], 'doctor_removed', "إزالة دكتور من العيادة", 'clinic_doctors');
                    $success_message = 'تم إزالة الدكتور من العيادة بنجاح';
                } else {
                    throw new Exception('فشل في إزالة الدكتور');
                }
                break;
                
            default:
                throw new Exception('عملية غير صحيحة');
        }
        
        // Handle AJAX requests
        if (isAjaxRequest()) {
            $response = ['success' => true, 'message' => $success_message];
            
            // Include updated data for certain actions
            if ($action === 'create' && isset($clinic_id)) {
                $response['clinic'] = $clinic->getById($clinic_id);
            }
            
            jsonResponse($response);
        }
        
        // Regular form submission
        $redirect_url = $_POST['redirect'] ?? '/clinics/index.php';
        redirectWithMessage($redirect_url, $success_message);
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Clinic operation failed: " . $error_message);
        
        // Handle AJAX requests
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $error_message], 400);
        }
        
        // Regular form submission
        setErrorMessage($error_message);
        $redirect_url = $_POST['redirect'] ?? '/clinics/index.php';
        header('Location: ' . $redirect_url);
        exit;
    }
}

// Handle GET requests for clinic data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get':
            requirePermission('clinics', 'read');
            
            $clinic_id = (int)($_GET['id'] ?? 0);
            if (!$clinic_id) {
                jsonResponse(['success' => false, 'message' => 'معرف العيادة مطلوب'], 400);
            }
            
            $clinic_data = $clinic->getById($clinic_id);
            if (!$clinic_data) {
                jsonResponse(['success' => false, 'message' => 'العيادة غير موجودة'], 404);
            }
            
            jsonResponse(['success' => true, 'clinic' => $clinic_data]);
            break;
            
        case 'search':
            requirePermission('clinics', 'read');
            
            $query = sanitizeInput($_GET['q'] ?? '');
            $limit = min((int)($_GET['limit'] ?? 10), 50);
            
            $results = $clinic->search($query, $limit);
            jsonResponse(['success' => true, 'clinics' => $results]);
            break;
            
        case 'doctors':
            requirePermission('clinics', 'read');
            
            $clinic_id = (int)($_GET['clinic_id'] ?? 0);
            if (!$clinic_id) {
                jsonResponse(['success' => false, 'message' => 'معرف العيادة مطلوب'], 400);
            }
            
            $doctors = $clinic->getDoctors($clinic_id);
            jsonResponse(['success' => true, 'doctors' => $doctors]);
            break;
            
        case 'statistics':
            requirePermission('clinics', 'read');
            
            $clinic_id = (int)($_GET['clinic_id'] ?? 0);
            if (!$clinic_id) {
                jsonResponse(['success' => false, 'message' => 'معرف العيادة مطلوب'], 400);
            }
            
            $stats = $clinic->getStatistics($clinic_id);
            jsonResponse(['success' => true, 'statistics' => $stats]);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'عملية غير صحيحة'], 400);
    }
}

// If no valid action, redirect to clinics list
header('Location: /clinics/index.php');
exit;
?>
