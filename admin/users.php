<?php
include 'includes/header.php';
include 'includes/sidebar.php';

// Toggle User Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    // Check current status
    $curr = $conn->query("SELECT status FROM users WHERE id = $id")->fetchColumn();
    $new = $curr ? 0 : 1;
    $conn->query("UPDATE users SET status = $new WHERE id = $id");
    redirect('/admin/users.php');
}

$users = $conn->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
        <p class="text-sm text-gray-500">View and manage registered users.</p>
    </div>
    <div class="flex items-center gap-4">
        <div class="hidden md:flex bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-gray-500">Total Users</p>
                <p class="text-lg font-bold text-gray-900"><?= count($users) ?></p>
            </div>
        </div>
        <div class="relative">
            <input type="text" placeholder="Search users..." class="pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all w-64 focus:w-72">
            <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4 font-semibold">User Details</th>
                    <th class="px-6 py-4 font-semibold">Role</th>
                    <th class="px-6 py-4 font-semibold">Joined Date</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($users as $u): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center font-bold text-sm">
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
                            $roleColors = [
                                'admin' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                'user' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                'delivery' => 'bg-orange-50 text-orange-700 ring-orange-600/20'
                            ];
                            $roleClass = $roleColors[$u['role']] ?? 'bg-gray-50 text-gray-700';
                        ?>
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset <?= $roleClass ?>">
                            <?= strtoupper($u['role']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?= date('M d, Y', strtotime($u['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($u['status']): ?>
                            <span class="inline-flex items-center rounded-full bg-green-50 text-green-700 px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ring-green-600/20">Active</span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-red-50 text-red-700 px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ring-red-600/20">Blocked</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <?php if($u['role'] != 'admin'): ?>
                            <a href="?toggle_status=1&id=<?= $u['id'] ?>" class="text-sm font-medium hover:underline <?= $u['status'] ? 'text-red-600' : 'text-green-600' ?>">
                                <?= $u['status'] ? 'Block User' : 'Activate User' ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
