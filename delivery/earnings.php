<?php
include 'includes/header.php';

// Calculate Earnings
$stats = $conn->query("SELECT COUNT(*) as count, SUM(earning_amount) as total FROM orders WHERE delivery_boy_id = $user_id AND order_status = 'delivered'")->fetch();
$completed = $stats['count'];
$total_earnings = $stats['total'] ?: 0;
$paid_earnings = $conn->query("SELECT SUM(amount) FROM delivery_payouts WHERE delivery_boy_id = $user_id AND status = 'approved'")->fetchColumn() ?: 0;
$pending_payout = $conn->query("SELECT SUM(amount) FROM delivery_payouts WHERE delivery_boy_id = $user_id AND status = 'pending'")->fetchColumn() ?: 0;
$balance = $total_earnings - $paid_earnings - $pending_payout;

// Handle Payout Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_payout'])) {
    if ($balance >= 100) {
        $conn->prepare("INSERT INTO delivery_payouts (delivery_boy_id, amount, status) VALUES ($user_id, $balance, 'pending')")->execute();
        setFlash('success', 'Payout requested successfully');
        redirect('/delivery/earnings.php');
    } else {
        setFlash('error', 'Minimum balance of ₹100 required');
    }
}
?>

<!-- Balance Card -->
<div class="relative overflow-hidden rounded-3xl bg-black text-white shadow-xl mb-8 p-6 text-center">
    <div class="absolute top-0 right-0 -mr-16 -mt-16 h-48 w-48 rounded-full bg-gray-800 opacity-50 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -ml-16 -mb-16 h-48 w-48 rounded-full bg-green-900 opacity-50 blur-3xl"></div>
    
    <div class="relative z-10">
        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-2">Available Balance</p>
        <h1 class="text-5xl font-bold mb-4">₹<?= number_format($balance, 2) ?></h1>
        
        <div class="flex justify-center gap-4">
            <div class="bg-white/10 px-4 py-2 rounded-xl backdrop-blur-md border border-white/5">
                <p class="text-[10px] text-gray-400 uppercase">Total Earned</p>
                <p class="text-sm font-bold">₹<?= number_format($total_earnings, 2) ?></p>
            </div>
            <div class="bg-white/10 px-4 py-2 rounded-xl backdrop-blur-md border border-white/5">
                <p class="text-[10px] text-gray-400 uppercase">Withdrawn</p>
                <p class="text-sm font-bold">₹<?= number_format($paid_earnings, 2) ?></p>
            </div>
        </div>
    </div>
</div>

<?php if ($msg = getFlash()): ?>
    <div class="p-4 rounded-xl mb-6 flex items-center gap-3 <?= $msg['type'] == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
        <i class="fas <?= $msg['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
        <p class="text-sm font-bold"><?= htmlspecialchars($msg['message']) ?></p>
    </div>
<?php endif; ?>

<!-- Actions -->
<div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-8">
    <h3 class="font-bold text-gray-900 mb-4">Payout Actions</h3>
    
    <?php if ($balance >= 100): ?>
        <form method="POST">
            <button type="submit" name="request_payout" class="w-full bg-black text-white py-4 rounded-xl font-bold text-lg hover:bg-gray-800 shadow-lg shadow-gray-200 transition flex items-center justify-center gap-2">
                Request Payout <i class="fas fa-arrow-right text-sm"></i>
            </button>
            <p class="text-center text-xs text-gray-500 mt-3">Request processing takes 24-48 hours.</p>
        </form>
    <?php else: ?>
        <button disabled class="w-full bg-gray-100 text-gray-400 py-4 rounded-xl font-bold text-lg cursor-not-allowed">
            Request Payout
        </button>
        <div class="mt-3 flex items-center justify-center gap-2 text-xs text-gray-500">
             <i class="fas fa-lock"></i>
             Minimum ₹100 required to withdraw
        </div>
    <?php endif; ?>
</div>

<!-- History -->
<h3 class="font-bold text-gray-900 text-lg mb-4">Payout History</h3>
<div class="space-y-4">
    <?php
    $payouts = $conn->query("SELECT * FROM delivery_payouts WHERE delivery_boy_id = $user_id ORDER BY request_date DESC")->fetchAll();
    if(count($payouts) > 0):
        foreach($payouts as $p):
    ?>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex justify-between items-center group hover:shadow-md transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 group-hover:bg-black group-hover:text-white transition">
                <i class="fas fa-history text-lg"></i>
            </div>
            <div>
                <p class="font-bold text-gray-900">Payout Request</p>
                <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($p['request_date'])) ?></p>
            </div>
        </div>
        <div class="text-right">
            <p class="font-bold text-lg text-gray-900">₹<?= $p['amount'] ?></p>
            <span class="inline-block px-2 py-1 rounded-md text-[10px] font-bold uppercase 
                <?= $p['status'] == 'approved' ? 'bg-green-100 text-green-700' : '' ?>
                <?= $p['status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                <?= $p['status'] == 'rejected' ? 'bg-red-100 text-red-700' : '' ?>
            ">
                <?= ucfirst($p['status']) ?>
            </span>
        </div>
    </div>
    <?php endforeach; else: ?>
        <div class="text-center py-10 bg-white rounded-2xl border border-dashed border-gray-200">
            <i class="fas fa-receipt text-3xl text-gray-300 mb-3"></i>
            <p class="text-gray-500 text-sm">No payout history found.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
