<?php
require_once '../config/db.php';
require_once '../config/functions.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ---------------------------------------------------------
    // CANCEL ORDER
    // ---------------------------------------------------------
    if ($action === 'cancel_order') {
        $order_id = $_POST['order_id'];
        $reason = $_POST['reason'];

        // Verify Ownership & Status
        $stmt = $conn->prepare("SELECT order_status FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();

        if ($order && in_array($order['order_status'], ['pending', 'confirmed'])) {
            $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled', cancellation_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $order_id]);
            
            // Add Notification
            addNotification($conn, $user_id, "Order Cancelled", "Order #$order_id has been cancelled.", "info");
            
            $_SESSION['flash_success'] = "Order cancelled successfully.";
        } else {
            $_SESSION['flash_error'] = "Order cannot be cancelled at this stage.";
        }
    }

    // ---------------------------------------------------------
    // RETURN ORDER
    // ---------------------------------------------------------
    elseif ($action === 'return_order') {
        $order_id = $_POST['order_id'];
        $reason = $_POST['reason'];

        // Verify Ownership & Status
        $stmt = $conn->prepare("SELECT order_status, return_status FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();

        if ($order && $order['order_status'] === 'delivered' && $order['return_status'] === 'none') {
            $stmt = $conn->prepare("UPDATE orders SET return_status = 'requested', return_reason = ?, is_returned = 1 WHERE id = ?");
            $stmt->execute([$reason, $order_id]);

             // Add Notification
             addNotification($conn, $user_id, "Return Requested", "Return request for Order #$order_id submitted.", "info");

            $_SESSION['flash_success'] = "Return request submitted.";
        } else {
            $_SESSION['flash_error'] = "Return cannot be processed.";
        }
    }

    // ---------------------------------------------------------
    // RE-ORDER (Clone to Cart)
    // ---------------------------------------------------------
    elseif ($action === 'reorder') {
        $order_id = $_POST['order_id'];

        // Get Items
        $stmt = $conn->prepare("SELECT service_id, quantity FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($items) {
             // Clear existing cart? Optional. Let's append to cart.
            $insert = $conn->prepare("INSERT INTO cart (user_id, service_id, quantity) VALUES (?, ?, ?)");
            foreach ($items as $item) {
                // Check if already in cart
                $check = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND service_id = ?");
                $check->execute([$user_id, $item['service_id']]);
                if (!$check->fetch()) {
                    $insert->execute([$user_id, $item['service_id'], $item['quantity']]);
                }
            }
            $_SESSION['flash_success'] = "Items added to cart!";
            header('Location: cart.php');
            exit;
        } else {
            $_SESSION['flash_error'] = "Could not find items to reorder.";
        }
    }
}

// Redirect back to orders page
header('Location: orders.php');
exit;

// Helper function just in case functions.php isn't robust
function addNotification($conn, $user_id, $title, $message, $type) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $message, $type]);
}
?>
