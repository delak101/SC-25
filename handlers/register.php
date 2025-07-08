<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireCSRF();
        
        // Handle file uploads
        $national_id_image = handleFileUpload('national_id_image', 'national_ids');
        $service_card_image = handleFileUpload('service_card_image', 'service_cards', false);

        // Prepare user data
        $user_data = [
            'name' => sanitizeInput($_POST['name']),
            'email' => sanitizeInput($_POST['email']),
            'password' => $_POST['password'],
            'phone' => sanitizeInput($_POST['phone']),
            'national_id' => sanitizeInput($_POST['national_id']),
            'national_id_image' => $national_id_image,
            'gender' => sanitizeInput($_POST['gender']),
            'hearing_status' => sanitizeInput($_POST['hearing_status']),
            'marital_status' => sanitizeInput($_POST['marital_status'] ?? null),
            'sign_language_level' => sanitizeInput($_POST['sign_language_level'] ?? null),
            'governorate' => sanitizeInput($_POST['governorate']),
            'age' => (int)$_POST['age'],
            'job' => sanitizeInput($_POST['job'] ?? null),
            'service_card_image' => $service_card_image,
            'medical_history' => sanitizeInput($_POST['medical_history'] ?? null),
            'allergies' => sanitizeInput($_POST['allergies'] ?? null),
            'emergency_contact' => sanitizeInput($_POST['emergency_contact'] ?? null),
            'emergency_phone' => sanitizeInput($_POST['emergency_phone'] ?? null),
            'blood_type' => sanitizeInput($_POST['blood_type'] ?? null),
            'role' => 'patient'
        ];

        // Register user
        $auth = new Auth();
        $user_id = $auth->register($user_data);

        // Assign patient role
        $role_record = $rbac->getRoleByName('patient');
        if ($role_record) {
            $rbac->assignRoleToUser($user_id, $role_record['id']);
        }

        // Send welcome email
        $welcome_message = "مرحباً بك في نظام Silent Connect...";
        sendEmail($user_data['email'], 'مرحباً بك', $welcome_message);

        redirectWithMessage('/login.php', 'تم إنشاء الحساب بنجاح. يمكنك الآن تسجيل الدخول');

    } catch (Exception $e) {
        setErrorMessage($e->getMessage());
        header('Location: /register.php');
        exit;
    }
}

function handleFileUpload($field, $dir, $required = true) {
    if ($required && (!isset($_FILES[$field]) || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE)){
        if ($required) throw new Exception("حقل $field مطلوب");
        return null;
    }

    if (isset($_FILES[$field])) {
        $file = $_FILES[$field];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("حدث خطأ أثناء رفع الملف");
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception("نوع الملف غير مسموح به. يسمح فقط بصور JPEG, PNG, GIF");
        }

        $max_size = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $max_size) {
            throw new Exception("حجم الملف كبير جداً. الحد الأقصى 2MB");
        }

        $upload_path = __DIR__ . "/../uploads/$dir";
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = "$upload_path/$filename";

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("فشل في حفظ الملف");
        }

        return "$dir/$filename";
    }
    return null;
}

header('Location: /register.php');
exit;

