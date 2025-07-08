<?php
/**
 * User Registration Page
 * Handles new user registration with required fields
 */

$page_title = 'إنشاء حساب جديد';
$hide_sidebar = true;

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

// Check if registration is allowed for non-authenticated users
$allow_self_registration = true;
$is_admin_creating = isAuthenticated() && hasPermission('users', 'create');

// If user is authenticated and doesn't have permission to create users, redirect
if (isAuthenticated() && !$is_admin_creating) {
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
            padding: 2rem 0;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .register-header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .register-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .btn-register {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }
        
        .strength-meter {
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 3px;
        }
        
        .file-upload {
            position: relative;
            overflow: hidden;
        }
        
        .file-upload-input {
            position: absolute;
            font-size: 50px;
            opacity: 0;
            right: 0;
            top: 0;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: block;
            padding: 10px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
        }
        
        .file-upload-label:hover {
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="register-container">
                    <!-- Header -->
                    <div class="register-header">
                        <h2><i class="fas fa-user-plus me-2"></i><?php echo $page_title; ?></h2>
                        <p class="mb-0">انضم إلى نظام <?php echo APP_NAME; ?></p>
                    </div>
                    
                    <!-- Body -->
                    <div class="register-body">
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
                        
                        <form method="POST" action="/api/v1/auth/register" id="registerForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <!-- Basic Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary border-bottom pb-2">
                                        <i class="fas fa-info-circle me-2"></i>المعلومات الأساسية
                                    </h5>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                           placeholder="أدخل اسمك الكامل">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required 
                                           placeholder="example@email.com">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required 
                                           placeholder="01xxxxxxxxx" pattern="[0-9+\-\s()]+">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="national_id" class="form-label">الرقم القومي <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="national_id" name="national_id" required 
                                           placeholder="أدخل الرقم القومي">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="gender" class="form-label">النوع <span class="text-danger">*</span></label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">اختر النوع</option>
                                        <option value="male">ذكر</option>
                                        <option value="female">أنثى</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="hearing_status" class="form-label">الحالة <span class="text-danger">*</span></label>
                                    <select class="form-select" id="hearing_status" name="hearing_status" required>
                                        <option value="">اختر الحالة</option>
                                        <option value="deaf">أصم</option>
                                        <option value="hard_of_hearing">ضعيف السمع</option>
                                        <option value="hearing">سليم</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="marital_status" class="form-label">الحالة الاجتماعية</label>
                                    <select class="form-select" id="marital_status" name="marital_status">
                                        <option value="">اختر الحالة الاجتماعية</option>
                                        <option value="single">أعزب</option>
                                        <option value="married">متزوج</option>
                                        <option value="divorced">مطلق</option>
                                        <option value="widowed">أرمل</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="sign_language_level" class="form-label">مستوى تعلم لغة الإشارة</label>
                                    <select class="form-select" id="sign_language_level" name="sign_language_level">
                                        <option value="">اختر المستوى</option>
                                        <option value="beginner">مبتدئ</option>
                                        <option value="intermediate">متوسط</option>
                                        <option value="advanced">متقدم</option>
                                        <option value="none">لم يدرس</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="governorate" class="form-label">المحافظة <span class="text-danger">*</span></label>
                                    <select class="form-select" id="governorate" name="governorate" required>
                                        <option value="">اختر المحافظة</option>
                                        <option value="cairo">القاهرة</option>
                                        <option value="alexandria">الإسكندرية</option>
                                        <option value="giza">الجيزة</option>
                                        <!-- Add more governorates as needed -->
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="age" class="form-label">السن <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="age" name="age" required 
                                           min="10" max="100" placeholder="أدخل السن">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="job" class="form-label">الوظيفة</label>
                                    <input type="text" class="form-control" id="job" name="job" 
                                           placeholder="أدخل الوظيفة">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="national_id_image" class="form-label">صورة البطاقة القومية <span class="text-danger">*</span></label>
                                    <div class="file-upload">
                                        <input type="file" class="file-upload-input" id="national_id_image" name="national_id_image" accept="image/*" required>
                                        <label for="national_id_image" class="file-upload-label" id="national_id_image_label">
                                            <i class="fas fa-upload me-2"></i>
                                            <span>اختر ملف البطاقة القومية</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="service_card_image" class="form-label">صورة بطاقة الخدمات</label>
                                    <div class="file-upload">
                                        <input type="file" class="file-upload-input" id="service_card_image" name="service_card_image" accept="image/*">
                                        <label for="service_card_image" class="file-upload-label" id="service_card_image_label">
                                            <i class="fas fa-upload me-2"></i>
                                            <span>اختر ملف بطاقة الخدمات</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Password Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary border-bottom pb-2">
                                        <i class="fas fa-lock me-2"></i>كلمة المرور
                                    </h5>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="password" class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               required placeholder="كلمة مرور قوية" 
                                               onkeyup="checkPasswordStrength()">
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="strength-meter">
                                        <div class="strength-bar" id="strengthBar"></div>
                                    </div>
                                    <small id="strengthText" class="text-muted">قوة كلمة المرور</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" required placeholder="أعد كتابة كلمة المرور"
                                               onkeyup="checkPasswordMatch()">
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small id="matchText" class="text-muted"></small>
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                        <label class="form-check-label" for="terms">
                                            أوافق على <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">الشروط والأحكام</a> 
                                            و<a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">سياسة الخصوصية</a>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
                                        <i class="fas fa-user-plus me-2"></i>
                                        إنشاء الحساب
                                    </button>
                                    
                                    <div class="text-center">
                                        <span class="text-muted">لديك حساب بالفعل؟</span>
                                        <a href="/login.php" class="text-decoration-none ms-1">
                                            تسجيل الدخول
                                        </a>
                                    </div>
                                    
                                    <?php if ($is_admin_creating): ?>
                                    <div class="text-center mt-2">
                                        <a href="/admin/user_management.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>العودة لإدارة المستخدمين
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">الشروط والأحكام</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. قبول الشروط</h6>
                    <p>بإنشاء حساب في نظام <?php echo APP_NAME; ?>، فإنك توافق على الالتزام بهذه الشروط والأحكام.</p>
                    
                    <h6>2. استخدام النظام</h6>
                    <p>يُستخدم هذا النظام لأغراض طبية فقط ويجب استخدامه بطريقة مسؤولة وأخلاقية.</p>
                    
                    <h6>3. حماية البيانات</h6>
                    <p>نلتزم بحماية بياناتك الشخصية والطبية وفقاً لأعلى معايير الأمان والخصوصية.</p>
                    
                    <h6>4. المسؤولية</h6>
                    <p>المستخدم مسؤول عن دقة المعلومات المدخلة وعدم مشاركة بيانات الدخول مع الآخرين.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">سياسة الخصوصية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>جمع البيانات</h6>
                    <p>نجمع البيانات الضرورية فقط لتقديم الخدمات الطبية وتحسين تجربة المستخدم.</p>
                    
                    <h6>استخدام البيانات</h6>
                    <p>تُستخدم البيانات لتقديم الرعاية الطبية وإدارة المواعيد والتواصل الطبي.</p>
                    
                    <h6>مشاركة البيانات</h6>
                    <p>لا نشارك بياناتك مع أطراف ثالثة إلا بموافقتك أو عند الضرورة الطبية.</p>
                    
                    <h6>أمان البيانات</h6>
                    <p>نستخدم أحدث تقنيات التشفير والحماية لضمان أمان بياناتك.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            switch (strength) {
                case 0:
                case 1:
                    strengthBar.style.width = '20%';
                    strengthBar.style.background = '#dc3545';
                    feedback = 'ضعيفة جداً';
                    break;
                case 2:
                    strengthBar.style.width = '40%';
                    strengthBar.style.background = '#fd7e14';
                    feedback = 'ضعيفة';
                    break;
                case 3:
                    strengthBar.style.width = '60%';
                    strengthBar.style.background = '#ffc107';
                    feedback = 'متوسطة';
                    break;
                case 4:
                    strengthBar.style.width = '80%';
                    strengthBar.style.background = '#20c997';
                    feedback = 'قوية';
                    break;
                case 5:
                    strengthBar.style.width = '100%';
                    strengthBar.style.background = '#198754';
                    feedback = 'قوية جداً';
                    break;
            }
            
            strengthText.textContent = feedback;
            strengthText.style.color = strengthBar.style.background;
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('matchText');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    matchText.textContent = 'كلمات المرور متطابقة ✓';
                    matchText.style.color = '#198754';
                } else {
                    matchText.textContent = 'كلمات المرور غير متطابقة ✗';
                    matchText.style.color = '#dc3545';
                }
            } else {
                matchText.textContent = '';
            }
        }
        
        // Handle file upload labels
        document.getElementById('national_id_image').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'اختر ملف البطاقة القومية';
            document.getElementById('national_id_image_label').querySelector('span').textContent = fileName;
        });
        
        document.getElementById('service_card_image').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'اختر ملف بطاقة الخدمات';
            document.getElementById('service_card_image_label').querySelector('span').textContent = fileName;
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('كلمات المرور غير متطابقة');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('يجب الموافقة على الشروط والأحكام');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جارٍ إنشاء الحساب...';
            submitBtn.disabled = true;
            
            // Handle API submission
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/api/v1/auth/register', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('تم إنشاء الحساب بنجاح! سيتم مراجعة حسابك قريباً.');
                    window.location.href = '/login.php';
                } else {
                    // Show error message
                    alert('خطأ: ' + data.message);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الشبكة. يرجى المحاولة مرة أخرى.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.startsWith('2')) {
                value = '+' + value;
            } else if (value.startsWith('01')) {
                // Egyptian mobile format
            }
            this.value = value;
        });
    </script>
</body>
</html>