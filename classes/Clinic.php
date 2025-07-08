<?php
require_once __DIR__ . '/Database.php';

/**
 * Clinic Class
 * Handles clinic management operations
 */
class Clinic {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }


    /**
     * Get all clinics with pagination
     */
    public function getAll($page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            $sql = "SELECT * FROM clinics WHERE status != 'deleted' ORDER BY id DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = $this->db->getConnection()->query("SELECT COUNT(*) FROM clinics WHERE status != 'deleted'")->fetchColumn();
            $pages = ceil($total / $limit);
            return [
                'data' => $data,
                'total' => (int)$total,
                'page' => $page,
                'limit' => $limit,
                'pages' => $pages
            ];
        } catch (Exception $e) {
            error_log("Clinic getAll error: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'pages' => 0
            ];
        }
    }

    /**
     * Create a new clinic
     */
    public function create($data) {
        $sql = "INSERT INTO clinics (name, specialization, description, status, created_by, created_at, updated_at) VALUES (:name, :specialization, :description, :status, :created_by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':specialization', $data['specialization'] ?? null);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'active');
        $stmt->bindValue(':created_by', $data['created_by'] ?? null);
        $stmt->execute();
        return $this->db->getConnection()->lastInsertId();
    }

    /**
     * Update a clinic
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        foreach (['name', 'specialization', 'description', 'status'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        $sql = "UPDATE clinics SET ".implode(',', $fields).", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a clinic (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE clinics SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get clinic by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM clinics WHERE id = :id AND status != 'deleted'";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get clinic statistics
     */
    public function getStatistics() {
        $sql = "SELECT COUNT(*) as total, 
                       SUM(status = 'active') as active, 
                       SUM(status = 'inactive') as inactive
                FROM clinics WHERE status != 'deleted'";
        $row = $this->db->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC);
        return [
            'total' => (int)($row['total'] ?? 0),
            'active' => (int)($row['active'] ?? 0),
            'inactive' => (int)($row['inactive'] ?? 0)
        ];
    }
}
?>