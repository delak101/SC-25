<?php
require_once __DIR__ . '/Database.php';

/**
 * Doctor Class
 * Handles doctor management operations
 */
class Doctor {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new doctor
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO doctors 
                    (user_id, specialization, license_number, experience_years, education, certifications, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            
            $this->db->prepare($sql);
            $this->db->execute([
                $data['user_id'],
                $data['specialization'],
                $data['license_number'],
                $data['experience_years'] ?? 0,
                $data['education'] ?? '',
                $data['certifications'] ?? ''
            ]);

            return $this->db->lastInsertId();

        } catch (Exception $e) {
            error_log("Create doctor failed: " . $e->getMessage());
            throw new Exception("فشل في إنشاء الطبيب");
        }
    }

    /**
     * Get doctor by user ID
     */
    public function getByUserId($user_id) {
        try {
            $sql = "SELECT d.*, u.name, u.email, u.phone, u.status 
                    FROM doctors d
                    JOIN users u ON d.user_id = u.id
                    WHERE d.user_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$user_id]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get doctor by user ID failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get doctor by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT d.*, u.name, u.email, u.phone, u.status 
                    FROM doctors d
                    JOIN users u ON d.user_id = u.id
                    WHERE d.id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$id]);

            return $this->db->fetch();

        } catch (Exception $e) {
            error_log("Get doctor by ID failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update doctor
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $values = [];

            foreach ($data as $key => $value) {
                if (in_array($key, ['specialization', 'license_number', 'experience_years', 'education', 'certifications'])) {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }

            if (empty($fields)) {
                throw new Exception("لا توجد بيانات للتحديث");
            }

            $fields[] = "updated_at = CURRENT_TIMESTAMP";
            $values[] = $id;

            $sql = "UPDATE doctors SET " . implode(', ', $fields) . " WHERE id = ?";
            $this->db->prepare($sql);
            $this->db->execute($values);

            return $this->db->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Update doctor failed: " . $e->getMessage());
            throw new Exception("فشل في تحديث بيانات الطبيب");
        }
    }

    /**
     * Get all doctors with pagination
     */
    public function getAll($page = 1, $limit = 20, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $where_conditions = ["u.status = 'active'"];
            $params = [];

            // Apply filters
            if (!empty($filters['specialization'])) {
                $where_conditions[] = "d.specialization = ?";
                $params[] = $filters['specialization'];
            }

            if (!empty($filters['search'])) {
                $where_conditions[] = "(u.name LIKE ? OR d.specialization LIKE ? OR d.license_number LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }

            $where_clause = implode(' AND ', $where_conditions);

            // Get total count
            $count_sql = "SELECT COUNT(*) as total 
                          FROM doctors d
                          JOIN users u ON d.user_id = u.id
                          WHERE $where_clause";
            $this->db->prepare($count_sql);
            $this->db->execute($params);
            $total = $this->db->fetch()['total'];

            // Get doctors
            $sql = "SELECT d.*, u.name, u.email, u.phone 
                    FROM doctors d
                    JOIN users u ON d.user_id = u.id
                    WHERE $where_clause 
                    ORDER BY u.name ASC 
                    LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $this->db->prepare($sql);
            $this->db->execute($params);
            $doctors = $this->db->fetchAll();

            return [
                'doctors' => $doctors,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];

        } catch (Exception $e) {
            error_log("Get all doctors failed: " . $e->getMessage());
            return [
                'doctors' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'total_pages' => 0
            ];
        }
    }

    /**
     * Get doctor statistics
     */
    public function getStatistics($user_id) {
        try {
            // Total patients
            $sql_patients = "SELECT COUNT(DISTINCT patient_id) as total_patients 
                            FROM appointments 
                            WHERE doctor_id = ?";
            $this->db->prepare($sql_patients);
            $this->db->execute([$user_id]);
            $total_patients = $this->db->fetch()['total_patients'];

            // Today's appointments
            $sql_today = "SELECT COUNT(*) as today_appointments 
                         FROM appointments 
                         WHERE doctor_id = ? AND DATE(appointment_date) = CURDATE()";
            $this->db->prepare($sql_today);
            $this->db->execute([$user_id]);
            $today_appointments = $this->db->fetch()['today_appointments'];

            // Total clinics
            $sql_clinics = "SELECT COUNT(DISTINCT clinic_id) as total_clinics 
                           FROM doctor_clinics 
                           WHERE doctor_id = ?";
            $this->db->prepare($sql_clinics);
            $this->db->execute([$user_id]);
            $total_clinics = $this->db->fetch()['total_clinics'];

            // Total appointments
            $sql_appointments = "SELECT COUNT(*) as total_appointments 
                               FROM appointments 
                               WHERE doctor_id = ?";
            $this->db->prepare($sql_appointments);
            $this->db->execute([$user_id]);
            $total_appointments = $this->db->fetch()['total_appointments'];

            return [
                'total_patients' => $total_patients,
                'today_appointments' => $today_appointments,
                'total_clinics' => $total_clinics,
                'total_appointments' => $total_appointments
            ];

        } catch (Exception $e) {
            error_log("Get doctor statistics failed: " . $e->getMessage());
            return [
                'total_patients' => 0,
                'today_appointments' => 0,
                'total_clinics' => 0,
                'total_appointments' => 0
            ];
        }
    }

    /**
     * Get doctor's upcoming appointments
     */
    public function getUpcomingAppointments($user_id, $limit = 5) {
        try {
            $sql = "SELECT a.*, p.name as patient_name, p.phone as patient_phone
                    FROM appointments a
                    JOIN users p ON a.patient_id = p.id
                    WHERE a.doctor_id = ? AND a.appointment_date >= NOW()
                    ORDER BY a.appointment_date ASC
                    LIMIT ?";
            $this->db->prepare($sql);
            $this->db->execute([$user_id, $limit]);

            return $this->db->fetchAll();

        } catch (Exception $e) {
            error_log("Get doctor appointments failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get doctor's clinics
     */
    public function getClinics($user_id) {
        try {
            $sql = "SELECT c.*, dc.working_hours, dc.consultation_fee
                    FROM clinics c
                    JOIN doctor_clinics dc ON c.id = dc.clinic_id
                    WHERE dc.doctor_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$user_id]);

            return $this->db->fetchAll();

        } catch (Exception $e) {
            error_log("Get doctor clinics failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if license number exists (for validation)
     */
    public function licenseNumberExists($license_number, $exclude_id = null) {
        try {
            $sql = "SELECT id FROM doctors WHERE license_number = ?";
            $params = [$license_number];

            if ($exclude_id) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }

            $this->db->prepare($sql);
            $this->db->execute($params);

            return $this->db->fetch() !== false;

        } catch (Exception $e) {
            error_log("Check license number exists failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get doctor's specialties (if multiple)
     */
    public function getSpecialties($user_id) {
        try {
            $sql = "SELECT DISTINCT specialization FROM doctors WHERE user_id = ?";
            $this->db->prepare($sql);
            $this->db->execute([$user_id]);

            return $this->db->fetchAll(PDO::FETCH_COLUMN, 0);

        } catch (Exception $e) {
            error_log("Get doctor specialties failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get doctor's schedule
     */
    public function getSchedule($user_id, $clinic_id = null) {
        try {
            $sql = "SELECT * FROM doctor_schedules 
                    WHERE doctor_id = ?" . 
                    ($clinic_id ? " AND clinic_id = ?" : "") . 
                    " ORDER BY day_of_week, start_time";
            
            $params = [$user_id];
            if ($clinic_id) {
                $params[] = $clinic_id;
            }

            $this->db->prepare($sql);
            $this->db->execute($params);

            return $this->db->fetchAll();

        } catch (Exception $e) {
            error_log("Get doctor schedule failed: " . $e->getMessage());
            return [];
        }
    }
}
?>