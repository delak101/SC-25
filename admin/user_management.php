<?php
/**
 * User Management Interface
 * Manage all system users
 */

$page_title = 'إدارة المستخدمين';
require_once __DIR__ . '/../includes/header.php';

// Require user management permission
requirePermission('users', 'read');

$user = new User();
$rbac = new RBAC();

// Get filter parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
$role_filter = sanitizeInput($_GET['role'] ?? '');
$status_filter = sanitizeInput($_GET['status'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');

// Build filters
$filters = [];
if ($role_filter) $filters['role'] = $role_filter;
if ($status_filter) $filters['status'] = $status_filter;
if ($search) $filters['search'] = $search;

// Get users
$result = $user->getAll($page, $limit, $filters);
$users = $result['users'];
$total = $result['total'];
$total_pages = $result['total_pages'];

// Get roles for filter dropdown
$roles = $rbac->getAllRoles();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users me-2"></i><?php echo $page_title; ?></h2>
        <div>
            <?php if (hasPermission('users', 'create')): ?>
            <a href="/register.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i>إضافة مستخدم جديد
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">البحث</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo sanitizeInput($search); ?>" 
                           placeholder="اسم، إيميل، هاتف أو رقم قومي">
                </div>
                
                <div class="col-md-2">
                    <label for="role" class="form-label">الدور</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">جميع الأدوار</option>
                        <?php foreach (USER_ROLES as $role_key => $role_name): ?>
                        <option value="<?php echo $role_key; ?>" 
                                <?php echo $role_filter === $role_key ? 'selected' : ''; ?>>
                            <?php echo $role_name; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">الحالة</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">جميع الحالات</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>نشط</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="limit" class="form-label">عدد النتائج</label>
                    <select class="form-select" id="limit" name="limit">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>بحث
                    </button>
                    <a href="/admin/user_management.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>مسح
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-list me-2"></i>قائمة المستخدمين</h5>
            <span class="badge bg-info"><?php echo $total; ?> مستخدم</span>
        </div>
        <div class="card-body">
            <?php if (!empty($users)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>الرقم القومي</th>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>الهاتف</th>
                            <th>المحافظة</th>
                            <th>الدور</th>
                            <th>الحالة</th>
                            <th>آخر دخول</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['national_id'] ?? 'غير محدد'; ?></td>
                            <td>
                                <strong><?php echo sanitizeInput($u['name']); ?></strong>
                            </td>
                            <td>
                                <a href="mailto:<?php echo sanitizeInput($u['email']); ?>">
                                    <?php echo sanitizeInput($u['email']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($u['phone']): ?>
                                <a href="tel:<?php echo sanitizeInput($u['phone']); ?>">
                                    <?php echo sanitizeInput($u['phone']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">غير محدد</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo sanitizeInput($u['governorate'] ?? 'غير محدد'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'doctor' ? 'success' : 'primary'); ?>">
                                    <?php echo USER_ROLES[$u['role']] ?? $u['role']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'active'): ?>
                                <span class="badge bg-success">نشط</span>
                                <?php else: ?>
                                <span class="badge bg-danger">غير نشط</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['last_login']): ?>
                                <small><?php echo formatArabicDate($u['last_login'], 'Y-m-d H:i'); ?></small>
                                <?php else: ?>
                                <span class="text-muted">لم يدخل بعد</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-info" 
                                            onclick="viewUser(<?php echo $u['id']; ?>)" 
                                            title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if (hasPermission('users', 'update')): ?>
                                    <button class="btn btn-primary" 
                                            onclick="editUser(<?php echo $u['id']; ?>)" 
                                            title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('users', 'update') && $u['id'] !== $current_user['id']): ?>
                                        <?php if ($u['status'] === 'active'): ?>
                                        <form method="POST" action="/handlers/user_handler.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="redirect" value="/admin/user_management.php">
                                            <button type="submit" class="btn btn-warning" 
                                                    onclick="return confirm('هل أنت متأكد من إلغاء تفعيل هذا المستخدم؟')" 
                                                    title="إلغاء التفعيل">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" action="/handlers/user_handler.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="redirect" value="/admin/user_management.php">
                                            <button type="submit" class="btn btn-success" 
                                                    title="تفعيل">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('users', 'delete') && $u['id'] !== $current_user['id']): ?>
                                    <form method="POST" action="/handlers/user_handler.php" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="redirect" value="/admin/user_management.php">
                                        <button type="submit" class="btn btn-danger" 
                                                onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟ هذا الإجراء لا يمكن التراجع عنه.')" 
                                                title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <?php 
                $pagination_params = $_GET;
                echo createPagination($page, $total_pages, '/admin/user_management.php', $pagination_params);
                ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">لا توجد مستخدمين</h5>
                <p class="text-muted">لم يتم العثور على مستخدمين يطابقون معايير البحث</p>
                <?php if (hasPermission('users', 'create')): ?>
                <a href="/register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i>إضافة أول مستخدم
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">تفاصيل المستخدم</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="userDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">تحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">تعديل المستخدم</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="editUserContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">تحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="submitEditForm()">حفظ التغييرات</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewUser(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    const content = document.getElementById('userDetailsContent');
    
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">تحميل...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch(`/handlers/user_handler.php?action=get&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5 class="border-bottom pb-2">المعلومات الأساسية</h5>
                                    <table class="table table-borderless">
                                        <tr><td><strong>الاسم:</strong></td><td>${user.name}</td></tr>
                                        <tr><td><strong>البريد الإلكتروني:</strong></td><td>${user.email}</td></tr>
                                        <tr><td><strong>الهاتف:</strong></td><td>${user.phone || 'غير محدد'}</td></tr>
                                        <tr><td><strong>الرقم القومي:</strong></td><td>${user.national_id || 'غير محدد'}</td></tr>
                                        <tr><td><strong>العمر:</strong></td><td>${user.age || 'غير محدد'}</td></tr>
                                        <tr><td><strong>النوع:</strong></td><td>${user.gender === 'male' ? 'ذكر' : (user.gender === 'female' ? 'أنثى' : 'غير محدد')}</td></tr>
                                        <tr><td><strong>الحالة الاجتماعية:</strong></td><td>${getMaritalStatus(user.marital_status)}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="border-bottom pb-2">المعلومات الإضافية</h5>
                                    <table class="table table-borderless">
                                        <tr><td><strong>المحافظة:</strong></td><td>${user.governorate || 'غير محدد'}</td></tr>
                                        <tr><td><strong>الوظيفة:</strong></td><td>${user.job || 'غير محدد'}</td></tr>
                                        <tr><td><strong>حالة السمع:</strong></td><td>${getHearingStatus(user.hearing_status)}</td></tr>
                                        <tr><td><strong>مستوى لغة الإشارة:</strong></td><td>${getSignLanguageLevel(user.sign_language_level)}</td></tr>
                                        <tr><td><strong>فصيلة الدم:</strong></td><td>${user.blood_type || 'غير محدد'}</td></tr>
                                        <tr><td><strong>الدور:</strong></td><td><span class="badge bg-primary">${user.roles || user.role}</span></td></tr>
                                        <tr><td><strong>الحالة:</strong></td><td><span class="badge bg-${user.status === 'active' ? 'success' : 'danger'}">${user.status === 'active' ? 'نشط' : 'غير نشط'}</span></td></tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5 class="border-bottom pb-2">معلومات الطوارئ</h5>
                                    <table class="table table-borderless">
                                        <tr><td><strong>جهة الاتصال:</strong></td><td>${user.emergency_contact || 'غير محدد'}</td></tr>
                                        <tr><td><strong>هاتف الطوارئ:</strong></td><td>${user.emergency_phone || 'غير محدد'}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="border-bottom pb-2">المعلومات الطبية</h5>
                                    <table class="table table-borderless">
                                        <tr><td><strong>التاريخ الطبي:</strong></td><td>${user.medical_history || 'غير محدد'}</td></tr>
                                        <tr><td><strong>الحساسيات:</strong></td><td>${user.allergies || 'غير محدد'}</td></tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="border-bottom pb-2">معلومات النظام</h5>
                                    <table class="table table-borderless">
                                        <tr><td><strong>آخر دخول:</strong></td><td>${user.last_login || 'لم يدخل بعد'}</td></tr>
                                        <tr><td><strong>تاريخ التسجيل:</strong></td><td>${user.created_at}</td></tr>
                                        <tr><td><strong>آخر تحديث:</strong></td><td>${user.updated_at}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message || 'خطأ في تحميل البيانات'}
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    خطأ في الاتصال بالخادم
                </div>
            `;
        });
}

function getHearingStatus(status) {
    const statusMap = {
        'deaf': 'أصم',
        'hard_of_hearing': 'ضعيف السمع',
        'hearing': 'يسمع'
    };
    return statusMap[status] || 'غير محدد';
}

function getSignLanguageLevel(level) {
    const levelMap = {
        'beginner': 'مبتدئ',
        'intermediate': 'متوسط',
        'advanced': 'متقدم',
        'none': 'لا يعرف'
    };
    return levelMap[level] || 'غير محدد';
}

function getMaritalStatus(status) {
    const statusMap = {
        'single': 'أعزب',
        'married': 'متزوج',
        'divorced': 'مطلق',
        'widowed': 'أرمل'
    };
    return statusMap[status] || 'غير محدد';
}

function editUser(userId) {
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    const content = document.getElementById('editUserContent');
    
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">تحميل...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch(`/handlers/user_handler.php?action=get&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                content.innerHTML = `
                    <form id="editUserForm" method="POST" action="/handlers/user_handler.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" value="${user.id}">
                        <input type="hidden" name="redirect" value="/admin/user_management.php">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">الاسم الكامل</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" value="${user.name}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" value="${user.email}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_phone" class="form-label">الهاتف</label>
                                    <input type="tel" class="form-control" id="edit_phone" name="phone" value="${user.phone || ''}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_national_id" class="form-label">الرقم القومي</label>
                                    <input type="text" class="form-control" id="edit_national_id" name="national_id" value="${user.national_id || ''}">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_age" class="form-label">العمر</label>
                                    <input type="number" class="form-control" id="edit_age" name="age" value="${user.age || ''}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_gender" class="form-label">النوع</label>
                                    <select class="form-select" id="edit_gender" name="gender">
                                        <option value="">اختر النوع</option>
                                        <option value="male" ${user.gender === 'male' ? 'selected' : ''}>ذكر</option>
                                        <option value="female" ${user.gender === 'female' ? 'selected' : ''}>أنثى</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_governorate" class="form-label">المحافظة</label>
                                    <input type="text" class="form-control" id="edit_governorate" name="governorate" value="${user.governorate || ''}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_job" class="form-label">الوظيفة</label>
                                    <input type="text" class="form-control" id="edit_job" name="job" value="${user.job || ''}">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_hearing_status" class="form-label">حالة السمع</label>
                                    <select class="form-select" id="edit_hearing_status" name="hearing_status">
                                        <option value="deaf" ${user.hearing_status === 'deaf' ? 'selected' : ''}>أصم</option>
                                        <option value="hard_of_hearing" ${user.hearing_status === 'hard_of_hearing' ? 'selected' : ''}>ضعيف السمع</option>
                                        <option value="hearing" ${user.hearing_status === 'hearing' ? 'selected' : ''}>يسمع</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_sign_language_level" class="form-label">مستوى لغة الإشارة</label>
                                    <select class="form-select" id="edit_sign_language_level" name="sign_language_level">
                                        <option value="none" ${user.sign_language_level === 'none' ? 'selected' : ''}>لا يعرف</option>
                                        <option value="beginner" ${user.sign_language_level === 'beginner' ? 'selected' : ''}>مبتدئ</option>
                                        <option value="intermediate" ${user.sign_language_level === 'intermediate' ? 'selected' : ''}>متوسط</option>
                                        <option value="advanced" ${user.sign_language_level === 'advanced' ? 'selected' : ''}>متقدم</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_marital_status" class="form-label">الحالة الاجتماعية</label>
                                    <select class="form-select" id="edit_marital_status" name="marital_status">
                                        <option value="">اختر الحالة</option>
                                        <option value="single" ${user.marital_status === 'single' ? 'selected' : ''}>أعزب</option>
                                        <option value="married" ${user.marital_status === 'married' ? 'selected' : ''}>متزوج</option>
                                        <option value="divorced" ${user.marital_status === 'divorced' ? 'selected' : ''}>مطلق</option>
                                        <option value="widowed" ${user.marital_status === 'widowed' ? 'selected' : ''}>أرمل</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_blood_type" class="form-label">فصيلة الدم</label>
                                    <select class="form-select" id="edit_blood_type" name="blood_type">
                                        <option value="">اختر فصيلة الدم</option>
                                        <option value="A+" ${user.blood_type === 'A+' ? 'selected' : ''}>A+</option>
                                        <option value="A-" ${user.blood_type === 'A-' ? 'selected' : ''}>A-</option>
                                        <option value="B+" ${user.blood_type === 'B+' ? 'selected' : ''}>B+</option>
                                        <option value="B-" ${user.blood_type === 'B-' ? 'selected' : ''}>B-</option>
                                        <option value="AB+" ${user.blood_type === 'AB+' ? 'selected' : ''}>AB+</option>
                                        <option value="AB-" ${user.blood_type === 'AB-' ? 'selected' : ''}>AB-</option>
                                        <option value="O+" ${user.blood_type === 'O+' ? 'selected' : ''}>O+</option>
                                        <option value="O-" ${user.blood_type === 'O-' ? 'selected' : ''}>O-</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_emergency_contact" class="form-label">جهة اتصال الطوارئ</label>
                                    <input type="text" class="form-control" id="edit_emergency_contact" name="emergency_contact" value="${user.emergency_contact || ''}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_emergency_phone" class="form-label">هاتف الطوارئ</label>
                                    <input type="tel" class="form-control" id="edit_emergency_phone" name="emergency_phone" value="${user.emergency_phone || ''}">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_medical_history" class="form-label">التاريخ الطبي</label>
                                    <textarea class="form-control" id="edit_medical_history" name="medical_history" rows="3">${user.medical_history || ''}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_allergies" class="form-label">الحساسيات</label>
                                    <textarea class="form-control" id="edit_allergies" name="allergies" rows="3">${user.allergies || ''}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_role" class="form-label">الدور</label>
                                    <select class="form-select" id="edit_role" name="role">
                                        <?php foreach (USER_ROLES as $role_key => $role_name): ?>
                                        <option value="<?php echo $role_key; ?>" ${user.role === '<?php echo $role_key; ?>' ? 'selected' : ''}><?php echo $role_name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">الحالة</label>
                                    <select class="form-select" id="edit_status" name="status">
                                        <option value="active" ${user.status === 'active' ? 'selected' : ''}>نشط</option>
                                        <option value="inactive" ${user.status === 'inactive' ? 'selected' : ''}>غير نشط</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message || 'خطأ في تحميل البيانات'}
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    خطأ في الاتصال بالخادم
                </div>
            `;
        });
}

function submitEditForm() {
    document.getElementById('editUserForm').submit();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>