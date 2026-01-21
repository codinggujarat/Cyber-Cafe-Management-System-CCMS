<?php
include 'includes/header.php';

$month = $_GET['month'] ?? date('Y-m');
$startDate = $month . '-01';
$endDate = date('Y-m-t', strtotime($startDate));

$orders = $conn->query("
    SELECT * FROM orders 
    WHERE delivery_boy_id = $user_id 
    AND order_status = 'delivered' 
    AND DATE(delivered_at) BETWEEN '$startDate' AND '$endDate'
    ORDER BY delivered_at DESC
")->fetchAll();

$totalOrders = count($orders);
$totalEarnings = 0;
$lateCount = 0;
$onTimeCount = 0;

foreach ($orders as $o) {
    $totalEarnings += $o['earning_amount'];
    if ($o['is_late']) $lateCount++; else $onTimeCount++;
}

$perfScore = $totalOrders > 0 ? (($onTimeCount / $totalOrders) * 5) : 5.0;
?>

<div class="flex justify-between items-center mb-6 no-print">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Earnings Report</h2>
        <p class="text-sm text-gray-500">Performance summary.</p>
    </div>
    <form class="flex gap-2">
        <input type="month" name="month" value="<?= $month ?>" class="rounded-lg border-gray-200 text-sm focus:ring-black focus:border-black" onchange="this.form.submit()">
        <button type="button" onclick="window.print()" class="bg-black text-white p-2 rounded-lg hover:bg-gray-800">
            <i class="fas fa-print"></i>
        </button>
    </form>
</div>

<!-- Report Sheet -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 print:shadow-none print:border-none print:p-0">
    
    <!-- Report Header -->
    <div class="border-b border-gray-100 pb-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-1">Monthly Statement</h1>
                <p class="text-gray-500 text-sm"><?= date('F Y', strtotime($startDate)) ?></p>
            </div>
            <div class="text-right">
                <p class="font-bold text-gray-900"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                <p class="text-xs text-gray-500">ID: #<?= str_pad($user_id, 4, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="bg-gray-50 rounded-xl p-4 text-center print:border print:border-gray-200">
             <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Total Earned</p>
             <p class="text-xl font-bold text-gray-900">₹<?= number_format($totalEarnings, 2) ?></p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4 text-center print:border print:border-gray-200">
             <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Deliveries</p>
             <p class="text-xl font-bold text-gray-900"><?= $totalOrders ?></p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4 text-center print:border print:border-gray-200">
             <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">On Time</p>
             <p class="text-xl font-bold text-green-600"><?= $onTimeCount ?></p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4 text-center print:border print:border-gray-200">
             <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Score</p>
             <div class="flex items-center justify-center gap-1 text-yellow-400 text-sm font-bold">
                 <span><?= number_format($perfScore, 1) ?></span> <i class="fas fa-star"></i>
             </div>
        </div>
    </div>

    <!-- Detailed Table -->
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b-2 border-gray-100 text-xs text-gray-500 uppercase">
                <th class="py-3">Date</th>
                <th class="py-3">Order ID</th>
                <th class="py-3 text-center">Status</th>
                <th class="py-3 text-right">Earning</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php foreach ($orders as $o): ?>
            <tr>
                <td class="py-3 text-gray-600"><?= date('M d, H:i', strtotime($o['delivered_at'])) ?></td>
                <td class="py-3 font-mono font-medium text-gray-900">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
                <td class="py-3 text-center">
                    <?php if($o['is_late']): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Late</span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">On Time</span>
                    <?php endif; ?>
                </td>
                <td class="py-3 text-right font-bold text-gray-900">₹<?= number_format($o['earning_amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(count($orders) == 0): ?>
                <tr><td colspan="4" class="py-8 text-center text-gray-500">No deliveries in this period.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot class="border-t border-gray-200">
            <tr>
                <td colspan="3" class="py-4 font-bold text-gray-800 text-right">Net Earnings</td>
                <td class="py-4 font-bold text-gray-900 text-right text-lg">₹<?= number_format($totalEarnings, 2) ?></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="mt-8 pt-8 border-t border-gray-100 text-center text-xs text-gray-400 print:block hidden">
        <p>CyberCafe Delivery Partner Report - Generated on <?= date('d M Y') ?></p>
    </div>
</div>

<style>
    @media print {
        @page { margin: 0.5cm; }
        body { background: white; padding: 0; }
        .no-print { display: none !important; }
        .print\:shadow-none { box-shadow: none; }
        .print\:border-none { border: none; }
        .print\:p-0 { padding: 0; }
        .print\:border { border-width: 1px; }
        .print\:block { display: block; }
    }
</style>

<?php include 'includes/footer.php'; ?>
