<?php
require_once 'config/db.php';
require_once 'config/functions.php'; // For mask functions if needed, or define here

$database = new Database();
$conn = $database->getConnection();

$hash = $_GET['hash'] ?? '';

if (empty($hash)) {
    die("Invalid Tracking Link");
}

// Fetch Order by Hash
$stmt = $conn->prepare("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.order_hash = ?
");
$stmt->execute([$hash]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order Not Found");
}

// Fetch Items
$stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mask Name: A***n
$nameParts = explode(' ', $order['user_name']);
$maskedName = substr($nameParts[0], 0, 1) . str_repeat('*', max(3, strlen($nameParts[0]) - 2)) . substr($nameParts[0], -1);

// Status Config
$steps = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered'];
$currentStatus = $order['order_status'];
$canceled = $currentStatus === 'cancelled';
$returned = ($order['return_status'] ?? 'none') !== 'none';

$currentIndex = array_search($currentStatus, $steps);
if ($currentIndex === false) $currentIndex = -1; // For cancelled/other
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?= $order['id'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 min-h-screen">

    <div class="max-w-md mx-auto bg-white min-h-screen shadow-2xl relative overflow-hidden">
        
        <!-- Header -->
        <div class="bg-black text-white p-6 pb-12 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-600 rounded-full blur-3xl opacity-50 -mr-10 -mt-10"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-400 uppercase tracking-widest">Order Tracking</span>
                    <span class="bg-white/10 px-2 py-1 rounded text-[10px] backdrop-blur-sm">
                        <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                    </span>
                </div>
                <h1 class="text-3xl font-bold">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h1>
                <p class="text-sm text-gray-400 mt-1">Customer: <span class="text-white"><?= $maskedName ?></span></p>
            </div>
        </div>

        <!-- Status Card -->
        <div class="relative px-6 -mt-8 mb-6">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <!-- Status Badge -->
                <div class="flex justify-center mb-6">
                    <?php if($canceled): ?>
                        <span class="px-4 py-2 bg-red-100 text-red-700 rounded-full font-bold text-sm flex items-center gap-2">
                            <i class="fas fa-times-circle"></i> Cancelled
                        </span>
                    <?php elseif($returned): ?>
                         <span class="px-4 py-2 bg-pink-100 text-pink-700 rounded-full font-bold text-sm flex items-center gap-2">
                            <i class="fas fa-undo"></i> Return Requested
                        </span>
                    <?php else: ?>
                        <span class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-full font-bold text-sm flex items-center gap-2">
                            <i class="fas fa-circle animate-pulse text-[10px]"></i> <?= ucfirst(str_replace('_', ' ', $currentStatus)) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Timeline -->
                <?php if(!$canceled): ?>
                <div class="space-y-6 relative ml-2">
                    <!-- Vertical Line -->
                    <div class="absolute left-3 top-2 bottom-2 w-0.5 bg-gray-100 -z-10"></div>
                    
                    <?php foreach ($steps as $index => $step): ?>
                        <?php 
                            $active = $index <= $currentIndex;
                            $current = $index === $currentIndex;
                            
                            $icons = [
                                'pending' => 'fa-clipboard-list',
                                'confirmed' => 'fa-check-circle',
                                'preparing' => 'fa-cog',
                                'out_for_delivery' => 'fa-motorcycle',
                                'delivered' => 'fa-box-open'
                            ];
                        ?>
                        <div class="flex items-center gap-4 <?= $active ? 'opacity-100' : 'opacity-40 grayscale' ?>">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center border-2 <?= $active ? 'bg-black border-black text-white' : 'bg-white border-gray-300 text-gray-400' ?> z-10 transition-all shadow-sm">
                                <i class="fas <?= $icons[$step] ?> text-[10px]"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900"><?= ucwords(str_replace('_', ' ', $step)) ?></h4>
                                <?php if($current): ?>
                                    <p class="text-[10px] text-indigo-600 font-medium">Current Status</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Summary -->
        <div class="px-6 pb-8">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-3">Items Ordered</h3>
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
                <?php foreach ($items as $item): ?>
                <div class="p-4 flex items-center justify-between border-b border-gray-50 last:border-0">
                    <div>
                        <p class="font-medium text-gray-800 text-sm"><?= $item['service_name'] ?></p>
                        <p class="text-xs text-gray-500">Qty: <?= $item['quantity'] ?></p>
                    </div>
                    <span class="font-bold text-sm">₹<?= $item['price'] * $item['quantity'] ?></span>
                </div>
                <?php endforeach; ?>
                <div class="p-4 bg-gray-50 flex justify-between items-center">
                    <span class="font-bold text-gray-700">Total</span>
                    <span class="font-bold text-xl text-black">₹<?= $order['total_amount'] ?></span>
                </div>
            </div>
            
            <p class="text-center text-xs text-gray-400 mt-8">
                &copy; <?= date('Y') ?> Cyber Cafe System
            </p>
        </div>
    </div>

</body>
</html>
