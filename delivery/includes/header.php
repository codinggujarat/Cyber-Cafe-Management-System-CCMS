<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn() || !isDelivery()) {
    redirect('/login.php');
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Partner - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-nav {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="bg-gray-50 pb-24">

<!-- Top Header -->
<div class="bg-white/80 backdrop-blur-md border-b border-gray-100 sticky top-0 z-40">
    <div class="px-5 py-4 flex justify-between items-center max-w-lg mx-auto">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center font-bold">
                <i class="fas fa-cube"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold text-gray-900 leading-tight">CyberDelivery</h1>
                <p class="text-[10px] text-gray-500 font-medium tracking-wide uppercase">Partner App</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                <p class="text-xs text-green-500 font-medium">● Online</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 border border-gray-200">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </div>
</div>

<div class="p-5 max-w-lg mx-auto">
