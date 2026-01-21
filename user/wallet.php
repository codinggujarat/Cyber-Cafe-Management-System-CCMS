<?php
include 'includes/header.php';

// Handle Add Money Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    
    $amount = $_POST['amount'];
    $rzp_payment_id = $_POST['razorpay_payment_id'];
    $rzp_order_id = $_POST['razorpay_order_id'];
    $rzp_signature = $_POST['razorpay_signature'];

    $generated_signature = hash_hmac('sha256', $rzp_order_id . "|" . $rzp_payment_id, RAZORPAY_KEY_SECRET);

    if ($generated_signature == $rzp_signature) {
        // Success
        $conn->beginTransaction();
        try {
            // Update User Wallet
            $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);

            // Log Transaction
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'credit', ?, 'Added to Wallet (Online)')");
            $stmt->execute([$user_id, $amount]);

            $conn->commit();
            setFlash('success', "₹$amount Added to Wallet Successfully!");
        } catch (Exception $e) {
            $conn->rollBack();
            setFlash('error', "Transaction Failed: " . $e->getMessage());
        }
    } else {
        setFlash('error', "Payment Verification Failed!");
    }
    redirect('wallet.php');
}

// Fetch Wallet Data
$balance = $conn->query("SELECT wallet_balance FROM users WHERE id = $user_id")->fetchColumn() ?: 0.00;
$transactions = $conn->query("SELECT * FROM transactions WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 20")->fetchAll();
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">My Wallet</h1>
            <p class="text-gray-500">Manage your funds and transactions.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
        <!-- Balance Card -->
        <div class="md:col-span-1 bg-gradient-to-br from-black to-gray-800 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-xl"></div>
            
            <p class="text-gray-400 text-sm font-medium uppercase tracking-wider mb-2">Total Balance</p>
            <h2 class="text-4xl font-bold mb-8">₹<?= number_format($balance, 2) ?></h2>

            <!-- Add Money Form -->
            <div class="relative">
                <input type="number" id="amount" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-white mb-3 appearance-none" placeholder="Enter Amount">
                <button onclick="addMoney()" class="w-full bg-white text-black font-bold py-3 rounded-xl hover:bg-gray-100 transition shadow-lg">
                    <i class="fas fa-plus-circle mr-2"></i> Add Money
                </button>
            </div>
            <p id="msg" class="text-xs text-red-400 mt-2 hidden"></p>
        </div>

        <!-- History -->
        <div class="md:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
            <h3 class="font-bold text-lg text-gray-900 mb-6">Recent Transactions</h3>
            
            <div class="overflow-hidden">
                <?php if (count($transactions) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($transactions as $txn): ?>
                        <div class="flex items-center justify-between p-4 rounded-xl hover:bg-gray-50 transition border border-gray-50">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $txn['type'] == 'credit' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                                    <i class="fas <?= $txn['type'] == 'credit' ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900"><?= htmlspecialchars($txn['description']) ?></p>
                                    <p class="text-xs text-gray-400"><?= date('M d, h:i A', strtotime($txn['created_at'])) ?></p>
                                </div>
                            </div>
                            <p class="font-bold <?= $txn['type'] == 'credit' ? 'text-green-600' : 'text-gray-900' ?>">
                                <?= $txn['type'] == 'credit' ? '+' : '-' ?> ₹<?= number_format($txn['amount'], 2) ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-10 text-gray-400">
                        <i class="fas fa-receipt text-3xl mb-3"></i>
                        <p>No transactions yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Verify -->
<form id="verifyForm" method="POST" class="hidden">
    <input type="hidden" name="amount" id="verifyAmount">
    <input type="hidden" name="razorpay_payment_id" id="rzp_pid">
    <input type="hidden" name="razorpay_order_id" id="rzp_oid">
    <input type="hidden" name="razorpay_signature" id="rzp_sig">
</form>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    async function addMoney() {
        const amount = document.getElementById('amount').value;
        const msg = document.getElementById('msg');
        
        if (!amount || amount < 1) {
            msg.innerText = "Minimum amount is ₹1";
            msg.classList.remove('hidden');
            return;
        }
        msg.classList.add('hidden');

        // Create Order
        try {
            const res = await fetch('ajax_create_wallet_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: parseFloat(amount) })
            });
            const data = await res.json();
            
            if (data.error) throw new Error(data.error);

            // Razorpay
            const options = {
                "key": data.key,
                "amount": data.amount,
                "currency": "INR",
                "name": "Add to Wallet",
                "description": "Wallet Recharge",
                "order_id": data.id,
                "handler": function (response){
                    document.getElementById('verifyAmount').value = amount;
                    document.getElementById('rzp_pid').value = response.razorpay_payment_id;
                    document.getElementById('rzp_oid').value = response.razorpay_order_id;
                    document.getElementById('rzp_sig').value = response.razorpay_signature;
                    document.getElementById('verifyForm').submit();
                },
                "theme": { "color": "#000000" }
            };
            const rzp = new Razorpay(options);
            rzp.open();

        } catch (err) {
            msg.innerText = err.message;
            msg.classList.remove('hidden');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
