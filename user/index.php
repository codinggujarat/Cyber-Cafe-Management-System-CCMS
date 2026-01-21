<?php
include 'includes/header.php';

// Fetch Data
$recentOrders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5")->fetchAll();
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id")->fetchColumn();
$pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND order_status NOT IN ('delivered', 'cancelled')")->fetchColumn();
$totalSpent = $conn->query("SELECT SUM(total_amount) FROM orders WHERE user_id = $user_id AND order_status != 'cancelled'")->fetchColumn() ?: 0;
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 w-full">
            
            <!-- Hero Section -->
            <div class="relative overflow-hidden rounded-2xl bg-black text-white mb-8 shadow-lg">
                <div class="absolute top-0 right-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-gray-800 opacity-50 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -ml-16 -mb-16 h-64 w-64 rounded-full bg-indigo-900 opacity-50 blur-3xl"></div>
                
                <div class="relative z-10 p-8 md:p-10">
                    <h1 class="text-2xl md:text-3xl font-bold tracking-tight mb-2">
                        Hello, <?= explode(' ', $_SESSION['user_name'])[0] ?>.
                    </h1>
                    <p class="text-gray-300 mb-6 font-light text-sm md:text-base">
                        Welcome to your dashboard.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a href="../index.php" class="bg-white text-black px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-gray-100 transition shadow-lg flex items-center gap-2">
                            <i class="fas fa-plus"></i> New Order
                        </a>
                        <a href="../scan.php" class="bg-white/10 backdrop-blur-md text-white border border-white/20 px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-white/20 transition flex items-center gap-2">
                            <i class="fas fa-qrcode"></i> Scan QR
                        </a>
                        <a href="orders.php" class="bg-white/10 backdrop-blur-md text-white border border-white/20 px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-white/20 transition flex items-center gap-2">
                            <i class="fas fa-list-ul"></i> My Orders
                        </a>
                        <a href="book_appointment.php" class="bg-indigo-600 text-white border border-indigo-500 px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-indigo-700 transition flex items-center gap-2 shadow-lg shadow-indigo-900/50">
                            <i class="far fa-calendar-plus"></i> Book Appointment
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Stat 1 -->
                <div class="group p-5 border border-gray-100 rounded-xl bg-white hover:border-gray-200 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-2">
                        <div class="p-2 bg-gray-50 rounded-lg group-hover:bg-black group-hover:text-white transition">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                    <p class="text-xs font-medium text-gray-500">Total Orders</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900"><?= $totalOrders ?></h3>
                </div>

                <!-- Stat 2 -->
                <div class="group p-5 border border-gray-100 rounded-xl bg-white hover:border-gray-200 hover:shadow-md transition">
                     <div class="flex items-center justify-between mb-2">
                        <div class="p-2 bg-gray-50 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <p class="text-xs font-medium text-gray-500">Active Orders</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900"><?= $pendingOrders ?></h3>
                </div>

                <!-- Stat 3 -->
                <div class="group p-5 border border-gray-100 rounded-xl bg-white hover:border-gray-200 hover:shadow-md transition">
                     <div class="flex items-center justify-between mb-2">
                        <div class="p-2 bg-gray-50 rounded-lg group-hover:bg-green-600 group-hover:text-white transition">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <p class="text-xs font-medium text-gray-500">Total Spent</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900">₹<?= formatPrice($totalSpent) ?></h3>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900">Recent Activity</h2>
                <a href="orders.php" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition">View all</a>
            </div>

            <div class="bg-white border border-gray-100 rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (count($recentOrders) > 0): ?>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='orders.php?id=<?= $order['id'] ?>'">
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-900">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'confirmed' => 'bg-blue-100 text-blue-800',
                                            'preparing' => 'bg-purple-100 text-purple-800',
                                            'out_for_delivery' => 'bg-orange-100 text-orange-800',
                                            'delivered' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                        ];
                                        $colorClass = $statusColors[$order['order_status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?= $colorClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $order['order_status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900">
                                    <?= formatPrice($order['total_amount']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                    No recent activity.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
