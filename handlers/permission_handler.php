<?php
// handlers/permission_handler.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/RBAC.php';
require_once __DIR__ . '/../classes/User.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['action'])) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

$action = $_GET['action'];
$rbac = new RBAC();
$user = new User();

if ($action === 'get_roles_for_permission') {
    $permission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$permission_id) {
        echo json_encode(['success' => false, 'error' => 'Permission ID required']);
        exit;
    }
    $permission = $rbac->getPermissionById($permission_id);
    if (!$permission) {
        echo json_encode(['success' => false, 'error' => 'Permission not found']);
        exit;
    }
    $roles = $rbac->getRolesWithPermission($permission['feature'], $permission['action']);
    echo json_encode([
        'success' => true,
        'permission' => [
            'id' => $permission['id'],
            'feature' => $permission['feature'],
            'action' => $permission['action'],
        ],
        'roles' => $roles
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
exit;
