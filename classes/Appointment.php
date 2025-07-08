<?php
require_once __DIR__ . '/Database.php';

/**
 * Appointment Class
 * Handles appointment management operations
 */
class Appointment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get appointment statistics (all, today, upcoming, completed)
     */
    public function getStatistics() {
        $today = date('Y-m-d');
        $stats = [
            'total' => 0,
            'today' => 0,
            'upcoming' => 0,
            'completed' => 0
        ];
        $db = $this->db->getConnection();
        $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
        $stats['today'] = (int)$db->query("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = '$today'")->fetchColumn();
        $stats['upcoming'] = (int)$db->query("SELECT COUNT(*) FROM appointments WHERE appointment_date > '$today'")->fetchColumn();
        $stats['completed'] = (int)$db->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetchColumn();
        return $stats;
    }

    /**
     * Get today's appointments
     */
    public function getTodaysAppointments() {
        $today = date('Y-m-d');
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM appointments WHERE DATE(appointment_date) = :today ORDER BY appointment_date, appointment_time");
        $stmt->execute([':today' => $today]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all appointments (limit for display)
     */
    public function getAll($limit = 20) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM appointments ORDER BY appointment_date DESC, appointment_time DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get upcoming appointments
     */
    public function getUpcomingAppointments($user_id = null, $user_role = null, $limit = 5) {
        $today = date('Y-m-d');
        $sql = "SELECT * FROM appointments WHERE appointment_date > :today ORDER BY appointment_date, appointment_time LIMIT :limit";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':today', $today);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>