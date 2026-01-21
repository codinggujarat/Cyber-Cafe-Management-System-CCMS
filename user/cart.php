<?php
include 'includes/header.php';

// Handle Cart Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $service_id = $_POST['service_id'];
            $quantity = $_POST['quantity'];
            $custom_data = !empty($_POST['custom_data']) ? $_POST['custom_data'] : null;
            
            // Check if item exists with SAME custom data
            $check = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND service_id = ? AND (custom_data = ? OR (custom_data IS NULL AND ? IS NULL))");
            $check->execute([$user_id, $service_id, $custom_data, $custom_data]);
            $existing = $check->fetch();

            if ($existing) {
                $new_qty = $existing['quantity'] + $quantity;
                $conn->query("UPDATE cart SET quantity = $new_qty WHERE id = " . $existing['id']);
            } else {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, service_id, quantity, custom_data) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $service_id, $quantity, $custom_data]);
            }
            setFlash('success', 'Item added to cart');
        } elseif ($action === 'update') {
            $cart_id = $_POST['cart_id'];
            $qty = $_POST['quantity'];
            $conn->query("UPDATE cart SET quantity = $qty WHERE id = $cart_id");
            setFlash('success', 'Cart updated');
        } elseif ($action === 'remove') {
            $cart_id = $_POST['cart_id'];
            $conn->query("DELETE FROM cart WHERE id = $cart_id");
            setFlash('success', 'Item removed');
        }
        
        redirect('user/cart.php');
    }
}

$cart_items = $conn->query("SELECT c.*, s.name, s.price, s.price_type, s.image FROM cart c JOIN services s ON c.service_id = s.id WHERE c.user_id = $user_id")->fetchAll();
$total = 0;
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 w-full">
            <div class="mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Your Cart</h1>
            </div>

            <?php if ($msg = getFlash()): ?>
                <div class="mb-6 rounded-lg bg-<?= $msg['type'] == 'success' ? 'green' : 'red' ?>-50 p-4 border border-<?= $msg['type'] == 'success' ? 'green' : 'red' ?>-100">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-<?= $msg['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?> text-<?= $msg['type'] == 'success' ? 'green' : 'red' ?>-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-<?= $msg['type'] == 'success' ? 'green' : 'red' ?>-800"><?= $msg['message'] ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (count($cart_items) > 0): ?>
                <div class="flex flex-col xl:flex-row gap-8">
                    <!-- Cart Items -->
                    <div class="flex-1">
                        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                            <ul role="list" class="divide-y divide-gray-50">
                                <?php foreach ($cart_items as $item): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $total += $item_total;
                                ?>
                                <li class="flex flex-col sm:flex-row sm:items-center gap-6 p-6">
                                    <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                                        <img src="../uploads/services/<?= !empty($item['image']) ? $item['image'] : 'default_service.png' ?>" class="h-full w-full object-cover object-center">
                                    </div>

                                    <div class="flex-1">
                                        <h3 class="text-base font-semibold text-gray-900"><?= htmlspecialchars($item['name']) ?></h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            ₹<?= $item['price'] ?> 
                                            <span class="text-xs text-gray-400 uppercase bg-gray-50 px-1.5 py-0.5 rounded ml-1"><?= str_replace('_', ' ', $item['price_type']) ?></span>
                                        </p>
                                        <?php if(!empty($item['custom_data'])): ?>
                                            <div class="mt-2 space-y-1">
                                                <?php foreach(json_decode($item['custom_data'], true) as $k => $v): ?>
                                                    <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= htmlspecialchars($k) ?>:</span> <?= htmlspecialchars($v) ?></p>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto">
                                        <form method="POST" class="flex items-center gap-4">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                            <div class="flex items-center rounded-lg border border-gray-200 bg-gray-50/50">
                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" onchange="this.form.submit()" class="w-16 border-0 bg-transparent py-1.5 text-center text-sm font-medium focus:ring-0">
                                            </div>
                                        </form>

                                        <div class="text-right min-w-[80px]">
                                            <p class="text-lg font-bold text-gray-900">₹<?= $item_total ?></p>
                                            <form method="POST" class="mt-1">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700 transition">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="w-full xl:w-80">
                        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sticky top-24">
                            <h2 class="text-lg font-bold text-gray-900 mb-6">Order Summary</h2>
                            <div class="flex items-center justify-between py-4 border-b border-gray-50">
                                <span class="text-gray-500">Subtotal</span>
                                <span class="font-medium text-gray-900">₹<?= $total ?></span>
                            </div>
                             <div class="flex items-center justify-between py-4 border-b border-gray-50">
                                <span class="text-gray-500">Tax</span>
                                <span class="font-medium text-gray-900">₹0.00</span>
                            </div>
                            <div class="flex items-center justify-between py-4 mb-2">
                                <span class="text-base font-bold text-gray-900">Total</span>
                                <span class="text-2xl font-bold text-gray-900">₹<?= $total ?></span>
                            </div>
                            <div class="mt-6">
                                <a href="checkout.php" class="flex w-full items-center justify-center rounded-xl bg-black px-6 py-4 text-sm font-bold text-white shadow-lg hover:bg-gray-800 transition transform active:scale-95">
                                    Proceed to Checkout
                                </a>
                            </div>
                            <div class="mt-6 flex items-center justify-center gap-2 text-xs text-gray-400">
                                <i class="fas fa-lock"></i> Secure Checkout
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-24 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/50">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm ring-1 ring-gray-100">
                        <i class="fas fa-shopping-basket text-2xl text-gray-300"></i>
                    </div>
                    <h3 class="mt-2 text-base font-bold text-gray-900">Your cart is empty</h3>
                    <p class="mt-1 text-sm text-gray-500 mb-6">Looks like you haven't added anything yet.</p>
                    <a href="../index.php#browse-services" class="inline-flex items-center rounded-full bg-black px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-gray-800 transition">
                        Browse Services
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
