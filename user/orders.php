<?php
include 'includes/header.php';

$orders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC")->fetchAll();
$totalSpent = $conn->query("SELECT SUM(total_amount) FROM orders WHERE user_id = $user_id AND order_status != 'cancelled'")->fetchColumn() ?: 0;
$activeCount = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND order_status NOT IN ('delivered', 'cancelled')")->fetchColumn();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 w-full">
            
            <!-- Hero / Header Section -->
            <div class="relative bg-black rounded-3xl overflow-hidden mb-8 shadow-xl">
                <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
                <div class="absolute top-0 right-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-indigo-900 opacity-50 blur-3xl"></div>
                
                <div class="relative z-10 p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-2xl font-bold text-white tracking-tight">My Orders</h1>
                        <p class="text-gray-400 mt-1 text-sm">Track services and invoices.</p>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/10">
                        <div class="px-3 border-r border-white/10">
                            <p class="text-[10px] text-gray-400 uppercase tracking-widest">Spent</p>
                            <p class="text-lg font-bold text-white">₹<?= formatPrice($totalSpent) ?></p>
                        </div>
                         <div class="px-3">
                            <p class="text-[10px] text-gray-400 uppercase tracking-widest">Active</p>
                            <p class="text-lg font-bold text-white"><?= $activeCount ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Details</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 bg-white">
                            <?php if (count($orders) > 0): ?>
                                <?php foreach ($orders as $order): ?>
                                <tr class="group hover:bg-gray-50 transition duration-150 ease-in-out">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-4">
                                            <?php
                                            $img = $conn->query("SELECT s.image FROM order_items oi JOIN services s ON oi.service_id = s.id WHERE oi.order_id = {$order['id']} LIMIT 1")->fetchColumn();
                                            $imgSrc = !empty($img) ? "../uploads/services/$img" : '../uploads/services/default_service.png';
                                            ?>
                                            <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100 border border-gray-200">
                                                <img src="<?= $imgSrc ?>" class="h-full w-full object-cover">
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-900">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                                <span class="text-xs text-gray-500"><?= date('M j, Y', strtotime($order['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800 ring-yellow-600/10',
                                                'confirmed' => 'bg-blue-100 text-blue-800 ring-blue-700/10',
                                                'preparing' => 'bg-purple-100 text-purple-800 ring-purple-700/10',
                                                'out_for_delivery' => 'bg-orange-100 text-orange-800 ring-orange-600/10',
                                                'delivered' => 'bg-green-100 text-green-800 ring-green-600/10',
                                                'cancelled' => 'bg-red-100 text-red-800 ring-red-600/10',
                                            ];
                                            $colorClass = $statusColors[$order['order_status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset <?= $colorClass ?>">
                                            <?= ucwords(str_replace('_', ' ', $order['order_status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-sm font-bold text-gray-900"><?= formatPrice($order['total_amount']) ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                         <a href="order_details.php?id=<?= $order['id'] ?>" class="inline-flex items-center gap-1 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold mr-2 hover:bg-gray-50 transition">
                                            <i class="fas fa-eye"></i> Details
                                         </a>
                                         <?php if(!in_array($order['order_status'], ['cancelled', 'delivered'])): ?>
                                             <a href="track_order.php?id=<?= $order['id'] ?>" class="inline-flex items-center gap-1 bg-black text-white px-3 py-1.5 rounded-lg text-xs font-bold mr-2 hover:bg-gray-800 transition">
                                                 <i class="fas fa-map-marker-alt"></i> Track
                                             </a>
                                         <?php endif; ?>
                                         <a href="invoice.php?id=<?= $order['id'] ?>" target="_blank" class="text-indigo-600 hover:text-black transition" title="Invoice">
                                             <i class="fas fa-file-invoice"></i>
                                         </a>

                                         <!-- Action Buttons -->
                                          <div class="mt-2 flex gap-2">
                                            
                                            <!-- REORDER (Always available) -->
                                            <form action="order_actions.php" method="POST" class="inline">
                                                <input type="hidden" name="action" value="reorder">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <button type="submit" class="text-[10px] bg-gray-100 text-gray-700 px-2 py-1 rounded hover:bg-gray-200" title="Buy Again">
                                                    <i class="fas fa-redo"></i> Reorder
                                                </button>
                                            </form>

                                            <!-- CANCEL (Pending/Confirmed) -->
                                            <?php if(in_array($order['order_status'], ['pending', 'confirmed'])): ?>
                                                <button onclick="openModal('cancelModal', <?= $order['id'] ?>)" class="text-[10px] bg-red-50 text-red-600 px-2 py-1 rounded hover:bg-red-100">
                                                    Cancel
                                                </button>
                                            <?php endif; ?>

                                            <!-- RETURN (Delivered + Not Returned Yet) -->
                                            <?php if($order['order_status'] === 'delivered' && ($order['return_status'] ?? 'none') === 'none'): ?>
                                                <button onclick="openModal('returnModal', <?= $order['id'] ?>)" class="text-[10px] bg-orange-50 text-orange-600 px-2 py-1 rounded hover:bg-orange-100">
                                                    Return
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- RETURN STATUS -->
                                            <?php if(($order['return_status'] ?? 'none') !== 'none'): ?>
                                                <span class="text-[10px] px-2 py-1 rounded bg-orange-50 text-orange-600">
                                                    Return: <?= ucfirst($order['return_status']) ?>
                                                </span>
                                            <?php endif; ?>
                                          </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                        No orders found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-96 shadow-2xl transform transition-all">
        <h3 class="text-lg font-bold mb-4">Cancel Order?</h3>
        <p class="text-sm text-gray-500 mb-4">Are you sure? This action cannot be undone.</p>
        <form action="order_actions.php" method="POST">
            <input type="hidden" name="action" value="cancel_order">
            <input type="hidden" name="order_id" id="cancel_order_id">
            <textarea name="reason" placeholder="Reason for cancellation..." required class="w-full border rounded-lg p-2 text-sm mb-4" rows="3"></textarea>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeModal('cancelModal')" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg">Keep Order</button>
                <button type="submit" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">Yes, Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Return Modal -->
<div id="returnModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-96 shadow-2xl transform transition-all">
        <h3 class="text-lg font-bold mb-4">Request Return</h3>
        <p class="text-sm text-gray-500 mb-4">Submit a return request. We will review it shortly.</p>
        <form action="order_actions.php" method="POST">
            <input type="hidden" name="action" value="return_order">
            <input type="hidden" name="order_id" id="return_order_id">
            <textarea name="reason" placeholder="Reason for return..." required class="w-full border rounded-lg p-2 text-sm mb-4" rows="3"></textarea>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeModal('returnModal')" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg">Close</button>
                <button type="submit" class="px-4 py-2 text-sm bg-black text-white rounded-lg hover:bg-gray-800">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id, orderId) {
    document.getElementById(id).classList.remove('hidden');
    if(id === 'cancelModal') document.getElementById('cancel_order_id').value = orderId;
    if(id === 'returnModal') document.getElementById('return_order_id').value = orderId;
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>
