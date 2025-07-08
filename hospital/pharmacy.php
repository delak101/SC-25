<?php
/**
 * Hospital Pharmacy Page
 * Pharmacy management interface
 */

$page_title = 'إدارة الصيدلية';
require_once __DIR__ . '/../includes/header.php';

// Require authentication and pharmacy role
requireAuth();
$current_user = getCurrentUser();

?>

<div class="container-fluid p-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-pills me-2"></i>إدارة الصيدلية</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">لوحة التحكم</a></li>
                <li class="breadcrumb-item active">الصيدلية</li>
            </ol>
        </nav>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-pills text-success fa-lg"></i>
                    </div>
                    <h3 class="mb-1">0</h3>
                    <p class="text-muted mb-0">الأدوية المتاحة</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-exclamation-triangle text-warning fa-lg"></i>
                    </div>
                    <h3 class="mb-1">0</h3>
                    <p class="text-muted mb-0">أدوية منتهية الصلاحية</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-danger bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-chart-line-down text-danger fa-lg"></i>
                    </div>
                    <h3 class="mb-1">0</h3>
                    <p class="text-muted mb-0">أدوية نفدت</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-info bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-prescription text-info fa-lg"></i>
                    </div>
                    <h3 class="mb-1">0</h3>
                    <p class="text-muted mb-0">الوصفات اليوم</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Medicine Inventory -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-boxes me-2"></i>مخزون الأدوية</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                        <i class="fas fa-plus me-2"></i>إضافة دواء
                    </button>
                </div>
                <div class="card-body">
                    <!-- Search and Filter -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="البحث عن دواء">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option value="">جميع الفئات</option>
                                <option value="antibiotics">مضادات حيوية</option>
                                <option value="painkillers">مسكنات</option>
                                <option value="vitamins">فيتامينات</option>
                                <option value="chronic">أدوية مزمنة</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option value="">جميع الحالات</option>
                                <option value="available">متاح</option>
                                <option value="low">كمية قليلة</option>
                                <option value="out">نفد</option>
                                <option value="expired">منتهي الصلاحية</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>اسم الدواء</th>
                                    <th>الفئة</th>
                                    <th>الكمية</th>
                                    <th>تاريخ الانتهاء</th>
                                    <th>السعر</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        لا توجد أدوية مسجلة في النظام
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Prescriptions -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-prescription me-2"></i>الوصفات الطبية</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-prescription fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">لا توجد وصفات طبية</h5>
                        <p class="text-muted">الوصفات الطبية ستظهر هنا عند إصدارها من الأطباء</p>
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
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                            <i class="fas fa-plus me-2"></i>إضافة دواء جديد
                        </button>
                        <button class="btn btn-outline-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>فحص تواريخ الانتهاء
                        </button>
                        <button class="btn btn-outline-info">
                            <i class="fas fa-chart-bar me-2"></i>تقرير المخزون
                        </button>
                        <button class="btn btn-outline-success">
                            <i class="fas fa-file-invoice me-2"></i>إصدار فاتورة
                        </button>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>تنبيهات المخزون</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="text-muted mb-0">جميع الأدوية متوفرة</p>
                        <small class="text-success">لا توجد تنبيهات حالياً</small>
                    </div>
                </div>
            </div>

            <!-- Medical Terms -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-book-medical me-2"></i>المصطلحات الطبية</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/videos/index.php" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-video me-2"></i>فيديوهات تعليمية
                        </a>
                        <a href="/medical-terms.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-book me-2"></i>قاموس المصطلحات
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Medicine Modal -->
<div class="modal fade" id="addMedicineModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة دواء جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">اسم الدواء</label>
                                <input type="text" class="form-control" placeholder="اسم الدواء التجاري" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">الاسم العلمي</label>
                                <input type="text" class="form-control" placeholder="الاسم العلمي للدواء">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">الفئة</label>
                                <select class="form-select" required>
                                    <option value="">اختر الفئة</option>
                                    <option value="antibiotics">مضادات حيوية</option>
                                    <option value="painkillers">مسكنات</option>
                                    <option value="vitamins">فيتامينات</option>
                                    <option value="chronic">أدوية مزمنة</option>
                                    <option value="emergency">أدوية طوارئ</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">الشركة المصنعة</label>
                                <input type="text" class="form-control" placeholder="اسم الشركة المصنعة">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">الكمية</label>
                                <input type="number" class="form-control" placeholder="الكمية المتاحة" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">السعر</label>
                                <input type="number" step="0.01" class="form-control" placeholder="السعر" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">الوحدة</label>
                                <select class="form-select">
                                    <option value="tablet">قرص</option>
                                    <option value="capsule">كبسولة</option>
                                    <option value="bottle">زجاجة</option>
                                    <option value="tube">أنبوب</option>
                                    <option value="box">علبة</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">تاريخ الإنتاج</label>
                                <input type="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">تاريخ انتهاء الصلاحية</label>
                                <input type="date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">التركيب والجرعة</label>
                        <input type="text" class="form-control" placeholder="مثال: 500mg">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">تعليمات الاستخدام</label>
                        <textarea class="form-control" rows="3" placeholder="تعليمات الاستخدام والجرعة"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">التحذيرات والآثار الجانبية</label>
                        <textarea class="form-control" rows="2" placeholder="التحذيرات والآثار الجانبية المحتملة"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الدواء</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>