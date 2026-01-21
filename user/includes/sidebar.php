<?php
// Get current page filename to highlight active link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="w-full lg:w-64 flex-shrink-0 mb-8 lg:mb-0">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden sticky top-24">
        
        <!-- User Brief -->
        <div class="p-6 bg-gray-50/50 border-b border-gray-50 text-center">
            <div class="relative inline-block">
                <?php 
                    // Use $base from global header for absolute path
                    $profileSrc = !empty($_SESSION['user_profile_pic']) ? $base . 'uploads/profiles/' . $_SESSION['user_profile_pic'] : "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['user_name']) . "&background=random&size=128";
                ?>
                <img class="h-20 w-20 rounded-full mx-auto bg-white p-1 ring-1 ring-gray-100 object-cover" src="<?= $profileSrc ?>" alt="">
                <span class="absolute bottom-1 right-1 h-4 w-4 rounded-full bg-green-500 ring-2 ring-white"></span>
            </div>
            <h3 class="mt-3 font-bold text-gray-900"><?= explode(' ', $_SESSION['user_name'])[0] ?></h3>
            <p class="text-xs text-gray-500"><?= $_SESSION['user_email'] ?></p>
        </div>

        <!-- Navigation -->
        <nav class="p-4 space-y-1">
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all group <?= $current_page == 'index.php' ? 'bg-black text-white shadow-md' : 'text-gray-600 hover:bg-gray-50 hover:text-black' ?>">
                <i class="fas fa-columns w-5 text-center <?= $current_page == 'index.php' ? 'text-white' : 'text-gray-400 group-hover:text-black' ?>"></i>
                Dashboard
            </a>
            
            <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all group <?= $current_page == 'orders.php' ? 'bg-black text-white shadow-md' : 'text-gray-600 hover:bg-gray-50 hover:text-black' ?>">
                <i class="fas fa-box w-5 text-center <?= $current_page == 'orders.php' ? 'text-white' : 'text-gray-400 group-hover:text-black' ?>"></i>
                My Orders
            </a>

            <a href="appointments.php" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all group <?= ($current_page == 'appointments.php' || $current_page == 'book_appointment.php') ? 'bg-black text-white shadow-md' : 'text-gray-600 hover:bg-gray-50 hover:text-black' ?>">
                <i class="far fa-calendar-alt w-5 text-center <?= ($current_page == 'appointments.php' || $current_page == 'book_appointment.php') ? 'text-white' : 'text-gray-400 group-hover:text-black' ?>"></i>
                Appointments
            </a>

            <a href="wishlist.php" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all group <?= $current_page == 'wishlist.php' ? 'bg-black text-white shadow-md' : 'text-gray-600 hover:bg-gray-50 hover:text-black' ?>">
                <i class="far fa-heart w-5 text-center <?= $current_page == 'wishlist.php' ? 'text-white' : 'text-gray-400 group-hover:text-black' ?>"></i>
                Wishlist
            </a>

            <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all group <?= $current_page == 'profile.php' ? 'bg-black text-white shadow-md' : 'text-gray-600 hover:bg-gray-50 hover:text-black' ?>">
                <i class="fas fa-user-cog w-5 text-center <?= $current_page == 'profile.php' ? 'text-white' : 'text-gray-400 group-hover:text-black' ?>"></i>
                Settings
            </a>
            
            <div class="border-t border-gray-50 my-2"></div>

            <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 rounded-xl hover:bg-red-50 transition-all">
                <i class="fas fa-sign-out-alt w-5 text-center"></i>
                Sign out
            </a>
        </nav>
    </div>
</div>
