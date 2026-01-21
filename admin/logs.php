<?php
include 'includes/header.php';
include 'includes/sidebar.php';

requireRole(['admin', 'manager']);

// Pagination / Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$sql = "SELECT l.*, u.name, u.email, u.role FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT $limit OFFSET $offset";
$logs = $conn->query($sql)->fetchAll();

// Total for pagination
$total = $conn->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
$totalPages = ceil($total / $limit);
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">System Logs</h2>
        <p class="text-sm text-gray-500">Audit trail of all administrative actions.</p>
    </div>
    <div class="bg-gray-100 px-4 py-2 rounded-lg text-xs font-mono text-gray-600">
        Total Entries: <?= $total ?>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4 font-semibold">User</th>
                    <th class="px-6 py-4 font-semibold">Action</th>
                    <th class="px-6 py-4 font-semibold">Description</th>
                    <th class="px-6 py-4 font-semibold">IP Address</th>
                    <th class="px-6 py-4 font-semibold text-right">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if(count($logs) > 0): ?>
                    <?php foreach ($logs as $l): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <?php if($l['name']): ?>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                                        <?= substr($l['name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($l['name']) ?></p>
                                        <p class="text-[10px] text-gray-500 uppercase"><?= $l['role'] ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-sm text-gray-400 italic">System / Deleted User</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                <?= htmlspecialchars($l['action']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?= htmlspecialchars($l['description']) ?>
                        </td>
                        <td class="px-6 py-4 text-xs font-mono text-gray-500">
                            <?= htmlspecialchars($l['ip_address']) ?>
                        </td>
                        <td class="px-6 py-4 text-right text-xs text-gray-500">
                            <?= date('M d, H:i', strtotime($l['created_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                         <td colspan="5" class="px-6 py-12 text-center text-gray-500">No logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div class="px-6 py-4 border-t border-gray-100 flex justify-center gap-2">
        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-bold <?= $i == $page ? 'bg-black text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
