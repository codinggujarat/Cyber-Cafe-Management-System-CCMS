<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireLogin();
$db = new Database();
$conn = $db->getConnection();

if (!isset($_GET['id'])) die("Invalid Order ID");

$order_id = $_GET['id'];
$order = $conn->query("SELECT * FROM orders WHERE id = $order_id AND user_id = " . $_SESSION['user_id'])->fetch();

if (!$order) die("Order not found or access denied.");

// Self-Healing: Generate Hash if missing
if (empty($order['order_hash'])) {
    $newHash = bin2hex(random_bytes(16));
    try {
        $updateStmt = $conn->prepare("UPDATE orders SET order_hash = ? WHERE id = ?");
        $updateStmt->execute([$newHash, $order_id]);
        $order['order_hash'] = $newHash;
    } catch (Exception $e) {
        // Silent fail, but QR might fail
    }
}

$user = $conn->query("SELECT * FROM users WHERE id = " . $order['user_id'])->fetch();
$items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id")->fetchAll();
$tax_amount = $order['tax_amount'] ?? 0;
$subtotal = $order['total_amount'] - $tax_amount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
         tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Outfit', 'sans-serif'] } } }
        }
    </script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; -webkit-print-color-adjust: exact; }
            .invoice-box { box-shadow: none; border: none; padding: 0; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-10 antialiased text-slate-800">

    <div class="invoice-box max-w-4xl mx-auto bg-white p-12 rounded-3xl shadow-xl border border-gray-100 relative overflow-hidden">
        
        <!-- Decorative Top Bar -->
        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-black via-gray-800 to-black"></div>

        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-16 mt-4">
            <div>
                 <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-8 rounded-lg bg-black text-white flex items-center justify-center text-sm font-bold">CC</span>
                    <h1 class="text-2xl font-bold tracking-tighter text-black">CyberCafe</h1>
                </div>
                <p class="text-sm text-gray-500">Professional Services Portal</p>
                <p class="text-sm text-gray-500">GSTIN: 22AAAAA0000A1Z5</p>
            </div>
            <div class="text-left md:text-right mt-6 md:mt-0">
                <h2 class="text-4xl font-bold text-gray-900 tracking-tight">TAX INVOICE</h2>
                <p class="text-gray-500 mt-1 font-medium">#INV-<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-16 border-b border-gray-100 pb-12">
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Billed To</h3>
                <p class="text-lg font-bold text-gray-900 mb-1"><?= htmlspecialchars($user['name']) ?></p>
                <p class="text-gray-600 mb-1"><?= htmlspecialchars($user['email']) ?></p>
                <p class="text-gray-600 max-w-xs leading-relaxed"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
            </div>
            <div class="md:text-right">
                <div class="grid grid-cols-2 gap-x-8 gap-y-4 inline-block text-left">
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Invoice Date</h3>
                        <p class="text-gray-900 font-medium mt-1"><?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Payment</h3>
                        <p class="text-gray-900 font-medium mt-1 uppercase"><?= $order['payment_method'] ?></p>
                    </div>
                     <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Due Date</h3>
                        <p class="text-gray-900 font-medium mt-1">Paid</p>
                    </div>
                     <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Amount</h3>
                        <p class="text-gray-900 font-bold mt-1">₹<?= formatPrice($order['total_amount']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="mb-12">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-black">
                        <th class="text-left py-4 text-xs font-bold text-black uppercase tracking-wider w-1/2">Description</th>
                        <th class="text-right py-4 text-xs font-bold text-black uppercase tracking-wider">Qty</th>
                        <th class="text-right py-4 text-xs font-bold text-black uppercase tracking-wider">Unit Price</th>
                        <th class="text-right py-4 text-xs font-bold text-black uppercase tracking-wider">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="py-5">
                            <p class="font-bold text-gray-900"><?= htmlspecialchars($item['service_name']) ?></p>
                            <?php if ($item['hsn_code']): ?>
                                <span class="text-[10px] text-gray-400">HSN: <?= $item['hsn_code'] ?></span>
                            <?php endif; ?>
                            <?php if($item['instructions']): ?>
                                <p class="text-xs text-gray-500 mt-1 italic">Note: <?= htmlspecialchars($item['instructions']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="py-5 text-right text-gray-600 font-medium"><?= $item['quantity'] ?></td>
                        <td class="py-5 text-right text-gray-600 font-medium">₹<?= formatPrice($item['price']) ?></td>
                        <td class="py-5 text-right text-gray-900 font-bold">₹<?= formatPrice($item['price'] * $item['quantity']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="flex justify-end">
            <div class="w-full md:w-1/3 space-y-3">
                <div class="flex justify-between text-gray-600 text-sm">
                    <span>Taxable Amount</span>
                    <span class="font-medium">₹<?= formatPrice($subtotal) ?></span>
                </div>
                <div class="flex justify-between text-gray-600 text-sm">
                    <span>Total Tax (GST)</span>
                    <span class="font-medium">₹<?= formatPrice($tax_amount) ?></span>
                </div>
                <div class="flex justify-between text-gray-600 text-sm border-b border-gray-100 pb-3">
                    <span>Discount</span>
                    <span class="font-medium text-green-600">-₹0.00</span>
                </div>
                <div class="flex justify-between items-center text-xl font-bold text-black pt-2">
                    <span>Grand Total</span>
                    <span>₹<?= formatPrice($order['total_amount']) ?></span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-20 pt-8 border-t border-gray-100">
             <div class="flex flex-col md:flex-row justify-between items-end gap-6">
                <div class="text-xs text-gray-400">
                    <p class="font-bold text-gray-900 mb-1">Terms & Conditions</p>
                    <p>1. This is a computer generated invoice.</p>
                    <p>2. Subject to local jurisdiction.</p>
                </div>
                
                <!-- QR Code for Tracking -->
                <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <?php 
                        $trackLink = BASE_URL . 'track_public.php?hash=' . ($order['order_hash'] ?? '');
                        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode($trackLink);
                    ?>
                    <img src="<?= $qrUrl ?>" alt="Scan to Track" class="w-20 h-20 mix-blend-multiply">
                    <div>
                        <p class="text-[10px] font-bold text-gray-900 uppercase tracking-wider">Scan to Track</p>
                        <p class="text-[10px] text-gray-500">Live Status & Verification</p>
                    </div>
                </div>

                <div class="text-center">
                    <!-- Signature placeholder -->
                    <div class="h-12"></div>
                    <p class="text-xs font-bold text-gray-900 uppercase">Authorized Signatory</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Floating Actions -->
    <div class="no-print fixed bottom-8 left-1/2 transform -translate-x-1/2 flex gap-4 bg-white/90 backdrop-blur p-2 rounded-2xl shadow-2xl border border-gray-200">
        <button onclick="window.print()" class="flex items-center gap-2 bg-black text-white px-6 py-3 rounded-xl font-bold hover:bg-gray-800 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Invoice
        </button>
        <a href="orders.php" class="flex items-center gap-2 bg-white text-gray-700 border border-gray-200 px-6 py-3 rounded-xl font-bold hover:bg-gray-50 transition">
            Close
        </a>
    </div>

</body>
</html>
