<?php
require_once __DIR__ . '/Database.php';

/**
 * Patient Class
 * Handles patient-specific operations
 */
class Patient {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create patient profile
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO patients (
                user_id, national_id, national_id_image, gender, 
                hearing_status, marital_status, sign_language_level, 
                governorate, age, job, service_card_image, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            
            $this->db->prepare($sql);
            $this->db->execute([
                $data['user_id'],
                $data['national_id'],
                $data['national_id_image'],
                $data['gender'],
                $data['hearing_status'],
                $data['marital_status'],
                $data['sign_language_level'],
                $data['governorate'],
                $data['age'],
                $data['job'],
                $data['service_card_image'] ?? null
            ]);

            return $this->db->lastInsertId();

        } catch (Exception $e) {
            error_log("Create patient failed: " . $e->getMessage());
            throw new Exception("فشل في إنشاء ملف المريض");
        }
    }

    /**
     * Get patient by user ID
     */
    public function getByUserId($user_id) {
        try {
            $sql = "SELECT * FROM patients WHERE user_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$user_id]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get patient by user ID failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update patient profile
     */
    public function update($user_id, $data) {
        try {
            $fields = [];
            $values = [];

            foreach ($data as $key => $value) {
                if (in_array($key, [
                    'national_id', 'national_id_image', 'gender', 
                    'hearing_status', 'marital_status', 'sign_language_level',
                    'governorate', 'age', 'job', 'service_card_image'
                ])) {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }

            if (empty($fields)) {
                throw new Exception("لا توجد بيانات للتحديث");
            }

            $fields[] = "updated_at = CURRENT_TIMESTAMP";
            $values[] = $user_id;

            $sql = "UPDATE patients SET " . implode(', ', $fields) . " WHERE user_id = ?";
            $this->db->prepare($sql);
            $this->db->execute($values);

            return $this->db->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Update patient failed: " . $e->getMessage());
            throw new Exception("فشل في تحديث ملف المريض");
        }
    }
}
?>