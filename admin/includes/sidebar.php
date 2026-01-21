<!-- Modern Premium Sidebar -->
<aside class="w-64 bg-black border-r border-white/10 hidden md:flex flex-col fixed inset-y-0 left-0 z-50 transition-all duration-300 shadow-2xl">
    <!-- Brand -->
    <div class="h-20 flex items-center px-8 border-b border-white/5 bg-white/5 backdrop-blur-xl">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-white flex items-center justify-center shadow-lg shadow-white/20">
                <i class="fas fa-cube text-black text-sm"></i>
            </div>
            <span class="text-white font-bold text-lg tracking-wide font-['Outfit']">CyberPanel</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">
        <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3 font-['Outfit']">Dashboard</p>
        
        <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'fas' : 'far' ?> fa-chart-bar w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Overview</span>
        </a>

        <?php if(hasRole(['admin', 'manager'])): ?>
        <a href="analytics.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'fas' : 'far' ?> fa-chart-pie w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Analytics</span>
        </a>
        <?php endif; ?>

        <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-8 mb-3 font-['Outfit']">Management</p>
        
        <?php if(hasRole(['admin', 'manager', 'staff'])): ?>
        <a href="orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'fas' : 'far' ?> fa-shopping-bag w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Orders</span>
        </a>

        <a href="appointments.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'fas' : 'far' ?> fa-calendar-check w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Appointments</span>
        </a>
        <?php endif; ?>

        <?php if(hasRole(['admin', 'manager'])): ?>
        <a href="services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'fas' : 'far' ?> fa-layer-group w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Services</span>
        </a>

        <a href="categories.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'fas' : 'far' ?> fa-bookmark w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Categories</span>
        </a>
        
        <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'fas' : 'far' ?> fa-user w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Users</span>
        </a>

        <a href="staff.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'fas' : 'far' ?> fa-id-badge w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Staff Team</span>
        </a>
        
        <a href="delivery_boys.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'delivery_boys.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'delivery_boys.php' ? 'fas' : 'far' ?> fa-motorcycle w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Delivery Team</span>
        </a>
        <?php endif; ?>

        <?php if(hasRole(['admin', 'manager', 'accountant'])): ?>
        <a href="payouts.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'payouts.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'payouts.php' ? 'fas' : 'far' ?> fa-wallet w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Payouts</span>
        </a>
        <?php endif; ?>

        <?php if(hasRole(['admin', 'manager', 'staff'])): ?>
        <a href="inventory.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 group <?= basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'bg-white text-black shadow-lg shadow-white/10 translate-x-1' : 'text-gray-400 hover:text-white hover:bg-white/5 hover:translate-x-1' ?>">
            <i class="<?= basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'fas' : 'far' ?> fa-boxes w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-['Outfit']">Stock Manage</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-4 border-t border-white/5 bg-white/5 backdrop-blur-xl">
        <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-red-500 hover:bg-red-500/10 transition-all duration-300 group">
            <i class="fas fa-sign-out-alt w-5 transition-transform group-hover:-translate-x-1"></i>
            <span class="font-['Outfit']">Sign Out</span>
        </a>
    </div>
</aside>

<!-- Mobile Header Trigger (Hidden on Desktop) -->
<div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-black z-50 flex items-center justify-between px-4 border-b border-white/10">
     <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center">
            <i class="fas fa-cube text-black text-sm"></i>
        </div>
        <span class="text-white font-bold text-lg font-['Outfit']">CyberPanel</span>
    </div>
    <button class="text-white p-2">
        <i class="fas fa-bars text-xl"></i>
    </button>
</div>

<!-- Main Wrapper Starts Here (after sidebar) -->
<main class="md:ml-64 pt-20 min-h-screen transition-all duration-300 bg-gray-50/50">
    <div class="p-6 sm:p-8 w-full">
