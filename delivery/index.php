<?php
include 'includes/header.php';

// Stats
$today = date('Y-m-d');
$delivered_today = $conn->query("SELECT COUNT(*) FROM orders WHERE delivery_boy_id = $user_id AND order_status = 'delivered' AND DATE(updated_at) = '$today'")->fetchColumn();
$pending_deliveries = $conn->query("SELECT COUNT(*) FROM orders WHERE delivery_boy_id = $user_id AND order_status IN ('confirmed', 'preparing', 'out_for_delivery')")->fetchColumn();
$earnings_today = $delivered_today * 10; // 10 Rs per order
?>

<!-- Hero Stats Card -->
<div class="relative overflow-hidden rounded-3xl bg-black text-white shadow-xl mb-8 p-6">
    <div class="absolute top-0 right-0 -mr-16 -mt-16 h-48 w-48 rounded-full bg-gray-800 opacity-50 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -ml-16 -mb-16 h-48 w-48 rounded-full bg-indigo-900 opacity-50 blur-3xl"></div>
    
    <div class="relative z-10">
        <div class="flex justify-between items-start mb-6">
            <div>
                <p class="text-gray-400 text-xs font-medium uppercase tracking-wider">Today's Earnings</p>
                <h2 class="text-3xl font-bold mt-1">₹<?= number_format($earnings_today, 2) ?></h2>
            </div>
            <div class="p-2 bg-white/10 rounded-xl backdrop-blur-sm">
                <i class="fas fa-wallet text-xl"></i>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white/10 rounded-xl p-3 backdrop-blur-sm border border-white/5">
                <p class="text-gray-400 text-[10px] uppercase font-bold">Pending</p>
                <p class="text-xl font-bold text-orange-400"><?= $pending_deliveries ?></p>
            </div>
            <div class="bg-white/10 rounded-xl p-3 backdrop-blur-sm border border-white/5">
                <p class="text-gray-400 text-[10px] uppercase font-bold">Completed</p>
                <p class="text-xl font-bold text-green-400"><?= $delivered_today ?></p>
            </div>
        </div>
    </div>
</div>

<div class="flex items-center justify-between mb-4">
    <h3 class="font-bold text-gray-900 text-lg">Active Tasks</h3>
    <span class="text-xs font-medium text-gray-500"><?= date('D, M d') ?></span>
</div>

<?php
$activeOrders = $conn->query("SELECT o.*, u.name as user_name, u.phone as user_phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.delivery_boy_id = $user_id AND o.order_status NOT IN ('delivered', 'cancelled') ORDER BY o.created_at ASC")->fetchAll();

if (count($activeOrders) > 0):
    foreach ($activeOrders as $order):
?>
    <div class="bg-white rounded-2xl p-5 mb-5 shadow-sm border border-gray-100 relative overflow-hidden">
        <!-- Status Badge -->
        <div class="absolute top-0 right-0 px-3 py-1 bg-black text-white text-[10px] font-bold uppercase rounded-bl-xl">
            <?= str_replace('_', ' ', $order['order_status']) ?>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-gray-700 font-bold text-lg border border-gray-100">
                <?= substr($order['user_name'], 0, 1) ?>
            </div>
            <div>
                <h4 class="font-bold text-gray-900 text-lg">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h4>
                <p class="text-sm font-medium text-gray-500"><?= htmlspecialchars($order['user_name']) ?></p>
            </div>
        </div>
        
        <div class="space-y-3 mb-5">
             <div class="flex items-start gap-3">
                <div class="mt-1 w-6 h-6 rounded-full bg-indigo-50 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-map-marker-alt text-indigo-600 text-xs"></i>
                </div>
                <p class="text-sm text-gray-600 leading-snug"><?= htmlspecialchars($order['shipping_address']) ?></p>
             </div>
             <div class="flex items-center gap-3">
                <div class="w-6 h-6 rounded-full bg-green-50 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-phone text-green-600 text-xs"></i>
                </div>
                <a href="tel:<?= $order['user_phone'] ?>" class="text-sm font-medium text-gray-900 hover:underline"><?= $order['user_phone'] ?></a>
             </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
             <a href="orders.php?id=<?= $order['id'] ?>" class="flex items-center justify-center gap-2 bg-gray-100 text-gray-900 py-3 rounded-xl font-bold text-sm hover:bg-gray-200 transition">
                 Details
             </a>
             
             <?php if($order['order_status'] == 'out_for_delivery'): ?>
                 <form method="POST" action="orders.php">
                     <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                     <input type="hidden" name="action" value="delivered">
                     <button type="submit" class="w-full bg-green-500 text-white py-3 rounded-xl font-bold text-sm hover:bg-green-600 shadow-lg shadow-green-200 transition">
                         Complete
                     </button>
                 </form>
             <?php elseif($order['order_status'] != 'delivered'): ?>
                 <form method="POST" action="orders.php">
                     <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                     <input type="hidden" name="action" value="out_for_delivery">
                     <button type="submit" class="w-full bg-black text-white py-3 rounded-xl font-bold text-sm hover:bg-gray-800 shadow-lg shadow-gray-200 transition">
                         Start Delivery
                     </button>
                 </form>
             <?php endif; ?>
        </div>
    </div>
<?php endforeach; else: ?>
    <div class="text-center py-16">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
            <i class="fas fa-motorcycle text-3xl text-gray-300"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-900">All Caught Up!</h3>
        <p class="text-sm text-gray-500 mt-2 max-w-xs mx-auto">No pending deliveries assigned to you right now. Enjoy your break!</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
