<?php
/**
 * Appointments API Endpoints
 */

$method = $_SERVER['REQUEST_METHOD'];
$segments = explode('/', trim($path, '/'));
$action = $segments[1] ?? '';
$appointmentId = $segments[2] ?? null;

switch ($method) {
    case 'GET':
        if ($appointmentId) {
            handleGetAppointment($appointmentId);
        } else {
            handleGetAppointments();
        }
        break;
    case 'POST':
        if ($action === 'search') {
            handleSearchAppointments();
        } elseif ($action === 'availability') {
            handleCheckAvailability();
        } else {
            handleCreateAppointment();
        }
        break;
    case 'PUT':
        if ($appointmentId) {
            handleUpdateAppointment($appointmentId);
        } else {
            ApiResponse::error('Appointment ID required for update');
        }
        break;
    case 'DELETE':
        if ($appointmentId) {
            handleCancelAppointment($appointmentId);
        } else {
            ApiResponse::error('Appointment ID required for cancellation');
        }
        break;
    default:
        ApiResponse::error('Method not allowed', 405);
}

function handleGetAppointments() {
    $user = ApiAuth::authenticate();
    
    $db = Database::getInstance();
    
    // Build query based on user role
    $whereClause = '';
    $params = [];
    
    if ($user['role'] === 'patient') {
        $whereClause = 'WHERE a.patient_id = ?';
        $params[] = $user['id'];
    } elseif ($user['role'] === 'doctor') {
        $whereClause = 'WHERE c.doctor_id = ?';
        $params[] = $user['id'];
    }
    
    // Get filters
    $status = $_GET['status'] ?? '';
    $date = $_GET['date'] ?? '';
    $clinicId = $_GET['clinic_id'] ?? '';
    
    if ($status) {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'a.status = ?';
        $params[] = $status;
    }
    
    if ($date) {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'a.appointment_date = ?';
        $params[] = $date;
    }
    
    if ($clinicId) {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'a.clinic_id = ?';
        $params[] = $clinicId;
    }
    
    // Get pagination parameters
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 10), 50);
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT a.*, 
                     c.name as clinic_name, c.location as clinic_location,
                     d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.phone as doctor_phone,
                     p.first_name as patient_first_name, p.last_name as patient_last_name, p.phone as patient_phone
              FROM appointments a
              JOIN clinics c ON a.clinic_id = c.id
              JOIN users d ON c.doctor_id = d.id
              JOIN users p ON a.patient_id = p.id
              $whereClause
              ORDER BY a.appointment_date DESC, a.appointment_time DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM appointments a JOIN clinics c ON a.clinic_id = c.id $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute(array_slice($params, 0, -2));
    $totalCount = $stmt->fetchColumn();
    
    ApiResponse::success([
        'appointments' => $appointments,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $limit)
        ]
    ]);
}

function handleGetAppointment($appointmentId) {
    $user = ApiAuth::authenticate();
    
    $db = Database::getInstance();
    
    $stmt = $db->prepare("SELECT a.*, 
                                 c.name as clinic_name, c.location as clinic_location, c.phone as clinic_phone,
                                 d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.phone as doctor_phone,
                                 p.first_name as patient_first_name, p.last_name as patient_last_name, p.phone as patient_phone,
                                 dp.specialization, dp.experience_years
                          FROM appointments a
                          JOIN clinics c ON a.clinic_id = c.id
                          JOIN users d ON c.doctor_id = d.id
                          JOIN users p ON a.patient_id = p.id
                          LEFT JOIN doctor_profiles dp ON d.id = dp.user_id
                          WHERE a.id = ?");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        ApiResponse::notFound('Appointment not found');
    }
    
    // Check access permissions
    $hasAccess = false;
    if ($user['role'] === 'admin' || $user['role'] === 'secretary') {
        $hasAccess = true;
    } elseif ($user['role'] === 'patient' && $appointment['patient_id'] == $user['id']) {
        $hasAccess = true;
    } elseif ($user['role'] === 'doctor') {
        // Check if user is the doctor for this clinic
        $stmt = $db->prepare("SELECT doctor_id FROM clinics WHERE id = ?");
        $stmt->execute([$appointment['clinic_id']]);
        $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($clinic && $clinic['doctor_id'] == $user['id']) {
            $hasAccess = true;
        }
    }
    
    if (!$hasAccess) {
        ApiResponse::forbidden('Access denied to this appointment');
    }
    
    ApiResponse::success($appointment);
}

function handleCreateAppointment() {
    $user = ApiAuth::authenticate();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $clinicId = $input['clinic_id'] ?? '';
    $patientId = $input['patient_id'] ?? $user['id'];
    $appointmentDate = $input['appointment_date'] ?? '';
    $appointmentTime = $input['appointment_time'] ?? '';
    $reason = $input['reason'] ?? '';
    $notes = $input['notes'] ?? '';
    
    if (empty($clinicId) || empty($appointmentDate) || empty($appointmentTime)) {
        ApiResponse::error('Clinic, date, and time are required');
    }
    
    // Only admin/secretary can book for other patients
    if ($user['role'] === 'patient') {
        $patientId = $user['id'];
    }
    
    $db = Database::getInstance();
    
    // Validate clinic exists and is active
    $stmt = $db->prepare("SELECT id, doctor_id, status FROM clinics WHERE id = ?");
    $stmt->execute([$clinicId]);
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$clinic || $clinic['status'] !== 'active') {
        ApiResponse::error('Invalid or inactive clinic');
    }
    
    // Check if appointment time is available
    $stmt = $db->prepare("SELECT id FROM appointments WHERE clinic_id = ? AND appointment_date = ? AND appointment_time = ? AND status IN ('scheduled', 'confirmed')");
    $stmt->execute([$clinicId, $appointmentDate, $appointmentTime]);
    if ($stmt->fetch()) {
        ApiResponse::error('Appointment slot is not available', 409);
    }
    
    // Check if patient exists
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'patient'");
    $stmt->execute([$patientId]);
    if (!$stmt->fetch()) {
        ApiResponse::error('Invalid patient');
    }
    
    try {
        $db->beginTransaction();
        
        // Create appointment
        $stmt = $db->prepare("INSERT INTO appointments (clinic_id, patient_id, appointment_date, appointment_time, reason, notes, status) 
                             VALUES (?, ?, ?, ?, ?, ?, 'scheduled')");
        $stmt->execute([$clinicId, $patientId, $appointmentDate, $appointmentTime, $reason, $notes]);
        
        $appointmentId = $db->lastInsertId();
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                             VALUES (?, 'create_appointment', 'appointments', ?, ?, ?)");
        $stmt->execute([$user['id'], $appointmentId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        
        $db->commit();
        
        // Get created appointment with details
        $stmt = $db->prepare("SELECT a.*, 
                                     c.name as clinic_name,
                                     d.first_name as doctor_first_name, d.last_name as doctor_last_name,
                                     p.first_name as patient_first_name, p.last_name as patient_last_name
                              FROM appointments a
                              JOIN clinics c ON a.clinic_id = c.id
                              JOIN users d ON c.doctor_id = d.id
                              JOIN users p ON a.patient_id = p.id
                              WHERE a.id = ?");
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        ApiResponse::success($appointment, 'Appointment scheduled successfully', 201);
        
    } catch (Exception $e) {
        $db->rollBack();
        ApiResponse::error('Failed to create appointment: ' . $e->getMessage(), 500);
    }
}

function handleUpdateAppointment($appointmentId) {
    $user = ApiAuth::authenticate();
    
    $db = Database::getInstance();
    
    // Get appointment with clinic info
    $stmt = $db->prepare("SELECT a.*, c.doctor_id FROM appointments a JOIN clinics c ON a.clinic_id = c.id WHERE a.id = ?");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        ApiResponse::notFound('Appointment not found');
    }
    
    // Check permissions
    $canUpdate = false;
    if ($user['role'] === 'admin' || $user['role'] === 'secretary') {
        $canUpdate = true;
    } elseif ($user['role'] === 'doctor' && $appointment['doctor_id'] == $user['id']) {
        $canUpdate = true;
    } elseif ($user['role'] === 'patient' && $appointment['patient_id'] == $user['id']) {
        // Patients can only update certain fields and only if appointment is not confirmed
        $canUpdate = true;
    }
    
    if (!$canUpdate) {
        ApiResponse::forbidden('Access denied');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $updateFields = [];
    $params = [];
    
    // Define allowed fields based on role
    $allowedFields = [];
    if ($user['role'] === 'admin' || $user['role'] === 'secretary') {
        $allowedFields = ['appointment_date', 'appointment_time', 'reason', 'notes', 'status'];
    } elseif ($user['role'] === 'doctor') {
        $allowedFields = ['appointment_date', 'appointment_time', 'notes', 'status'];
    } elseif ($user['role'] === 'patient') {
        $allowedFields = ['reason', 'notes'];
        // Patients can only update if appointment is scheduled
        if ($appointment['status'] !== 'scheduled') {
            ApiResponse::error('Cannot update confirmed or completed appointments');
        }
    }
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    if (empty($updateFields)) {
        ApiResponse::error('No fields to update');
    }
    
    // If updating time/date, check availability
    if ((isset($input['appointment_date']) || isset($input['appointment_time'])) && 
        ($user['role'] === 'admin' || $user['role'] === 'secretary' || $user['role'] === 'doctor')) {
        
        $newDate = $input['appointment_date'] ?? $appointment['appointment_date'];
        $newTime = $input['appointment_time'] ?? $appointment['appointment_time'];
        
        $stmt = $db->prepare("SELECT id FROM appointments WHERE clinic_id = ? AND appointment_date = ? AND appointment_time = ? AND status IN ('scheduled', 'confirmed') AND id != ?");
        $stmt->execute([$appointment['clinic_id'], $newDate, $newTime, $appointmentId]);
        if ($stmt->fetch()) {
            ApiResponse::error('New appointment slot is not available', 409);
        }
    }
    
    $params[] = $appointmentId;
    
    try {
        $db->beginTransaction();
        
        // Update appointment
        $query = "UPDATE appointments SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                             VALUES (?, 'update_appointment', 'appointments', ?, ?, ?)");
        $stmt->execute([$user['id'], $appointmentId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        
        $db->commit();
        
        // Get updated appointment
        $stmt = $db->prepare("SELECT a.*, 
                                     c.name as clinic_name,
                                     d.first_name as doctor_first_name, d.last_name as doctor_last_name,
                                     p.first_name as patient_first_name, p.last_name as patient_last_name
                              FROM appointments a
                              JOIN clinics c ON a.clinic_id = c.id
                              JOIN users d ON c.doctor_id = d.id
                              JOIN users p ON a.patient_id = p.id
                              WHERE a.id = ?");
        $stmt->execute([$appointmentId]);
        $updatedAppointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        ApiResponse::success($updatedAppointment, 'Appointment updated successfully');
        
    } catch (Exception $e) {
        $db->rollBack();
        ApiResponse::error('Failed to update appointment: ' . $e->getMessage(), 500);
    }
}

function handleCancelAppointment($appointmentId) {
    $user = ApiAuth::authenticate();
    
    $db = Database::getInstance();
    
    // Get appointment
    $stmt = $db->prepare("SELECT a.*, c.doctor_id FROM appointments a JOIN clinics c ON a.clinic_id = c.id WHERE a.id = ?");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        ApiResponse::notFound('Appointment not found');
    }
    
    // Check permissions
    $canCancel = false;
    if ($user['role'] === 'admin' || $user['role'] === 'secretary') {
        $canCancel = true;
    } elseif ($user['role'] === 'doctor' && $appointment['doctor_id'] == $user['id']) {
        $canCancel = true;
    } elseif ($user['role'] === 'patient' && $appointment['patient_id'] == $user['id']) {
        $canCancel = true;
    }
    
    if (!$canCancel) {
        ApiResponse::forbidden('Access denied');
    }
    
    if ($appointment['status'] === 'cancelled') {
        ApiResponse::error('Appointment is already cancelled');
    }
    
    if ($appointment['status'] === 'completed') {
        ApiResponse::error('Cannot cancel completed appointment');
    }
    
    try {
        $db->beginTransaction();
        
        // Cancel appointment
        $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$appointmentId]);
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                             VALUES (?, 'cancel_appointment', 'appointments', ?, ?, ?)");
        $stmt->execute([$user['id'], $appointmentId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        
        $db->commit();
        
        ApiResponse::success(null, 'Appointment cancelled successfully');
        
    } catch (Exception $e) {
        $db->rollBack();
        ApiResponse::error('Failed to cancel appointment: ' . $e->getMessage(), 500);
    }
}

function handleCheckAvailability() {
    $user = ApiAuth::authenticate();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $clinicId = $input['clinic_id'] ?? '';
    $date = $input['date'] ?? '';
    
    if (empty($clinicId) || empty($date)) {
        ApiResponse::error('Clinic ID and date are required');
    }
    
    $db = Database::getInstance();
    
    // Get clinic schedule for the day
    $dayOfWeek = date('w', strtotime($date)); // 0 = Sunday, 6 = Saturday
    
    $stmt = $db->prepare("SELECT start_time, end_time FROM clinic_schedules WHERE clinic_id = ? AND day_of_week = ?");
    $stmt->execute([$clinicId, $dayOfWeek]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        ApiResponse::success(['available_slots' => []], 'No working hours for this day');
    }
    
    // Get booked appointments
    $stmt = $db->prepare("SELECT appointment_time FROM appointments WHERE clinic_id = ? AND appointment_date = ? AND status IN ('scheduled', 'confirmed')");
    $stmt->execute([$clinicId, $date]);
    $bookedTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Generate available time slots (assuming 30-minute slots)
    $availableSlots = [];
    $startTime = strtotime($schedule['start_time']);
    $endTime = strtotime($schedule['end_time']);
    
    for ($time = $startTime; $time < $endTime; $time += 1800) { // 30 minutes
        $timeSlot = date('H:i:s', $time);
        if (!in_array($timeSlot, $bookedTimes)) {
            $availableSlots[] = $timeSlot;
        }
    }
    
    ApiResponse::success(['available_slots' => $availableSlots]);
}

function handleSearchAppointments() {
    $user = ApiAuth::requireRole(['admin', 'secretary', 'doctor']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $searchTerm = $input['query'] ?? '';
    $dateFrom = $input['date_from'] ?? '';
    $dateTo = $input['date_to'] ?? '';
    $status = $input['status'] ?? '';
    $clinicId = $input['clinic_id'] ?? '';
    
    $db = Database::getInstance();
    
    $whereClause = '';
    $params = [];
    
    if ($user['role'] === 'doctor') {
        $whereClause = 'WHERE c.doctor_id = ?';
        $params[] = $user['id'];
    }
    
    if ($searchTerm) {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 
                       "(p.first_name LIKE ? OR p.last_name LIKE ? OR d.first_name LIKE ? OR d.last_name LIKE ? OR c.name LIKE ?)";
        $searchPattern = "%$searchTerm%";
        $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
    }
    
    if ($dateFrom) {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'a.appointment_date >= ?';
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'a.appointment_date <= ?';
        $params[] = $dateTo;
    }
    
    if ($status) {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'a.status = ?';
        $params[] = $status;
    }
    
    if ($clinicId) {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'a.clinic_id = ?';
        $params[] = $clinicId;
    }
    
    $query = "SELECT a.*, 
                     c.name as clinic_name,
                     d.first_name as doctor_first_name, d.last_name as doctor_last_name,
                     p.first_name as patient_first_name, p.last_name as patient_last_name, p.phone as patient_phone
              FROM appointments a
              JOIN clinics c ON a.clinic_id = c.id
              JOIN users d ON c.doctor_id = d.id
              JOIN users p ON a.patient_id = p.id
              $whereClause
              ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success($appointments);
}
?>