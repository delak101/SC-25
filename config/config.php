<?php
/**
 * Application Configuration for DigitalOcean Deployment
 */

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Error Reporting - Different for production
if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Timezone
date_default_timezone_set('Africa/Cairo');

// Application Settings
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Silent Connect');
define('APP_VERSION', '2.4.4');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://104.236.102.224');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// Security Settings
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 3600 * 8)); // 8 hours
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', (int)($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5));
define('LOGIN_LOCKOUT_TIME', (int)($_ENV['LOGIN_LOCKOUT_TIME'] ?? 300)); // 5 minutes

// File Upload Settings
define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024); // 50MB
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? __DIR__ . '/../uploads/');
define('ALLOWED_VIDEO_TYPES', ['mp4', 'avi', 'mov', 'wmv', 'flv']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);

// Default Admin User
define('DEFAULT_ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@silentconnect.com');
define('DEFAULT_ADMIN_PASSWORD', $_ENV['ADMIN_PASSWORD'] ?? 'Admin123!');

// Encryption Key for sensitive data
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'SilentConnect2024SecureKey' . uniqid());

// Available Roles
define('USER_ROLES', [
    'admin' => 'مدير النظام',
    'doctor' => 'دكتور',
    'patient' => 'مريض',
    'secretary' => 'سكرتارية',
    'pharmacy' => 'صيدلي',
    'reception' => 'استقبال'
]);

// Available Features for RBAC
define('SYSTEM_FEATURES', [
    'users' => 'إدارة المستخدمين',
    'clinics' => 'إدارة العيادات',
    'appointments' => 'إدارة المواعيد',
    'videos' => 'إدارة الفيديوهات',
    'patients' => 'إدارة المرضى',
    'doctors' => 'إدارة الأطباء',
    'pharmacy' => 'إدارة الصيدلية',
    'reception' => 'إدارة الاستقبال',
    'medical_terms' => 'المصطلحات الطبية',
    'reports' => 'التقارير',
    'settings' => 'إعدادات النظام',
    'rbac' => 'إدارة الصلاحيات'
]);

// Available Actions for RBAC
define('SYSTEM_ACTIONS', [
    'create' => 'إنشاء',
    'read' => 'عرض',
    'update' => 'تعديل',
    'delete' => 'حذف',
    'manage' => 'إدارة'
]);

// Ensure required directories exist
$required_dirs = [
    UPLOAD_PATH,
    __DIR__ . '/../logs',
    __DIR__ . '/../uploads/national_ids',
    __DIR__ . '/../uploads/service_cards',
    __DIR__ . '/../uploads/videos',
    __DIR__ . '/../uploads/profiles'
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $class_file = __DIR__ . '/../classes/' . $class_name . '.php';
    if (file_exists($class_file)) {
        require_once $class_file;
    }
});

// Initialize database if needed (only in setup mode)
if (($_GET['setup'] ?? false) || !file_exists(__DIR__ . '/.installed')) {
    try {
        $db = Database::getInstance();
        
        // Check if tables exist, if not initialize them
        if (!$db->tableExists('users')) {
            $db->initializeTables();
            
            // Create default admin user
            $hashedPassword = password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, 'admin', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute(['مدير النظام', DEFAULT_ADMIN_EMAIL, $hashedPassword]);
            
            // Mark as installed
            file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
            error_log("Database initialized successfully for production");
        }
    } catch (Exception $e) {
        error_log("Database initialization error: " . $e->getMessage());
    }
}
?>