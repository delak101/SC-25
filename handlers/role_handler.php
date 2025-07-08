<?php
// handlers/role_handler.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/RBAC.php';

header('Content-Type: application/json; charset=utf-8');

// Support both GET and POST for different actions
// Always set $action from POST or GET
$rbac = new RBAC();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'delete_role') {
        require_once __DIR__ . '/../includes/functions.php';
        try {
            requireCSRF();
            if (!isAuthenticated() || !hasPermission('rbac', 'manage')) {
                echo json_encode(['success' => false, 'message' => 'غير مصرح']);
                exit;
            }
            $role_id = (int)($_POST['role_id'] ?? 0);
            if (!$role_id) {
                echo json_encode(['success' => false, 'message' => 'معرف الدور مفقود']);
                exit;
            }
            $role = $rbac->getRoleById($role_id);
            if (!$role) {
                echo json_encode(['success' => false, 'message' => 'الدور غير موجود']);
                exit;
            }
            if (in_array($role['name'], ['admin', 'user'])) {
                echo json_encode(['success' => false, 'message' => 'لا يمكن حذف هذا الدور']);
                exit;
            }
            $user = getCurrentUser();
            $ok = $rbac->deleteRole($role_id);
            if ($ok) {
                logActivity($user['id'], 'role_deleted', 'حذف دور: ' . $role['display_name'], 'roles', $role_id);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'فشل حذف الدور']);
            }
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'update_role') {
        $role_id = (int)($_POST['role_id'] ?? 0);
        $display_name = trim($_POST['display_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (!$role_id || !$display_name) {
            echo json_encode(['success' => false, 'error' => 'معرف الدور واسم العرض مطلوبان']);
            exit;
        }
        $role = $rbac->getRoleById($role_id);
        if (!$role) {
            echo json_encode(['success' => false, 'error' => 'الدور غير موجود']);
            exit;
        }
        $ok = $rbac->updateRole($role_id, $display_name, $description);
        echo json_encode(['success' => $ok]);
        exit;
    }
    if ($action === 'toggle_permission') {
        $role_id = (int)($_POST['role_id'] ?? 0);
        $permission_id = (int)($_POST['permission_id'] ?? 0);
        $grant = isset($_POST['grant']) ? (bool)$_POST['grant'] : false;
        if (!$role_id || !$permission_id) {
            echo json_encode(['success' => false, 'error' => 'معرف الدور أو الصلاحية مفقود']);
            exit;
        }
        $rbac->assignPermissionToRole($role_id, $permission_id, $grant);
        echo json_encode(['success' => true]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'get_permissions') {
        $role_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$role_id) {
            echo json_encode(['success' => false, 'message' => 'No role id']);
            exit;
        }
        // Get permissions for the role, including inherited
        $permissions = $rbac->getRolePermissions($role_id, true);
        // Only return relevant fields
        $result = array_map(function($perm) {
            return [
                'feature' => $perm['feature'],
                'action' => $perm['action'],
                'inherited' => !empty($perm['inherited'])
            ];
        }, $permissions);
        echo json_encode(['success' => true, 'permissions' => $result]);
        exit;
    }
    if ($action === 'get_role') {
        $role_id = (int)($_GET['role_id'] ?? 0);
        $role = $rbac->getRoleById($role_id);
        if (!$role) {
            echo json_encode(['success' => false, 'error' => 'الدور غير موجود']);
            exit;
        }
        echo json_encode(['success' => true, 'role' => $role]);
        exit;
    }
    if ($action === 'get_permissions_matrix') {
        $role_id = (int)($_GET['role_id'] ?? 0);
        $all_permissions = $rbac->getAllPermissions();
        $role_permissions = $rbac->getRolePermissions($role_id, true);
        $role_perm_ids = array_column($role_permissions, 'id');
        echo json_encode([
            'success' => true,
            'permissions' => $all_permissions,
            'role_permissions' => $role_perm_ids
        ]);
        exit;
    }
}
echo json_encode(['success' => false, 'error' => 'طلب غير صالح']);
