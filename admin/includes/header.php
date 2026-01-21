<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireAdmin();
$db = new Database();
$conn = $db->getConnection();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = ucfirst($currentPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f9fafb; }
        .glass-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

<!-- Top Bar (Fixed) -->
<header class="fixed top-0 right-0 left-0 md:left-64 h-20 glass-header z-30 px-6 sm:px-8 flex items-center justify-between transition-all duration-300">
    
    <!-- Breadcrumbs / Page Title -->
    <div>
        <h1 class="text-xl font-bold text-gray-900 capitalize"><?= $pageTitle == 'Index' ? 'Overview' : str_replace('_', ' ', $pageTitle) ?></h1>
        <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
            <span>Admin</span>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-gray-900 font-medium"><?= $pageTitle == 'Index' ? 'Dashboard' : str_replace('_', ' ', $pageTitle) ?></span>
        </div>
    </div>

    <!-- Right Actions -->
    <div class="flex items-center gap-4 sm:gap-6">
        <button class="relative p-2 text-gray-400 hover:text-black transition-colors rounded-lg hover:bg-gray-50">
            <i class="far fa-bell text-xl"></i>
            <span class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
        </button>
        
        <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>

        <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <p class="text-sm font-semibold text-gray-900">Super Admin</p>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">Administrator</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center font-bold text-sm ring-4 ring-gray-100 cursor-pointer shadow-sm">
                SA
            </div>
        </div>
    </div>
</header>
