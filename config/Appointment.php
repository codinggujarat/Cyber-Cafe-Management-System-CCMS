<?php
require_once 'db.php';

class Appointment {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Check slot availability
    public function isSlotAvailable($date, $time, $duration = 30) {
        // Simple overlap check
        // Assuming strict slots for now, or check range
        // For simplicity: check if any appointment starts at this time on this date and is NOT cancelled
        $query = "SELECT COUNT(*) FROM appointments 
                  WHERE appointment_date = :date 
                  AND appointment_time = :time 
                  AND status != 'cancelled'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':date' => $date, ':time' => $time]);
        
        // Let's assume 1 concurrent appointment allowed per slot generally, 
        // unless we account for multiple staff. 
        // For this system, let's limit to 3 concurrent appointments (capacity).
        return $stmt->fetchColumn() < 3;
    }

    public function book($userId, $serviceId, $type, $date, $time, $notes = '') {
        if (!$this->isSlotAvailable($date, $time)) {
            return ['status' => false, 'message' => 'Slot not available'];
        }

        $query = "INSERT INTO appointments (user_id, service_id, appointment_type, appointment_date, appointment_time, notes) 
                  VALUES (:uid, :sid, :type, :date, :time, :notes)";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([
            ':uid' => $userId,
            ':sid' => $serviceId,
            ':type' => $type,
            ':date' => $date,
            ':time' => $time,
            ':notes' => $notes
        ])) {
            return ['status' => true, 'message' => 'Appointment requested successfully'];
        }
        return ['status' => false, 'message' => 'Database error'];
    }

    public function getUserAppointments($userId) {
        $query = "SELECT a.*, s.name as service_name, u.name as staff_name 
                  FROM appointments a 
                  JOIN services s ON a.service_id = s.id 
                  LEFT JOIN users u ON a.staff_id = u.id 
                  WHERE a.user_id = :uid 
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAppointment($id) {
         $query = "SELECT a.*, s.name as service_name, u.name as user_name, u.email as user_email
                  FROM appointments a 
                  JOIN services s ON a.service_id = s.id 
                  JOIN users u ON a.user_id = u.id
                  WHERE a.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status, $staffId = null, $link = null) {
        $sql = "UPDATE appointments SET status = :status";
        $params = [':status' => $status, ':id' => $id];

        if ($staffId) {
            $sql .= ", staff_id = :staff";
            $params[':staff'] = $staffId;
        }
        if ($link) {
             $sql .= ", meeting_link = :link";
             $params[':link'] = $link;
        }

        $sql .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }
}
?>
