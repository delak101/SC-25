<?php
/**
 * User Login Page
 * Handles user authentication
 */

$page_title = 'تسجيل الدخول';
$hide_sidebar = true;

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already authenticated
if (isAuthenticated()) {
    header('Location: /dashboard.php');
    exit;
}

$error_message = getErrorMessage();
$success_message = getSuccessMessage();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="/css/normalize.css">
    <link rel="stylesheet" href="/css/style.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            min-height: 500px;
        }
        
        .login-left {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            text-align: center;
        }
        
        .login-right {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .logo-container {
            margin-bottom: 2rem;
        }
        
        .logo-container img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .feature-item i {
            font-size: 1.5rem;
            margin-left: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        @media (max-width: 768px) {
            .login-left {
                order: 2;
                min-height: 300px;
            }
            
            .login-right {
                order: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="login-container">
                    <div class="row h-100">
                        <!-- Left Side - Branding -->
                        <div class="col-md-6 login-left">
                            <div class="logo-container">
                                <img src="/images/logo.png" alt="<?php echo APP_NAME; ?>" 
                                     onerror="this.src='https://via.placeholder.com/120x120/007bff/ffffff?text=SC'">
                                <h2><?php echo APP_NAME; ?></h2>
                                <p class="mb-4">نظام إدارة طبي متكامل للصم وضعاف السمع</p>
                            </div>
                            
                        </div>
                        
                        <!-- Right Side - Login Form -->
                        <div class="col-md-6 login-right">
                            <div class="text-center mb-4">
                                <h3 class="text-primary">تسجيل الدخول</h3>
                                <p class="text-muted">أدخل بياناتك للوصول إلى النظام</p>
                            </div>
                            
                            <!-- Alert Messages -->
                            <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo sanitizeInput($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo sanitizeInput($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="/handlers/login.php" id="loginForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>البريد الإلكتروني
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           required placeholder="أدخل بريدك الإلكتروني">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>كلمة المرور
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               required placeholder="أدخل كلمة المرور">
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="togglePassword()" id="toggleBtn">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="remember_me" name="remember_me">
                                            <label class="form-check-label" for="remember_me">
                                                تذكرني
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6 text-end">
                                        <a href="/forgetbs.php" class="text-decoration-none">
                                            نسيت كلمة المرور؟
                                        </a>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    تسجيل الدخول
                                </button>
                                
                            </form>
                            
                            <!-- Demo Accounts -->
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6 class="text-muted mb-2">حسابات تجريبية:</h6>
                                <div class="row small">
                                    <div class="col-6">
                                        <strong>مدير النظام:</strong><br>
                                        <code>admin@silentconnect.com</code><br>
                                        <code>Admin123!</code>
                                    </div>
                                    <div class="col-6">
                                        <strong>أو إنشاء حساب جديد</strong><br>
                                        <small class="text-muted">للأطباء والمرضى</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('يرجى إدخال جميع البيانات المطلوبة');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جارٍ تسجيل الدخول...';
            submitBtn.disabled = true;
            
            // Re-enable button after 5 seconds (in case of network issues)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
