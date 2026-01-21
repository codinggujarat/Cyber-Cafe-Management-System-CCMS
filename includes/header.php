<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine User State
$isLoggedIn = isset($_SESSION['user_id']);
$user_name = $isLoggedIn ? $_SESSION['user_name'] : null;
$user_email = $isLoggedIn ? $_SESSION['user_email'] : null;

// Determine Cart Count (if logged in)
$cartCount = 0;
if ($isLoggedIn && isset($conn)) {
    try {
        $cartCount = $conn->query("SELECT SUM(quantity) FROM cart WHERE user_id = {$_SESSION['user_id']}")->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $cartCount = 0;
    }
}

// Base Path Helper (Rough estimation based on typical XAMPP structure)
$base = '/cafemgmt/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('APP_NAME') ? APP_NAME : 'CyberCafe' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .backdrop-blur-md { backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
    </style>
    
    <!-- PWA Settings -->
    <link rel="manifest" href="<?= $base ?>manifest.json">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= $base ?>assets/icons/icon-192x192.png">
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= $base ?>sw.js')
                    .then(reg => console.log('SW Registered'))
                    .catch(err => console.log('SW Fail', err));
            });
        }
    </script>
</head>
<body class="bg-white text-slate-800 antialiased selection:bg-black selection:text-white">

    <!-- Unified Navigation Bar -->
    <nav class="fixed w-full z-50 top-0 bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-1">
                    <a href="<?= $base ?>index.php" class="text-2xl font-bold tracking-tighter text-black flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-black text-white flex items-center justify-center text-sm font-bold">CC</span>
                        CyberCafe
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-8">
                    <?php if ($isLoggedIn): ?>
                         <a href="<?= $base ?>user/services.php" class="text-sm font-medium text-gray-500 hover:text-black transition">Services</a>
                         <a href="<?= $base ?>user/appointments.php" class="text-sm font-medium text-gray-500 hover:text-black transition">Appointments</a>
                         <a href="<?= $base ?>user/orders.php" class="text-sm font-medium text-gray-500 hover:text-black transition">Orders</a>
                    <?php else: ?>
                        <a href="<?= $base ?>index.php#services" class="text-sm font-medium text-gray-500 hover:text-black transition">Services</a>
                        <a href="<?= $base ?>index.php#features" class="text-sm font-medium text-gray-500 hover:text-black transition">Features</a>
                    <?php endif; ?>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-4">
                    <?php if ($isLoggedIn): ?>
                        
                        <!-- Wishlist -->
                        <a href="<?= $base ?>user/wishlist.php" class="group p-2 rounded-full hover:bg-gray-100 transition relative" title="Wishlist">
                            <i class="far fa-heart text-lg text-gray-500 group-hover:text-red-500 transition"></i>
                        </a>

                        <!-- Cart -->
                        <a href="<?= $base ?>user/cart.php" class="group p-2 rounded-full hover:bg-gray-100 transition relative" title="Cart">
                             <i class="fas fa-shopping-bag text-lg text-gray-500 group-hover:text-black transition"></i>
                            <?php if($cartCount > 0): ?>
                            <span class="absolute top-0 right-0 flex h-4 w-4 items-center justify-center rounded-full bg-black text-[10px] font-bold text-white ring-2 ring-white transform translate-x-1 -translate-y-1"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <!-- Profile Dropdown -->
                        <div class="relative group ml-2">
                            <button class="flex items-center gap-3 focus:outline-none py-2 px-1 rounded-full hover:bg-gray-50 transition border border-transparent hover:border-gray-100">
                                <?php 
                                    $navProfileSrc = !empty($_SESSION['user_profile_pic']) ? $base . 'uploads/profiles/' . $_SESSION['user_profile_pic'] : "https://ui-avatars.com/api/?name=" . urlencode($user_name) . "&background=random";
                                ?>
                                <img class="h-8 w-8 rounded-full bg-gray-100 object-cover ring-2 ring-gray-50" src="<?= $navProfileSrc ?>" alt="">
                                <div class="hidden md:block text-left mr-2">
                                    <p class="text-xs font-semibold text-gray-900"><?= explode(' ', $user_name)[0] ?></p>
                                    <p class="text-[10px] text-gray-400">Member</p>
                                </div>
                                <i class="fas fa-chevron-down text-[10px] text-gray-400 mr-2"></i>
                            </button>

                             <div class="absolute right-0 mt-4 w-60 origin-top-right rounded-2xl bg-white p-2 shadow-2xl ring-1 ring-black ring-opacity-5 focus:outline-none hidden group-hover:block border border-gray-100 enter-active">
                                <div class="px-4 py-4 border-b border-gray-50 mb-2 bg-gray-50/50 rounded-xl">
                                    <p class="text-sm font-medium text-black truncate"><?= $user_name ?></p>
                                    <p class="text-xs text-gray-500 truncate"><?= $user_email ?></p>
                                </div>
                                
                                <a href="<?= $base ?>user/index.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 rounded-xl hover:bg-gray-50 hover:text-black transition">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center"><i class="fas fa-columns text-xs"></i></div>
                                    Dashboard
                                </a>
                                <a href="<?= $base ?>user/orders.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 rounded-xl hover:bg-gray-50 hover:text-black transition">
                                     <div class="w-8 h-8 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center"><i class="fas fa-box text-xs"></i></div>
                                    My Orders
                                </a>
                                <a href="<?= $base ?>user/appointments.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 rounded-xl hover:bg-gray-50 hover:text-black transition">
                                     <div class="w-8 h-8 rounded-full bg-green-50 text-green-600 flex items-center justify-center"><i class="fas fa-calendar-alt text-xs"></i></div>
                                    Appointments
                                </a>
                                 <a href="<?= $base ?>user/wishlist.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 rounded-xl hover:bg-gray-50 hover:text-black transition">
                                     <div class="w-8 h-8 rounded-full bg-pink-50 text-pink-600 flex items-center justify-center"><i class="far fa-heart text-xs"></i></div>
                                    Wishlist
                                </a>
                                <a href="<?= $base ?>user/profile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 rounded-xl hover:bg-gray-50 hover:text-black transition">
                                     <div class="w-8 h-8 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center"><i class="fas fa-user-cog text-xs"></i></div>
                                    Settings
                                </a>
                                
                                <div class="border-t border-gray-50 my-2"></div>
                                
                                <a href="<?= $base ?>logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 rounded-xl hover:bg-red-50 transition">
                                     <div class="w-8 h-8 rounded-full bg-red-50 text-red-600 flex items-center justify-center"><i class="fas fa-sign-out-alt text-xs"></i></div>
                                    Sign out
                                </a>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="flex items-center gap-3">
                            <a href="<?= $base ?>login.php" class="text-sm font-semibold text-gray-600 hover:text-black px-4 py-2 rounded-full hover:bg-gray-50 transition">Log in</a>
                            <a href="<?= $base ?>login.php?action=register" class="bg-black text-white px-5 py-2.5 rounded-full text-sm font-medium hover:bg-gray-800 transition shadow-lg shadow-gray-200">Sign up</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <div class="pt-20"> <!-- Spacer for fixed navbar -->
