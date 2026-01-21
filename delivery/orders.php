<?php
include 'includes/header.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = $_POST['order_id'];
    $action = $_POST['action'];
    $new_status = '';

    if ($action === 'out_for_delivery') {
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'out_for_delivery' WHERE id = :id AND delivery_boy_id = :uid");
        $stmt->execute([':id' => $order_id, ':uid' => $user_id]);
        setFlash('success', 'Order picked up. Timer started!');
    } elseif ($action === 'delivered') {
        // SLA Logic
        $orderInfo = $conn->query("SELECT delivery_assigned_at FROM orders WHERE id = $order_id")->fetch();
        $is_late = 0;
        $earning = 10.00; // Base Fee
        
        if ($orderInfo && $orderInfo['delivery_assigned_at']) {
            $assignedTime = strtotime($orderInfo['delivery_assigned_at']);
            $timeTaken = time() - $assignedTime;
            if ($timeTaken > (30 * 60)) { // 30 Mins SLA
                $is_late = 1;
                $earning = 5.00; // Penalty Applied
            }
        }

        $stmt = $conn->prepare("UPDATE orders SET order_status = 'delivered', delivered_at = NOW(), is_late = ?, earning_amount = ? WHERE id = ? AND delivery_boy_id = ?");
        $stmt->execute([$is_late, $earning, $order_id, $user_id]);
        
        // COD Handling
        $chk = $conn->query("SELECT payment_method FROM orders WHERE id = $order_id")->fetchColumn();
        if($chk === 'cod') {
             $conn->query("UPDATE orders SET payment_status = 'paid' WHERE id = $order_id");
        }
        
        if ($is_late) {
            setFlash('error', 'Order Delivered Late! Penalty applied.');
        } else {
            setFlash('success', 'Order Delivered on Time! Full earning credited.');
        }
    }
    redirect('/delivery/orders.php');
}

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';
$where = "delivery_boy_id = $user_id";

if ($filter == 'active') {
    $where .= " AND order_status NOT IN ('delivered', 'cancelled')";
} elseif ($filter == 'history') {
    $where .= " AND order_status IN ('delivered', 'cancelled')";
}

$orders = $conn->query("SELECT * FROM orders WHERE $where ORDER BY created_at DESC")->fetchAll();
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">My Deliveries</h2>
    <div class="bg-white rounded-lg p-1 shadow border border-gray-100">
        <a href="?filter=active" class="px-3 py-1.5 text-sm font-medium rounded-md <?= $filter == 'active' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-50' ?>">Active</a>
        <a href="?filter=history" class="px-3 py-1.5 text-sm font-medium rounded-md <?= $filter == 'history' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-50' ?>">History</a>
    </div>
</div>

<div class="space-y-4">
    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 relative overflow-hidden">
            
            <?php if ($filter == 'active' && $order['delivery_assigned_at']): ?>
                <?php 
                    $deadline = strtotime($order['delivery_assigned_at']) + (30 * 60); 
                    $remaining = $deadline - time();
                ?>
                <div class="absolute top-0 right-0 left-0 h-1 bg-gray-100">
                    <div class="h-full bg-green-500 transition-all duration-1000" id="progress-<?= $order['id'] ?>" style="width: 100%"></div>
                </div>
            <?php endif; ?>

            <div class="flex justify-between items-start mb-4 mt-2">
                <div>
                    <span class="inline-block bg-gray-100 text-gray-800 text-[10px] uppercase font-bold px-2 py-0.5 rounded mb-1">
                        #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?>
                    </span>
                    <h3 class="font-bold text-gray-900"><?= htmlspecialchars($order['shipping_address']) ?></h3>
                </div>
                
                <?php if ($filter == 'active' && $order['delivery_assigned_at']): ?>
                    <div class="text-right">
                         <div class="sla-timer font-mono font-bold text-xl <?= $remaining < 300 ? 'text-red-500' : 'text-green-600' ?>" data-deadline="<?= $deadline ?>">
                             --:--
                         </div>
                         <p class="text-[10px] text-gray-400 uppercase">SLA Timer</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-4">
                 <div>
                     <span class="block text-xs text-gray-400 uppercase">Amount</span>
                     <span class="font-bold text-gray-900">₹<?= formatPrice($order['total_amount']) ?></span>
                     <span class="text-xs ml-1"><?= strtoupper($order['payment_method']) ?></span>
                 </div>
                 <div>
                     <span class="block text-xs text-gray-400 uppercase">Assigned At</span>
                     <span class="font-medium"><?= $order['delivery_assigned_at'] ? date('h:i A', strtotime($order['delivery_assigned_at'])) : '-' ?></span>
                 </div>
            </div>

            <div class="flex justify-between items-center border-t border-gray-50 pt-4">
                 <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium 
                    <?php 
                    $st = $order['order_status']; 
                    if($st=='delivered') echo $order['is_late'] ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'; 
                    else echo 'bg-blue-50 text-blue-700';
                    ?>">
                     <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                     <?= ucwords(str_replace('_',' ', $st)) ?>
                     <?php if($order['is_late']) echo ' (Late)'; ?>
                 </span>
                 
                 <?php if($order['order_status'] != 'delivered' && $order['order_status'] != 'cancelled'): ?>
                    <?php if($order['order_status'] == 'out_for_delivery'): ?>
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="action" value="delivered">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-lg shadow-green-200 transition">
                                <i class="fas fa-check mr-1"></i> Mark Delivered
                            </button>
                        </form>
                    <?php else: ?>
                         <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="action" value="out_for_delivery">
                            <button type="submit" class="bg-black hover:bg-gray-800 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-lg transition">
                                Start Delivery
                            </button>
                        </form>
                    <?php endif; ?>
                 <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                <i class="fas fa-box-open text-2xl"></i>
            </div>
            <p class="text-gray-500">No orders found.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    // Live Timer & Progress
    function updateTimers() {
        const now = Math.floor(Date.now() / 1000);
        
        document.querySelectorAll('.sla-timer').forEach(el => {
            const deadline = parseInt(el.dataset.deadline);
            let diff = deadline - now;
            
            if (diff <= 0) {
                el.innerText = "LATE";
                el.classList.remove('text-green-600');
                el.classList.add('text-red-500');
            } else {
                const m = Math.floor(diff / 60);
                const s = diff % 60;
                el.innerText = `${m}:${s.toString().padStart(2, '0')}`;
                
                // Urgency Colors
                if (diff < 300) { // 5 mins
                    el.classList.remove('text-green-600');
                    el.classList.add('text-red-500', 'animate-pulse');
                }
            }
        });
    }
    
    setInterval(updateTimers, 1000);
    updateTimers();
</script>

<?php include 'includes/footer.php'; ?>
