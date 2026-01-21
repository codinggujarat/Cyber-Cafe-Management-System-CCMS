<?php
include 'includes/header.php';
include 'includes/sidebar.php';

requireRole(['admin', 'manager']);

// 1. Most Requested Services
$topReq = $conn->query("
    SELECT service_name, COUNT(*) as cnt, SUM(price * quantity) as revenue 
    FROM order_items 
    GROUP BY service_id 
    ORDER BY cnt DESC 
    LIMIT 10
")->fetchAll();

// 2. Average Processing Time (Global)
// created_at -> updated_at for 'delivered' status in orders
$avgTime = $conn->query("
    SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) 
    FROM orders 
    WHERE order_status = 'delivered'
")->fetchColumn();
$avgTimeStr = $avgTime ? round($avgTime, 1) . " Hours" : "N/A";

// 3. Rejection Stats per Service
$rejectionStats = $conn->query("
    SELECT 
        service_name, 
        COUNT(*) as total,
        SUM(CASE WHEN approval_status = 'rejected' OR service_status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM order_items
    GROUP BY service_id
    HAVING rejected > 0
    ORDER BY rejected DESC
")->fetchAll();

// 4. Revenue Per Service (Already in $topReq roughly, but let's get full list desc by rev)
$revStats = $conn->query("
    SELECT service_name, SUM(price * quantity) as revenue
    FROM order_items
    GROUP BY service_id
    ORDER BY revenue DESC
    LIMIT 5
")->fetchAll();

?>

<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Service Analytics</h2>
        <p class="text-sm text-gray-500">Business performance and efficiency insights.</p>
    </div>
    <div class="bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
            <i class="fas fa-clock"></i>
        </div>
        <div>
            <p class="text-[10px] uppercase font-bold text-gray-500">Avg Processing Time</p>
            <p class="text-lg font-bold text-gray-900"><?= $avgTimeStr ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    
    <!-- Most Requested Services (Bar Chart) -->
    <div class="bg-black text-white p-6 rounded-2xl shadow-xl relative overflow-hidden">
         <div class="absolute top-0 right-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <h3 class="font-bold text-lg mb-6 relative z-10">Most Requested Services</h3>
        <div class="relative h-80 w-full z-10">
            <canvas id="reqChart"></canvas>
        </div>
    </div>

    <!-- Revenue Distribution (Doughnut) -->
    <div class="bg-black text-white p-6 rounded-2xl shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <h3 class="font-bold text-lg mb-6 relative z-10">Revenue Leaders</h3>
        <div class="flex flex-col md:flex-row items-center gap-8 relative z-10">
            <div class="w-full md:w-1/2 relative h-64">
                <canvas id="revChart"></canvas>
            </div>
            <div class="w-full md:w-1/2 space-y-4">
                <?php 
                    $colors = ['#4F46E5', '#0EA5E9', '#10B981', '#F59E0B', '#EC4899'];
                    foreach($revStats as $i => $rv): 
                        $color = $colors[$i % count($colors)];
                ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                         <span class="w-3 h-3 rounded-full" style="background-color: <?= $color ?>"></span>
                         <span class="text-sm text-gray-400 font-medium"><?= htmlspecialchars($rv['service_name']) ?></span>
                    </div>
                    <span class="text-sm font-bold text-white">₹<?= formatPrice($rv['revenue']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-gray-900">Rejection Analysis & Efficiency</h3>
        <button class="text-sm text-gray-500 hover:text-black"><i class="fas fa-download mr-1"></i> Export</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Service Name</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center">Total Requests</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center">Rejections</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center">Rejection Rate</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right">Health</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if(count($rejectionStats) > 0): ?>
                    <?php foreach($rejectionStats as $rs): 
                         $rate = ($rs['rejected'] / $rs['total']) * 100;
                         $status = $rate > 20 ? 'Critical' : ($rate > 10 ? 'Warning' : 'Good');
                         $color = $rate > 20 ? 'text-red-600 bg-red-50' : ($rate > 10 ? 'text-orange-600 bg-orange-50' : 'text-green-600 bg-green-50');
                    ?>
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($rs['service_name']) ?></td>
                        <td class="px-6 py-4 text-center text-gray-600"><?= $rs['total'] ?></td>
                        <td class="px-6 py-4 text-center font-bold text-gray-900"><?= $rs['rejected'] ?></td>
                        <td class="px-6 py-4 text-center">
                            <span class="font-mono text-sm"><?= number_format($rate, 1) ?>%</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-xs px-2 py-1 rounded-full font-bold <?= $color ?>"><?= $status ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">No rejections recorded significantly. Keep up the good work!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Request Chart (Dark Mode)
    new Chart(document.getElementById('reqChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($topReq, 'service_name')) ?>,
            datasets: [{
                label: 'Orders',
                data: <?= json_encode(array_column($topReq, 'cnt')) ?>,
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                borderRadius: 6,
                barThickness: 20,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#000',
                    bodyColor: '#000',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: { 
                y: { 
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.1)', drawBorder: false },
                    ticks: { color: 'rgba(255, 255, 255, 0.6)' }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { color: 'rgba(255, 255, 255, 0.6)' }
                }
            }
        }
    });

    // Rev Chart (Modern Palette)
    new Chart(document.getElementById('revChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($revStats, 'service_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($revStats, 'revenue')) ?>,
                backgroundColor: [
                    '#4F46E5', // Indigo
                    '#0EA5E9', // Sky
                    '#10B981', // Emerald
                    '#F59E0B', // Amber
                    '#EC4899'  // Pink
                ],
                hoverOffset: 4,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '75%',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
