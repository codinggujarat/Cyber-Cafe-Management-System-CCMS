<?php
include 'includes/header.php';

if (!isset($_GET['id'])) {
    redirect('orders.php');
}

$order_id = $_GET['id'];

// Fetch Order
$order = $conn->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id AND o.user_id = $user_id")->fetch();

if (!$order) {
    redirect('orders.php');
}

// Fetch Items
$items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id")->fetchAll();
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="orders.php" class="text-gray-500 hover:text-black transition">Orders</a></li>
            <li><i class="fas fa-chevron-right text-xs text-gray-400"></i></li>
            <li class="font-bold text-black">Order #<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?></li>
        </ol>
    </nav>

    <!-- Order Header -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Order #<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?></h1>
            <p class="text-sm text-gray-500 mt-1">
                Placed on <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?>
            </p>
        </div>
        <div class="flex gap-3 items-center">
            <!-- QR Code (Hidden on mobile, visible on desktop) -->
             <div class="hidden md:block group relative">
                <?php 
                    $trackLink = BASE_URL . 'track_public.php?hash=' . ($order['order_hash'] ?? '');
                    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode($trackLink);
                ?>
                <img src="<?= $qrUrl ?>" alt="QR" class="w-10 h-10 border rounded mix-blend-multiply cursor-help">
                
                <!-- Hover Tooltip -->
                 <div class="absolute top-12 right-0 w-32 bg-white p-2 rounded-lg shadow-xl border border-gray-100 hidden group-hover:block z-50 text-center">
                    <img src="<?= $qrUrl ?>" class="w-full mix-blend-multiply mb-1">
                    <p class="text-[10px] text-gray-500">Scan to Track</p>
                </div>
            </div>

             <a href="invoice.php?id=<?= $order['id'] ?>" target="_blank" class="px-5 py-2.5 bg-gray-50 text-gray-900 font-bold text-sm rounded-xl hover:bg-gray-100 transition border border-gray-200">
                <i class="fas fa-file-invoice mr-2"></i> Invoice
            </a>
            <?php if($order['order_status'] !== 'delivered' && $order['order_status'] !== 'cancelled'): ?>
            <a href="track_order.php?id=<?= $order['id'] ?>" class="px-5 py-2.5 bg-black text-white font-bold text-sm rounded-xl hover:bg-gray-800 transition shadow-lg shadow-gray-200">
                <i class="fas fa-map-marker-alt mr-2"></i> Track Delivery
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items & Workflow -->
    <div class="space-y-6">
        <?php foreach ($items as $item): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Service Icon/Image -->
                <div class="w-16 h-16 rounded-xl bg-gray-100 border border-gray-200 flex-shrink-0 flex items-center justify-center">
                    <i class="fas fa-file-alt text-2xl text-gray-400"></i>
                </div>

                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($item['service_name']) ?></h3>
                            <p class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?> • Status: 
                                <span class="font-medium text-black capitalize"><?= str_replace('_', ' ', $item['service_status'] ?? 'submitted') ?></span>
                            </p>
                        </div>
                        <p class="text-lg font-bold text-gray-900">₹<?= $item['price'] * $item['quantity'] ?></p>
                    </div>

                    <!-- Workflow Progress Bar -->
                    <?php 
                        $stages = ['submitted', 'in_process', 'govt_submitted', 'approved', 'delivered'];
                        $current = $item['service_status'] ?? 'submitted';
                        $currentIndex = array_search($current, $stages);
                        if($current == 'rejected') $currentIndex = -1;
                    ?>
                    
                     <?php if($current == 'rejected'): ?>
                        <div class="mt-6 p-4 bg-red-50 rounded-xl border border-red-100 text-red-700">
                             <div class="flex items-center gap-3">
                                 <i class="fas fa-times-circle text-xl"></i>
                                 <div>
                                     <p class="font-bold">Application Rejected</p>
                                     <p class="text-sm"><?= htmlspecialchars($item['rejection_reason'] ?? 'Please contact detailed support.') ?></p>
                                 </div>
                             </div>
                        </div>
                     <?php else: ?>
                        <!-- Progress Steps -->
                        <div class="mt-6 relative">
                            <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-100 -translate-y-1/2 rounded-full z-0"></div>
                            <div class="relative z-10 flex justify-between">
                                <?php foreach($stages as $index => $stage): 
                                    $active = $index <= $currentIndex;
                                    $currentStep = $index === $currentIndex;
                                ?>
                                <div class="flex flex-col items-center gap-2 group">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 border-2 <?= $active ? 'bg-black border-black text-white' : 'bg-white border-gray-200 text-gray-300 group-hover:border-gray-300' ?> <?= $currentStep ? 'ring-4 ring-gray-100' : '' ?>">
                                        <?php if($active): ?><i class="fas fa-check"></i><?php else: ?><?= $index + 1 ?><?php endif; ?>
                                    </div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider <?= $active ? 'text-black' : 'text-gray-300' ?> hidden sm:block">
                                        <?= str_replace('Govt', 'Govt.', ucwords(str_replace('_', ' ', $stage))) ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                     <?php endif; ?>

                    <!-- Action Area (Receipt & App No) -->
                    <?php if($item['govt_application_no'] || $item['acknowledgement_file']): ?>
                    <div class="mt-6 bg-indigo-50/50 rounded-xl border border-indigo-100 p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                <i class="fas fa-university"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-indigo-900">Government Portal Details</h4>
                                <?php if($item['govt_application_no']): ?>
                                    <p class="text-xs text-indigo-700">App No: <span class="font-mono font-bold"><?= htmlspecialchars($item['govt_application_no']) ?></span></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if($item['acknowledgement_file']): ?>
                            <a href="../uploads/receipts/<?= $item['acknowledgement_file'] ?>" download class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
                                <i class="fas fa-download"></i> Download Receipt
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
