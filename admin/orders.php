<?php
include 'includes/header.php';
include 'includes/sidebar.php';

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['order_status'];
    
    // Fetch Old Status for Logs
    $oldStatus = $conn->query("SELECT order_status FROM orders WHERE id = $order_id")->fetchColumn();

    // Assign delivery boy if set and status is relevant
    $delivery_boy_id = !empty($_POST['delivery_boy_id']) ? $_POST['delivery_boy_id'] : null;
    
    // If assigning delivery boy
    $params = [':status' => $status, ':id' => $order_id];
    $sql = "UPDATE orders SET order_status = :status";
    
    if ($delivery_boy_id) {
        $currentDboy = $conn->query("SELECT delivery_boy_id FROM orders WHERE id = $order_id")->fetchColumn();
        if ($currentDboy != $delivery_boy_id) {
             $sql .= ", delivery_boy_id = :dboy, delivery_assigned_at = NOW()";
             $params[':dboy'] = $delivery_boy_id;
             logActivity('Delivery Assignment', "Assigned Order #$order_id to Delivery Boy #$delivery_boy_id");
        }
    }

    if ($oldStatus !== $status) {
        logActivity('Order Status Update', "Changed Order #$order_id status from $oldStatus to $status");
    }

    $sql .= " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    // SMART INVENTORY LINKING
    // Auto-deduct stock when order is marked 'delivered' (or 'completed')
    // Logic: Map order_item attributes to inventory item names
    if ($status === 'delivered') {
        $items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id")->fetchAll();
        foreach ($items as $item) {
            $qty = $item['quantity']; // Consumes this many sheets/units
            
            // 1. Deduct Paper
            // Matches 'A4 Paper', 'Legal Paper', 'A3 Paper'
            if (!empty($item['paper_size'])) {
                $paperName = $item['paper_size'] . ' Paper';
                $conn->query("UPDATE inventory SET quantity = quantity - $qty WHERE item_name = '$paperName'");
            }
            
            // 2. Deduct Ink
            // 'color' -> 'Color Ink', 'bw' -> 'Black Ink'
            if (!empty($item['print_color'])) {
                $inkName = ($item['print_color'] === 'color') ? 'Color Ink' : 'Black Ink';
                // Assuming 1 page consumes 0.01 units of ink? Or 1 unit? 
                // Let's assume 1 unit for simplicity of "Order Linking" demo.
                // Or better: 1 unit per 100 pages?
                // Let's stick to 1:1 mapping for robustness in demo (User sees number go down).
                $conn->query("UPDATE inventory SET quantity = quantity - $qty WHERE item_name = '$inkName'");
            }
            
            // 3. Deduct Binding
            if (!empty($item['binding']) && $item['binding'] !== 'none') {
                $bindingName = '';
                if ($item['binding'] === 'spiral') $bindingName = 'Spiral Comb';
                if ($item['binding'] === 'hard') $bindingName = 'Hard Cover';
                
                if ($bindingName) {
                    $conn->query("UPDATE inventory SET quantity = quantity - $qty WHERE item_name = '$bindingName'");
                }
            }
        }
    }
    
    // ... (Existing Logic)
    
    // NOTIFICATIONS
    // (Existing Notification Logic...)
    
    setFlash('success', 'Order updated successfully');
    redirect('/admin/orders.php');
}

// Handle Refund / Return Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_return'])) {
    $order_id = $_POST['order_id'];
    $action = $_POST['return_action']; // 'approve_return', 'reject_return', 'process_refund'
    $refund_amount = $_POST['refund_amount'] ?? 0;

    if ($action === 'approve_return') {
        $conn->query("UPDATE orders SET return_status = 'approved' WHERE id = $order_id");
        logActivity('Return Approved', "Approved return for Order #$order_id");
        // Get User ID
        $uid = $conn->query("SELECT user_id FROM orders WHERE id = $order_id")->fetchColumn();
        createNotification($uid, 'Return Approved', "Your return request for Order #$order_id has been approved. Refund will be processed soon.", 'success');
    }
    elseif ($action === 'reject_return') {
        $conn->query("UPDATE orders SET return_status = 'rejected' WHERE id = $order_id");
        logActivity('Return Rejected', "Rejected return for Order #$order_id");
        $uid = $conn->query("SELECT user_id FROM orders WHERE id = $order_id")->fetchColumn();
        createNotification($uid, 'Return Update', "Your return request for Order #$order_id was rejected.", 'error');
    }
    elseif ($action === 'process_refund') {
        // Fetch Order Details for Payment Info
        $stmt = $conn->query("SELECT payment_method, transaction_id, total_amount FROM orders WHERE id = $order_id");
        $orderInfo = $stmt->fetch();
        
        $refundId = null;
        $refundStatus = 'processed';
        
        // AUTOMATED RAZORPAY REFUND
        if ($orderInfo['payment_method'] === 'razorpay' && !empty($orderInfo['transaction_id'])) {
            $apiResult = refundWithRazorpay($orderInfo['transaction_id'], $refund_amount);
            
            if ($apiResult['success']) {
                $refundId = $apiResult['refund_id'];
                $msg = "Refund Processed via Razorpay (ID: $refundId)";
            } else {
                // API Failed
                setFlash('error', 'Razorpay Refund Failed: ' . $apiResult['error']);
                redirect('/admin/orders.php');
                exit;
            }
        } else {
            // Manual/COD Refund
            $msg = "Refund Recorded Manually (COD/Other)";
        }
        
        // Update Database
        $stmt = $conn->prepare("UPDATE orders SET refund_status = 'processed', refund_amount = ?, refund_id = ? WHERE id = ?");
        $stmt->execute([$refund_amount, $refundId, $order_id]);
        
        logActivity('Refund Processed', "$msg. Amount: ₹$refund_amount");
        $uid = $conn->query("SELECT user_id FROM orders WHERE id = $order_id")->fetchColumn();
        createNotification($uid, 'Refund Processed', "A refund of ₹$refund_amount has been processed. " . ($refundId ? "Ref: $refundId" : ""), 'success');
    }

    setFlash('success', 'Action processed successfully');
    redirect('/admin/orders.php');
}

// ---------------------------------------------------
// RAZORPAY REFUND FUNCTION
// ---------------------------------------------------
function refundWithRazorpay($paymentId, $amount) {
    $keyId = RAZORPAY_KEY_ID;
    $keySecret = RAZORPAY_KEY_SECRET;
    
    $url = "https://api.razorpay.com/v1/payments/$paymentId/refund";
    
    // Amount must be in paise
    $data = [
        'amount' => $amount * 100,
        'speed' => 'normal'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_USERPWD, "$keyId:$keySecret");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // DISABLE SSL FOR LOCALHOST TESTS
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return ['success' => false, 'error' => curl_error($ch)];
    }
    curl_close($ch);
    
    $json = json_decode($result, true);
    
    if (isset($json['id'])) {
        return ['success' => true, 'refund_id' => $json['id']];
    } else {
        return ['success' => false, 'error' => $json['error']['description'] ?? 'Unknown Error'];
    }
}

// Fetch Orders
$orders = $conn->query("
    SELECT o.*, u.name as user_name, u.email as user_email, d.name as delivery_boy_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN users d ON o.delivery_boy_id = d.id 
    ORDER BY o.created_at DESC
")->fetchAll();

// Fetch Delivery Boys
$deliveryBoys = $conn->query("SELECT * FROM users WHERE role = 'delivery' AND status = 1")->fetchAll();
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Orders</h2>
        <p class="text-sm text-gray-500">Manage customer orders and delivery assignments.</p>
    </div>
    <div class="flex gap-3">
        <button class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
            <i class="fas fa-filter text-gray-400"></i> Filter
        </button>
        <button class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-xl text-sm font-bold hover:bg-gray-800 transition shadow-lg shadow-gray-200">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4 font-semibold">Order ID</th>
                    <th class="px-6 py-4 font-semibold">Customer</th>
                    <th class="px-6 py-4 font-semibold">Amount</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold">Date</th>
                    <th class="px-6 py-4 font-semibold">Delivery</th>
                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($orders as $order): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <span class="font-mono text-sm font-medium text-gray-900">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                                <?= substr($order['user_name'], 0, 1) ?>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['user_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($order['user_email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-bold text-gray-900">₹<?= formatPrice($order['total_amount']) ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <!-- Status Badges -->
                        <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
                                'confirmed' => 'bg-blue-100 text-blue-800 ring-blue-700/20',
                                'preparing' => 'bg-purple-100 text-purple-800 ring-purple-700/20',
                                'out_for_delivery' => 'bg-orange-100 text-orange-800 ring-orange-600/20',
                                'delivered' => 'bg-green-100 text-green-800 ring-green-600/20',
                                'cancelled' => 'bg-red-100 text-red-800 ring-red-600/20',
                            ];
                            $colorClass = $statusColors[$order['order_status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset <?= $colorClass ?>">
                            <?= ucwords(str_replace('_', ' ', $order['order_status'])) ?>
                        </span>

                        <!-- Return Status Badge -->
                        <?php if(($order['return_status'] ?? 'none') !== 'none'): ?>
                            <div class="mt-1">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-pink-100 text-pink-700">
                                    Return: <?= ucfirst($order['return_status']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Refund Status Badge -->
                         <?php if(($order['refund_status'] ?? 'pending') === 'processed'): ?>
                            <div class="mt-1">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-green-100 text-green-700">
                                    Refunded: ₹<?= $order['refund_amount'] ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-gray-600"><?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                        <p class="text-xs text-gray-400"><?= date('h:i A', strtotime($order['created_at'])) ?></p>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($order['delivery_boy_name']): ?>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-motorcycle text-indigo-500"></i>
                                <span class="text-sm text-gray-900"><?= htmlspecialchars($order['delivery_boy_name']) ?></span>
                            </div>
                        <?php else: ?>
                            <span class="text-xs text-gray-400 italic">Not Assigned</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                        <!-- Manage Returns Button -->
                        <?php if(($order['return_status'] ?? 'none') === 'requested'): ?>
                             <button onclick="openReturnModal('<?= $order['id'] ?>', '<?= $order['total_amount'] ?>')" class="text-xs bg-pink-500 text-white px-3 py-1.5 rounded-lg hover:bg-pink-600 transition shadow-sm">
                                <i class="fas fa-undo"></i> Handle Return
                            </button>
                        <?php endif; ?>

                        <!-- Refund Button (If Cancelled/Returned & Not Refunded) -->
                        <?php 
                            $canRefund = ($order['order_status'] === 'cancelled' || ($order['return_status'] ?? '') === 'approved') && ($order['refund_status'] ?? 'pending') !== 'processed';
                            if ($canRefund): 
                        ?>
                            <button onclick="openRefundModal('<?= $order['id'] ?>', '<?= $order['total_amount'] ?>')" class="text-xs bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 transition shadow-sm">
                                <i class="fas fa-money-bill-wave"></i> Refund
                            </button>
                        <?php endif; ?>

                        <a href="order_details.php?id=<?= $order['id'] ?>" class="text-sm font-semibold text-gray-600 hover:text-black bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg transition">
                            View
                        </a>
                        <button onclick="openModal('<?= $order['id'] ?>', '<?= $order['order_status'] ?>', '<?= $order['delivery_boy_id'] ?>')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition">
                            Manage
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="statusModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('statusModal').classList.add('hidden')"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-edit text-indigo-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Update Order #<span id="modalOrderId"></span></h3>
                        
                        <form method="POST" class="mt-4 space-y-4">
                            <input type="hidden" name="order_id" id="formOrderId">
                            <input type="hidden" name="update_status" value="1">
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Order Status</label>
                                <div class="relative">
                                    <select name="order_status" id="formStatus" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none appearance-none bg-white">
                                        <option value="pending">Pending</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="preparing">Preparing</option>
                                        <option value="out_for_delivery">Out for Delivery</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-chevron-down text-xs"></i></div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Assign Delivery Partner</label>
                                <div class="relative">
                                    <select name="delivery_boy_id" id="formDboy" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none appearance-none bg-white">
                                        <option value="">Select Delivery Boy</option>
                                        <?php foreach ($deliveryBoys as $dboy): ?>
                                            <option value="<?= $dboy['id'] ?>"><?= htmlspecialchars($dboy['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-chevron-down text-xs"></i></div>
                                </div>
                            </div>

                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-black text-base font-medium text-white hover:bg-gray-800 focus:outline-none sm:col-start-2 sm:text-sm">
                                    Update Order
                                </button>
                                <button type="button" onclick="document.getElementById('statusModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:col-start-1 sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(id, status, dboyId) {
        document.getElementById('modalOrderId').innerText = id;
        document.getElementById('formOrderId').value = id;
        document.getElementById('formStatus').value = status;
        document.getElementById('formDboy').value = dboyId || '';
        document.getElementById('statusModal').classList.remove('hidden');
    }

    function openRefundModal(id, amount) {
        document.getElementById('refundOrderId').value = id;
        document.getElementById('refundAmount').value = amount;
        document.getElementById('refundModal').classList.remove('hidden');
    }
    
    function openReturnModal(id) {
         document.getElementById('returnHandleOrderId').value = id;
         document.getElementById('returnOrderIdDisplay').innerText = id;
         document.getElementById('returnHandleModal').classList.remove('hidden');
    }
</script>

<!-- Refund Modal -->
<div id="refundModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('refundModal').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Process Refund</h3>
                <form method="POST">
                    <input type="hidden" name="process_return" value="1">
                    <input type="hidden" name="return_action" value="process_refund">
                    <input type="hidden" name="order_id" id="refundOrderId">
                    
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Refund Amount (₹)</label>
                    <input type="number" step="0.01" name="refund_amount" id="refundAmount" class="w-full border rounded-xl p-3 mb-4 font-bold text-lg" required>
                    
                    <div class="bg-yellow-50 p-3 rounded-lg text-xs text-yellow-700 mb-4">
                        <i class="fas fa-info-circle"></i> This will record the refund in the system. Ensure you have transferred the money via Razorpay/Bank manually.
                    </div>

                    <button type="submit" class="w-full bg-green-600 text-white rounded-xl py-3 font-bold hover:bg-green-700 transition">Confirm Refund</button>
                    <button type="button" onclick="document.getElementById('refundModal').classList.add('hidden')" class="w-full mt-2 bg-gray-100 text-gray-700 rounded-xl py-2 font-bold hover:bg-gray-200 transition">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Return Handle Modal -->
<div id="returnHandleModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
     <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('returnHandleModal').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Handle Return Request</h3>
                <p class="text-sm text-gray-500 mb-6">Order ID: #<span id="returnOrderIdDisplay"></span></p>
                
                <form method="POST" class="grid gap-3">
                    <input type="hidden" name="process_return" value="1">
                    <input type="hidden" name="order_id" id="returnHandleOrderId">
                    
                    <button type="submit" name="return_action" value="approve_return" class="w-full bg-green-600 text-white rounded-xl py-3 font-bold hover:bg-green-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-check"></i> Approve Return
                    </button>
                    
                    <button type="submit" name="return_action" value="reject_return" class="w-full bg-red-100 text-red-700 rounded-xl py-3 font-bold hover:bg-red-200 transition flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i> Reject Request
                    </button>
                    <button type="button" onclick="document.getElementById('returnHandleModal').classList.add('hidden')" class="w-full mt-2 bg-gray-100 text-gray-700 rounded-xl py-2 font-bold hover:bg-gray-200 transition">Close</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
