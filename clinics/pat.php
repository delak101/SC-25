<?php
/**
 * Patient Clinics Page
 * Clinic browsing interface for patients
 */

$page_title = 'العيادات المتاحة - المرضى';
require_once __DIR__ . '/../includes/header.php';

// Require authentication and patient role
requireAuth();
$current_user = getCurrentUser();

require_once __DIR__ . '/../classes/Clinic.php';
$clinicObj = new Clinic();
$clinics = $clinicObj->getAll(1, 100)['data'];

?>

<div class="container-fluid p-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clinic-medical me-2"></i>العيادات المتاحة</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">لوحة التحكم</a></li>
                <li class="breadcrumb-item active">العيادات</li>
            </ol>
        </nav>
    </div>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="البحث عن عيادة أو دكتور">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select">
                        <option value="">جميع التخصصات</option>
                        <option value="general">طب عام</option>
                        <option value="cardiology">أمراض القلب</option>
                        <option value="neurology">أمراض الأعصاب</option>
                        <option value="orthopedics">جراحة العظام</option>
                        <option value="pediatrics">طب الأطفال</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select">
                        <option value="">الكل</option>
                        <option value="available">متاحة الآن</option>
                        <option value="today">متاحة اليوم</option>
                        <option value="week">متاحة هذا الأسبوع</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>فلترة
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Clinics Grid -->
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
                        <a href="/dashboard.php" class="btn btn-primary mt-2">
                            <i class="fas fa-home me-2"></i>العودة للوحة التحكم
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($clinics as $clinic): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm clinic-card" style="cursor:pointer;" onclick="window.location.href='/videos/index.php?clinic_id=<?= $clinic['id'] ?>'">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-clinic-medical me-2"></i><?= htmlspecialchars($clinic['name'] ?? '') ?></h5>
                    </div>
                    <div class="card-body p-4">
                        <h6 class="card-subtitle mb-3 text-muted">التخصص: <?= htmlspecialchars($clinic['specialization'] ?? '-') ?></h6>
                        <p class="card-text mb-4 flex-grow-1"><?= htmlspecialchars($clinic['description'] ?? '-') ?></p>
                        <span class="badge bg-<?= ($clinic['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">الحالة: <?= htmlspecialchars($clinic['status'] ?? '-') ?></span>
                    </div>
                    <div class="card-footer bg-white border-0 text-center p-3">
                        <a href="/videos/index.php?clinic_id=<?= $clinic['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-video me-2"></i>فيديوهات العيادة
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Appointment Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">حجز موعد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">تاريخ الموعد</label>
                        <input type="date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وقت الموعد</label>
                        <select class="form-select" required>
                            <option value="">اختر الوقت المناسب</option>
                            <option value="09:00">9:00 ص</option>
                            <option value="10:00">10:00 ص</option>
                            <option value="11:00">11:00 ص</option>
                            <option value="14:00">2:00 م</option>
                            <option value="15:00">3:00 م</option>
                            <option value="16:00">4:00 م</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">سبب الزيارة</label>
                        <textarea class="form-control" rows="3" placeholder="اختياري - وصف مختصر لسبب الزيارة"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف للتأكيد</label>
                        <input type="tel" class="form-control" placeholder="رقم الهاتف" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تأكيد الحجز</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle booking button clicks
document.addEventListener('DOMContentLoaded', function() {
    const bookingButtons = document.querySelectorAll('.btn-primary:contains("حجز موعد")');
    bookingButtons.forEach(button => {
        if (button.textContent.includes('حجز موعد')) {
            button.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
                modal.show();
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>