<?php
/**
 * RBAC Management Interface
 * Advanced Role-Based Access Control management
 */

// Handle form submissions BEFORE any output or includes

// Handle form submissions BEFORE any output or includes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only include session and logic, NOT header.php (which outputs HTML)
    require_once __DIR__ . '/../includes/session.php';
    require_once __DIR__ . '/../classes/RBAC.php';
    require_once __DIR__ . '/../classes/User.php';
    require_once __DIR__ . '/../includes/functions.php';
    requirePermission('rbac', 'manage');
    $rbac = new RBAC();
    $user = new User();
    try {
        requireCSRF();
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'assign_role':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $role_id = (int)($_POST['role_id'] ?? 0);
                if (!$user_id || !$role_id) {
                    throw new Exception('معرف المستخدم والدور مطلوبان');
                }
                $rbac->assignRoleToUser($user_id, $role_id);
                logActivity($current_user['id'], 'role_assigned', "تعيين دور للمستخدم", 'user_roles');
                setSuccessMessage('تم تعيين الدور بنجاح');
                break;
            case 'remove_role':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $role_id = (int)($_POST['role_id'] ?? 0);
                if (!$user_id || !$role_id) {
                    throw new Exception('معرف المستخدم والدور مطلوبان');
                }
                $rbac->removeRoleFromUser($user_id, $role_id);
                logActivity($current_user['id'], 'role_removed', "إزالة دور من المستخدم", 'user_roles');
                setSuccessMessage('تم إزالة الدور بنجاح');
                break;
            case 'assign_permission':
                $role_id = (int)($_POST['role_id'] ?? 0);
                $permission_id = (int)($_POST['permission_id'] ?? 0);
                $granted = isset($_POST['granted']) ? 1 : 0;
                if (!$role_id || !$permission_id) {
                    throw new Exception('معرف الدور والصلاحية مطلوبان');
                }
                $rbac->assignPermissionToRole($role_id, $permission_id, $granted);
                logActivity($current_user['id'], 'permission_assigned', "تعيين صلاحية للدور", 'role_permissions');
                setSuccessMessage('تم تعيين الصلاحية بنجاح');
                break;
        }
        header('Location: /admin/rbac_management.php');
        exit;
    } catch (Exception $e) {
        setErrorMessage($e->getMessage());
    }
}

// Now safe to include header and output
$page_title = 'إدارة الصلاحيات';
require_once __DIR__ . '/../includes/header.php';
requirePermission('rbac', 'manage');
$rbac = new RBAC();
$user = new User();
$roles = $rbac->getAllRoles();
$permissions = $rbac->getAllPermissions();
$users_result = $user->getAll(1, 100); // Get first 100 users
$users = $users_result['users'];
$permissions_by_feature = [];
foreach ($permissions as $permission) {
    $permissions_by_feature[$permission['feature']][] = $permission;
}
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-shield-alt me-2"></i><?php echo $page_title; ?></h2>
        <div>
            <a href="/admin/role_management.php" class="btn btn-outline-primary me-2">
                <i class="fas fa-user-tag me-1"></i>إدارة الأدوار
            </a>
            <a href="/admin/permission_management.php" class="btn btn-outline-secondary">
                <i class="fas fa-key me-1"></i>إدارة الصلاحيات
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <!-- User Role Assignment - Centered -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white text-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>تعيين الأدوار للمستخدمين</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="assign_role">
                        
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="user_id" class="form-label fw-bold">
                                    <i class="fas fa-user me-2"></i>المستخدم
                                </label>
                                <select name="user_id" id="user_id" class="form-select form-select-lg" required>
                                    <option value="">اختر المستخدم</option>
                                    <?php foreach ($users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>">
                                        <?php echo sanitizeInput($u['name'] . ' (' . $u['email'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label for="role_id" class="form-label fw-bold">
                                    <i class="fas fa-user-tag me-2"></i>الدور
                                </label>
                                <select name="role_id" id="role_id" class="form-select form-select-lg" required>
                                    <option value="">اختر الدور</option>
                                    <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>">
                                        <?php echo sanitizeInput($role['display_name']); ?>
                                        <?php if ($role['name'] === 'admin'): ?>
                                        (صلاحيات كاملة)
                                        <?php elseif ($role['name'] === 'viewer'): ?>
                                        (مشاهدة فقط)
                                        <?php else: ?>
                                        (مخصص)
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-check me-2"></i>تعيين الدور
                                </button>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="bg-light rounded p-3">
                                    <h6 class="mb-2 fw-bold">
                                        <i class="fas fa-info-circle me-2"></i>الدور الحالي
                                    </h6>
                                    <div id="user-roles-list" style="min-height: 40px;">
                                        <p class="text-muted mb-0">اختر مستخدماً لعرض دوره الحالي</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Roles and Permissions Matrix -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-table me-2"></i>مصفوفة الأدوار والصلاحيات</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>الصلاحية</th>
                            <?php foreach ($roles as $role): ?>
                            <th class="text-center" style="min-width: 100px;">
                                <?php echo sanitizeInput($role['display_name']); ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions_by_feature as $feature => $feature_permissions): ?>
                        <tr class="table-secondary">
                            <td colspan="<?php echo count($roles) + 1; ?>">
                                <strong><?php echo SYSTEM_FEATURES[$feature] ?? $feature; ?></strong>
                            </td>
                        </tr>
                        <?php foreach ($feature_permissions as $permission): ?>
                        <tr>
                            <td><?php echo SYSTEM_ACTIONS[$permission['action']] ?? $permission['action']; ?></td>
                            <?php foreach ($roles as $role): ?>
                            <td class="text-center">
                                <?php 
                                $has_permission = $rbac->roleHasPermission($role['id'], $permission['feature'], $permission['action']);
                                if ($has_permission): 
                                ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i>
                                </span>
                                <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="remove_permission">
                                    <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                    <input type="hidden" name="permission_id" value="<?php echo $permission['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger ms-1" title="إزالة">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-times"></i>
                                </span>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="assign_permission">
                                    <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                    <input type="hidden" name="permission_id" value="<?php echo $permission['id']; ?>">
                                    <input type="hidden" name="granted" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-success ms-1" title="إضافة">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Load user roles when user is selected
document.getElementById('user_id').addEventListener('change', function() {
    const userId = this.value;
    const container = document.getElementById('user-roles-list');
    
    if (!userId) {
        container.innerHTML = '<p class="text-muted small mb-0">اختر مستخدماً لعرض أدواره</p>';
        return;
    }
    
    fetch(`/handlers/user_handler.php?action=get&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user.roles) {
                const roles = data.user.roles.split(', ');
                let html = '';
                
                roles.forEach(role => {
                    if (role.trim()) {
                        html += `
                            <span class="badge bg-primary me-2 mb-2">${role}</span>
                        `;
                    }
                });
                
                if (!html) {
                    html = '<p class="text-muted small mb-0">لا توجد أدوار مُعينة</p>';
                }
                
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-danger small mb-0">خطأ في تحميل البيانات</p>';
            }
        })
        .catch(error => {
            container.innerHTML = '<p class="text-danger small mb-0">خطأ في تحميل البيانات</p>';
        });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
