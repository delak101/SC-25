<?php
/**
 * Permission Management Interface
 * Create and manage system permissions
 */

$page_title = 'إدارة الصلاحيات';
require_once __DIR__ . '/../includes/header.php';

// Require RBAC management permission
requirePermission('rbac', 'manage');

$rbac = new RBAC();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireCSRF();
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create_permission') {
            $feature = sanitizeInput($_POST['feature'] ?? '');
            $action_name = sanitizeInput($_POST['action_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (empty($feature) || empty($action_name)) {
                throw new Exception('الميزة والإجراء مطلوبان');
            }
            
            // Validate feature and action names
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $feature) || !preg_match('/^[a-zA-Z0-9_]+$/', $action_name)) {
                throw new Exception('الميزة والإجراء يجب أن يحتويا على أحرف وأرقام فقط');
            }
            
            $permission_id = $rbac->createPermission($feature, $action_name, $description);
            
            logActivity($current_user['id'], 'permission_created', "إنشاء صلاحية: $feature:$action_name", 'permissions', $permission_id);
            setSuccessMessage('تم إنشاء الصلاحية بنجاح');
        }
        
        header('Location: /admin/permission_management.php');
        exit;
        
    } catch (Exception $e) {
        setErrorMessage($e->getMessage());
    }
}

// Get all permissions
$permissions = $rbac->getAllPermissions();

// Group permissions by feature
$permissions_by_feature = [];
foreach ($permissions as $permission) {
    $permissions_by_feature[$permission['feature']][] = $permission;
}
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-key me-2"></i><?php echo $page_title; ?></h2>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                <i class="fas fa-plus me-1"></i>إنشاء صلاحية جديدة
            </button>
            <a href="/admin/rbac_management.php" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i>العودة لإدارة الصلاحيات
            </a>
        </div>
    </div>

    <!-- System Features Overview -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-cogs me-2"></i>ميزات النظام</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach (SYSTEM_FEATURES as $feature_key => $feature_name): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><?php echo $feature_name; ?></span>
                                <code class="small"><?php echo $feature_key; ?></code>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-tasks me-2"></i>إجراءات النظام</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach (SYSTEM_ACTIONS as $action_key => $action_name): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><?php echo $action_name; ?></span>
                                <code class="small"><?php echo $action_key; ?></code>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions List -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list me-2"></i>قائمة الصلاحيات</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($permissions_by_feature)): ?>
                <?php foreach ($permissions_by_feature as $feature => $feature_permissions): ?>
                <div class="mb-4">
                    <h6 class="border-bottom pb-2">
                        <i class="fas fa-folder me-2"></i>
                        <?php echo SYSTEM_FEATURES[$feature] ?? $feature; ?>
                        <small class="text-muted">(<code><?php echo $feature; ?></code>)</small>
                    </h6>
                    
                    <div class="row">
                        <?php foreach ($feature_permissions as $permission): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card card-sm border">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">
                                            <?php echo SYSTEM_ACTIONS[$permission['action']] ?? $permission['action']; ?>
                                        </h6>
                                        <small class="text-muted">#<?php echo $permission['id']; ?></small>
                                    </div>
                                    
                                    <p class="card-text small text-muted mb-2">
                                        <code><?php echo $permission['feature'] . ':' . $permission['action']; ?></code>
                                    </p>
                                    
                                    <?php if ($permission['description']): ?>
                                    <p class="card-text small">
                                        <?php echo sanitizeInput($permission['description']); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <?php echo formatArabicDate($permission['created_at']); ?>
                                        </small>
                                        
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-sm" 
                                                    onclick="viewPermissionRoles(<?php echo $permission['id']; ?>)" 
                                                    title="عرض الأدوار">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" 
                                                    onclick="editPermission(<?php echo $permission['id']; ?>)" 
                                                    title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-key fa-3x text-muted mb-3"></i>
                    <p class="text-muted">لا توجد صلاحيات في النظام</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                        إنشاء أول صلاحية
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Permission Modal -->
<div class="modal fade" id="createPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إنشاء صلاحية جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create_permission">
                    
                    <div class="mb-3">
                        <label for="feature" class="form-label">الميزة <span class="text-danger">*</span></label>
                        <select class="form-select" id="feature" name="feature" required>
                            <option value="">اختر الميزة</option>
                            <?php foreach (SYSTEM_FEATURES as $feature_key => $feature_name): ?>
                            <option value="<?php echo $feature_key; ?>">
                                <?php echo $feature_name; ?> (<?php echo $feature_key; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">أو يمكنك كتابة ميزة جديدة</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="custom_feature" class="form-label">ميزة مخصصة</label>
                        <input type="text" class="form-control" id="custom_feature" 
                               pattern="[a-zA-Z0-9_]+" 
                               title="أحرف وأرقام و _ فقط"
                               placeholder="مثال: custom_feature">
                        <div class="form-text">اتركه فارغاً لاستخدام الميزة المحددة أعلاه</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="action_name" class="form-label">الإجراء <span class="text-danger">*</span></label>
                        <select class="form-select" id="action_name" name="action_name" required>
                            <option value="">اختر الإجراء</option>
                            <?php foreach (SYSTEM_ACTIONS as $action_key => $action_name): ?>
                            <option value="<?php echo $action_key; ?>">
                                <?php echo $action_name; ?> (<?php echo $action_key; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">أو يمكنك كتابة إجراء جديد</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="custom_action" class="form-label">إجراء مخصص</label>
                        <input type="text" class="form-control" id="custom_action" 
                               pattern="[a-zA-Z0-9_]+" 
                               title="أحرف وأرقام و _ فقط"
                               placeholder="مثال: custom_action">
                        <div class="form-text">اتركه فارغاً لاستخدام الإجراء المحدد أعلاه</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">الوصف</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="وصف مختصر عن الصلاحية"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>مثال:</strong> للسماح للأطباء بإنشاء المواعيد، استخدم الميزة "appointments" والإجراء "create"
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إنشاء الصلاحية</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Permission Roles Modal -->
<div class="modal fade" id="permissionRolesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">الأدوار التي تملك هذه الصلاحية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="permissionRolesContent">
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
function viewPermissionRoles(permissionId) {
    const modal = new bootstrap.Modal(document.getElementById('permissionRolesModal'));
    const content = document.getElementById('permissionRolesContent');
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">تحميل...</span>
            </div>
        </div>
    `;
    modal.show();
    fetch(`/handlers/permission_handler.php?action=get_roles_for_permission&id=${permissionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.roles)) {
                if (data.roles.length === 0) {
                    content.innerHTML = `<div class='alert alert-warning text-center'>لا يوجد أي دور يملك هذه الصلاحية.</div>`;
                } else {
                    let html = `<ul class='list-group'>`;
                    data.roles.forEach(role => {
                        html += `<li class='list-group-item'>
                            <strong>${role.display_name}</strong>
                            ${role.description ? `<span class='text-muted small ms-2'>${role.description}</span>` : ''}
                        </li>`;
                    });
                    html += `</ul>`;
                    content.innerHTML = html;
                }
            } else {
                content.innerHTML = `<div class='alert alert-danger text-center'>تعذر تحميل الأدوار: ${data.error || 'خطأ غير معروف'}</div>`;
            }
        })
        .catch(() => {
            content.innerHTML = `<div class='alert alert-danger text-center'>تعذر الاتصال بالخادم</div>`;
        });
}

function editPermission(permissionId) {
    alert('ميزة التعديل قيد التطوير');
}

// Handle custom feature/action inputs
document.getElementById('custom_feature').addEventListener('input', function() {
    const featureSelect = document.getElementById('feature');
    if (this.value.trim()) {
        featureSelect.value = '';
        featureSelect.required = false;
        this.name = 'feature';
    } else {
        featureSelect.required = true;
        this.name = 'custom_feature';
    }
});

document.getElementById('feature').addEventListener('change', function() {
    const customFeature = document.getElementById('custom_feature');
    if (this.value) {
        customFeature.value = '';
        customFeature.name = 'custom_feature';
    }
});

document.getElementById('custom_action').addEventListener('input', function() {
    const actionSelect = document.getElementById('action_name');
    if (this.value.trim()) {
        actionSelect.value = '';
        actionSelect.required = false;
        this.name = 'action_name';
    } else {
        actionSelect.required = true;
        this.name = 'custom_action';
    }
});

document.getElementById('action_name').addEventListener('change', function() {
    const customAction = document.getElementById('custom_action');
    if (this.value) {
        customAction.value = '';
        customAction.name = 'custom_action';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
