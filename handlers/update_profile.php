<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'])) {
    setErrorMessage('رمز الحماية غير صالح. يرجى إعادة المحاولة.');
    header('Location: /profile.php');
    exit;
}

// Require authentication
requireAuth();
$current_user = getCurrentUser();

// Validate input
$required_fields = ['name', 'email', 'phone', 'national_id', 'gender', 'hearing_status', 'governorate', 'age'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        setErrorMessage('جميع الحقول المطلوبة يجب ملؤها.');
        header('Location: /profile.php');
        exit;
    }
}

// Prepare data
$data = [
    'name' => sanitizeInput($_POST['name']),
    'email' => sanitizeInput($_POST['email']),
    'phone' => sanitizeInput($_POST['phone']),
    'national_id' => sanitizeInput($_POST['national_id']),
    'gender' => sanitizeInput($_POST['gender']),
    'hearing_status' => sanitizeInput($_POST['hearing_status']),
    'marital_status' => sanitizeInput($_POST['marital_status'] ?? null),
    'sign_language_level' => sanitizeInput($_POST['sign_language_level'] ?? null),
    'governorate' => sanitizeInput($_POST['governorate']),
    'age' => intval($_POST['age']),
    'job' => sanitizeInput($_POST['job'] ?? null),
];

// Handle password change if provided
if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
    if (!password_verify($_POST['current_password'], $current_user['password'])) {
        setErrorMessage('كلمة المرور الحالية غير صحيحة.');
        header('Location: /profile.php');
        exit;
    }
    
    if ($_POST['new_password'] !== $_POST['confirm_new_password']) {
        setErrorMessage('كلمات المرور الجديدة غير متطابقة.');
        header('Location: /profile.php');
        exit;
    }
    
    $data['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
}

// Handle file uploads
$upload_dir = __DIR__ . '/../uploads/';
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

// Profile image
if (!empty($_FILES['profile_image']['name'])) {
    $file_info = $_FILES['profile_image'];
    if (in_array($file_info['type'], $allowed_types)) {
        $ext = pathinfo($file_info['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $current_user['id'] . '_' . time() . '.' . $ext;
        $destination = $upload_dir . 'profiles/' . $filename;
        
        if (move_uploaded_file($file_info['tmp_name'], $destination)) {
            // Delete old profile image if exists
            if (!empty($current_user['profile_image'])) {
                @unlink($upload_dir . 'profiles/' . $current_user['profile_image']);
            }
            $data['profile_image'] = $filename;
        }
    }
}

// National ID image
if (!empty($_FILES['national_id_image']['name'])) {
    $file_info = $_FILES['national_id_image'];
    if (in_array($file_info['type'], $allowed_types)) {
        $ext = pathinfo($file_info['name'], PATHINFO_EXTENSION);
        $filename = 'national_id_' . $current_user['id'] . '_' . time() . '.' . $ext;
        $destination = $upload_dir . 'documents/' . $filename;
        
        if (move_uploaded_file($file_info['tmp_name'], $destination)) {
            // Delete old national ID image if exists
            if (!empty($current_user['national_id_image'])) {
                @unlink($upload_dir . 'documents/' . $current_user['national_id_image']);
            }
            $data['national_id_image'] = $filename;
        }
    }
}

// Service card image
if (!empty($_FILES['service_card_image']['name'])) {
    $file_info = $_FILES['service_card_image'];
    if (in_array($file_info['type'], $allowed_types)) {
        $ext = pathinfo($file_info['name'], PATHINFO_EXTENSION);
        $filename = 'service_card_' . $current_user['id'] . '_' . time() . '.' . $ext;
        $destination = $upload_dir . 'documents/' . $filename;
        
        if (move_uploaded_file($file_info['tmp_name'], $destination)) {
            // Delete old service card image if exists
            if (!empty($current_user['service_card_image'])) {
                @unlink($upload_dir . 'documents/' . $current_user['service_card_image']);
            }
            $data['service_card_image'] = $filename;
        }
    }
}

// Update user in database
try {
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE users SET 
        name = :name,
        email = :email,
        phone = :phone,
        national_id = :national_id,
        gender = :gender,
        hearing_status = :hearing_status,
        marital_status = :marital_status,
        sign_language_level = :sign_language_level,
        governorate = :governorate,
        age = :age,
        job = :job,
        profile_image = COALESCE(:profile_image, profile_image),
        national_id_image = COALESCE(:national_id_image, national_id_image),
        service_card_image = COALESCE(:service_card_image, service_card_image),
        password = COALESCE(:password, password),
        updated_at = NOW()
        WHERE id = :id");
    
    $params = [
        ':name' => $data['name'],
        ':email' => $data['email'],
        ':phone' => $data['phone'],
        ':national_id' => $data['national_id'],
        ':gender' => $data['gender'],
        ':hearing_status' => $data['hearing_status'],
        ':marital_status' => $data['marital_status'],
        ':sign_language_level' => $data['sign_language_level'],
        ':governorate' => $data['governorate'],
        ':age' => $data['age'],
        ':job' => $data['job'],
        ':profile_image' => $data['profile_image'] ?? null,
        ':national_id_image' => $data['national_id_image'] ?? null,
        ':service_card_image' => $data['service_card_image'] ?? null,
        ':password' => $data['password'] ?? null,
        ':id' => $current_user['id']
    ];
    
    $stmt->execute($params);
    
    setSuccessMessage('تم تحديث الملف الشخصي بنجاح.');
    header('Location: /profile.php');
    exit;
} catch (PDOException $e) {
    setErrorMessage('حدث خطأ أثناء تحديث الملف الشخصي. يرجى المحاولة مرة أخرى.');
    error_log('Profile update error: ' . $e->getMessage());
    header('Location: /profile.php');
    exit;
}