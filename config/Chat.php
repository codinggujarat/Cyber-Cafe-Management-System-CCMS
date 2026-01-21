<?php
require_once 'db.php';

class Chat {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function sendMessage($senderId, $message, $contextType, $contextId) {
        $allowedContexts = ['appointment', 'order'];
        if (!in_array($contextType, $allowedContexts)) return false;

        $col = $contextType . '_id'; // appointment_id or order_id
        
        $query = "INSERT INTO support_messages (sender_id, message, $col) VALUES (:sender, :msg, :cid)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':sender' => $senderId, ':msg' => $message, ':cid' => $contextId]);
    }

    public function getMessages($contextType, $contextId) {
        $col = $contextType . '_id';
        $query = "SELECT m.*, u.name as sender_name, u.role as sender_role 
                  FROM support_messages m 
                  JOIN users u ON m.sender_id = u.id 
                  WHERE m.$col = :cid 
                  ORDER BY m.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':cid' => $contextId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
