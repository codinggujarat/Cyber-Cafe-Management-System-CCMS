<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

requireLogin();
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// FORCE REFRESH: Fetch latest profile pic to ensure Navbar/Sidebar shows it immediately
// This fixes the issue where the session isn't updated until re-login
try {
    $stmt = $conn->prepare("SELECT profile_pic, name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $freshUser = $stmt->fetch();
    
    if ($freshUser) {
        $_SESSION['user_profile_pic'] = $freshUser['profile_pic'];
        $_SESSION['user_name'] = $freshUser['name']; // Keep name synced too
    }
} catch (Exception $e) {
    // Silent fail if DB issue
}

// Include the Global Header
include __DIR__ . '/../../includes/header.php';
?>
