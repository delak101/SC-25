<?php
/**
 * Secretary Clinics Page
 * Clinic management interface for secretaries
 */

$page_title = 'إدارة العيادات - الإستقبال';
require_once __DIR__ . '/../includes/header.php';

// Require authentication and secretary role
requireAuth();
$current_user = getCurrentUser();

// Include Clinic class
require_once __DIR__ . '/../classes/Clinic.php';
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/Doctor.php';
$clinicObj = new Clinic();
$appointmentObj = new Appointment();
$doctorObj = new Doctor();
$clinics = $clinicObj->getAll(1, 100)['data'];
$clinicStats = $clinicObj->getStatistics();
$appointmentStats = $appointmentObj->getStatistics();
$todaysAppointments = $appointmentObj->getTodaysAppointments();
?>

<div class="container-fluid p-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clinic-medical me-2"></i>إدارة العيادات والمواعيد</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">لوحة التحكم</a></li>
                <li class="breadcrumb-item active">إدارة العيادات</li>
            </ol>
        </nav>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-calendar-day text-primary fa-lg"></i>
                    </div>
                    <h3 class="mb-1"><?= $appointmentStats['today'] ?></h3>
                    <p class="text-muted mb-0">مواعيد اليوم</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-clock text-warning fa-lg"></i>
                    </div>
                    <h3 class="mb-1"><?= $appointmentStats['upcoming'] ?></h3>
                    <p class="text-muted mb-0">في الانتظار</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-check-circle text-success fa-lg"></i>
                    </div>
                    <h3 class="mb-1"><?= $appointmentStats['completed'] ?></h3>
                    <p class="text-muted mb-0">تم إنجازها</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-info bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-clinic-medical text-info fa-lg"></i>
                    </div>
                    <h3 class="mb-1"><?= $clinicStats['active'] ?></h3>
                    <p class="text-muted mb-0">العيادات النشطة</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Today's Appointments -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-calendar-day me-2"></i>مواعيد اليوم</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                        <i class="fas fa-plus me-2"></i>إضافة موعد
                    </button>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-day fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">لا توجد مواعيد اليوم</h5>
                        <p class="text-muted">ابدأ بإضافة مواعيد للأطباء والمرضى</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                            <i class="fas fa-plus me-2"></i>إضافة موعد جديد
                        </button>
                    </div>
                </div>
            </div>

            <!-- Clinic Schedule -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock me-2"></i>جدولة العيادات</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الوقت</th>
                                    <th>العيادة</th>
                                    <th>الطبيب</th>
                                    <th>المريض</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($todaysAppointments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        لا توجد مواعيد مجدولة
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($todaysAppointments as $app): ?>
                                <tr>
                                    <td><?= htmlspecialchars($app['appointment_date']) ?></td>
                                    <td><?= htmlspecialchars($app['appointment_time']) ?></td>
                                    <td><?= htmlspecialchars($app['clinic_id']) ?></td>
                                    <td><?= htmlspecialchars($app['doctor_id']) ?></td>
                                    <td><?= htmlspecialchars($app['patient_id']) ?></td>
                                    <td><?= htmlspecialchars($app['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-bolt me-2"></i>إجراءات سريعة</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                            <i class="fas fa-calendar-plus me-2"></i>حجز موعد جديد
                        </button>
                        <a href="/patients/index.php" class="btn btn-outline-success">
                            <i class="fas fa-user-injured me-2"></i>إدارة المرضى
                        </a>
                        <a href="/videos/index.php" class="btn btn-outline-info">
                            <i class="fas fa-video me-2"></i>الفيديوهات التعليمية
                        </a>
                        <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                            <i class="fas fa-video-plus me-2"></i>إضافة فيديو للعيادة
                        </button>
                        <button class="btn btn-outline-warning">
                            <i class="fas fa-print me-2"></i>طباعة التقرير اليومي
                        </button>
                    </div>
                </div>
            </div>
<!-- Add Video Modal -->
<div class="modal fade" id="addVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة فيديو جديد للعيادة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addVideoForm" enctype="multipart/form-data" method="POST" action="/handlers/video_handler.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">اختيار العيادة</label>
                        <select class="form-select" name="clinic_id" required>
                            <option value="">اختر العيادة</option>
                            <?php foreach ($clinics as $clinic): ?>
                                <option value="<?= $clinic['id'] ?>"><?= htmlspecialchars($clinic['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">عنوان الفيديو</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وصف الفيديو</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملف الفيديو</label>
                        <input type="file" class="form-control" name="video_file" accept="video/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">رفع الفيديو</button>
                </div>
            </form>
        </div>
    </div>
</div>

            <!-- Clinic Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-heartbeat me-2"></i>حالة العيادات</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <i class="fas fa-clinic-medical fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">جميع العيادات متاحة</p>
                        <small class="text-success">النظام يعمل بشكل طبيعي</small>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bell me-2"></i>التنبيهات</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">لا توجد تنبيهات جديدة</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عيادات النظام -->
    <div class="card mt-4">
        <div class="card-header">
            <h5><i class="fas fa-clinic-medical me-2"></i>العيادات المتاحة</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php if (empty($clinics)): ?>
                    <div class="col-12 text-center">
                        <div class="card h-100 shadow-sm p-4 mb-4" style="max-width:400px;margin:auto;">
                            <div class="card-body">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                                    <i class="fas fa-clinic-medical text-primary fa-2x"></i>
                                </div>
                                <h5 class="card-title mb-2">لا توجد عيادات متاحة حالياً</h5>
                                <p class="card-text text-muted">يتم إضافة العيادات بواسطة الأطباء والإدارة</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($clinics as $clinic): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm clinic-card" style="cursor:pointer;" onclick="window.location.href='/videos/index.php?clinic_id=<?= $clinic['id'] ?>'">
                            <div class="card-body">
                                <h5 class="card-title mb-2"><i class="fas fa-clinic-medical me-1"></i><?= htmlspecialchars($clinic['name'] ?? '') ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">التخصص: <?= htmlspecialchars($clinic['specialization'] ?? '-') ?></h6>
                                <p class="card-text mb-2">الوصف: <?= htmlspecialchars($clinic['description'] ?? '-') ?></p>
                                <span class="badge bg-<?= ($clinic['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">الحالة: <?= htmlspecialchars($clinic['status'] ?? '-') ?></span>
                            </div>
                            <div class="card-footer text-center bg-white border-0">
                                <a href="/videos/index.php?clinic_id=<?= $clinic['id'] ?>" class="btn btn-outline-info btn-sm"><i class="fas fa-video me-1"></i>فيديوهات العيادة</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Appointment Modal -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">حجز موعد جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">اختيار العيادة</label>
                                <select class="form-select" required>
                                    <option value="">اختر العيادة</option>
                                    <option value="1">عيادة طب الأسرة</option>
                                    <option value="2">عيادة أمراض القلب</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">اختيار الطبيب</label>
                                <select class="form-select" required>
                                    <option value="">اختر الطبيب</option>
                                    <option value="1">د. أحمد محمد</option>
                                    <option value="2">د. سارة أحمد</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">تاريخ الموعد</label>
                                <input type="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">وقت الموعد</label>
                                <select class="form-select" required>
                                    <option value="">اختر الوقت</option>
                                    <option value="09:00">9:00 ص</option>
                                    <option value="10:00">10:00 ص</option>
                                    <option value="11:00">11:00 ص</option>
                                    <option value="14:00">2:00 م</option>
                                    <option value="15:00">3:00 م</option>
                                    <option value="16:00">4:00 م</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">معلومات المريض</label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="البحث عن مريض أو إدخال اسم جديد" required>
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">رقم الهاتف</label>
                                <input type="tel" class="form-control" placeholder="رقم هاتف المريض" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">نوع الزيارة</label>
                                <select class="form-select">
                                    <option value="consultation">استشارة</option>
                                    <option value="followup">متابعة</option>
                                    <option value="checkup">فحص دوري</option>
                                    <option value="emergency">طارئ</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control" rows="3" placeholder="ملاحظات إضافية عن الموعد"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الموعد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>