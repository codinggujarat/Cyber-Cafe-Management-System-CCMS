<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'config/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;
$user_name = $isLoggedIn ? $_SESSION['user_name'] : null;

// Handle Role Redirects
if ($isLoggedIn) {
    if (isAdmin()) redirect('admin/index.php');
    if (isDelivery()) redirect('delivery/index.php');
}

// Fetch Services
$services = $conn->query("SELECT * FROM services WHERE status = 1 LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Premium Cyber Services</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
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
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .text-gradient {
            background: linear-gradient(to right, #000000, #434343);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-gray-50 text-slate-800 antialiased selection:bg-black selection:text-white">

    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>
    
    <main>
        <?php if (!$isLoggedIn): ?>
            <!-- HERO SECTION -->
            <div class="relative isolate pt-14">
                <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
                    <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
                </div>
                
                <div class="py-24 sm:py-32 lg:pb-40">
                    <div class="mx-auto max-w-7xl px-6 lg:px-8">
                        <div class="mx-auto max-w-2xl text-center">
                            <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-6xl mb-6">
                                Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Digital Needs</span>,<br> Handled Professionally.
                            </h1>
                            <p class="mt-6 text-lg leading-8 text-gray-600">
                                Printing, scanning, designing, and documentation services at your fingertips. Why wait in line when you can order online?
                            </p>
                            <div class="mt-10 flex items-center justify-center gap-x-6">
                                <a href="login.php" class="rounded-full bg-black px-8 py-3.5 text-sm font-semibold text-white shadow-xl shadow-gray-200 hover:bg-gray-800 transition transform hover:-translate-y-1">Get Started</a>
                                <a href="#services" class="text-sm font-semibold leading-6 text-gray-900 group">Explore Services <span aria-hidden="true" class="group-hover:translate-x-1 inline-block transition">→</span></a>
                            </div>
                        </div>
                        
                        <!-- Trust Indicators -->
                        <div class="mt-16 sm:mt-24 border-t border-gray-900/10 pt-8 sm:pt-10 flex justify-center gap-8 text-gray-400 grayscale opacity-60">
                            <!-- Placeholder Icons for "Trusted By" vibes -->
                            <div class="flex items-center gap-2"><i class="fas fa-university fa-lg"></i> <span class="font-bold">Universities</span></div>
                            <div class="flex items-center gap-2"><i class="fas fa-building fa-lg"></i> <span class="font-bold">Corporates</span></div>
                            <div class="flex items-center gap-2"><i class="fas fa-user-graduate fa-lg"></i> <span class="font-bold">Students</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FEATURES GRID -->
            <div id="services" class="py-24 bg-white">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center mb-16">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Why Choose Us?</h2>
                        <p class="mt-2 text-lg leading-8 text-gray-600">We deliver quality with speed.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div class="rounded-3xl bg-gray-50 p-10 ring-1 ring-inset ring-gray-900/5 transition hover:shadow-xl hover:bg-white hover:-translate-y-1">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 mb-6 font-bold text-xl"><i class="fas fa-print"></i></div>
                            <h3 class="font-bold text-xl text-gray-900 mb-2">High Quality Printing</h3>
                            <p class="text-gray-500 leading-relaxed">Laser-sharp prints in color or B&W. Upload your documents, customize settings, and get them delivered.</p>
                        </div>
                         <div class="rounded-3xl bg-gray-50 p-10 ring-1 ring-inset ring-gray-900/5 transition hover:shadow-xl hover:bg-white hover:-translate-y-1">
                            <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center text-purple-600 mb-6 font-bold text-xl"><i class="fas fa-pen-nib"></i></div>
                            <h3 class="font-bold text-xl text-gray-900 mb-2">Graphic Design</h3>
                            <p class="text-gray-500 leading-relaxed">Need a resume, poster, or business card? Our expert designers are ready to help.</p>
                        </div>
                         <div class="rounded-3xl bg-gray-50 p-10 ring-1 ring-inset ring-gray-900/5 transition hover:shadow-xl hover:bg-white hover:-translate-y-1">
                            <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center text-orange-600 mb-6 font-bold text-xl"><i class="fas fa-truck-fast"></i></div>
                            <h3 class="font-bold text-xl text-gray-900 mb-2">Fast Delivery</h3>
                            <p class="text-gray-500 leading-relaxed">Don't step out. We print and deliver to your doorstep with real-time tracking.</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            
            <!-- DASHBOARD HERO -->
            <div class="pt-24 pb-12 bg-gray-50">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                     <div class="glass rounded-3xl p-8 md:p-12 shadow-sm border border-white/50 relative overflow-hidden">
                        <div class="relative z-10">
                            <h2 class="text-3xl font-bold text-gray-900">Welcome back, <?= explode(' ', $user_name)[0] ?> 👋</h2>
                             <p class="mt-2 text-gray-600">Ready to get some work done? Your dashboard is ready.</p>
                             
                             <div class="mt-8 flex gap-4">
                                 <a href="#services-list" class="bg-black text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-gray-800 transition">Place New Order</a>
                                 <a href="user/orders.php" class="bg-white text-gray-900 border border-gray-200 px-6 py-3 rounded-xl font-bold text-sm hover:bg-gray-50 transition">Track Orders</a>
                             </div>
                        </div>
                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/man-working-on-laptop-illustration-download-in-svg-png-gif-file-formats--person-business-male-office-pack-people-illustrations-4168692.png" class="absolute -right-10 -bottom-10 h-64 opacity-20 md:opacity-100">
                     </div>
                </div>
            </div>

            <!-- SERVICE CATALOG -->
            <div id="services-list" class="bg-white py-12">
                 <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Explore Services</h3>
                            <p class="text-sm text-gray-500">Select a service to configure and order.</p>
                        </div>
                         <!-- Search or Filter could go here -->
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($services as $service): ?>
                        <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white transition hover:shadow-xl hover:border-gray-200">
                             <!-- Image Badge -->
                             <?php if($service['price_type'] !== 'fixed'): ?>
                                <div class="absolute top-4 left-4 z-10">
                                    <span class="inline-flex items-center rounded-lg bg-white/90 backdrop-blur px-2.5 py-1 text-xs font-bold text-gray-900 shadow-sm border border-gray-100">
                                        <?= ucwords(str_replace('_', ' ', $service['price_type'])) ?>
                                    </span>
                                </div>
                             <?php endif; ?>

                             <!-- Service Image -->
                             <div class="aspect-[16/9] bg-gray-100 overflow-hidden relative">
                                <img src="uploads/services/<?= $service['image'] ?>" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" alt="">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition"></div>
                             </div>
                             
                             <div class="p-6 flex-1 flex flex-col">
                                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition">
                                    <a href="#"><?= htmlspecialchars($service['name']) ?></a>
                                </h3>
                                <p class="text-sm text-gray-500 mt-2 line-clamp-2 flex-1"><?= ($service['description'] ?? 'High quality output guaranteed.') ?></p>
                                
                                <div class="mt-4 pt-4 border-t border-gray-50 flex items-center justify-between">
                                    <span class="text-xl font-bold text-gray-900">₹<?= formatPrice($service['price']) ?></span>
                                    
                                    <div class="flex gap-2">
                                        <form action="user/wishlist_action.php" method="POST">
                                            <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                            <button type="submit" class="p-3 rounded-xl bg-gray-50 text-gray-400 hover:text-red-500 hover:bg-red-50 transition"><i class="far fa-heart"></i></button>
                                        </form>
                                        <form action="user/cart.php" method="POST">
                                            <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="p-3 rounded-xl bg-black text-white hover:bg-gray-800 transition shadow-lg shadow-gray-200 font-medium text-sm">
                                                Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                             </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                 </div>
            </div>

        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white border-t border-gray-800 mt-auto">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
            <div class="md:flex md:justify-between">
                <div class="mb-8 md:mb-0">
                    <span class="text-2xl font-bold tracking-tighter text-white flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-white text-black flex items-center justify-center text-sm font-bold">CC</span>
                        CyberCafe
                    </span>
                    <p class="mt-4 text-sm text-gray-400 max-w-xs">Connecting you to premium cyber services, anywhere, anytime.</p>
                </div>
                <div class="grid grid-cols-2 gap-8 sm:gap-6 sm:grid-cols-3">
                    <div>
                        <h2 class="mb-6 text-sm font-semibold text-gray-200 uppercase">Services</h2>
                        <ul class="text-gray-400 text-sm space-y-4">
                            <li><a href="#" class="hover:text-white transition">Printing</a></li>
                            <li><a href="#" class="hover:text-white transition">Designing</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-800 pt-8 md:flex md:items-center md:justify-between">
                <div class="text-sm text-gray-400">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</div>
            </div>
        </div>
    </footer>

</body>
</html>
