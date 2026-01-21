<?php
require_once 'db.php';
require_once 'functions.php';

class Auth {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function register($name, $email, $password, $phone) {
        if ($this->emailExists($email)) {
            return ['status' => false, 'message' => 'Email already registered'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user'; // Default role
        
        // OTP generation
        $otp = rand(100000, 999999);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $query = "INSERT INTO users (name, email, password, phone, role, otp, otp_expiry) VALUES (:name, :email, :pass, :phone, :role, :otp, :expiry)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':pass', $hash);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':otp', $otp);
        $stmt->bindParam(':expiry', $otp_expiry);

        if ($stmt->execute()) {
            $this->sendOtpEmail($email, $otp);
            return ['status' => true, 'message' => 'OTP sent to ' . $email . '. Please verify.', 'email' => $email];
        }
        return ['status' => false, 'message' => 'Registration failed'];
    }

    private function sendOtpEmail($email, $otp) {
        require_once __DIR__ . '/SMTP.php';
        
        $smtp = new SMTPMailer('smtp.gmail.com', 587, 'quintetonline@gmail.com', 'tmpwwvhruhiivfum');
        $subject = "Verify Your Account - " . APP_NAME;
        
        $body = "
            <h2>Welcome to " . APP_NAME . "</h2>
            <p>Your OTP for account verification is:</p>
            <h1 style='color: #4f46e5;'>$otp</h1>
            <p>This OTP is valid for 15 minutes.</p>
        ";

        if ($smtp->send($email, $subject, $body)) {
            // Success
            $logFile = __DIR__ . '/../email_log.txt';
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Email sent to $email | OTP: $otp" . PHP_EOL, FILE_APPEND);
        } else {
            // Fallback log
            $logFile = __DIR__ . '/../email_log.txt';
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] FAILED Email to $email | OTP: $otp" . PHP_EOL, FILE_APPEND);
        }
    }

    public function verifyOTP($email, $otp) {
        // Check if user exists but might be unverified/verified. 
        // Logic: if OTP matches, we clear it and ensure status is active
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            if ($user['otp'] == $otp && strtotime($user['otp_expiry']) > time()) {
                // Clear OTP and Activate
                $update = "UPDATE users SET otp = NULL, otp_expiry = NULL, status = 1 WHERE id = :id";
                $upStmt = $this->conn->prepare($update);
                $upStmt->bindParam(':id', $user['id']);
                $upStmt->execute();
                return ['status' => true, 'message' => 'Verification successful! You can now login.'];
            }
            return ['status' => false, 'message' => 'Invalid or expired OTP'];
        }
        return ['status' => false, 'message' => 'User not found'];
    }

    public function login($email, $password) {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            if (password_verify($password, $user['password'])) {
                // Optional: Check if OTP is still set (meaning unverified)? 
                // For now, if OTP is NULL, they are verified. If OTP is set, maybe enforce verification?
                // Let's assume if they have an OTP they haven't verified yet.
                if (!empty($user['otp'])) {
                     return ['status' => false, 'message' => 'Account not verified. Please verify OTP sent to email.'];
                }

                if ($user['status'] == 0) {
                     return ['status' => false, 'message' => 'Account is blocked. Contact Admin.'];
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_profile_pic'] = $user['profile_pic'];

                $redirect = '/user/services.php';
                if (in_array($user['role'], ['admin', 'manager', 'staff', 'accountant'])) {
                    $redirect = '/admin/index.php';
                }
                if ($user['role'] === 'delivery') $redirect = '/delivery/index.php';

                return ['status' => true, 'redirect' => $redirect];
            } else {
                 return ['status' => false, 'message' => 'Invalid password'];
            }
        }
        return ['status' => false, 'message' => 'Email not found'];
    }

    private function emailExists($email) {
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
