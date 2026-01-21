<?php
require_once 'config/config.php';
require_once 'config/Auth.php';

$auth = new Auth();
$error = '';
$success = '';
$verification_mode = false;
$verify_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Login
    if (isset($_POST['login'])) {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        $result = $auth->login($email, $password);
        if ($result['status']) {
            redirect($result['redirect']);
        } else {
            $error = $result['message'];
        }
    } 
    
    // Register
    elseif (isset($_POST['register'])) {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = $_POST['password'];

        $result = $auth->register($name, $email, $password, $phone);
        if ($result['status']) {
            $success = $result['message'];
            $verification_mode = true; // Switch to verification UI
            $verify_email = $email;
        } else {
            $error = $result['message'];
        }
    }

    // Verify OTP
    elseif (isset($_POST['verify_otp'])) {
        $email = sanitize($_POST['email']);
        $otp = sanitize($_POST['otp']);

        $result = $auth->verifyOTP($email, $otp);
        if ($result['status']) {
            $success = $result['message'];
            // Remain on login page (default view) to let them login
        } else {
            $error = $result['message'];
            $verification_mode = true; // Stay on verify
            $verify_email = $email;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Portal - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
        }
        .form-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .hidden-form {
            display: none;
            opacity: 0;
            transform: translateY(10px);
        }
        .visible-form {
            display: block;
            opacity: 1;
            transform: translateY(0);
            animation: fadeIn 0.5s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Background Effects -->
    <div class="absolute inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover opacity-400" alt="Cyberpunk Background">
        <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/90 to-black/80"></div>
    </div>

    <!-- Main Container -->
    <div class="w-full max-w-5xl h-[600px] bg-white/10 rounded-3xl shadow-2xl overflow-hidden flex relative z-10 backdrop-blur-sm border border-white/10">
        
        <!-- Left Side (Info/Branding) -->
        <div class="hidden lg:flex w-5/12 bg-black/40 p-12 flex-col justify-between text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/80 z-0"></div>
            
            <div class="relative z-10">
                <div class="h-10 w-10 bg-white rounded-lg flex items-center justify-center mb-6">
                    <i class="fas fa-cube text-black text-xl"></i>
                </div>
                <h1 class="text-4xl font-bold mb-2">Welcome Back</h1>
                <p class="text-gray-300 font-light">Manage your cyber cafe services with ease.</p>
            </div>

            <div class="relative z-10 space-y-6">
                <div class="flex items-center gap-4 group">
                    <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center border border-white/20 group-hover:bg-white group-hover:text-black transition-colors">
                        <i class="fas fa-print"></i>
                    </div>
                    <div>
                        <h3 class="font-medium">Printing Services</h3>
                        <p class="text-xs text-gray-400">High quality documents</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 group">
                    <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center border border-white/20 group-hover:bg-white group-hover:text-black transition-colors">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h3 class="font-medium">Fast Delivery</h3>
                        <p class="text-xs text-gray-400">Doorstep service</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 text-xs text-gray-400">
                &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
            </div>
        </div>

        <!-- Right Side (Forms) -->
        <div class="w-full lg:w-7/12 glass-panel p-8 md:p-12 relative flex items-center justify-center bg-white">
            <div class="w-full max-w-md">
                
                <?php if($error): ?>
                    <div class="mb-6 rounded-lg bg-red-50 p-4 border-l-4 border-red-500 flex items-center gap-3 animate-pulse">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                        <p class="text-sm text-red-700 font-medium"><?= $error ?></p>
                    </div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="mb-6 rounded-lg bg-green-50 p-4 border-l-4 border-green-500 flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <p class="text-sm text-green-700 font-medium"><?= $success ?></p>
                    </div>
                <?php endif; ?>

                <!-- LOGIN FORM -->
                <div id="loginForm" class="<?= $verification_mode ? 'hidden-form' : 'visible-form' ?>">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Sign In</h2>
                        <p class="text-gray-500 text-sm mt-1">Access your dashboard using your email.</p>
                    </div>

                    <form method="POST" class="space-y-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Email Address</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="far fa-envelope"></i>
                                </span>
                                <input type="email" name="email" required class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none transition-all bg-gray-50 focus:bg-white" placeholder="john@example.com">
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-xs font-semibold text-gray-700 uppercase">Password</label>
                                <a href="#" class="text-xs text-gray-500 hover:text-black hover:underline">Forgot?</a>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="password" required class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none transition-all bg-gray-50 focus:bg-white" placeholder="••••••••">
                            </div>
                        </div>

                        <button type="submit" name="login" class="w-full bg-black text-white font-semibold py-3.5 rounded-xl hover:bg-gray-800 transition transform focus:scale-[0.98] shadow-lg shadow-gray-200">
                            Sign In
                        </button>
                    </form>

                    <div class="my-6 flex items-center justify-center space-x-4">
                        <span class="h-px w-full bg-gray-200"></span>
                        <span class="text-xs text-gray-400 font-medium uppercase">Or</span>
                        <span class="h-px w-full bg-gray-200"></span>
                    </div>

                    <a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=<?= GOOGLE_CLIENT_ID ?>&redirect_uri=<?= urlencode(GOOGLE_REDIRECT_URI) ?>&response_type=code&scope=email%20profile" class="w-full flex items-center justify-center gap-3 bg-white text-gray-700 font-semibold py-3.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition transform focus:scale-[0.98]">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5" alt="Google">
                        Continue with Google
                    </a>

                    <p class="mt-8 text-center text-sm text-gray-500">
                        New around here? 
                        <button onclick="switchForm('register')" class="text-black font-semibold hover:underline">Create Account</button>
                    </p>
                </div>

                <!-- REGISTER FORM -->
                <div id="registerForm" class="hidden-form">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Create Account</h2>
                        <p class="text-gray-500 text-sm mt-1">Join us to start ordering services.</p>
                    </div>

                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Full Name</label>
                                <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none bg-gray-50 focus:bg-white" placeholder="John Doe">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Phone</label>
                                <input type="tel" name="phone" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none bg-gray-50 focus:bg-white" placeholder="98765...">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Email Address</label>
                            <input type="email" name="email" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none bg-gray-50 focus:bg-white" placeholder="john@example.com">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Password</label>
                            <input type="password" name="password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none bg-gray-50 focus:bg-white" placeholder="••••••••">
                        </div>

                        <button type="submit" name="register" class="w-full bg-black text-white font-semibold py-3 rounded-xl hover:bg-gray-800 transition transform focus:scale-[0.98] shadow-lg shadow-gray-200">
                            Sign Up
                        </button>
                    </form>

                    <div class="my-6 flex items-center justify-center space-x-4">
                        <span class="h-px w-full bg-gray-200"></span>
                        <span class="text-xs text-gray-400 font-medium uppercase">Or</span>
                        <span class="h-px w-full bg-gray-200"></span>
                    </div>

                    <a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=<?= GOOGLE_CLIENT_ID ?>&redirect_uri=<?= urlencode(GOOGLE_REDIRECT_URI) ?>&response_type=code&scope=email%20profile" class="w-full flex items-center justify-center gap-3 bg-white text-gray-700 font-semibold py-3.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition transform focus:scale-[0.98]">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5" alt="Google">
                        Sign up with Google
                    </a>

                    <p class="mt-6 text-center text-sm text-gray-500">
                        Already a member? 
                        <button onclick="switchForm('login')" class="text-black font-semibold hover:underline">Sign In</button>
                    </p>
                </div>

                <!-- VERIFY FORM -->
                <div id="verifyForm" class="<?= $verification_mode ? 'visible-form' : 'hidden-form' ?>">
                    <div class="mb-8 text-center">
                        <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-shield-alt text-2xl text-green-600"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Verify Email</h2>
                        <p class="text-gray-500 text-sm mt-2">
                            We've sent a code to <br> <span class="font-medium text-gray-900"><?= htmlspecialchars($verify_email ?: 'your email') ?></span>
                        </p>
                    </div>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($verify_email) ?>">
                        
                        <div>
                            <input type="text" name="otp" required maxlength="6" class="w-full px-4 py-4 text-center text-2xl tracking-[0.5em] font-bold rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none transition-all bg-gray-50 focus:bg-white" placeholder="000000">
                        </div>

                        <button type="submit" name="verify_otp" class="w-full bg-black text-white font-semibold py-3.5 rounded-xl hover:bg-gray-800 transition transform focus:scale-[0.98] shadow-lg shadow-gray-200">
                            Verify Account
                        </button>
                    </form>

                     <p class="mt-6 text-center text-sm text-gray-500">
                        Didn't receive it? <a href="#" class="text-black font-medium hover:underline">Resend Code</a>
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Script for Toggling Forms -->
    <script>
        function switchForm(target) {
            const forms = ['login', 'register', 'verify'];
            
            forms.forEach(form => {
                const el = document.getElementById(form + 'Form');
                if (form === target) {
                    el.classList.remove('hidden-form');
                    el.classList.add('visible-form');
                } else {
                    el.classList.add('hidden-form');
                    el.classList.remove('visible-form');
                }
            });
        }
    </script>
</body>
</html>
