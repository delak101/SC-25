<?php
/**
 * User Profile Page
 * Displays and allows editing of user profile information
 */

$page_title = 'الملف الشخصي';
require_once __DIR__ . '/includes/header.php';

// Require authentication
requireAuth();
$current_user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/handlers/update_profile.php';
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
        .profile-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            margin-bottom: 1rem;
        }
        
        .profile-body {
            padding: 2rem;
        }
        
        .profile-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .profile-section:last-child {
            border-bottom: none;
        }
        
        .form-control:disabled, .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 1;
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
        
        .document-preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="profile-container">
                    <!-- Header -->
                    <div class="profile-header">
                        <div class="text-center">
                            <?php if (!empty($current_user['profile_image'])): ?>
                                <img src="/uploads/profiles/<?php echo htmlspecialchars($current_user['profile_image']); ?>" 
                                     class="profile-avatar" alt="صورة المستخدم">
                            <?php else: ?>
                                <div class="profile-avatar bg-light d-flex align-items-center justify-content-center mx-auto">
                                    <i class="fas fa-user fa-3x text-secondary"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h3><?php echo htmlspecialchars($current_user['name']); ?></h3>
                            <p class="mb-0"><?php echo htmlspecialchars($current_user['email']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Body -->
                    <div class="profile-body">
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
                        
                        <form method="POST" action="/profile.php" id="profileForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <!-- Basic Information -->
                            <div class="profile-section">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <i class="fas fa-info-circle me-2"></i>المعلومات الأساسية
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required 
                                               value="<?php echo htmlspecialchars($current_user['name']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" required 
                                               value="<?php echo htmlspecialchars($current_user['email']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required 
                                               value="<?php echo htmlspecialchars($current_user['phone']); ?>" 
                                               pattern="[0-9+\-\s()]+">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="national_id" class="form-label">الرقم القومي <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="national_id" name="national_id" required 
                                               value="<?php echo htmlspecialchars($current_user['national_id']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="gender" class="form-label">النوع <span class="text-danger">*</span></label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="male" <?php echo ($current_user['gender'] == 'male') ? 'selected' : ''; ?>>ذكر</option>
                                            <option value="female" <?php echo ($current_user['gender'] == 'female') ? 'selected' : ''; ?>>أنثى</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="hearing_status" class="form-label">الحالة <span class="text-danger">*</span></label>
                                        <select class="form-select" id="hearing_status" name="hearing_status" required>
                                            <option value="deaf" <?php echo ($current_user['hearing_status'] == 'deaf') ? 'selected' : ''; ?>>أصم</option>
                                            <option value="hard_of_hearing" <?php echo ($current_user['hearing_status'] == 'hard_of_hearing') ? 'selected' : ''; ?>>ضعيف السمع</option>
                                            <option value="hearing" <?php echo ($current_user['hearing_status'] == 'hearing') ? 'selected' : ''; ?>>سليم</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="marital_status" class="form-label">الحالة الاجتماعية</label>
                                        <select class="form-select" id="marital_status" name="marital_status">
                                            <option value="">اختر الحالة الاجتماعية</option>
                                            <option value="single" <?php echo ($current_user['marital_status'] == 'single') ? 'selected' : ''; ?>>أعزب</option>
                                            <option value="married" <?php echo ($current_user['marital_status'] == 'married') ? 'selected' : ''; ?>>متزوج</option>
                                            <option value="divorced" <?php echo ($current_user['marital_status'] == 'divorced') ? 'selected' : ''; ?>>مطلق</option>
                                            <option value="widowed" <?php echo ($current_user['marital_status'] == 'widowed') ? 'selected' : ''; ?>>أرمل</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="sign_language_level" class="form-label">مستوى تعلم لغة الإشارة</label>
                                        <select class="form-select" id="sign_language_level" name="sign_language_level">
                                            <option value="">اختر المستوى</option>
                                            <option value="beginner" <?php echo ($current_user['sign_language_level'] == 'beginner') ? 'selected' : ''; ?>>مبتدئ</option>
                                            <option value="intermediate" <?php echo ($current_user['sign_language_level'] == 'intermediate') ? 'selected' : ''; ?>>متوسط</option>
                                            <option value="advanced" <?php echo ($current_user['sign_language_level'] == 'advanced') ? 'selected' : ''; ?>>متقدم</option>
                                            <option value="none" <?php echo ($current_user['sign_language_level'] == 'none') ? 'selected' : ''; ?>>لم يدرس</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="governorate" class="form-label">المحافظة <span class="text-danger">*</span></label>
                                        <select class="form-select" id="governorate" name="governorate" required>
                                            <option value="cairo" <?php echo ($current_user['governorate'] == 'cairo') ? 'selected' : ''; ?>>القاهرة</option>
                                            <option value="alexandria" <?php echo ($current_user['governorate'] == 'alexandria') ? 'selected' : ''; ?>>الإسكندرية</option>
                                            <option value="giza" <?php echo ($current_user['governorate'] == 'giza') ? 'selected' : ''; ?>>الجيزة</option>
                                            <!-- Add more governorates as needed -->
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="age" class="form-label">السن <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="age" name="age" required 
                                               min="10" max="100" value="<?php echo htmlspecialchars($current_user['age']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="job" class="form-label">الوظيفة</label>
                                        <input type="text" class="form-control" id="job" name="job" 
                                               value="<?php echo htmlspecialchars($current_user['job']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="profile_image" class="form-label">صورة الملف الشخصي</label>
                                        <div class="file-upload">
                                            <input type="file" class="file-upload-input" id="profile_image" name="profile_image" accept="image/*">
                                            <label for="profile_image" class="file-upload-label">
                                                <i class="fas fa-upload me-2"></i>
                                                <span>اختر صورة الملف الشخصي</span>
                                            </label>
                                        </div>
                                        <?php if (!empty($current_user['profile_image'])): ?>
                                            <img src="/uploads/profiles/<?php echo htmlspecialchars($current_user['profile_image']); ?>" 
                                                 class="document-preview mt-2">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Documents Section -->
                            <div class="profile-section">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <i class="fas fa-file-alt me-2"></i>الوثائق والمستندات
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="national_id_image" class="form-label">صورة البطاقة القومية <span class="text-danger">*</span></label>
                                        <div class="file-upload">
                                            <input type="file" class="file-upload-input" id="national_id_image" name="national_id_image" accept="image/*">
                                            <label for="national_id_image" class="file-upload-label">
                                                <i class="fas fa-upload me-2"></i>
                                                <span>تحديث ملف البطاقة القومية</span>
                                            </label>
                                        </div>
                                        <?php if (!empty($current_user['national_id_image'])): ?>
                                            <img src="/uploads/documents/<?php echo htmlspecialchars($current_user['national_id_image']); ?>" 
                                                 class="document-preview mt-2">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="service_card_image" class="form-label">صورة بطاقة الخدمات</label>
                                        <div class="file-upload">
                                            <input type="file" class="file-upload-input" id="service_card_image" name="service_card_image" accept="image/*">
                                            <label for="service_card_image" class="file-upload-label">
                                                <i class="fas fa-upload me-2"></i>
                                                <span>تحديث ملف بطاقة الخدمات</span>
                                            </label>
                                        </div>
                                        <?php if (!empty($current_user['service_card_image'])): ?>
                                            <img src="/uploads/documents/<?php echo htmlspecialchars($current_user['service_card_image']); ?>" 
                                                 class="document-preview mt-2">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Password Section -->
                            <div class="profile-section">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <i class="fas fa-lock me-2"></i>تغيير كلمة المرور
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" 
                                                   placeholder="أدخل كلمة المرور الحالية">
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('current_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                                   placeholder="كلمة مرور قوية">
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('new_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">اتركه فارغاً إذا كنت لا تريد تغيير كلمة المرور</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_new_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_new_password" 
                                                   name="confirm_new_password" placeholder="أعد كتابة كلمة المرور">
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('confirm_new_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-save me-2"></i>حفظ التغييرات
                                </button>
                                
                                <a href="/dashboard.php" class="btn btn-outline-secondary btn-lg px-5 ms-2">
                                    <i class="fas fa-times me-2"></i>إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
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
        
        // Handle file upload labels
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'اختر صورة الملف الشخصي';
            this.nextElementSibling.querySelector('span').textContent = fileName;
            
            // Preview image
            if (e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.createElement('img');
                    preview.src = event.target.result;
                    preview.className = 'document-preview mt-2';
                    
                    const existingPreview = this.parentElement.querySelector('.document-preview');
                    if (existingPreview) {
                        existingPreview.replaceWith(preview);
                    } else {
                        this.parentElement.appendChild(preview);
                    }
                }.bind(this);
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        document.getElementById('national_id_image').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'تحديث ملف البطاقة القومية';
            this.nextElementSibling.querySelector('span').textContent = fileName;
            
            // Preview image
            if (e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.createElement('img');
                    preview.src = event.target.result;
                    preview.className = 'document-preview mt-2';
                    
                    const existingPreview = this.parentElement.querySelector('.document-preview');
                    if (existingPreview) {
                        existingPreview.replaceWith(preview);
                    } else {
                        this.parentElement.appendChild(preview);
                    }
                }.bind(this);
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        document.getElementById('service_card_image').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'تحديث ملف بطاقة الخدمات';
            this.nextElementSibling.querySelector('span').textContent = fileName;
            
            // Preview image
            if (e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.createElement('img');
                    preview.src = event.target.result;
                    preview.className = 'document-preview mt-2';
                    
                    const existingPreview = this.parentElement.querySelector('.document-preview');
                    if (existingPreview) {
                        existingPreview.replaceWith(preview);
                    } else {
                        this.parentElement.appendChild(preview);
                    }
                }.bind(this);
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmNewPassword = document.getElementById('confirm_new_password').value;
            
            if (newPassword !== confirmNewPassword) {
                e.preventDefault();
                alert('كلمات المرور الجديدة غير متطابقة');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جارٍ حفظ التغييرات...';
            submitBtn.disabled = true;
            
            // Re-enable after 10 seconds (in case of network issues)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
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