<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$amount = $input['amount'] ?? 0;

if ($amount < 1) {
     echo json_encode(['error' => 'Minimum amount is ₹1']);
     exit;
}

// Create Razorpay Order
$api_key = RAZORPAY_KEY_ID;
$api_secret = RAZORPAY_KEY_SECRET;

$orderData = [
    'receipt'         => 'wlt_' . uniqid(),
    'amount'          => $amount * 100, // INR in paise
    'currency'        => 'INR',
    'payment_capture' => 1 
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':' . $api_secret);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$result = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Curl Error: ' . curl_error($ch)]);
} else {
    if ($http_status === 200) {
        $response = json_decode($result, true);
        echo json_encode([
            'id' => $response['id'],
            'amount' => $response['amount'],
            'key' => RAZORPAY_KEY_ID,
            'user_name' => $_SESSION['user_name'] ?? 'User',
            'user_email' => $_SESSION['user_email'] ?? 'user@example.com'
        ]);
    } else {
        echo json_encode(['error' => 'Razorpay Error: ' . $result]);
    }
}
curl_close($ch);
