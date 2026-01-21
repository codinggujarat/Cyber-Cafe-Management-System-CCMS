<?php
include 'includes/header.php';
include 'includes/sidebar.php';

// Handle Add Delivery Boy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_delivery_boy'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        setFlash('error', 'Email already registered');
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (:name, :email, :phone, :pass, 'delivery', 1)");
        $stmt->execute([':name' => $name, ':email' => $email, ':phone' => $phone, ':pass' => $password]);
        setFlash('success', 'Delivery Boy added successfully');
        redirect('/admin/delivery_boys.php');
    }
}

// Toggle User Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $curr = $conn->query("SELECT status FROM users WHERE id = $id")->fetchColumn();
    $new = $curr ? 0 : 1;
    $conn->query("UPDATE users SET status = $new WHERE id = $id");
    redirect('/admin/delivery_boys.php');
}

// Fetch Delivery Boys with Stats
$users = $conn->query("
    SELECT u.*, 
    (SELECT COUNT(*) FROM orders WHERE delivery_boy_id = u.id AND order_status = 'delivered') as total_deliveries,
    (SELECT COUNT(*) FROM orders WHERE delivery_boy_id = u.id AND order_status != 'delivered' AND order_status != 'cancelled') as active_deliveries
    FROM users u 
    WHERE u.role = 'delivery' 
    ORDER BY u.id DESC
")->fetchAll();
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Delivery Team</h2>
        <p class="text-sm text-gray-500">Manage your delivery personnel and performance.</p>
    </div>
    <div class="flex gap-3">
         <div class="hidden md:flex bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                <i class="fas fa-motorcycle"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-gray-500">Active Agents</p>
                <p class="text-lg font-bold text-gray-900"><?= count($users) ?></p>
            </div>
        </div>

        <a href="payouts.php" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium hover:bg-gray-50 transition text-gray-700">
            <i class="fas fa-wallet text-gray-400"></i> Payouts
        </a>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-xl text-sm font-bold hover:bg-gray-800 transition shadow-lg shadow-gray-200">
            <i class="fas fa-plus"></i> Add New Agent
        </button>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4 font-semibold">Agent Details</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold">Stats</th>
                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if(count($users) > 0): ?>
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-lg">
                                    <i class="fas fa-motorcycle"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($u['name']) ?></p>
                                    <p class="text-xs text-gray-500 flex items-center gap-2">
                                        <i class="fas fa-envelope text-[10px]"></i> <?= htmlspecialchars($u['email']) ?>
                                    </p>
                                    <?php if(!empty($u['phone'])): ?>
                                    <p class="text-xs text-gray-500 flex items-center gap-2">
                                        <i class="fas fa-phone text-[10px]"></i> <?= htmlspecialchars($u['phone']) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($u['status']): ?>
                                <span class="inline-flex items-center rounded-full bg-green-50 text-green-700 px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ring-green-600/20">Active</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-red-50 text-red-700 px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ring-red-600/20">Blocked</span>
                            <?php endif; ?>
                        </td>
                         <td class="px-6 py-4">
                            <div class="flex gap-4">
                                <div class="text-center">
                                    <span class="block text-xs text-gray-500 uppercase">Delivered</span>
                                    <span class="font-bold text-gray-900"><?= $u['total_deliveries'] ?></span>
                                </div>
                                <div class="text-center">
                                    <span class="block text-xs text-gray-500 uppercase">Active</span>
                                    <span class="font-bold text-indigo-600"><?= $u['active_deliveries'] ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                             <a href="?toggle_status=1&id=<?= $u['id'] ?>" class="text-xs font-bold uppercase hover:underline <?= $u['status'] ? 'text-red-500' : 'text-green-500' ?>">
                                <?= $u['status'] ? 'Block' : 'Activate' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-motorcycle text-4xl text-gray-200 mb-4"></i>
                                <p class="text-lg font-medium text-gray-900">No Delivery Agents Found</p>
                                <p class="text-sm text-gray-500">Add your first delivery partner to get started.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('addModal').classList.add('hidden')"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-black text-white sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-user-plus text-sm"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">New Delivery Agent</h3>
                        <div class="mt-2 text-sm text-gray-500">
                            Create a new account for delivery personnel. They can login with these credentials.
                        </div>
                        
                        <form method="POST" class="mt-6 space-y-4">
                            <input type="hidden" name="add_delivery_boy" value="1">
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Full Name</label>
                                <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Email Address</label>
                                <input type="email" name="email" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Phone Number</label>
                                <input type="text" name="phone" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Password</label>
                                <input type="password" name="password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none transition-all">
                            </div>

                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-black text-base font-medium text-white hover:bg-gray-800 focus:outline-none sm:col-start-2 sm:text-sm">
                                    Create Account
                                </button>
                                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:col-start-1 sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
