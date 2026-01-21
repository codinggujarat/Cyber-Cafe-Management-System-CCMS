<?php
include 'includes/header.php';
include 'includes/sidebar.php';

requireRole(['admin', 'manager']);

// Handle Add Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    
    // Security: Only Admin can add Managers
    if ($_POST['role'] === 'manager' && !isAdmin()) {
        setFlash('error', 'Only Super Admin can create Managers');
        redirect('/admin/staff.php');
    }

    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    // Check email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        setFlash('error', 'Email already registered');
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (:name, :email, :phone, :pass, :role, 1)");
        $stmt->execute([':name' => $name, ':email' => $email, ':phone' => $phone, ':pass' => $password, ':role' => $role]);
        setFlash('success', ucfirst($role) . ' added successfully');
        redirect('/admin/staff.php');
    }
}

// Toggle Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $curr = $conn->query("SELECT status FROM users WHERE id = $id")->fetchColumn();
    $new = $curr ? 0 : 1;
    $conn->query("UPDATE users SET status = $new WHERE id = $id");
    redirect('/admin/staff.php');
}

// Fetch Staff
$users = $conn->query("SELECT * FROM users WHERE role IN ('manager', 'staff', 'accountant') ORDER BY created_at DESC")->fetchAll();
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Staff Management</h2>
        <p class="text-sm text-gray-500">Manage enterprise roles and permissions.</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-xl text-sm font-bold hover:bg-gray-800 transition shadow-lg shadow-gray-200">
        <i class="fas fa-plus"></i> Add New Member
    </button>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4 font-semibold">User Details</th>
                    <th class="px-6 py-4 font-semibold">Role</th>
                    <th class="px-6 py-4 font-semibold">Contact</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if(count($users) > 0): ?>
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-base">
                                    <?= substr($u['name'], 0, 1) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($u['name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($u['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                                $badges = [
                                    'manager' => 'bg-purple-100 text-purple-700',
                                    'staff'   => 'bg-blue-100 text-blue-700',
                                    'accountant' => 'bg-green-100 text-green-700'
                                ];
                                $cls = $badges[$u['role']] ?? 'bg-gray-100';
                            ?>
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium <?= $cls ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= $u['phone'] ?: '-' ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($u['status']): ?>
                                <span class="inline-flex items-center rounded-full bg-green-50 text-green-700 px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ring-green-600/20">Active</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-red-50 text-red-700 px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ring-red-600/20">Blocked</span>
                            <?php endif; ?>
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
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">No staff members found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('addModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Add Staff Member</h3>
                <form method="POST" class="mt-6 space-y-4">
                    <input type="hidden" name="add_staff" value="1">
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Role</label>
                        <select name="role" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-black focus:border-black">
                            <?php if(isAdmin()): ?><option value="manager">Manager (Full Access)</option><?php endif; ?>
                            <option value="staff">Staff (Print & Orders)</option>
                            <option value="accountant">Accountant (Reports & Payouts)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Full Name</label>
                        <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-black focus:border-black">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Email Address</label>
                        <input type="email" name="email" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-black focus:border-black">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Phone Number</label>
                        <input type="text" name="phone" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-black focus:border-black">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-black focus:border-black">
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3">
                        <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="w-full py-2 bg-white border border-gray-300 rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="w-full py-2 bg-black text-white rounded-xl font-bold">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
