<?php
/**
 * User Handler
 * Processes user-related requests
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Patient.php';
require_once __DIR__ . '/../classes/Doctor.php';

// Require authentication
requireAuth();

$user = new User();
$patient = new Patient();
$doctor = new Doctor();
$current_user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        requireCSRF();
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update':
                $user_id = (int)($_POST['user_id'] ?? 0);
                
                // Check if user can edit this profile
                if ($user_id !== $current_user['id'] && !hasPermission('users', 'update')) {
                    throw new Exception('لا تملك صلاحية تعديل هذا المستخدم');
                }
                
                if (!$user_id) {
                    throw new Exception('معرف المستخدم مطلوب');
                }
                
                $update_data = [];
                
                // Basic user info
                if (isset($_POST['name'])) {
                    $update_data['name'] = sanitizeInput($_POST['name']);
                }
                
                if (isset($_POST['phone'])) {
                    $phone = sanitizeInput($_POST['phone']);
                    if (!isValidPhone($phone)) {
                        throw new Exception('رقم الهاتف غير صحيح');
                    }
                    $update_data['phone'] = $phone;
                }
                
                if (isset($_POST['email']) && hasPermission('users', 'update')) {
                    $email = sanitizeInput($_POST['email']);
                    if (!isValidEmail($email)) {
                        throw new Exception('صيغة البريد الإلكتروني غير صحيحة');
                    }
                    
                    // Check if email is already taken
                    if ($user->emailExists($email, $user_id)) {
                        throw new Exception('البريد الإلكتروني مستخدم بالفعل');
                    }
                    
                    $update_data['email'] = $email;
                }
                
                if (isset($_POST['role']) && hasPermission('users', 'update')) {
                    $role = sanitizeInput($_POST['role']);
                    if (!array_key_exists($role, USER_ROLES)) {
                        throw new Exception('الدور المحدد غير صحيح');
                    }
                    $update_data['role'] = $role;
                }
                
                if (isset($_POST['status']) && hasPermission('users', 'update')) {
                    $update_data['status'] = sanitizeInput($_POST['status']);
                }
                
                // Update user
                if (!empty($update_data)) {
                    if ($user->update($user_id, $update_data)) {
                        // Log activity
                        logActivity($current_user['id'], 'user_updated', "تحديث مستخدم", 'users', $user_id);
                        $success_message = 'تم تحديث المستخدم بنجاح';
                    } else {
                        throw new Exception('فشل في تحديث المستخدم');
                    }
                }
                
                // Handle role-specific updates
                $user_data = $user->getById($user_id);
                if ($user_data) {
                    if ($user_data['role'] === 'patient') {
                        $patient_data = [];
                        
                        if (isset($_POST['medical_history'])) {
                            $patient_data['medical_history'] = sanitizeInput($_POST['medical_history']);
                        }
                        
                        if (isset($_POST['allergies'])) {
                            $patient_data['allergies'] = sanitizeInput($_POST['allergies']);
                        }
                        
                        if (isset($_POST['emergency_contact'])) {
                            $patient_data['emergency_contact'] = sanitizeInput($_POST['emergency_contact']);
                        }
                        
                        if (isset($_POST['emergency_phone'])) {
                            $patient_data['emergency_phone'] = sanitizeInput($_POST['emergency_phone']);
                        }
                        
                        if (isset($_POST['blood_type'])) {
                            $patient_data['blood_type'] = sanitizeInput($_POST['blood_type']);
                        }
                        
                        if (!empty($patient_data)) {
                            $patient_record = $patient->getByUserId($user_id);
                            if ($patient_record) {
                                $patient->update($patient_record['id'], $patient_data);
                            } else {
                                $patient_data['user_id'] = $user_id;
                                $patient->create($patient_data);
                            }
                        }
                        
                    } elseif ($user_data['role'] === 'doctor') {
                        $doctor_data = [];
                        
                        if (isset($_POST['specialization'])) {
                            $doctor_data['specialization'] = sanitizeInput($_POST['specialization']);
                        }
                        
                        if (isset($_POST['license_number'])) {
                            $doctor_data['license_number'] = sanitizeInput($_POST['license_number']);
                        }
                        
                        if (isset($_POST['experience_years'])) {
                            $doctor_data['experience_years'] = (int)$_POST['experience_years'];
                        }
                        
                        if (isset($_POST['education'])) {
                            $doctor_data['education'] = sanitizeInput($_POST['education']);
                        }
                        
                        if (isset($_POST['certifications'])) {
                            $doctor_data['certifications'] = sanitizeInput($_POST['certifications']);
                        }
                        
                        if (!empty($doctor_data)) {
                            $doctor_record = $doctor->getByUserId($user_id);
                            if ($doctor_record) {
                                $doctor->update($doctor_record['id'], $doctor_data);
                            } else {
                                $doctor_data['user_id'] = $user_id;
                                $doctor->create($doctor_data);
                            }
                        }
                    }
                }
                
                break;
                
            case 'change_password':
                $user_id = (int)($_POST['user_id'] ?? 0);
                
                // Check if user can change this password
                if ($user_id !== $current_user['id'] && !hasPermission('users', 'update')) {
                    throw new Exception('لا تملك صلاحية تغيير كلمة مرور هذا المستخدم');
                }
                
                if (!$user_id) {
                    throw new Exception('معرف المستخدم مطلوب');
                }
                
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                // Validate current password (for self-password change)
                if ($user_id === $current_user['id']) {
                    if (empty($current_password)) {
                        throw new Exception('كلمة المرور الحالية مطلوبة');
                    }
                    
                    $user_data = $user->getById($user_id);
                    if (!password_verify($current_password, $user_data['password'])) {
                        throw new Exception('كلمة المرور الحالية غير صحيحة');
                    }
                }
                
                if (empty($new_password) || empty($confirm_password)) {
                    throw new Exception('كلمة المرور الجديدة وتأكيدها مطلوبان');
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception('كلمة المرور الجديدة وتأكيدها غير متطابقتين');
                }
                
                // Validate password strength
                $password_errors = validatePasswordStrength($new_password);
                if (!empty($password_errors)) {
                    throw new Exception(implode(', ', $password_errors));
                }
                
                if ($user->updatePassword($user_id, $new_password)) {
                    // Log activity
                    logActivity($current_user['id'], 'password_changed', "تغيير كلمة المرور", 'users', $user_id);
                    $success_message = 'تم تغيير كلمة المرور بنجاح';
                } else {
                    throw new Exception('فشل في تغيير كلمة المرور');
                }
                break;
                
            case 'delete':
                requirePermission('users', 'delete');
                
                $user_id = (int)($_POST['user_id'] ?? 0);
                if (!$user_id) {
                    throw new Exception('معرف المستخدم مطلوب');
                }
                
                // Don't allow deleting self
                if ($user_id === $current_user['id']) {
                    throw new Exception('لا يمكنك حذف حسابك الخاص');
                }
                
                if ($user->delete($user_id)) {
                    // Log activity
                    logActivity($current_user['id'], 'user_deleted', "حذف مستخدم", 'users', $user_id);
                    $success_message = 'تم حذف المستخدم بنجاح';
                } else {
                    throw new Exception('فشل في حذف المستخدم');
                }
                break;
                
            case 'activate':
                requirePermission('users', 'update');
                
                $user_id = (int)($_POST['user_id'] ?? 0);
                if (!$user_id) {
                    throw new Exception('معرف المستخدم مطلوب');
                }
                
                if ($user->activate($user_id)) {
                    // Log activity
                    logActivity($current_user['id'], 'user_activated', "تفعيل مستخدم", 'users', $user_id);
                    $success_message = 'تم تفعيل المستخدم بنجاح';
                } else {
                    throw new Exception('فشل في تفعيل المستخدم');
                }
                break;
                
            case 'deactivate':
                requirePermission('users', 'update');
                
                $user_id = (int)($_POST['user_id'] ?? 0);
                if (!$user_id) {
                    throw new Exception('معرف المستخدم مطلوب');
                }
                
                // Don't allow deactivating self
                if ($user_id === $current_user['id']) {
                    throw new Exception('لا يمكنك إلغاء تفعيل حسابك الخاص');
                }
                
                if ($user->deactivate($user_id)) {
                    // Log activity
                    logActivity($current_user['id'], 'user_deactivated', "إلغاء تفعيل مستخدم", 'users', $user_id);
                    $success_message = 'تم إلغاء تفعيل المستخدم بنجاح';
                } else {
                    throw new Exception('فشل في إلغاء تفعيل المستخدم');
                }
                break;
                
            default:
                throw new Exception('عملية غير صحيحة');
        }
        
        // Handle AJAX requests
        if (isAjaxRequest()) {
            $response = ['success' => true, 'message' => $success_message];
            
            // Include updated user data for certain actions
            if (in_array($action, ['update', 'activate', 'deactivate']) && isset($user_id)) {
                $response['user'] = $user->getProfile($user_id);
            }
            
            jsonResponse($response);
        }
        
        // Regular form submission
        $redirect_url = $_POST['redirect'] ?? '/admin/user_management.php';
        redirectWithMessage($redirect_url, $success_message);
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("User operation failed: " . $error_message);
        
        // Handle AJAX requests
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $error_message], 400);
        }
        
        // Regular form submission
        setErrorMessage($error_message);
        $redirect_url = $_POST['redirect'] ?? '/admin/user_management.php';
        header('Location: ' . $redirect_url);
        exit;
    }
}

// Handle GET requests for user data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get':
            $user_id = (int)($_GET['id'] ?? 0);
            
            // Check if user can view this profile
            if ($user_id !== $current_user['id'] && !hasPermission('users', 'read')) {
                jsonResponse(['success' => false, 'message' => 'لا تملك صلاحية عرض هذا المستخدم'], 403);
            }
            
            if (!$user_id) {
                jsonResponse(['success' => false, 'message' => 'معرف المستخدم مطلوب'], 400);
            }
            
            $user_data = $user->getProfile($user_id);
            if (!$user_data) {
                jsonResponse(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
            }
            
            // Add role-specific data
            if ($user_data['role'] === 'patient') {
                $patient_data = $patient->getByUserId($user_id);
                $user_data['patient_info'] = $patient_data;
            } elseif ($user_data['role'] === 'doctor') {
                $doctor_data = $doctor->getByUserId($user_id);
                $user_data['doctor_info'] = $doctor_data;
            }
            
            jsonResponse(['success' => true, 'user' => $user_data]);
            break;
            
        case 'search':
            requirePermission('users', 'read');
            
            $query = sanitizeInput($_GET['q'] ?? '');
            $role = sanitizeInput($_GET['role'] ?? '');
            $limit = min((int)($_GET['limit'] ?? 10), 50);
            
            $filters = [];
            if ($role) {
                $filters['role'] = $role;
            }
            if ($query) {
                $filters['search'] = $query;
            }
            
            $result = $user->getAll(1, $limit, $filters);
            jsonResponse(['success' => true, 'users' => $result['users']]);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'عملية غير صحيحة'], 400);
    }
}

// If no valid action, redirect to user management
header('Location: /admin/user_management.php');
exit;
?>
