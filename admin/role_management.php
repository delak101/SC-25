<?php
/**
 * Role Management Interface
 * Create and manage system roles
 */


// Handle form submissions BEFORE any output or includes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/session.php';
    require_once __DIR__ . '/../classes/RBAC.php';
    require_once __DIR__ . '/../includes/functions.php';
    requirePermission('rbac', 'manage');
    $rbac = new RBAC();
    try {
        requireCSRF();
        $action = $_POST['action'] ?? '';
        if ($action === 'create_role') {
            $name = sanitizeInput($_POST['name'] ?? '');
            $display_name = sanitizeInput($_POST['display_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $parent_role_id = !empty($_POST['parent_role_id']) ? (int)$_POST['parent_role_id'] : null;
            if (empty($name) || empty($display_name)) {
                throw new Exception('اسم الدور واسم العرض مطلوبان');
            }
            // Validate name format (alphanumeric and underscore only)
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                throw new Exception('اسم الدور يجب أن يحتوي على أحرف وأرقام فقط');
            }
            $role_id = $rbac->createRole($name, $display_name, $description, $parent_role_id);
            logActivity($current_user['id'], 'role_created', "إنشاء دور: $display_name", 'roles', $role_id);
            setSuccessMessage('تم إنشاء الدور بنجاح');
        }
        header('Location: /admin/role_management.php');
        exit;
    } catch (Exception $e) {
        setErrorMessage($e->getMessage());
    }
}

$page_title = 'إدارة الأدوار';
require_once __DIR__ . '/../includes/header.php';
requirePermission('rbac', 'manage');
$rbac = new RBAC();
// Get all roles
$roles = $rbac->getAllRoles();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-tag me-2"></i><?php echo $page_title; ?></h2>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                <i class="fas fa-plus me-1"></i>إنشاء دور جديد
            </button>
            <a href="/admin/rbac_management.php" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i>العودة لإدارة الصلاحيات
            </a>
        </div>
    </div>

    <!-- Roles List -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list me-2"></i>قائمة الأدوار</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>اسم الدور</th>
                            <th>اسم العرض</th>
                            <th>الوصف</th>
                            <th>الدور الأب</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?php echo $role['id']; ?></td>
                            <td>
                                <code><?php echo sanitizeInput($role['name']); ?></code>
                            </td>
                            <td>
                                <strong><?php echo sanitizeInput($role['display_name']); ?></strong>
                            </td>
                            <td>
                                <?php echo sanitizeInput($role['description'] ?: 'لا يوجد وصف'); ?>
                            </td>
                            <td>
                                <?php if ($role['parent_name']): ?>
                                <span class="badge bg-info">
                                    <?php echo sanitizeInput($role['parent_name']); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">لا يوجد</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($role['status'] === 'active'): ?>
                                <span class="badge bg-success">نشط</span>
                                <?php else: ?>
                                <span class="badge bg-danger">غير نشط</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo formatArabicDate($role['created_at']); ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" 
                                            onclick="viewRolePermissions(<?php echo $role['id']; ?>)" 
                                            title="عرض الصلاحيات">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" 
                                            onclick="editRole(<?php echo $role['id']; ?>)" 
                                            title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if (!in_array($role['name'], ['admin', 'user'])): ?>
                                    <button class="btn btn-outline-danger" 
                                            onclick="deleteRole(<?php echo $role['id']; ?>)" 
                                            title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($roles)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-user-tag fa-3x text-muted mb-3"></i>
                    <p class="text-muted">لا توجد أدوار في النظام</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Role Modal -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إنشاء دور جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create_role">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">اسم الدور <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required
                               pattern="[a-zA-Z0-9_]+" 
                               title="أحرف وأرقام فقط"
                               placeholder="مثال: custom_role">
                        <div class="form-text">أحرف وأرقام و _ فقط</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_name" class="form-label">اسم العرض <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="display_name" name="display_name" required
                               placeholder="مثال: دور مخصص">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">الوصف</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="وصف مختصر عن الدور وصلاحياته"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_role_id" class="form-label">الدور الأب (اختياري)</label>
                        <select class="form-select" id="parent_role_id" name="parent_role_id">
                            <option value="">لا يوجد دور أب</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>">
                                <?php echo sanitizeInput($role['display_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">سيرث الدور الجديد صلاحيات الدور الأب</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إنشاء الدور</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Role Permissions Modal -->
<div class="modal fade" id="rolePermissionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">صلاحيات الدور</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="rolePermissionsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">تحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewRolePermissions(roleId) {
    const modal = new bootstrap.Modal(document.getElementById('rolePermissionsModal'));
    const content = document.getElementById('rolePermissionsContent');
    
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">تحميل...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Here you would fetch role permissions via AJAX
    // For now, showing placeholder
    setTimeout(() => {
        content.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                هذه الميزة قيد التطوير. يمكنك استخدام مصفوفة الأدوار والصلاحيات في الصفحة الرئيسية.
            </div>
        `;
    }, 1000);
}


// Edit Role Modal logic
let editRoleModal = null;
let editRoleForm = null;
let editRoleId = null;

function editRole(roleId) {
    // Fetch role info via AJAX
    fetch(`/handlers/role_handler.php?action=get_role&role_id=${roleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.role) {
                showEditRoleModal(data.role);
            } else {
                showAlert(data.error || 'تعذر تحميل بيانات الدور', 'danger');
            }
        })
        .catch(() => {
            showAlert('تعذر الاتصال بالخادم. حاول مرة أخرى.', 'danger');
        });
}

function showEditRoleModal(role) {
    // Create modal if not exists
    if (!editRoleModal) {
        const modalHtml = `
        <div class="modal fade" id="editRoleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل الدور</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editRoleForm">
                        <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="role_id" id="edit_role_id">
                            <div class="mb-3">ٍ
                                <label class="form-label">اسم العرض <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="display_name" id="edit_display_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الوصف</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        editRoleModal = new bootstrap.Modal(document.getElementById('editRoleModal'));
        editRoleForm = document.getElementById('editRoleForm');
        editRoleForm.addEventListener('submit', submitEditRoleForm);
    }
    // Fill form
    document.getElementById('edit_role_id').value = role.id;
    document.getElementById('edit_display_name').value = role.display_name;
    document.getElementById('edit_description').value = role.description || '';
    editRoleModal.show();
}

function submitEditRoleForm(e) {
    e.preventDefault();
    const formData = new FormData(editRoleForm);
    fetch('/handlers/role_handler.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('تم تحديث الدور بنجاح', 'success');
            editRoleModal.hide();
            setTimeout(() => { location.reload(); }, 1000);
        } else {
            showAlert(data.error || 'حدث خطأ أثناء تحديث الدور', 'danger');
        }
    })
    .catch(() => {
        showAlert('تعذر الاتصال بالخادم. حاول مرة أخرى.', 'danger');
    });
}

function deleteRole(roleId) {
    if (confirm('هل أنت متأكد من حذف هذا الدور؟')) {
        // AJAX request to delete the role
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        fetch('/handlers/role_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_role&role_id=${encodeURIComponent(roleId)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(async response => {
            let text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                showAlert('خطأ في الاستجابة من الخادم: ' + text, 'danger');
                return;
            }
            if (data.success) {
                // Remove the role row from the table
                const row = document.querySelector(`button[onclick=\"deleteRole(${roleId})\"]`).closest('tr');
                if (row) row.remove();
                showAlert('تم حذف الدور بنجاح', 'success');
            } else {
                showAlert(data.message || 'حدث خطأ أثناء حذف الدور', 'danger');
            }
        })
        .catch((err) => {
            showAlert('تعذر الاتصال بالخادم. حاول مرة أخرى. ' + err, 'danger');
        });
    }
}

// Helper to show alerts
function showAlert(message, type = 'info') {
    let alertDiv = document.getElementById('roleAlertBox');
    if (!alertDiv) {
        alertDiv = document.createElement('div');
        alertDiv.id = 'roleAlertBox';
        alertDiv.className = 'alert alert-' + type + ' mt-3';
        const container = document.querySelector('.container-fluid');
        if (container) container.prepend(alertDiv);
    }
    alertDiv.className = 'alert alert-' + type + ' mt-3';
    alertDiv.innerHTML = `<i class="fas fa-info-circle me-2"></i>${message}`;
    setTimeout(() => { if (alertDiv) alertDiv.remove(); }, 4000);
}

// Auto-generate name from display nameplease
document.addEventListener('DOMContentLoaded', function() {
    var displayNameInput = document.getElementById('display_name');
    var nameInput = document.getElementById('name');
    if (displayNameInput && nameInput) {
        displayNameInput.addEventListener('input', function() {
            const displayName = this.value;
            // Convert to lowercase, replace spaces with underscores, remove special characters
            const generatedName = displayName
                .toLowerCase()
                .replace(/\s+/g, '_')
                .replace(/[^a-z0-9_]/g, '');
            if (!nameInput.value || nameInput.dataset.autoGenerated) {
                nameInput.value = generatedName;
                nameInput.dataset.autoGenerated = 'true';
            }
        });
        nameInput.addEventListener('input', function() {
            delete this.dataset.autoGenerated;
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
