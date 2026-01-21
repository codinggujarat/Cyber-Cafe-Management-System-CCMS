<?php
include 'includes/header.php';

if (!isset($_GET['id'])) {
    redirect('orders.php');
}

$order_id = $_GET['id'];
$order = $conn->query("SELECT o.*, d.name as dboy_name, d.phone as dboy_phone FROM orders o LEFT JOIN users d ON o.delivery_boy_id = d.id WHERE o.id = $order_id AND o.user_id = $user_id")->fetch();

if (!$order) {
    redirect('orders.php');
}

// Order Stages
$stages = [
    'pending' => ['label' => 'Order Placed', 'icon' => 'fa-clipboard-check', 'desc' => 'We have received your order.'],
    'confirmed' => ['label' => 'Confirmed', 'icon' => 'fa-check-circle', 'desc' => 'Order accepted by store.'],
    'preparing' => ['label' => 'Preparing', 'icon' => 'fa-print', 'desc' => 'Your files are printing.'],
    'out_for_delivery' => ['label' => 'On the Way', 'icon' => 'fa-motorcycle', 'desc' => 'Agent is delivering your order.'],
    'delivered' => ['label' => 'Delivered', 'icon' => 'fa-box-open', 'desc' => 'Order successfully delivered.']
];

$currentStatus = $order['order_status'];
$statusKeys = array_keys($stages);
$currentIndex = array_search($currentStatus, $statusKeys);

if ($currentStatus == 'cancelled') {
    $currentIndex = -1; // Handle separately
}
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="orders.php" class="text-sm text-gray-500 hover:text-black mb-2 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back to Orders</a>
            <h1 class="text-2xl font-bold text-gray-900">Track Order #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h1>
             <p class="text-sm text-gray-500">Expected Delivery: <?= date('h:i A', strtotime($order['created_at'] . ' + 2 hours')) ?></p>
        </div>
        <div class="text-right">
             <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase <?= $currentStatus == 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-black text-white' ?>">
                <?= str_replace('_', ' ', $currentStatus) ?>
             </span>
        </div>
    </div>

    <?php if($currentStatus == 'cancelled'): ?>
        <div class="bg-red-50 border border-red-100 rounded-2xl p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 text-red-600 text-2xl">
                <i class="fas fa-times"></i>
            </div>
            <h2 class="text-xl font-bold text-red-700 mb-2">Order Cancelled</h2>
            <p class="text-gray-600">This order has been cancelled. Please contact support if you have questions.</p>
        </div>
    <?php else: ?>
        
        <!-- Tracking Stepper -->
        <div class="bg-white rounded-3xl shadow-xl p-8 mb-8 border border-gray-100 relative overflow-hidden">
             <!-- Refresh Meta -->
            <meta http-equiv="refresh" content="30">
            <div class="absolute top-4 right-4 text-[10px] text-gray-400 animate-pulse flex items-center gap-1">
                <i class="fas fa-circle text-green-500 text-[6px]"></i> Live Updates
            </div>

            <div class="relative">
                <!-- Vertical Line (Mobile) / Horizontal (Desktop) -->
                <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-100 md:hidden"></div>
                <div class="hidden md:block absolute top-8 left-0 right-0 h-0.5 bg-gray-100"></div>
                
                <!-- Active Line -->
                <div class="hidden md:block absolute top-8 left-0 h-0.5 bg-green-500 transition-all duration-1000 ease-out" style="width: <?= ($currentIndex / (count($stages)-1)) * 100 ?>%"></div>

                <div class="flex flex-col md:flex-row justify-between relative z-10 gap-8 md:gap-0">
                    <?php $i = 0; foreach ($stages as $key => $stage): ?>
                        <?php 
                            $isActive = $i <= $currentIndex;
                            $isCurrent = $i === $currentIndex;
                            $color = $isActive ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400';
                            if ($isCurrent) $color = 'bg-black text-white ring-4 ring-gray-100';
                        ?>
                        <div class="flex md:flex-col items-center gap-4 md:gap-2 flex-1 md:text-center">
                            <div class="w-16 h-16 md:w-16 md:h-16 rounded-full flex items-center justify-center text-xl transition-all duration-500 <?= $color ?> shadow-lg">
                                <i class="fas <?= $stage['icon'] ?>"></i>
                            </div>
                            <div class="flex-1 md:flex-none">
                                <p class="font-bold text-sm text-gray-900 <?= $isActive ? '' : 'text-gray-400' ?>"><?= $stage['label'] ?></p>
                                <p class="text-xs text-gray-500 w-full md:w-32 md:mx-auto mt-1"><?= $stage['desc'] ?></p>
                            </div>
                        </div>
                    <?php $i++; endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Delivery Boy Info (Only when Out for Delivery) -->
        <?php if($currentStatus == 'out_for_delivery' && $order['dboy_name']): ?>
            <div class="bg-indigo-900 rounded-2xl p-6 text-white flex items-center justify-between shadow-xl">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-white/10 flex items-center justify-center text-2xl">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-xs uppercase font-bold tracking-wider">Your Delivery Partner</p>
                        <h3 class="text-xl font-bold"><?= htmlspecialchars($order['dboy_name']) ?></h3>
                        <p class="text-sm opacity-80"> is arriving soon with your package.</p>
                    </div>
                </div>
                <div>
                     <a href="tel:<?= $order['dboy_phone'] ?>" class="w-12 h-12 rounded-full bg-green-500 hover:bg-green-600 flex items-center justify-center text-white transition shadow-lg animate-bounce">
                        <i class="fas fa-phone"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
