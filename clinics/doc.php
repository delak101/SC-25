<?php
/**
 * Doctor Clinics Page
 * Clinic management interface for doctors
 */

$page_title = 'إدارة العيادات - الأطباء';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/Clinic.php';
// Require authentication and doctor role
requireAuth();
$current_user = getCurrentUser();

$clinicObj = new Clinic();
$clinics = $clinicObj->getAll(1, 100)['data'];
?>

<div class="container-fluid p-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clinic-medical me-2"></i>إدارة العيادات</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">لوحة التحكم</a></li>
                <li class="breadcrumb-item active">العيادات</li>
            </ol>
        </nav>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-clinic-medical text-primary fa-lg"></i>
                    </div>
                    <h3 class="mb-1"><?= count($clinics) ?></h3>
                    <p class="text-muted mb-0">العيادات المتاحة</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-calendar-check text-primary fa-lg"></i>
                    </div>
                    <h3 class="mb-1">0</h3>
                    <p class="text-muted mb-0">المواعيد اليوم</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-user-injured text-primary fa-lg"></i>
                    </div>
                    <h3 class="mb-1">0</h3>
                    <p class="text-muted mb-0">المرضى المسجلين</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-clock text-primary fa-lg"></i>
                    </div>
                    <h3 class="mb-1">0</h3>
                    <p class="text-muted mb-0">المواعيد المعلقة</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <!-- Clinics List -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-list me-2"></i>العيادات المتاحة</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addClinicModal">
                        <i class="fas fa-plus me-2"></i>إضافة عيادة
                    </button>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                    <?php if (empty($clinics)) : ?>
                        <div class="col-12 text-center">
                            <div class="card h-100 shadow-sm p-4 mb-4" style="max-width:400px;margin:auto;cursor:pointer;" data-bs-toggle="modal" data-bs-target="#addClinicModal">
                                <div class="card-body">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-clinic-medical text-primary fa-2x"></i>
                                    </div>
                                    <h5 class="card-title mb-2">لا توجد عيادات مسجلة</h5>
                                    <p class="card-text text-muted">ابدأ بإضافة عيادة جديدة لإدارة مواعيدك</p>
                                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addClinicModal">
                                        <i class="fas fa-plus me-2"></i>إضافة أول عيادة
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($clinics as $clinic): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
                            <div class="card h-100 shadow-sm clinic-card" 
                                 style="cursor:pointer;" 
                                 onclick="window.location.href='/videos/index.php?clinic_id=<?= $clinic['id'] ?>'"
                                 data-clinic-id="<?= $clinic['id'] ?>"
                                 data-clinic-name="<?= htmlspecialchars($clinic['name'] ?? '') ?>"
                                 data-clinic-specialization="<?= htmlspecialchars($clinic['specialization'] ?? '') ?>"
                                 data-clinic-description="<?= htmlspecialchars($clinic['description'] ?? '') ?>">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0"><i class="fas fa-clinic-medical me-2"></i><?= htmlspecialchars($clinic['name'] ?? '') ?></h5>
                                </div>
                                <div class="card-body p-4 d-flex flex-column">
                                    <h6 class="card-subtitle mb-3 text-muted">التخصص: <?= htmlspecialchars($clinic['specialization'] ?? '-') ?></h6>
                                    <p class="card-text mb-3 flex-grow-1"><?= htmlspecialchars($clinic['description'] ?? '-') ?></p>
                                    
                                    <?php if (!empty($clinic['assigned_doctors'])): ?>
                                        <div class="mb-3">
                                            <small class="text-muted">الأطباء المعينون:</small>
                                            <div class="mt-1">
                                                <?php foreach ($clinic['assigned_doctors'] as $doctor): ?>
                                                    <span class="badge bg-info me-1"><?= htmlspecialchars($doctor['name']) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <small class="text-warning">لا يوجد أطباء معينون</small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <span class="badge bg-<?= ($clinic['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?> align-self-start">الحالة: <?= htmlspecialchars($clinic['status'] ?? '-') ?></span>
                                </div>
                                <div class="card-footer bg-white border-0 d-flex justify-content-center gap-2 p-3">
                                    <button class="btn btn-sm btn-primary editClinicBtn" data-id="<?= $clinic['id'] ?>" onclick="event.stopPropagation();"><i class="fas fa-edit me-1"></i> تعديل</button>
                                    <button class="btn btn-sm btn-outline-danger deleteClinicBtn" data-id="<?= $clinic['id'] ?>" onclick="event.stopPropagation();"><i class="fas fa-trash me-1"></i> حذف</button>
                                    <button class="btn btn-sm btn-outline-primary assignDoctorBtn" data-id="<?= $clinic['id'] ?>" onclick="event.stopPropagation();"><i class="fas fa-user-md me-1"></i> تعيين</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Clinic Modal -->
<div class="modal fade" id="addClinicModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clinicModalTitle">إضافة عيادة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="clinicForm">
                <input type="hidden" name="clinic_id" id="clinic_id">
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم العيادة</label>
                        <input type="text" class="form-control" name="name" id="clinic_name" placeholder="أدخل اسم العيادة" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التخصص</label>
                        <select class="form-select" name="specialization" id="clinic_specialization" required>
                            <option value="">اختر التخصص</option>
                            <option value="طب عام">طب عام</option>
                            <option value="أمراض القلب">أمراض القلب</option>
                            <option value="أمراض الأعصاب">أمراض الأعصاب</option>
                            <option value="جراحة العظام">جراحة العظام</option>
                            <option value="طب الأطفال">طب الأطفال</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea class="form-control" name="description" id="clinic_description" rows="3" placeholder="وصف مختصر عن العيادة"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ العيادة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Doctor Modal -->
<div class="modal fade" id="assignDoctorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعيين دكتور للعيادة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignDoctorForm">
                <input type="hidden" name="clinic_id" id="assign_clinic_id">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اختر الدكتور</label>
                        <select class="form-select" name="doctor_id" id="doctorSelect" required>
                            <option value="">-- اختر الدكتور --</option>
                            <?php
                            try {
                                // Get users with doctor role
                                $db = Database::getInstance();
                                $sql = "SELECT u.id, u.name, 
                                               COALESCE(d.specialization, 'غير محدد') as specialization
                                        FROM users u 
                                        LEFT JOIN doctors d ON u.id = d.user_id 
                                        WHERE u.role = 'doctor' AND u.status = 'active'
                                        ORDER BY u.name ASC";
                                $stmt = $db->getConnection()->prepare($sql);
                                $stmt->execute();
                                $allDoctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($allDoctors as $doc) {
                                    echo '<option value="' . htmlspecialchars($doc['id']) . '">' . 
                                         htmlspecialchars($doc['name']) . ' - ' . 
                                         htmlspecialchars($doc['specialization']) . '</option>';
                                }
                            } catch (Exception $e) {
                                echo '<option value="">خطأ في تحميل الأطباء</option>';
                                error_log("Error loading doctors: " . $e->getMessage());
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تعيين</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// فتح المودال للإضافة
document.querySelectorAll('[data-bs-target="#addClinicModal"]').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('clinicModalTitle').textContent = 'إضافة عيادة جديدة';
        document.getElementById('clinicForm').reset();
        document.getElementById('clinic_id').value = '';
    });
});

// فتح المودال للتعديل
document.querySelectorAll('.editClinicBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        const card = btn.closest('.clinic-card');
        const clinicId = btn.dataset.id;
        const clinicName = card.dataset.clinicName;
        const clinicSpecialization = card.dataset.clinicSpecialization;
        const clinicDescription = card.dataset.clinicDescription;
        
        document.getElementById('clinicModalTitle').textContent = 'تعديل عيادة';
        document.getElementById('clinic_id').value = clinicId;
        document.getElementById('clinic_name').value = clinicName;
        document.getElementById('clinic_specialization').value = clinicSpecialization;
        document.getElementById('clinic_description').value = clinicDescription;
        
        var modal = new bootstrap.Modal(document.getElementById('addClinicModal'));
        modal.show();
    });
});

// حذف عيادة
document.querySelectorAll('.deleteClinicBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        if(confirm('هل أنت متأكد من حذف العيادة؟')) {
            const clinic_id = btn.dataset.id;
            const csrf_token = document.getElementById('csrf_token').value;
            fetch('../handlers/clinic_handler.php', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: new URLSearchParams({action: 'delete', clinic_id, csrf_token})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
                else alert(data.message);
            });
        }
    });
});

// إضافة/تعديل عيادة
document.getElementById('clinicForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    let action = formData.get('clinic_id') ? 'update' : 'create';
    formData.append('action', action);
    fetch('../handlers/clinic_handler.php', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) location.reload();
        else alert(data.message);
    });
});

// تعيين دكتور
document.querySelectorAll('.assignDoctorBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        const clinicId = btn.dataset.id;
        document.getElementById('assign_clinic_id').value = clinicId;
        var modal = new bootstrap.Modal(document.getElementById('assignDoctorModal'));
        modal.show();
    });
});

document.getElementById('assignDoctorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'assign_doctor');
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'جاري التعيين...';
    submitBtn.disabled = true;
    
    fetch('../handlers/clinic_handler.php', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if(data.success) {
                location.reload();
            } else {
                alert('خطأ: ' + data.message);
            }
        } catch (e) {
            console.error('Response is not valid JSON:', text);
            alert('خطأ في الاستجابة من الخادم');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تعيين الدكتور: ' + error.message);
    })
    .finally(() => {
        // Reset button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>