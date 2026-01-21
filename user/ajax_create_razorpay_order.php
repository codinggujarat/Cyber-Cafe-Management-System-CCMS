<?php
// ajax_create_razorpay_order.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

$db = new Database();
$conn = $db->getConnection(); 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$amount = $input['amount'] ?? 0;

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

// Razorpay Order Creation
// Ensure these constants are defined in your config
$key_id = defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : 'YOUR_KEY_ID';
$key_secret = defined('RAZORPAY_KEY_SECRET') ? RAZORPAY_KEY_SECRET : 'YOUR_KEY_SECRET';

if ($key_id === 'YOUR_KEY_ID') {
     http_response_code(500);
     echo json_encode(['error' => 'Razorpay Keys Not Configured']);
     exit;
}

$url = 'https://api.razorpay.com/v1/orders';

$data = [
    'amount' => $amount * 100, // Amount in paise
    'currency' => 'INR',
    'payment_capture' => 1 // Auto capture
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Curl Error: ' . curl_error($ch)]);
    exit;
}

curl_close($ch);

$order = json_decode($response, true);

if (isset($order['id'])) {
    echo json_encode([
        'id' => $order['id'],
        'amount' => $order['amount'],
        'key' => $key_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Razorpay Error', 'details' => $order]);
}
?>
