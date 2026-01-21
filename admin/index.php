<?php
include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch Statistics
$stats = [
    'total_orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue' => $conn->query("SELECT SUM(total_amount) FROM orders WHERE order_status != 'cancelled'")->fetchColumn() ?: 0,
    'users' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'active_services' => $conn->query("SELECT COUNT(*) FROM services WHERE status = 1")->fetchColumn()
];

// Recent Orders
$recentOrders = $conn->query("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

// Chart Data (Mock - last 6 months revenue)
// Chart Data (Last 6 Months Revenue)
$months = [];
$revenues = [];

// Get last 6 months distinct
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    
    // Query for this specific month
    $stmt = $conn->prepare("SELECT SUM(total_amount) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = :m AND order_status != 'cancelled'");
    $stmt->execute([':m' => $date]);
    $total = $stmt->fetchColumn() ?: 0;
    
    $months[] = $monthLabel;
    $revenues[] = (float)$total;
}

// Calculate Growth (Last Month vs Previous Month)
$lastMonthRev = end($revenues);
$prevMonthRev = prev($revenues);
$growth = 0;
if ($prevMonthRev > 0) {
    $growth = (($lastMonthRev - $prevMonthRev) / $prevMonthRev) * 100;
} elseif ($lastMonthRev > 0) {
    $growth = 100; // 100% growth if previous was 0 and now we have revenue
}
$growthStr = ($growth >= 0 ? '+' : '') . number_format($growth, 1) . '% Growth';
$growthColor = $growth >= 0 ? 'text-green-400' : 'text-red-400';
// Advanced Analytics
$avgTime = "N/A";
try {
    $avgMin = (float) $conn->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) FROM orders WHERE order_status = 'delivered'")->fetchColumn();
    if ($avgMin) {
        $h = floor($avgMin / 60);
        $m = round(fmod($avgMin, 60));
        $avgTime = "{$h}h {$m}m";
    }
} catch (Exception $e) {}

// Repeat Customers
$repeatRate = "0%";
if ($stats['users'] > 0) {
    $repeats = $conn->query("SELECT COUNT(*) FROM (SELECT user_id FROM orders WHERE order_status='delivered' GROUP BY user_id HAVING COUNT(*) > 1) as r")->fetchColumn();
    $repeatRate = round(($repeats / $stats['users']) * 100, 1) . "%";
}

// Top Customer
$topCustomer = $conn->query("SELECT u.name, SUM(o.total_amount) as spent FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_status!='cancelled' GROUP BY u.id ORDER BY spent DESC LIMIT 1")->fetch();

// Top Service
$topService = $conn->query("SELECT service_name, SUM(price * quantity) as rev FROM order_items GROUP BY service_id ORDER BY rev DESC LIMIT 1")->fetch();

// Peak Day
$peakDay = $conn->query("SELECT DAYNAME(created_at) as day, COUNT(*) as c FROM orders GROUP BY day ORDER BY c DESC LIMIT 1")->fetch();
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    
    <!-- Card 1 -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-black text-white flex items-center justify-center text-xl">
                <i class="fas fa-wallet"></i>
            </div>
            <span class="text-xs font-medium text-green-500 bg-green-50 px-2 py-1 rounded-full">+12%</span>
        </div>
        <h3 class="text-gray-500 text-sm font-medium">Total Revenue</h3>
        <p class="text-2xl font-bold text-gray-900 mt-1">₹<?= formatPrice($stats['revenue']) ?></p>
    </div>

    <!-- Card 2 -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center text-xl">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <span class="text-xs font-medium text-green-500 bg-green-50 px-2 py-1 rounded-full">+5%</span>
        </div>
        <h3 class="text-gray-500 text-sm font-medium">Total Orders</h3>
        <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total_orders'] ?></p>
    </div>

    <!-- Card 3 -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center text-xl">
                <i class="fas fa-users"></i>
            </div>
            <span class="text-xs font-medium text-gray-500 bg-gray-50 px-2 py-1 rounded-full">0%</span>
        </div>
        <h3 class="text-gray-500 text-sm font-medium">Active Users</h3>
        <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['users'] ?></p>
    </div>

    <!-- Card 4 -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center text-xl">
                <i class="fas fa-layer-group"></i>
            </div>
            <span class="text-xs font-medium text-gray-500 bg-gray-50 px-2 py-1 rounded-full">Services</span>
        </div>
        <h3 class="text-gray-500 text-sm font-medium">Live Services</h3>
        <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['active_services'] ?></p>
    </div>
</div>

<!-- Advanced Analytics (CEO Level) -->
<div class="mb-8 p-6 bg-gradient-to-r from-gray-900 to-black rounded-2xl shadow-xl text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500 opacity-10 blur-[80px] rounded-full -mr-16 -mt-16"></div>
    <div class="relative z-10">
        <h2 class="text-lg font-bold mb-6 flex items-center gap-2">
            <i class="fas fa-chart-line text-indigo-400"></i> Business Insights
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-8 divide-x divide-white/10">
            <!-- Avg Time -->
            <div class="pl-4 first:pl-0">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Avg Completion</p>
                <p class="text-2xl font-bold text-white"><?= $avgTime ?></p>
                <p class="text-[10px] text-green-400 mt-1">Order to Delivery</p>
            </div>
            <!-- Repeat Cust -->
            <div class="pl-4">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Repeat Rate</p>
                <p class="text-2xl font-bold text-white"><?= $repeatRate ?></p>
                <p class="text-[10px] text-gray-400 mt-1">For Delivered Orders</p>
            </div>
            <!-- Top Customer -->
            <div class="pl-4">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Top Customer</p>
                <p class="text-xl font-bold text-white truncate"><?= htmlspecialchars($topCustomer['name'] ?? '-') ?></p>
                <p class="text-[10px] text-indigo-300 mt-1">₹<?= formatPrice($topCustomer['spent'] ?? 0) ?> Lifetime</p>
            </div>
            <!-- Top Service -->
             <div class="pl-4">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Top Service</p>
                <p class="text-xl font-bold text-white truncate"><?= htmlspecialchars($topService['service_name'] ?? '-') ?></p>
                <p class="text-[10px] text-indigo-300 mt-1">Highest Revenue</p>
            </div>
            <!-- Peak Day -->
             <div class="pl-4">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Peak Day</p>
                <p class="text-2xl font-bold text-white"><?= $peakDay['day'] ?? '-' ?></p>
                <p class="text-[10px] text-gray-400 mt-1">Most Orders</p>
            </div>
        </div>
    </div>
</div>

<div class="flex flex-col xl:flex-row gap-8">
    
    <!-- Recent Orders Table -->
    <div class="w-full xl:w-2/3">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">Recent Orders</h2>
                <a href="orders.php" class="text-sm font-medium text-black hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Order ID</th>
                            <th class="px-6 py-4 font-semibold">User</th>
                            <th class="px-6 py-4 font-semibold">Amount</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($recentOrders as $order): ?>
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
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-gray-400 hover:text-black transition-colors">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Analytics Chart -->
    <div class="w-full xl:w-1/3">
        <div class="bg-black text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
             <div class="absolute top-0 right-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
             
             <h2 class="text-lg font-bold mb-6 relative z-10">Revenue Overview</h2>
             
             <div class="h-64 relative z-10">
                 <canvas id="revenueChart"></canvas>
             </div>

             <div class="mt-6 flex items-center justify-between text-sm text-gray-400 relative z-10">
                 <span>Last 6 Months</span>
                 <span class="<?= $growthColor ?> font-bold"><?= $growthStr ?></span>
             </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode($revenues) ?>,
                borderColor: '#fff',
                backgroundColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#000',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { color: 'rgba(255, 255, 255, 0.5)' }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                    ticks: { display: false }
                }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
