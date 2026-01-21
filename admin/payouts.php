<?php
include 'includes/header.php';
include 'includes/sidebar.php';

// Handle Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['payout_id'];
    $status = $_POST['status']; 
    
    if ($status === 'approved') {
        $txn_id = $_POST['transaction_id'] ?? null;
        $mode = $_POST['payment_method'] ?? 'Manual';
        
        $conn->prepare("UPDATE delivery_payouts SET status = 'approved', transaction_id = :txn, payment_method = :mode WHERE id = :id")
             ->execute([':txn' => $txn_id, ':mode' => $mode, ':id' => $id]);
             
        // Notification
        $boyId = $conn->query("SELECT delivery_boy_id FROM delivery_payouts WHERE id = $id")->fetchColumn();
        if($boyId) createNotification($boyId, 'Payout Approved', "Your payout request #$id has been approved.", 'success');

    } else {
        $conn->prepare("UPDATE delivery_payouts SET status = 'rejected' WHERE id = :id")->execute([':id' => $id]);
        
        // Return money to balance? 
        // Logic in earnings.php is: Balance = Total - Paid - Pending.
        // If 'rejected', it is neither Paid nor Pending. So it goes back to Balance automatically.
        // Notification
        $boyId = $conn->query("SELECT delivery_boy_id FROM delivery_payouts WHERE id = $id")->fetchColumn();
        if($boyId) createNotification($boyId, 'Payout Rejected', "Your payout request #$id was rejected.", 'error');
    }

    setFlash('success', 'Payout request ' . $status);
    redirect('/admin/payouts.php');
}

$payouts = $conn->query("
    SELECT p.*, u.name, u.phone, u.id as user_id 
    FROM delivery_payouts p 
    JOIN users u ON p.delivery_boy_id = u.id 
    ORDER BY p.request_date DESC
")->fetchAll();

$totalPaid = 0;
$totalPending = 0;
foreach($payouts as $p) {
    if($p['status'] == 'approved') $totalPaid += $p['amount'];
    if($p['status'] == 'pending') $totalPending += $p['amount'];
}
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Payout Requests</h2>
        <p class="text-sm text-gray-500">Manage payment requests from delivery partners.</p>
    </div>
    <div class="flex gap-4">
         <div class="px-4 py-2 bg-green-50 rounded-xl border border-green-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold">₹</div>
            <div>
                <p class="text-[10px] uppercase font-bold text-green-600">Total Paid</p>
                <p class="text-sm font-bold text-gray-900">₹<?= formatPrice($totalPaid) ?></p>
            </div>
        </div>
        <div class="px-4 py-2 bg-yellow-50 rounded-xl border border-yellow-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 font-bold">
                <i class="fas fa-clock text-xs"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-yellow-600">Pending</p>
                <p class="text-sm font-bold text-gray-900">₹<?= formatPrice($totalPending) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4 font-semibold">Request ID</th>
                    <th class="px-6 py-4 font-semibold">Delivery Partner</th>
                    <th class="px-6 py-4 font-semibold">Amount</th>
                    <th class="px-6 py-4 font-semibold">Requested On</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (count($payouts) > 0): ?>
                    <?php foreach ($payouts as $p): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-xs font-semibold text-gray-500">#REQ-<?= $p['id'] ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold text-xs">
                                    <?= substr($p['name'], 0, 1) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($p['name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= $p['phone'] ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-bold text-gray-900">₹<?= formatPrice($p['amount']) ?></span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= date('M d, Y', strtotime($p['request_date'])) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                                $statusMap = [
                                    'approved' => 'bg-green-100 text-green-700 ring-green-600/20',
                                    'pending' => 'bg-yellow-100 text-yellow-700 ring-yellow-600/20',
                                    'rejected' => 'bg-red-100 text-red-700 ring-red-600/20'
                                ];
                                $cls = $statusMap[$p['status']] ?? 'bg-gray-100 text-gray-700';
                            ?>
                             <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset <?= $cls ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <?php if($p['status'] == 'pending'): ?>
                                <div class="flex justify-end gap-2">
                                    <button onclick="openPayModal('<?= $p['id'] ?>', '<?= $p['amount'] ?>', '<?= $p['name'] ?>', '<?= $p['phone'] ?>')" class="px-3 py-1 bg-green-50 text-green-600 hover:bg-green-100 rounded-lg text-xs font-bold transition flex items-center gap-1">
                                        Accept <i class="fas fa-check"></i>
                                    </button>
                                    
                                    <form method="POST" onsubmit="return confirm('Reject this payout request?');">
                                        <input type="hidden" name="payout_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="px-3 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-bold transition">Decline</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs italic">
                                    <?= $p['payment_method'] == 'Manual' ? 'Processed' : $p['payment_method'] ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">No payout requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pay & Approve Modal -->
<div id="payModal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closePayModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Process Payout</h3>
                <p class="text-sm text-gray-500 mb-6">Record the payment details for this request.</p>
                
                <div class="bg-gray-50 p-4 rounded-xl mb-6">
                     <div class="flex justify-between items-center mb-2">
                         <span class="text-xs text-gray-500 uppercase font-bold">Pay To</span>
                         <span class="text-sm font-bold text-black" id="payName"></span>
                     </div>
                     <div class="flex justify-between items-center mb-2">
                         <span class="text-xs text-gray-500 uppercase font-bold">UPI / Phone</span>
                         <span class="text-sm text-gray-800" id="payPhone"></span>
                     </div>
                     <div class="border-t border-gray-200 mt-2 pt-2 flex justify-between items-center">
                         <span class="text-xs text-gray-500 uppercase font-bold">Amount</span>
                         <span class="text-xl font-bold text-green-600" id="payAmount"></span>
                     </div>
                </div>

                <form method="POST" id="payForm">
                    <input type="hidden" name="payout_id" id="formPayoutId">
                    <input type="hidden" name="status" value="approved">
                    
                    <div class="space-y-4">
                        <div>
                             <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Payment Method</label>
                             <select name="payment_method" class="w-full text-sm rounded-lg border-gray-200 focus:ring-black focus:border-black">
                                 <option value="UPI">UPI (GPay/PhonePe)</option>
                                 <option value="Razorpay">Razorpay Transfer</option>
                                 <option value="Bank Transfer">Bank Transfer (IMPS/NEFT)</option>
                                 <option value="Cash">Cash</option>
                             </select>
                        </div>
                        <div>
                             <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Transaction Ref / UTR</label>
                             <input type="text" name="transaction_id" placeholder="e.g. 1234567890" required class="w-full text-sm rounded-lg border-gray-200 focus:ring-black focus:border-black">
                        </div>
                    </div>
                    
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button type="button" onclick="closePayModal()" class="w-full py-2 bg-white border border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="w-full py-2 bg-black text-white rounded-xl text-sm font-bold hover:bg-gray-800">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openPayModal(id, amount, name, phone) {
        document.getElementById('formPayoutId').value = id;
        document.getElementById('payAmount').innerText = '₹' + amount;
        document.getElementById('payName').innerText = name;
        document.getElementById('payPhone').innerText = phone;
        document.getElementById('payModal').classList.remove('hidden');
    }
    function closePayModal() {
        document.getElementById('payModal').classList.add('hidden');
    }
</script>

<?php include 'includes/footer.php'; ?>
