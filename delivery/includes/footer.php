</div> 

<!-- Floating Bottom Navigation -->
<div class="fixed bottom-6 left-0 right-0 z-50 px-5 max-w-lg mx-auto">
    <div class="glass-nav rounded-2xl shadow-2xl flex justify-between items-center px-6 py-4 border border-white/10">
        <a href="index.php" class="flex flex-col items-center group transition-colors">
            <i class="fas fa-home text-xl mb-1 transition-colors <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-white' : 'text-gray-500 group-hover:text-gray-300' ?>"></i>
            <span class="text-[10px] font-medium transition-colors <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-white' : 'text-gray-500 group-hover:text-gray-300' ?>">Home</span>
        </a>

        <a href="orders.php" class="flex flex-col items-center group transition-colors">
            <div class="relative">
                <i class="fas fa-box text-xl mb-1 transition-colors <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'text-white' : 'text-gray-500 group-hover:text-gray-300' ?>"></i>
                <!-- Optional Badge Logic could go here -->
            </div>
            <span class="text-[10px] font-medium transition-colors <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'text-white' : 'text-gray-500 group-hover:text-gray-300' ?>">Orders</span>
        </a>

        <a href="earnings.php" class="flex flex-col items-center group transition-colors">
            <i class="fas fa-wallet text-xl mb-1 transition-colors <?= basename($_SERVER['PHP_SELF']) == 'earnings.php' ? 'text-white' : 'text-gray-500 group-hover:text-gray-300' ?>"></i>
            <span class="text-[10px] font-medium transition-colors <?= basename($_SERVER['PHP_SELF']) == 'earnings.php' ? 'text-white' : 'text-gray-500 group-hover:text-gray-300' ?>">Earnings</span>
        </a>

        <a href="../logout.php" class="flex flex-col items-center group transition-colors">
            <i class="fas fa-power-off text-xl mb-1 text-red-500 group-hover:text-red-400"></i>
            <span class="text-[10px] font-medium text-red-500 group-hover:text-red-400">Logout</span>
        </a>
    </div>
</div>

</body>
</html>
