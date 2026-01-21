<?php
include 'includes/header.php';

// Fetch Cart Items
$cart_items = $conn->query("SELECT c.*, c.custom_data, s.name, s.price, s.file_required as file_req, s.hsn_code, s.gst_rate FROM cart c JOIN services s ON c.service_id = s.id WHERE c.user_id = $user_id")->fetchAll();

if (count($cart_items) == 0) {
    redirect('cart.php');
}

// Fetch Wallet Balance
$wallet_balance = $conn->query("SELECT wallet_balance FROM users WHERE id = $user_id")->fetchColumn() ?: 0.00;

// Calculate Total for Display
$total = 0;
$total_tax = 0;
foreach ($cart_items as $item) {
    $item_price = $item['price'] * $item['quantity'];
    $item_tax = $item_price * ($item['gst_rate'] / 100);
    $total += $item_price + $item_tax;
    $total_tax += $item_tax;
}

// Handle Order Placement
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $address = sanitize($_POST['address']);
    $payment_method = $_POST['payment_method'];
    $use_wallet = isset($_POST['use_wallet']) ? 1 : 0;

    $final_total = 0;
    
    // RE-CALCULATE TOTAL SECURELY
    // (We must re-iterate logic to confirm final total before split)
    $order_items_data = [];
    foreach ($cart_items as $item) {
        $quantity = $item['quantity'];
        $page_count = 0;
        $file_path = null;

        // File Handler (Simplified for brevity, similar to before)
        if ($item['file_req'] && isset($_FILES['file_' . $item['id']])) {
             $file = $_FILES['file_' . $item['id']];
             if ($file['error'] == 0) {
                 $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                 // We need ID for filename, but ID not gen yet. Use Temp or Update later.
                 // Better: Generate unique name now.
                 $filename = 'order_' . uniqid() . '_item_' . $item['id'] . '.' . $ext;
                 $target = '../uploads/' . $filename;
                 if (move_uploaded_file($file['tmp_name'], $target)) {
                     $file_path = $filename;
                     if (strtolower($ext) === 'pdf') {
                         $detected = countPdfPages($target);
                         if ($detected > 0) { $quantity = $detected; $page_count = $detected; }
                     }
                 }
             }
        }

        $instructions = sanitize($_POST['note_' . $item['id']] ?? '');
        $color = $_POST['color_' . $item['id']] ?? 'bw';
        $side = $_POST['side_' . $item['id']] ?? 'single';
        $size = $_POST['size_' . $item['id']] ?? 'A4';
        $binding = $_POST['binding_' . $item['id']] ?? 'none';

        $price = $item['price']; 
        if ($color === 'color') $price += 5;
        $item_subtotal = $price * $quantity;
        
        $binding_fee = 0;
        if ($binding === 'spiral') $binding_fee = 30;
        if ($binding === 'hard') $binding_fee = 100;
        
        $taxable = $item_subtotal + $binding_fee;
        $gst_rate = $item['gst_rate'] ?? 18.00;
        $tax_amt = $taxable * ($gst_rate / 100);
        $line_total = $taxable + $tax_amt;
        
        $final_total += $line_total;
        
        $order_items_data[] = [
            'service_id' => $item['service_id'], 'service_name' => $item['name'],
            'quantity' => $quantity, 'price' => $price, 'file_path' => $file_path,
            'instructions' => $instructions, 'page_count' => $page_count,
            'color' => $color, 'size' => $size, 'side' => $side, 'binding' => $binding,
            'tax_amount' => $tax_amt, 'hsn_code' => $item['hsn_code'],
            'custom_data' => $item['custom_data']
        ];
    }

    // Wallet Logic
    $wallet_deduct = 0;
    if ($use_wallet) {
        $wallet_deduct = min($wallet_balance, $final_total);
    }
    $payable_amount = $final_total - $wallet_deduct;

    // Payment Logic
    $payment_status = 'pending';
    $transaction_id = null;

    if ($payable_amount <= 0) {
        $payment_status = 'paid';
        $payment_method = 'wallet';
    } elseif ($payment_method == 'razorpay') {
        if (!isset($_POST['razorpay_payment_id']) || !isset($_POST['razorpay_signature'])) {
            die("Payment Error: Missing Razorpay Data");
        }
        $rzp_payment_id = $_POST['razorpay_payment_id'];
        $rzp_order_id = $_POST['razorpay_order_id'];
        $rzp_signature = $_POST['razorpay_signature'];
        $generated_signature = hash_hmac('sha256', $rzp_order_id . "|" . $rzp_payment_id, RAZORPAY_KEY_SECRET);
        if ($generated_signature == $rzp_signature) {
            $payment_status = 'paid';
            $transaction_id = $rzp_payment_id;
        } else {
            die("Payment Verification Failed");
        }
    }

    // Consent Validation
    if (!isset($_POST['legal_consent'])) {
        die("Error: Default to User Consent is required.");
    }
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $consent_timestamp = date('Y-m-d H:i:s');

    // Create Order
    // Added IP and Consent Timestamp and Order Hash
    $order_hash = bin2hex(random_bytes(16));
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, payment_status, transaction_id, wallet_used, ip_address, consent_timestamp, order_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $final_total, $payment_method, $address, $payment_status, $transaction_id, $wallet_deduct, $ip_address, $consent_timestamp, $order_hash]);
    $order_id = $conn->lastInsertId();
    
    // Log Consent
    logActivity('Legal Consent', "User consented to terms for Order #$order_id. IP: $ip_address");

    // Insert Items
    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, service_id, service_name, quantity, price, file_path, instructions, page_count, print_color, paper_size, print_side, binding, tax_amount, hsn_code, custom_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $total_tax_db = 0;
    foreach ($order_items_data as $d) {
        $stmtItem->execute([$order_id, $d['service_id'], $d['service_name'], $d['quantity'], $d['price'], $d['file_path'], $d['instructions'], $d['page_count'], $d['color'], $d['size'], $d['side'], $d['binding'], $d['tax_amount'], $d['hsn_code'], $d['custom_data']]);
        $total_tax_db += $d['tax_amount'];
    }
    // Update Tax Total
    $conn->query("UPDATE orders SET tax_amount = $total_tax_db WHERE id = $order_id");

    // Process Wallet Deduction
    if ($wallet_deduct > 0) {
        $conn->query("UPDATE users SET wallet_balance = wallet_balance - $wallet_deduct WHERE id = $user_id");
        $conn->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'debit', ?, ?)")->execute([$user_id, $wallet_deduct, "Payment for Order #$order_id"]);
    }

    // Notifications
    createNotification($user_id, 'Order Placed', "Order #$order_id placed successfully.", 'success');
    $admin = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
    if ($admin) createNotification($admin['id'], 'New Order', "New Order #$order_id placed.", 'info');

    $conn->query("DELETE FROM cart WHERE user_id = $user_id");
    redirect('/user/orders.php');
}
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-light tracking-tight text-gray-900">Checkout</h1>
    </div>

    <form method="POST" enctype="multipart/form-data" class="space-y-8" id="checkoutForm">
        <!-- Items (Simplified View) -->
        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-medium text-gray-900 mb-6 border-b pb-2">Order Details</h2>
            <div class="space-y-6">
                <?php foreach ($cart_items as $item): ?>
                <div class="pb-6 border-b border-gray-100 last:border-0 last:pb-0" id="item-<?= $item['id'] ?>">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($item['name']) ?></p>
                            <span class="text-sm text-gray-500">Price: ₹<?= $item['price'] ?> | Qty: <span class="qty-display"><?= $item['quantity'] ?></span></span>
                        </div>
                        <p class="text-sm font-bold text-gray-900 price-display" data-base-price="<?= $item['price'] ?>" data-qty="<?= $item['quantity'] ?>">₹<?= $item['price'] * $item['quantity'] ?></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php if($item['file_req']): ?>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">File</label>
                            <input type="file" name="file_<?= $item['id'] ?>" accept=".pdf,.doc,.docx,.jpg,.png" onchange="detectPages(this, <?= $item['id'] ?>)" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:bg-gray-50 hover:file:bg-gray-100" required>
                            <input type="hidden" name="detected_pages_<?= $item['id'] ?>" id="pages_<?= $item['id'] ?>" value="0">
                        </div>
                        <!-- Options -->
                        <div class="col-span-1 md:col-span-2 bg-gray-50 p-3 rounded-lg border border-gray-100 grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <label class="text-[10px] uppercase font-bold text-gray-500">Color</label>
                                <select name="color_<?= $item['id'] ?>" class="w-full text-sm border-gray-200 rounded option-select" data-id="<?= $item['id'] ?>" onchange="updateLineItem(<?= $item['id'] ?>)">
                                    <option value="bw">B&W</option>
                                    <option value="color">Color (+₹5)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] uppercase font-bold text-gray-500">Binding</label>
                                <select name="binding_<?= $item['id'] ?>" class="w-full text-sm border-gray-200 rounded option-select" data-id="<?= $item['id'] ?>" onchange="updateLineItem(<?= $item['id'] ?>)">
                                    <option value="none">None</option>
                                    <option value="spiral">Spiral (+₹30)</option>
                                    <option value="hard">Hard (+₹100)</option>
                                </select>
                            </div>
                             <div>
                                <label class="text-[10px] uppercase font-bold text-gray-500">Side</label>
                                <select name="side_<?= $item['id'] ?>" class="w-full text-sm border-gray-200 rounded">
                                    <option value="single">Single</option>
                                    <option value="double">Double</option>
                                </select>
                            </div>
                             <div>
                                <label class="text-[10px] uppercase font-bold text-gray-500">Size</label>
                                <select name="size_<?= $item['id'] ?>" class="w-full text-sm border-gray-200 rounded">
                                    <option value="A4">A4</option>
                                    <option value="Legal">Legal</option>
                                    <option value="A3">A3</option>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div>
                             <input type="text" name="note_<?= $item['id'] ?>" class="w-full text-sm border-gray-200 rounded" placeholder="Instructions...">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-6 border-t pt-4 flex justify-between items-center">
                 <span class="font-bold">Total Amount</span>
                 <span class="text-xl font-bold" id="uiTotal">₹<?= $total ?></span>
            </div>
        </div>

        <!-- Wallet & Payment -->
        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Payment</h2>
            
            <!-- Wallet Section -->
            <div class="mb-6 p-4 bg-gray-900 text-white rounded-xl flex items-center justify-between">
                <div>
                     <div class="flex items-center gap-2">
                        <input type="checkbox" id="use_wallet" name="use_wallet" onchange="updatePaymentUI()" class="rounded border-gray-500 text-green-500 focus:ring-green-500 bg-gray-800 w-5 h-5">
                        <label for="use_wallet" class="font-bold cursor-pointer select-none">Use Wallet Balance</label>
                     </div>
                     <p class="text-xs text-gray-400 mt-1 ml-7">Available: ₹<span id="walletBal"><?= $wallet_balance ?></span></p>
                </div>
                <div class="text-lg font-bold">
                    - ₹<span id="walletDeduct">0.00</span>
                </div>
            </div>

            <!-- Address -->
            <div class="mb-6">
                 <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address</label>
                 <textarea name="address" rows="2" required class="block w-full rounded-md border-gray-200 shadow-sm sm:text-sm"></textarea>
            </div>

            <!-- Methods -->
             <div id="paymentMethods" class="space-y-3">
                <div class="flex items-center">
                    <input id="cod" name="payment_method" type="radio" value="cod" checked class="h-4 w-4 border-gray-300 text-black focus:ring-black">
                    <label for="cod" class="ml-3 block text-sm font-medium text-gray-700">Cash on Delivery (COD)</label>
                </div>
                <div class="flex items-center">
                    <input id="online" name="payment_method" type="radio" value="razorpay" class="h-4 w-4 border-gray-300 text-black focus:ring-black">
                    <label for="online" class="ml-3 block text-sm font-medium text-gray-700">Online Payment (UPI/Card)</label>
                </div>
            </div>
            
            <div id="walletFullMsg" class="hidden text-green-600 text-sm font-bold mt-4 flex items-center gap-2">
                <i class="fas fa-check-circle"></i> Fully covered by Wallet Balance.
            </div>
        </div>

        <!-- Legal Consent -->
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
            <div class="flex items-start gap-3">
                <input type="checkbox" name="legal_consent" id="legal_consent" required class="mt-1 w-5 h-5 rounded border-blue-300 text-blue-600 focus:ring-blue-500 bg-white">
                <label for="legal_consent" class="text-sm text-blue-900 leading-relaxed cursor-pointer">
                    <span class="font-bold block mb-1">Authorization & Disclaimer</span>
                    I hereby authorize <strong><?= APP_NAME ?></strong> to process and submit these government applications/documents on my behalf. I confirm that all input data is accurate and valid. I understand that processing times depend on respective government portals.
                </label>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end">
            <button type="submit" id="payBtn" class="rounded-md bg-black px-8 py-3 text-base font-semibold text-white shadow-sm hover:bg-gray-800 transition w-full md:w-auto">
                Place Order (₹<span id="payAmount"><?= $total ?></span>)
            </button>
        </div>
    </form>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

    let grandTotal = <?= $total ?>;
    let walletBalance = <?= $wallet_balance ?>;
    let payable = grandTotal;

    // Recalculate based on options (Simplified client-side logic)
    function updateLineItem(id) {
        const row = document.getElementById('item-' + id);
        const basePrice = parseFloat(row.querySelector('.price-display').dataset.basePrice);
        const qty = parseInt(row.querySelector('.qty-display').innerText);
        
        let unitAdd = 0;
        let flatAdd = 0;

        // Color
        const color = row.querySelector(`select[name="color_${id}"]`).value;
        if (color === 'color') unitAdd += 5;

        // Binding
        const binding = row.querySelector(`select[name="binding_${id}"]`).value;
        if (binding === 'spiral') flatAdd += 30;
        if (binding === 'hard') flatAdd += 100;

        let total = ((basePrice + unitAdd) * qty) + flatAdd;
        row.querySelector('.price-display').innerText = '₹' + total.toFixed(2);
        
        recalcGrandTotal();
    }

    function recalcGrandTotal() {
        let sum = 0;
        document.querySelectorAll('.price-display').forEach(el => {
            sum += parseFloat(el.innerText.replace('₹', ''));
        });
        grandTotal = sum;
        document.getElementById('uiTotal').innerText = '₹' + sum.toFixed(2);
        updatePaymentUI();
    }

    function updatePaymentUI() {
        const useWallet = document.getElementById('use_wallet').checked;
        let deduct = 0;

        if (useWallet) {
            deduct = Math.min(walletBalance, grandTotal);
        }

        document.getElementById('walletDeduct').innerText = deduct.toFixed(2);
        payable = grandTotal - deduct;
        document.getElementById('payAmount').innerText = payable.toFixed(2);

        const methods = document.getElementById('paymentMethods');
        const msg = document.getElementById('walletFullMsg');

        if (payable <= 0) {
            methods.classList.add('hidden');
            msg.classList.remove('hidden');
        } else {
            methods.classList.remove('hidden');
            msg.classList.add('hidden');
        }
    }

    // PDF Detect
    async function detectPages(input, id) {
        if (input.files && input.files[0] && input.files[0].type === 'application/pdf') {
            const reader = new FileReader();
            reader.onload = async function(e) {
                 const pdf = await pdfjsLib.getDocument(new Uint8Array(e.target.result)).promise;
                 const pages = pdf.numPages;
                 document.getElementById('pages_' + id).value = pages;
                 document.querySelector(`#item-${id} .qty-display`).innerText = pages;
                 // Also Update input hidden/data?
                 // Note: Logic in updateLineItem uses .qty-display text.
                 updateLineItem(id); 
            };
            reader.readAsArrayBuffer(input.files[0]);
        }
    }

    // Payment Handling
    document.getElementById('checkoutForm').addEventListener('submit', async function(e) {
        if (payable > 0) {
            const method = document.querySelector('input[name="payment_method"]:checked').value;
            if (method === 'razorpay') {
                e.preventDefault();
                // Call Razorpay with 'payable' amount
                const btn = document.getElementById('payBtn');
                btn.disabled = true;
                btn.innerText = 'Processing...';

                try {
                    const res = await fetch('ajax_create_razorpay_order.php', {
                        method: 'POST',
                        body: JSON.stringify({ amount: payable })
                    });
                    const data = await res.json();
                    
                    const options = {
                        "key": data.key, "amount": data.amount, "currency": "INR", "name": "Cyber Cafe",
                        "description": "Order Balance", "order_id": data.id,
                        "handler": function (response){
                            const form = document.getElementById('checkoutForm');
                            let i = document.createElement('input'); 
                            i.type='hidden'; i.name='razorpay_payment_id'; i.value=response.razorpay_payment_id; form.appendChild(i);
                            let j = document.createElement('input'); 
                            j.type='hidden'; j.name='razorpay_signature'; j.value=response.razorpay_signature; form.appendChild(j);
                            let k = document.createElement('input'); 
                            k.type='hidden'; k.name='razorpay_order_id'; k.value=response.razorpay_order_id; form.appendChild(k);
                            form.submit();
                        }
                    };
                    new Razorpay(options).open();
                } catch(err) {
                    alert('Error starting payment');
                    btn.disabled = false;
                }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
