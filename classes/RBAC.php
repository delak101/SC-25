<?php
require_once __DIR__ . '/Database.php';

/**
 * Role-Based Access Control (RBAC) Class
 * Advanced RBAC system with dynamic rule creation
 */
class RBAC {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new role
     */
    public function createRole($name, $display_name, $description = '', $parent_role_id = null) {
        try {
            // Check if role already exists
            if ($this->getRoleByName($name)) {
                throw new Exception('الدور موجود بالفعل');
            }

            $sql = "INSERT INTO roles (name, display_name, description, parent_role_id, created_at) 
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
            $this->db->prepare($sql);
            $this->db->execute([$name, $display_name, $description, $parent_role_id]);

            return $this->db->lastInsertId();

        } catch (Exception $e) {
            error_log("Create role failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new permission
     */
        /**
    /**
     * Get permission by ID
     */
    public function getPermissionById($id) {
        try {
            $sql = "SELECT * FROM permissions WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$id]);
            return $this->db->fetch();
        } catch (Exception $e) {
            error_log("Get permission by ID failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all roles that have a given permission (direct or inherited)
     */
    public function getRolesWithPermission($feature, $action) {
        $roles_with_permission = [];
        $all_roles = $this->getAllRoles();
        foreach ($all_roles as $role) {
            $direct = false;
            // Check direct assignment
            $sql = "SELECT rp.granted FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = ? AND p.feature = ? AND p.action = ?";
            $this->db->prepare($sql);
            $this->db->execute([$role['id'], $feature, $action]);
            $result = $this->db->fetch();
            if ($result && $result['granted']) { $direct = true; }
            if ($this->roleHasPermission($role['id'], $feature, $action)) {
                $roles_with_permission[] = [
                    'id' => $role['id'],
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                    'inherited' => !$direct
                ];
            }
        }
        return $roles_with_permission;
    }

    public function createPermission($feature, $action, $description = '') {
        try {
            // Check if permission already exists
            if ($this->getPermissionByFeatureAction($feature, $action)) {
                throw new Exception('الصلاحية موجودة بالفعل');
            }

            $sql = "INSERT INTO permissions (feature, action, description, created_at) 
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
            $this->db->prepare($sql);
            $this->db->execute([$feature, $action, $description]);

            return $this->db->lastInsertId();

        } catch (Exception $e) {
            error_log("Create permission failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign permission to role
     */
    public function assignPermissionToRole($role_id, $permission_id, $granted = true) {
        try {
            // Check if assignment already exists
            $existing = $this->getRolePermission($role_id, $permission_id);
            
            if ($existing) {
                // Update existing assignment
                $sql = "UPDATE role_permissions SET granted = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE role_id = ? AND permission_id = ?";
                $this->db->prepare($sql);
                $this->db->execute([$granted ? 1 : 0, $role_id, $permission_id]);
            } else {
                // Create new assignment
                $sql = "INSERT INTO role_permissions (role_id, permission_id, granted, created_at) 
                        VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
                $this->db->prepare($sql);
                $this->db->execute([$role_id, $permission_id, $granted ? 1 : 0]);
            }

            return true;

        } catch (Exception $e) {
            error_log("Assign permission to role failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser($user_id, $role_id) {
        try {
            // Check if assignment already exists
            $existing = $this->getUserRole($user_id, $role_id);
            
            if (!$existing) {
                $sql = "INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
                $this->db->prepare($sql);
                $this->db->execute([$user_id, $role_id]);
            }

            return true;

        } catch (Exception $e) {
            error_log("Assign role to user failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($user_id, $feature, $action) {
        try {
            // Get user roles
            $user_roles = $this->getUserRoles($user_id);
            
            if (empty($user_roles)) {
                return false;
            }

            // Check each role for the permission
            foreach ($user_roles as $role) {
                if ($this->roleHasPermission($role['id'], $feature, $action)) {
                    return true;
                }
            }

            return false;

        } catch (Exception $e) {
            error_log("Check permission failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if role has permission (including inheritance)
     */
    public function roleHasPermission($role_id, $feature, $action) {
        try {
            // Direct permission check
            $sql = "SELECT rp.granted FROM role_permissions rp
                    JOIN permissions p ON rp.permission_id = p.id
                    WHERE rp.role_id = ? AND p.feature = ? AND p.action = ?";
            $this->db->prepare($sql);
            $this->db->execute([$role_id, $feature, $action]);
            
            $result = $this->db->fetch();
            if ($result) {
                return (bool)$result['granted'];
            }

            // Check parent role permissions (inheritance)
            $role = $this->getRoleById($role_id);
            if ($role && $role['parent_role_id']) {
                return $this->roleHasPermission($role['parent_role_id'], $feature, $action);
            }

            return false;

        } catch (Exception $e) {
            error_log("Role permission check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user roles
     */
    public function getUserRoles($user_id) {
        try {
            $sql = "SELECT r.* FROM user_roles ur
                    JOIN roles r ON ur.role_id = r.id
                    WHERE ur.user_id = ? AND r.status = 'active'
                    ORDER BY r.name";
            $this->db->prepare($sql);
            $this->db->execute([$user_id]);

            return $this->db->fetchAll();

        } catch (Exception $e) {
            error_log("Get user roles failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get role permissions
     */
    public function getRolePermissions($role_id, $include_inherited = true) {
        try {
            $permissions = [];

            // Direct permissions
            $sql = "SELECT p.*, rp.granted FROM role_permissions rp
                    JOIN permissions p ON rp.permission_id = p.id
                    WHERE rp.role_id = ?
                    ORDER BY p.feature, p.action";
            $this->db->prepare($sql);
            $this->db->execute([$role_id]);

            $direct_permissions = $this->db->fetchAll();
            foreach ($direct_permissions as $permission) {
                $key = $permission['feature'] . ':' . $permission['action'];
                $permissions[$key] = $permission;
            }

            // Inherited permissions from parent roles
            if ($include_inherited) {
                $role = $this->getRoleById($role_id);
                if ($role && $role['parent_role_id']) {
                    $inherited = $this->getRolePermissions($role['parent_role_id'], true);
                    
                    // Add inherited permissions that are not overridden
                    foreach ($inherited as $key => $permission) {
                        if (!isset($permissions[$key])) {
                            $permission['inherited'] = true;
                            $permissions[$key] = $permission;
                        }
                    }
                }
            }

            return array_values($permissions);

        } catch (Exception $e) {
            error_log("Get role permissions failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all roles
     */
    public function getAllRoles() {
        try {
            $sql = "SELECT r.*, pr.display_name as parent_name FROM roles r
                    LEFT JOIN roles pr ON r.parent_role_id = pr.id
                    ORDER BY r.name";
            $this->db->prepare($sql);
            $this->db->execute();

            return $this->db->fetchAll();

        } catch (Exception $e) {
            error_log("Get all roles failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions() {
        try {
            $sql = "SELECT * FROM permissions ORDER BY feature, action";
            $this->db->prepare($sql);
            $this->db->execute();

            return $this->db->fetchAll();

        } catch (Exception $e) {
            error_log("Get all permissions failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get role by name
     */
    public function getRoleByName($name) {
        try {
            $sql = "SELECT * FROM roles WHERE name = ?";
            $this->db->prepare($sql);
            $this->db->execute([$name]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get role by name failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get role by ID
     */
    public function getRoleById($id) {
        try {
            $sql = "SELECT * FROM roles WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$id]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get role by ID failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get permission by feature and action
     */
    public function getPermissionByFeatureAction($feature, $action) {
        try {
            $sql = "SELECT * FROM permissions WHERE feature = ? AND action = ?";
            $this->db->prepare($sql);
            $this->db->execute([$feature, $action]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get permission by feature/action failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get role permission assignment
     */
    private function getRolePermission($role_id, $permission_id) {
        try {
            $sql = "SELECT * FROM role_permissions WHERE role_id = ? AND permission_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$role_id, $permission_id]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get role permission failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user role assignment
     */
    private function getUserRole($user_id, $role_id) {
        try {
            $sql = "SELECT * FROM user_roles WHERE user_id = ? AND role_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$user_id, $role_id]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get user role failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser($user_id, $role_id) {
        try {
            $sql = "DELETE FROM user_roles WHERE user_id = ? AND role_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$user_id, $role_id]);

            return true;

        } catch (Exception $e) {
            error_log("Remove role from user failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove permission from role
     */
    public function removePermissionFromRole($role_id, $permission_id) {
        try {
            $sql = "DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$role_id, $permission_id]);

            return true;

        } catch (Exception $e) {
            error_log("Remove permission from role failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize default roles and permissions
     */
    public function initializeDefaults() {
        try {
            $this->db->beginTransaction();

            // Create default roles
            $admin_role_id = $this->createDefaultRole('admin', 'مدير النظام', 'صلاحية كاملة على النظام');
            $doctor_role_id = $this->createDefaultRole('doctor', 'دكتور', 'إدارة المرضى والمواعيد');
            $patient_role_id = $this->createDefaultRole('patient', 'مريض', 'عرض المواعيد والملف الطبي');
            $secretary_role_id = $this->createDefaultRole('secretary', 'سكرتارية', 'إدارة المواعيد والفيديوهات');
            $pharmacy_role_id = $this->createDefaultRole('pharmacy', 'صيدلي', 'إدارة الأدوية والوصفات');
            $reception_role_id = $this->createDefaultRole('reception', 'استقبال', 'إدارة استقبال المرضى');

            // Create default permissions
            foreach (SYSTEM_FEATURES as $feature => $feature_name) {
                foreach (SYSTEM_ACTIONS as $action => $action_name) {
                    $this->createDefaultPermission($feature, $action, "$action_name $feature_name");
                }
            }

            // Assign all permissions to admin role
            $permissions = $this->getAllPermissions();
            foreach ($permissions as $permission) {
                $this->assignPermissionToRole($admin_role_id, $permission['id'], true);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Initialize defaults failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create default role if not exists
     */
    private function createDefaultRole($name, $display_name, $description) {
        $existing = $this->getRoleByName($name);
        if ($existing) {
            return $existing['id'];
        }
        return $this->createRole($name, $display_name, $description);
    }

    /**
     * Create default permission if not exists
     */
    private function createDefaultPermission($feature, $action, $description) {
        $existing = $this->getPermissionByFeatureAction($feature, $action);
        if ($existing) {
            return $existing['id'];
        }
        return $this->createPermission($feature, $action, $description);
    }

    /**
     * Update role display name and description
     */
    public function updateRole($role_id, $display_name, $description) {
        try {
            $sql = "UPDATE roles SET display_name = ?, description = ? WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$display_name, $description, $role_id]);
            return true;
        } catch (Exception $e) {
            error_log("Update role failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a role by ID
     */
    public function deleteRole($role_id) {
        try {
            // Remove all user-role assignments
            $sql = "DELETE FROM user_roles WHERE role_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$role_id]);

            // Remove all role-permission assignments
            $sql = "DELETE FROM role_permissions WHERE role_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$role_id]);

            // Remove as parent from other roles (set parent_role_id to NULL)
            $sql = "UPDATE roles SET parent_role_id = NULL WHERE parent_role_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$role_id]);

            // Finally, delete the role itself
            $sql = "DELETE FROM roles WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$role_id]);

            return true;
        } catch (Exception $e) {
            error_log("Delete role failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
