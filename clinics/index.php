<?php
// clinics/index.php - Simple Clinic Portfolio Page
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/Clinic.php';

$page_title = 'العيادات';
$clinicObj = new Clinic();
$clinics = $clinicObj->getAll(1, 100)['data'];
?>
<div class="container py-4">
    <h2 class="mb-4 text-center"><i class="fas fa-clinic-medical me-2"></i>العيادات</h2>
    <div class="row">
        <?php if (empty($clinics)): ?>
            <div class="col-12 text-center text-muted">لا توجد عيادات متاحة حالياً</div>
        <?php else: ?>
            <?php foreach ($clinics as $clinic): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><i class="fas fa-clinic-medical me-2"></i><?= htmlspecialchars($clinic['name'] ?? '') ?></h5>
                        </div>
                        <div class="card-body p-4">
                            <h6 class="card-subtitle mb-3 text-muted">التخصص: <?= htmlspecialchars($clinic['specialization'] ?? '-') ?></h6>
                            <p class="card-text mb-4 flex-grow-1"><?= htmlspecialchars($clinic['description'] ?? '-') ?></p>
                            <span class="badge bg-<?= ($clinic['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">الحالة: <?= htmlspecialchars($clinic['status'] ?? '-') ?></span>
                        </div>
                        <div class="card-footer bg-white border-0 text-center p-3">
                            <a href="doc.php?clinic_id=<?= $clinic['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-user-md me-2"></i>الأطباء
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
