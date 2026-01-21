<?php

function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . BASE_URL . $url);
        exit();
    } else {
        echo '<script>window.location.href="' . BASE_URL . $url . '";</script>';
        exit();
    }
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, info
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function hasRole($roles) {
    if (!is_array($roles)) $roles = [$roles];
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
}

function isAdmin() {
    return hasRole('admin');
}

function isEnterpriseUser() {
    return hasRole(['admin', 'manager', 'accountant', 'staff']);
}

function isDelivery() {
    return hasRole('delivery');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function requireAdmin() {
    // Allows access to Admin Panel Structure
    // Access control for specific pages handled in Sidebar/Page logic
    if (!isEnterpriseUser()) {
        redirect('/index.php');
    }
}

function requireRole($roles) {
    if (!hasRole($roles)) {
        setFlash('error', 'Access Denied: You do not have permission.');
        redirect('/admin/index.php');
    }
}

function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

function countPdfPages($path) {
    $pdf = file_get_contents($path);
    $number = preg_match_all("/\/Page\W/", $pdf, $dummy);
    return $number;
}

// Email Helper
function sendEmail($to, $subject, $message) {
    // Basic HTML Email Wrapper
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Cyber Cafe <noreply@cybercafe.com>' . "\r\n";
    $headers .= 'Reply-To: support@cybercafe.com' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();
    
    // Template
    $body = "
    <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; max-width: 600px; margin: 0 auto; color: #333;'>
        <div style='background: #000; padding: 20px; border-radius: 12px 12px 0 0;'>
            <h2 style='color: #fff; margin: 0; font-size: 18px;'>Cyber Cafe Service</h2>
        </div>
        <div style='padding: 30px; border: 1px solid #eee; border-top: 0; border-radius: 0 0 12px 12px; background: #fff;'>
            $message
            <p style='margin-top: 30px; font-size: 14px; color: #666;'>
                <a href='" . BASE_URL . "user/orders.php' style='color: #000; font-weight: bold; text-decoration: none;'>View Orders &rsaquo;</a>
            </p>
        </div>
        <div style='text-align: center; margin-top: 20px; font-size: 11px; color: #aaa;'>
            &copy; " . date('Y') . " Cyber Cafe Management System
        </div>
    </div>";
    
    // Attempt send (Suppress errors to correct flow)
    @mail($to, $subject, $body, $headers);
}

// Notification Helpers
function createNotification($user_id, $title, $message, $type = 'info') {
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $type]);
        
        // Trigger Email
        $user = $conn->query("SELECT email, name FROM users WHERE id = $user_id")->fetch();
        if ($user && !empty($user['email'])) {
             $emailSubject = "$title";
             $emailBody = "<p>Hello <strong>{$user['name']}</strong>,</p><h3 style='margin-top:0;'>$title</h3><p>$message</p>";
             sendEmail($user['email'], $emailSubject, $emailBody);
        }
        
    } catch (Exception $e) {
        // Silent fail
    }
}

function getRecentNotifications($user_id, $limit = 5) {
    global $conn;
    try {
        return $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $limit")->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function countUnreadNotifications($user_id) {
    global $conn;
    try {
        return $conn->query("SELECT COUNT(*) FROM notifications WHERE user_id = $user_id AND is_read = 0")->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function logActivity($action, $description = '') {
    global $conn;
    try {
        $user_id = $_SESSION['user_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $description, $ip]);
    } catch (Exception $e) {
        // Silent fail
    }
}
