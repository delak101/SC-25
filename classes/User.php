<?php
require_once __DIR__ . '/Database.php';

/**
 * User Class
 * Handles user management operations
 */
class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new user
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO users (
                name, email, password, phone, national_id, national_id_image,
                gender, hearing_status, marital_status, sign_language_level,
                governorate, age, job, service_card_image, medical_history,
                allergies, emergency_contact, emergency_phone, blood_type, role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->prepare($sql);
            $this->db->execute([
                $data['name'],
                $data['email'],
                $data['password'],
                $data['phone'],
                $data['national_id'] ?? null,
                $data['national_id_image'] ?? null,
                $data['gender'] ?? null,
                $data['hearing_status'],
                $data['marital_status'] ?? null,
                $data['sign_language_level'] ?? null,
                $data['governorate'],
                $data['age'],
                $data['job'] ?? null,
                $data['service_card_image'] ?? null,
                $data['medical_history'] ?? null,
                $data['allergies'] ?? null,
                $data['emergency_contact'] ?? null,
                $data['emergency_phone'] ?? null,
                $data['blood_type'] ?? null,
                $data['role'] ?? 'patient'
            ]);

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Create user failed: " . $e->getMessage());
            throw new Exception("فشل في إنشاء المستخدم");
        }
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM users WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$id]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get user by ID failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        try {
            $sql = "SELECT * FROM users WHERE email = ?";
            $this->db->prepare($sql);
            $this->db->execute([$email]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get user by email failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $values = [];

            foreach ($data as $key => $value) {
                if (in_array($key, ['name', 'email', 'phone', 'role', 'status'])) {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }

            if (empty($fields)) {
                throw new Exception("لا توجد بيانات للتحديث");
            }

            $fields[] = "updated_at = CURRENT_TIMESTAMP";
            $values[] = $id;

            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute($values);

            return $this->db->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Update user failed: " . $e->getMessage());
            throw new Exception("فشل في تحديث المستخدم");
        }
    }

    /**
     * Delete user
     */
    public function delete($id) {
        try {
            // Soft delete by updating status
            $sql = "UPDATE users SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$id]);

            return $this->db->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Delete user failed: " . $e->getMessage());
            throw new Exception("فشل في حذف المستخدم");
        }
    }

    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $limit = 20, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $where_conditions = ["status != 'deleted'"];
            $params = [];

            // Apply filters
            if (!empty($filters['role'])) {
                $where_conditions[] = "role = ?";
                $params[] = $filters['role'];
            }

            if (!empty($filters['status'])) {
                $where_conditions[] = "status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }

            $where_clause = implode(' AND ', $where_conditions);

            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
            $this->db->prepare($count_sql);
            $this->db->execute($params);
            $total = $this->db->fetch()['total'];

            // Get users
            $sql = "SELECT * FROM users WHERE $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $this->db->prepare($sql);
            $this->db->execute($params);
            $users = $this->db->fetchAll();

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];

        } catch (Exception $e) {
            error_log("Get all users failed: " . $e->getMessage());
            return [
                'users' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'total_pages' => 0
            ];
        }
    }

    /**
     * Get users by role
     */
    public function getByRole($role) {
        try {
            $sql = "SELECT * FROM users WHERE role = ? AND status = 'active' ORDER BY name";
            $this->db->prepare($sql);
            $this->db->execute([$role]);

            return $this->db->fetchAll();

        } catch (Exception $e) {
            error_log("Get users by role failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update user password
     */
    public function updatePassword($id, $new_password) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $sql = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$hashed_password, $id]);

            return $this->db->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Update password failed: " . $e->getMessage());
            throw new Exception("فشل في تحديث كلمة المرور");
        }
    }

    /**
     * Get user profile with additional information
     */
    public function getProfile($id) {
        try {
            $sql = "SELECT u.*, 
                           GROUP_CONCAT(r.display_name SEPARATOR ', ') as roles
                    FROM users u
                    LEFT JOIN user_roles ur ON u.id = ur.user_id
                    LEFT JOIN roles r ON ur.role_id = r.id
                    WHERE u.id = ? AND u.status != 'deleted'
                    GROUP BY u.id";
            
            $this->db->prepare($sql);
            $this->db->execute([$id]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get user profile failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile($id, $data) {
        try {
            $allowed_fields = ['name', 'phone'];
            $fields = [];
            $values = [];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowed_fields)) {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }

            if (empty($fields)) {
                throw new Exception("لا توجد بيانات للتحديث");
            }

            $fields[] = "updated_at = CURRENT_TIMESTAMP";
            $values[] = $id;

            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute($values);

            return $this->db->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Update user profile failed: " . $e->getMessage());
            throw new Exception("فشل في تحديث الملف الشخصي");
        }
    }

    /**
     * Check if email exists (for validation)
     */
    public function emailExists($email, $exclude_id = null) {
        try {
            $sql = "SELECT id FROM users WHERE email = ?";
            $params = [$email];

            if ($exclude_id) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }

            $this->db->prepare($sql);
            $this->db->execute($params);

            return $this->db->fetch() !== false;

        } catch (Exception $e) {
            error_log("Check email exists failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activate user account
     */
    public function activate($id) {
        try {
            $sql = "UPDATE users SET status = 'active', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$id]);

            return $this->db->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Activate user failed: " . $e->getMessage());
            throw new Exception("فشل في تفعيل المستخدم");
        }
    }

    /**
     * Deactivate user account
     */
    public function deactivate($id) {
        try {
            $sql = "UPDATE users SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$id]);

            return $this->db->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Deactivate user failed: " . $e->getMessage());
            throw new Exception("فشل في إلغاء تفعيل المستخدم");
        }
    }
}
?>
