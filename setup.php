<?php
/**
 * Database Setup and Management Page
 * Allows switching between MySQL and SQLite, initializing database
 */

$page_title = 'إعداد قاعدة البيانات';
$hide_sidebar = true;

// Handle database operations
$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'init_sqlite':
                // Remove existing SQLite database
                if (file_exists(__DIR__ . '/database/silent_connect.db')) {
                    unlink(__DIR__ . '/database/silent_connect.db');
                }
                
                // Set environment for SQLite
                $_ENV['DB_TYPE'] = 'sqlite';
                
                // Include config to initialize database
                require_once __DIR__ . '/config/config.php';
                
                $message = 'تم إنشاء قاعدة بيانات SQLite بنجاح مع المستخدم الافتراضي';
                $message_type = 'success';
                break;
                
            case 'init_mysql':
                // Set environment for MySQL
                $_ENV['DB_TYPE'] = 'mysql';
                $_ENV['DB_HOST'] = $_POST['db_host'] ?? 'localhost';
                $_ENV['DB_NAME'] = $_POST['db_name'] ?? 'silent_connect';
                $_ENV['DB_USER'] = $_POST['db_user'] ?? 'root';
                $_ENV['DB_PASS'] = $_POST['db_pass'] ?? '';
                $_ENV['DB_PORT'] = $_POST['db_port'] ?? '3306';
                
                // Include config to initialize database
                require_once __DIR__ . '/config/config.php';
                
                $message = 'تم إنشاء قاعدة بيانات MySQL بنجاح مع المستخدم الافتراضي';
                $message_type = 'success';
                break;
                
            case 'create_user':
                require_once __DIR__ . '/config/config.php';
                
                $name = $_POST['user_name'] ?? '';
                $email = $_POST['user_email'] ?? '';
                $password = $_POST['user_password'] ?? '';
                $role = $_POST['user_role'] ?? 'admin';
                
                if ($name && $email && $password) {
                    $db = Database::getInstance();
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare('INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $email, $password_hash, $role, 'active']);
                    
                    $message = 'تم إنشاء المستخدم بنجاح';
                    $message_type = 'success';
                } else {
                    $message = 'جميع الحقول مطلوبة';
                    $message_type = 'error';
                }
                break;
                
            case 'test_login':
                require_once __DIR__ . '/config/config.php';
                
                $email = $_POST['test_email'] ?? '';
                $password = $_POST['test_password'] ?? '';
                
                if ($email && $password) {
                    $auth = new Auth();
                    
                    if ($auth->login($email, $password)) {
                        $message = 'تم تسجيل الدخول بنجاح! يمكنك الآن الانتقال إلى لوحة التحكم';
                        $message_type = 'success';
                    } else {
                        $message = 'فشل في تسجيل الدخول - تحقق من البيانات';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'أدخل البريد الإلكتروني وكلمة المرور';
                    $message_type = 'error';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'خطأ: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Check current database status
$db_status = 'غير متصل';
$db_type = 'غير محدد';
$users_count = 0;

try {
    if (file_exists(__DIR__ . '/config/config.php')) {
        require_once __DIR__ . '/config/config.php';
        $db = Database::getInstance();
        
        if ($db->tableExists('users')) {
            $db_status = 'متصل';
            $db_type = DB_TYPE === 'sqlite' ? 'SQLite' : 'MySQL';
            
            $stmt = $db->prepare('SELECT COUNT(*) as count FROM users');
            $stmt->execute();
            $result = $stmt->fetch();
            $users_count = $result['count'];
        }
    }
} catch (Exception $e) {
    $db_status = 'خطأ: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Silent Connect</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .setup-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .setup-header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .setup-body {
            padding: 2rem;
        }
        
        .status-card {
            border: none;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }
        
        .btn-setup {
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: bold;
            margin: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <!-- Header -->
            <div class="setup-header">
                <h2><i class="fas fa-database me-2"></i><?php echo $page_title; ?></h2>
                <p class="mb-0">إعداد وإدارة قاعدة البيانات لنظام Silent Connect</p>
            </div>
            
            <!-- Body -->
            <div class="setup-body">
                <!-- Message -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : ($message_type === 'error' ? 'danger' : 'info'); ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'error' ? 'exclamation-circle' : 'info-circle'); ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Database Status -->
                <div class="card status-card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-server me-2"></i>حالة قاعدة البيانات</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>الحالة:</strong> 
                                <span class="badge bg-<?php echo $db_status === 'متصل' ? 'success' : 'danger'; ?>">
                                    <?php echo $db_status; ?>
                                </span>
                            </div>
                            <div class="col-md-4">
                                <strong>النوع:</strong> <?php echo $db_type; ?>
                            </div>
                            <div class="col-md-4">
                                <strong>عدد المستخدمين:</strong> <?php echo $users_count; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Database Setup Options -->
                <div class="row">
                    <!-- SQLite Setup -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5><i class="fas fa-database me-2"></i>إعداد SQLite</h5>
                            </div>
                            <div class="card-body">
                                <p>قاعدة بيانات محلية سريعة وسهلة للتطوير والاختبار</p>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="init_sqlite">
                                    <button type="submit" class="btn btn-success btn-setup w-100">
                                        <i class="fas fa-play me-2"></i>إنشاء قاعدة بيانات SQLite
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- MySQL Setup -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">
                                <h5><i class="fas fa-server me-2"></i>إعداد MySQL</h5>
                            </div>
                            <div class="card-body">
                                <p>قاعدة بيانات احترافية للإنتاج</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="init_mysql">
                                    <div class="row">
                                        <div class="col-6 mb-2">
                                            <input type="text" class="form-control form-control-sm" name="db_host" placeholder="المضيف" value="localhost">
                                        </div>
                                        <div class="col-6 mb-2">
                                            <input type="text" class="form-control form-control-sm" name="db_port" placeholder="المنفذ" value="3306">
                                        </div>
                                        <div class="col-6 mb-2">
                                            <input type="text" class="form-control form-control-sm" name="db_name" placeholder="اسم القاعدة" value="silent_connect">
                                        </div>
                                        <div class="col-6 mb-2">
                                            <input type="text" class="form-control form-control-sm" name="db_user" placeholder="المستخدم" value="root">
                                        </div>
                                        <div class="col-12 mb-2">
                                            <input type="password" class="form-control form-control-sm" name="db_pass" placeholder="كلمة المرور">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-info btn-setup w-100">
                                        <i class="fas fa-play me-2"></i>إنشاء قاعدة بيانات MySQL
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Management -->
                <div class="card mt-4">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-user-plus me-2"></i>إنشاء مستخدم جديد</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create_user">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="user_name" placeholder="الاسم الكامل" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="email" class="form-control" name="user_email" placeholder="البريد الإلكتروني" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="password" class="form-control" name="user_password" placeholder="كلمة المرور" required>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="user_role">
                                        <option value="admin">مدير</option>
                                        <option value="doctor">دكتور</option>
                                        <option value="patient">مريض</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Test Login -->
                <div class="card mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5><i class="fas fa-sign-in-alt me-2"></i>اختبار تسجيل الدخول</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="test_login">
                            <div class="row">
                                <div class="col-md-5">
                                    <input type="email" class="form-control" name="test_email" placeholder="البريد الإلكتروني" value="admin@silentconnect.com">
                                </div>
                                <div class="col-md-5">
                                    <input type="password" class="form-control" name="test_password" placeholder="كلمة المرور" value="Admin123!">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-secondary w-100">
                                        <i class="fas fa-sign-in-alt me-2"></i>اختبار
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="text-center mt-4">
                    <a href="/login.php" class="btn btn-primary btn-setup">
                        <i class="fas fa-arrow-right me-2"></i>الانتقال لصفحة تسجيل الدخول
                    </a>
                    <a href="/dashboard.php" class="btn btn-success btn-setup">
                        <i class="fas fa-tachometer-alt me-2"></i>لوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>