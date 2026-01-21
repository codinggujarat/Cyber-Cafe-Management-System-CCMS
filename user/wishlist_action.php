<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireLogin();
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'])) {
    $sid = $_POST['service_id'];
    
    // Toggle Logic
    $check = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND service_id = ?");
    $check->execute([$user_id, $sid]);
    
    if ($check->rowCount() > 0) {
        $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND service_id = ?")->execute([$user_id, $sid]);
        setFlash('success', 'Removed from wishlist');
    } else {
        $conn->prepare("INSERT INTO wishlist (user_id, service_id) VALUES (?, ?)")->execute([$user_id, $sid]);
        setFlash('success', 'Added to wishlist');
    }
}

// Return to previous page
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
