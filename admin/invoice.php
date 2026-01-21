<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireAdmin();
$db = new Database();
$conn = $db->getConnection();

if (!isset($_GET['id'])) {
    die("Invalid Order ID");
}

$order_id = $_GET['id'];
// ADMIN: Can view ANY order
$order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch();

if (!$order) {
    die("Order not found.");
}

$user = $conn->query("SELECT * FROM users WHERE id = " . $order['user_id'])->fetch();
$items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $order_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-3xl mx-auto bg-white p-10 shadow-lg rounded-lg">
        
        <div class="flex justify-between items-center mb-10 border-b pb-6">
            <div>
                <h1 class="text-4xl font-bold text-gray-800">INVOICE</h1>
                <p class="text-gray-500">#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-indigo-700"><?= APP_NAME ?></h2>
                <p class="text-sm text-gray-500">Your Trusted Cyber Services Partner</p>
                <p class="text-sm text-gray-500">support@cybercafe.com</p>
            </div>
        </div>

        <div class="flex justify-between mb-10">
            <div>
                <h3 class="font-bold text-gray-700 uppercase text-xs mb-2">Billed To:</h3>
                <p class="font-bold text-lg"><?= htmlspecialchars($user['name']) ?></p>
                <p class="text-gray-600 w-64"><?= htmlspecialchars($order['shipping_address']) ?></p>
                <p class="text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <div class="text-right">
                <div class="mb-2">
                    <span class="font-bold text-gray-700 uppercase text-xs">Order Date:</span>
                    <p class="text-gray-600"><?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                </div>
                 <div>
                    <span class="font-bold text-gray-700 uppercase text-xs">Payment Method:</span>
                    <p class="text-gray-600 uppercase"><?= $order['payment_method'] ?></p>
                </div>
            </div>
        </div>

        <table class="w-full mb-10">
            <thead>
                <tr class="text-left border-b-2 border-gray-300">
                    <th class="pb-3 font-bold text-gray-700 uppercase text-xs">Item Description</th>
                    <th class="pb-3 text-right font-bold text-gray-700 uppercase text-xs">Qty</th>
                    <th class="pb-3 text-right font-bold text-gray-700 uppercase text-xs">Price</th>
                    <th class="pb-3 text-right font-bold text-gray-700 uppercase text-xs">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr class="border-b border-gray-100">
                    <td class="py-4">
                        <p class="font-bold text-gray-800"><?= htmlspecialchars($item['service_name']) ?></p>
                        <?php if($item['instructions']): ?>
                            <p class="text-xs text-gray-500">Note: <?= htmlspecialchars($item['instructions']) ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="py-4 text-right"><?= $item['quantity'] ?></td>
                    <td class="py-4 text-right"><?= formatPrice($item['price']) ?></td>
                    <td class="py-4 text-right font-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="flex justify-end border-t border-gray-300 pt-6">
            <div class="text-right">
                <p class="text-gray-600 mb-2">Subtotal: <span class="font-bold text-gray-800 ml-4"><?= formatPrice($order['total_amount']) ?></span></p>
                <p class="text-xl font-bold text-indigo-700 mt-4">Total Due: <?= formatPrice($order['total_amount']) ?></p>
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-gray-100 text-center text-gray-500 text-sm">
            <p>Thank you for your business!</p>
        </div>

        <div class="mt-8 text-center no-print">
            <button onclick="window.print()" class="bg-indigo-600 text-white font-bold py-2 px-6 rounded hover:bg-indigo-700 transition">Download / Print Invoice</button>
            <a href="order_details.php?id=<?= $order_id ?>" class="ml-4 text-gray-600 hover:underline">Back to Order Details</a>
        </div>

    </div>

</body>
</html>
