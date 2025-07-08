<?php
/**
 * Common Functions
 * Utility functions used throughout the application
 */

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Egyptian format)
 */
function isValidPhone($phone) {
    // Egyptian phone number patterns
    $patterns = [
        '/^01[0125][0-9]{8}$/',     // Mobile
        '/^0[2-9][0-9]{7,8}$/',    // Landline
        '/^\+201[0125][0-9]{8}$/'  // International mobile
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $phone)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Format date for Arabic display
 */
function formatArabicDate($date, $format = 'Y-m-d') {
    if (!$date) return '';
    
    $timestamp = is_string($date) ? strtotime($date) : $date;
    return date($format, $timestamp);
}

/**
 * Format time for Arabic display
 */
function formatArabicTime($time) {
    if (!$time) return '';
    
    $timestamp = is_string($time) ? strtotime($time) : $time;
    return date('h:i A', $timestamp);
}

/**
 * Get Arabic day name
 */
function getArabicDayName($day_number) {
    $days = [
        0 => 'الأحد',
        1 => 'الإثنين', 
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
        6 => 'السبت'
    ];
    
    return $days[$day_number] ?? '';
}

/**
 * Get Arabic month name
 */
function getArabicMonthName($month_number) {
    $months = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
    ];
    
    return $months[(int)$month_number] ?? '';
}

/**
 * Create pagination links
 */
function createPagination($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav aria-label="صفحات"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_params = array_merge($params, ['page' => $current_page - 1]);
        $prev_url = $base_url . '?' . http_build_query($prev_params);
        $html .= '<li class="page-item"><a class="page-link" href="' . $prev_url . '">السابق</a></li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $page_params = array_merge($params, ['page' => $i]);
        $page_url = $base_url . '?' . http_build_query($page_params);
        $active = ($i == $current_page) ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $page_url . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_params = array_merge($params, ['page' => $current_page + 1]);
        $next_url = $base_url . '?' . http_build_query($next_params);
        $html .= '<li class="page-item"><a class="page-link" href="' . $next_url . '">التالي</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Upload file with validation
 */
function uploadFile($file, $allowed_types, $max_size, $upload_dir) {
    try {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception("لم يتم رفع الملف بشكل صحيح");
        }
        
        // Validate file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception("نوع الملف غير مدعوم");
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            throw new Exception("حجم الملف كبير جداً");
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $full_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $full_path)) {
            throw new Exception("فشل في حفظ الملف");
        }
        
        return [
            'filename' => $filename,
            'path' => $full_path,
            'size' => $file['size']
        ];
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Delete file safely
 */
function deleteFile($file_path) {
    if ($file_path && file_exists($file_path)) {
        return unlink($file_path);
    }
    return true;
}

/**
 * Generate breadcrumb navigation
 */
function generateBreadcrumb($items) {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    $count = count($items);
    foreach ($items as $index => $item) {
        if ($index == $count - 1) {
            // Last item (current page)
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . sanitizeInput($item['title']) . '</li>';
        } else {
            // Previous items with links
            $html .= '<li class="breadcrumb-item"><a href="' . sanitizeInput($item['url']) . '">' . sanitizeInput($item['title']) . '</a></li>';
        }
    }
    
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Log activity for audit trail
 */
function logActivity($user_id, $action, $details = '', $table_name = '', $record_id = null) {
    try {
        $db = Database::getInstance();
        
        $sql = "INSERT INTO activity_logs (user_id, action, details, table_name, record_id, 
                                         ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        
        $db->prepare($sql);
        $db->execute([
            $user_id,
            $action,
            $details,
            $table_name,
            $record_id,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Log activity failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email notification
 */
function sendEmail($to, $subject, $message, $from = null) {
    // This is a basic implementation
    // In production, you should use a proper email service like PHPMailer
    
    $from = $from ?: 'noreply@silentconnect.com';
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $from,
        'Content-Type: text/html; charset=UTF-8',
        'MIME-Version: 1.0'
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Generate QR code for appointments
 */
function generateQRCode($data, $size = 200) {
    // This would integrate with a QR code library
    // For now, return a placeholder URL
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Return JSON response
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    if ($type === 'success') {
        setSuccessMessage($message);
    } else {
        setErrorMessage($message);
    }
    
    header('Location: ' . $url);
    exit;
}

/**
 * Get file size in human readable format
 */
function formatFileSize($bytes) {
    $units = ['بايت', 'كيلوبايت', 'ميجابايت', 'جيجابايت'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Convert Arabic numerals to English
 */
function arabicToEnglishNumbers($string) {
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    return str_replace($arabic, $english, $string);
}

/**
 * Convert English numerals to Arabic
 */
function englishToArabicNumbers($string) {
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    
    return str_replace($english, $arabic, $string);
}

/**
 * Get user avatar URL
 */
function getUserAvatar($user_id, $size = 100) {
    // Check if user has uploaded avatar
    $avatar_path = "uploads/avatars/{$user_id}.jpg";
    if (file_exists($avatar_path)) {
        return $avatar_path;
    }
    
    // Return default avatar or gravatar
    return "https://via.placeholder.com/{$size}x{$size}/007bff/ffffff?text=المستخدم";
}

/**
 * Encrypt sensitive data
 */
function encryptData($data) {
    $key = ENCRYPTION_KEY;
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt sensitive data
 */
function decryptData($encrypted_data) {
    $key = ENCRYPTION_KEY;
    $data = base64_decode($encrypted_data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "كلمة المرور يجب أن تكون " . PASSWORD_MIN_LENGTH . " أحرف على الأقل";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "كلمة المرور يجب أن تحتوي على حرف كبير واحد على الأقل";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "كلمة المرور يجب أن تحتوي على حرف صغير واحد على الأقل";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "كلمة المرور يجب أن تحتوي على رقم واحد على الأقل";
    }
    
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "كلمة المرور يجب أن تحتوي على رمز خاص واحد على الأقل";
    }
    
    return $errors;
}
?>
