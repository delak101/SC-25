<?php
/**
 * Dashboard Page
 * Main dashboard with role-based widgets and navigation
 */

$page_title = 'لوحة التحكم';
require_once __DIR__ . '/includes/header.php';

// Require authentication
requireAuth();

$current_user = getCurrentUser();
$user_role = $current_user['role'];

// Get dashboard statistics based on user role
$stats = [];

try {
    switch ($user_role) {
        case 'admin':
            // Admin gets all statistics
            $user_obj = new User();
            $clinic_obj = new Clinic();
            $appointment_obj = new Appointment();
            $video_obj = new Video();
            
            $user_stats = $user_obj->getAll(1, 1);
            $clinic_stats = $clinic_obj->getAll(1, 1);
            $appointment_stats = $appointment_obj->getStatistics();
            $video_stats = $video_obj->getStatistics();
            
            $stats = [
                'total_users' => $user_stats['total'],
                'total_clinics' => $clinic_stats['total'],
                'total_appointments' => $appointment_stats['total'] ?? 0,
                'today_appointments' => $appointment_stats['today'] ?? 0,
                'total_videos' => $video_stats['total'] ?? 0,
                'active_videos' => $video_stats['active'] ?? 0
            ];
            break;
            
        case 'doctor':
            // Doctor gets their specific statistics
            $doctor_obj = new Doctor();
            $appointment_obj = new Appointment();
            
            $doctor_data = $doctor_obj->getByUserId($current_user['id']);
            if ($doctor_data) {
                $stats = $doctor_obj->getStatistics($current_user['id']);
            }
            break;
            
        case 'patient':
            // Patient gets their specific statistics
            $patient_obj = new Patient();
            $appointment_obj = new Appointment();
            
            $patient_data = $patient_obj->getByUserId($current_user['id']);
            if ($patient_data) {
                $stats = $patient_obj->getStatistics($patient_data['id']);
            }
            break;
            
        default:
            // Other roles get basic statistics
            $appointment_obj = new Appointment();
            $video_obj = new Video();
            
            $stats = [
                'total_videos' => $video_obj->getStatistics()['total'] ?? 0
            ];
            break;
    }
} catch (Exception $e) {
    error_log("Dashboard statistics error: " . $e->getMessage());
    $stats = [];
}

// Get recent activities based on role
$recent_activities = [];
$upcoming_appointments = [];

try {
    if (in_array($user_role, ['doctor', 'patient'])) {
        $appointment_obj = new Appointment();
        $upcoming_appointments = $appointment_obj->getUpcomingAppointments($current_user['id'], $user_role, 5);
    }
    
    // Get recent videos for all users
    $video_obj = new Video();
    $recent_videos = $video_obj->getRecent(6, $user_role);
} catch (Exception $e) {
    error_log("Dashboard activities error: " . $e->getMessage());
    $recent_videos = [];
}
?>

<div class="container-fluid p-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-start">
                            <h3 class="mb-2">مرحباً، <?php echo sanitizeInput($current_user['name']); ?></h3>
                            <p class="mb-0">
                                <i class="fas fa-user-tag me-2"></i>
                                <?php echo USER_ROLES[$user_role] ?? $user_role; ?>
                            </p>
                            <small class="opacity-75">
                                آخر دخول: <?php echo $current_user['last_login'] ? formatArabicDate($current_user['last_login'], 'Y-m-d H:i') : 'أول مرة'; ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <?php if (!empty($current_user['profile_picture']) && file_exists(__DIR__ . '/uploads/profiles/' . $current_user['profile_picture'])): ?>
                                    <img src="/uploads/profiles/<?php echo $current_user['profile_picture']; ?>" 
                                         alt="Profile Picture" 
                                         class="rounded-circle" 
                                         style="width: 70px; height: 70px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                        <i class="fas fa-user text-primary fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Statistics Cards -->
<!-- Statistics Cards - Updated to 2 boxes per row -->
<div class="row mb-4">
    <?php if ($user_role === 'admin'): ?>
    <!-- Admin Statistics -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-users text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['total_users'] ?? 0); ?></h3>
                <p class="text-muted mb-0">إجمالي المستخدمين</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-clinic-medical text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['total_clinics'] ?? 0); ?></h3>
                <p class="text-muted mb-0">العيادات</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-calendar-alt text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['today_appointments'] ?? 0); ?></h3>
                <p class="text-muted mb-0">مواعيد اليوم</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-video text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['active_videos'] ?? 0); ?></h3>
                <p class="text-muted mb-0">الفيديوهات النشطة</p>
            </div>
        </div>
    </div>
    
    <?php elseif ($user_role === 'doctor'): ?>
    <!-- Doctor Statistics -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-user-injured text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['total_patients'] ?? 0); ?></h3>
                <p class="text-muted mb-0">إجمالي المرضى</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-calendar-check text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['today_appointments'] ?? 0); ?></h3>
                <p class="text-muted mb-0">مواعيد اليوم</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-clinic-medical text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['total_clinics'] ?? 0); ?></h3>
                <p class="text-muted mb-0">العيادات</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-clipboard-list text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['total_appointments'] ?? 0); ?></h3>
                <p class="text-muted mb-0">إجمالي المواعيد</p>
            </div>
        </div>
    </div>
    
    <?php elseif ($user_role === 'patient'): ?>
    <!-- Patient Statistics -->
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-calendar-alt text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['upcoming_appointments'] ?? 0); ?></h3>
                <p class="text-muted mb-0">المواعيد القادمة</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-file-medical text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['total_records'] ?? 0); ?></h3>
                <p class="text-muted mb-0">السجلات الطبية</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-clipboard-check text-primary fa-lg"></i>
                </div>
                <h3 class="mb-1"><?php echo number_format($stats['total_appointments'] ?? 0); ?></h3>
                <p class="text-muted mb-0">إجمالي المواعيد</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Quick Actions - Updated to take full width -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-bolt me-2"></i>الإجراءات السريعة</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php if ($user_role === 'admin'): ?>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="/register.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-user-plus fa-2x mb-2"></i>
                    <span>إضافة مستخدم</span>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="/admin/user_management.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <span>إدارة المستخدمين</span>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="/admin/rbac_management.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-shield-alt fa-2x mb-2"></i>
                    <span>إدارة الصلاحيات</span>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="/videos/index.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-video fa-2x mb-2"></i>
                    <span>إدارة الفيديوهات</span>
                </a>
            </div>
            
            <?php elseif ($user_role === 'doctor'): ?>
            <div class="col-lg-4 col-md-6 mb-3">
                <a href="/appointments/index.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                    <span>المواعيد</span>
                </a>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <a href="/patients/index.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-user-injured fa-2x mb-2"></i>
                    <span>المرضى</span>
                </a>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <a href="/clinics/doc.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-clinic-medical fa-2x mb-2"></i>
                    <span>العيادات</span>
                </a>
            </div>
            
            <?php elseif ($user_role === 'patient'): ?>
            <div class="col-lg-6 col-md-6 mb-3">
                <a href="/clinics/pat.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-clinic-medical fa-2x mb-2"></i>
                    <span>العيادات</span>
                </a>
            </div>
            <div class="col-lg-6 col-md-6 mb-3">
                <a href="/appointments/my_appointments.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                    <span>مواعيدي</span>
                </a>
            </div>
            
            <?php else: ?>
            <div class="col-lg-4 col-md-6 mb-3">
                <a href="/videos/index.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-video fa-2x mb-2"></i>
                    <span>الفيديوهات</span>
                </a>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <a href="/medical_terms/index.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-book-medical fa-2x mb-2"></i>
                    <span>المصطلحات الطبية</span>
                </a>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <a href="/profile.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-user-cog fa-2x mb-2"></i>
                    <span>الملف الشخصي</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Upcoming Appointments - Full Width -->
<?php if (!empty($upcoming_appointments)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-alt me-2"></i>المواعيد القادمة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($upcoming_appointments as $appointment): ?>
                    <div class="col-lg-6 col-md-12 mb-3">
                        <div class="d-flex align-items-center p-3 border rounded">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                <i class="fas fa-calendar-check text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <?php if ($user_role === 'doctor'): ?>
                                        <?php echo sanitizeInput($appointment['patient_name']); ?>
                                    <?php else: ?>
                                        د. <?php echo sanitizeInput($appointment['doctor_name']); ?>
                                    <?php endif; ?>
                                </h6>
                                <small class="text-muted">
                                    <?php echo sanitizeInput($appointment['clinic_name']); ?>
                                </small>
                                <div class="mt-1">
                                    <small class="text-primary">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo formatArabicDate($appointment['appointment_date']); ?> - 
                                        <?php echo formatArabicTime($appointment['appointment_time']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-3">
                    <a href="/appointments/index.php" class="btn btn-outline-primary">
                        <i class="fas fa-calendar-alt me-2"></i>عرض جميع المواعيد
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- System Information - Full Width -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>معلومات النظام</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>نسخة النظام:</span>
                    <strong><?php echo APP_VERSION; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>نوع قاعدة البيانات:</span>
                    <strong><?php echo strtoupper(DB_TYPE); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>وقت الخادم:</span>
                    <strong><?php echo date('H:i'); ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>تاريخ اليوم:</span>
                    <strong><?php echo formatArabicDate(date('Y-m-d')); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Welcome Modal for First Time Users -->
<?php if (!isset($_SESSION['welcomed'])): ?>
<div class="modal fade" id="welcomeModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">مرحباً بك في <?php echo APP_NAME; ?></h5>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <img src="/images/logo.png" alt="<?php echo APP_NAME; ?>" 
                         style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;"
                         onerror="this.style.display='none'">
                </div>
                
                <h4 class="text-center mb-4">أهلاً وسهلاً <?php echo sanitizeInput($current_user['name']); ?></h4>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="feature-item p-4 bg-light rounded text-center h-100 d-flex flex-column justify-content-center" style="min-height: 180px;">
                            <i class="fas fa-stethoscope text-primary fa-3x mb-3"></i>
                            <h6 class="mb-2">نظام طبي متكامل</h6>
                            <p class="small text-muted mb-0">إدارة شاملة للعيادات والمواعيد والسجلات الطبية</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item p-4 bg-light rounded text-center h-100 d-flex flex-column justify-content-center" style="min-height: 180px;">
                            <i class="fas fa-video text-primary fa-3x mb-3"></i>
                            <h6 class="mb-2">محتوى مرئي تفاعلي</h6>
                            <p class="small text-muted mb-0">فيديوهات تعليمية وتوضيحية بلغة الإشارة</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item p-4 bg-light rounded text-center h-100 d-flex flex-column justify-content-center" style="min-height: 180px;">
                            <i class="fas fa-shield-alt text-primary fa-3x mb-3"></i>
                            <h6 class="mb-2">أمان وخصوصية</h6>
                            <p class="small text-muted mb-0">حماية متقدمة للبيانات الطبية والشخصية</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item p-4 bg-light rounded text-center h-100 d-flex flex-column justify-content-center" style="min-height: 180px;">
                            <i class="fas fa-mobile-alt text-primary fa-3x mb-3"></i>
                            <h6 class="mb-2">واجهة سهلة الاستخدام</h6>
                            <p class="small text-muted mb-0">تصميم بديهي ومتجاوب مع جميع الأجهزة</p>
                        </div>
                    </div>
                </div>
                
                <?php if ($user_role === 'admin'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-crown me-2"></i>
                    <strong>مرحباً مدير النظام!</strong> يمكنك إدارة جميع جوانب النظام من خلال لوحة التحكم.
                </div>
                <?php elseif ($user_role === 'doctor'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-user-md me-2"></i>
                    <strong>مرحباً دكتور!</strong> يمكنك إدارة مواعيدك ومرضاك من خلال النظام.
                </div>
                <?php elseif ($user_role === 'patient'): ?>
                <div class="alert alert-primary">
                    <i class="fas fa-user-injured me-2"></i>
                    <strong>مرحباً بك!</strong> يمكنك حجز المواعيد ومتابعة ملفك الطبي بسهولة.
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="markWelcomed()">
                    <i class="fas fa-check me-2"></i>فهمت، لنبدأ!
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Show welcome modal on page load
document.addEventListener('DOMContentLoaded', function() {
    const welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'));
    welcomeModal.show();
});

function markWelcomed() {
    fetch('/handlers/mark_welcomed.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({welcomed: true})
    });
}
</script>

<?php 
$_SESSION['welcomed'] = true;
endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
