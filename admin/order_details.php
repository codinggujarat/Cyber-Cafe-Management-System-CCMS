<?php
include 'includes/header.php';
include 'includes/sidebar.php';

if (!isset($_GET['id'])) {
    redirect('/admin/orders.php');
}

$order_id = $_GET['id'];

// Handle Item Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_item_status'])) {
        $item_id = $_POST['item_id'];
        $status = $_POST['status'];
        $reason = $_POST['reason'] ?? null;
        
        $sql = "UPDATE order_items SET approval_status = :status, rejection_reason = :reason WHERE id = :id";
        $conn->prepare($sql)->execute([':status' => $status, ':reason' => $reason, ':id' => $item_id]);
        
        // Notify User
        $info = $conn->query("SELECT o.user_id, o.id as order_id, i.service_name FROM order_items i JOIN orders o ON i.order_id = o.id WHERE i.id = $item_id")->fetch();
        if ($info) {
            $msgType = ($status == 'approved') ? 'success' : 'error';
            createNotification($info['user_id'], 'Item Status Update', "Item '{$info['service_name']}' in Order #{$info['order_id']} was " . ucfirst($status), $msgType);
        }

        setFlash('success', 'Item status updated');
        redirect("/admin/order_details.php?id=$order_id"); // Stay on page
    }

    if (isset($_POST['update_workflow'])) {
        $item_id = $_POST['item_id'];
        $svc_status = $_POST['service_status'];
        $app_no = $_POST['govt_application_no'];
        
        // Prepare Directory
        $target_dir = "../uploads/receipts/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $sql = "UPDATE order_items SET service_status = ?, govt_application_no = ? WHERE id = ?";
        $params = [$svc_status, $app_no, $item_id];
        
        // Handle File
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
            $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $filename = "receipt_{$item_id}_" . time() . ".$ext";
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_dir . $filename)) {
                $sql = "UPDATE order_items SET service_status = ?, govt_application_no = ?, acknowledgement_file = ? WHERE id = ?";
                $params = [$svc_status, $app_no, $filename, $item_id];
            }
        }
        
        $conn->prepare($sql)->execute($params);
        
        // Notify User
        $info = $conn->query("SELECT o.user_id, o.id as order_id, i.service_name FROM order_items i JOIN orders o ON i.order_id = o.id WHERE i.id = $item_id")->fetch();
        if ($info) {
             createNotification($info['user_id'], 'Application Update', "Status Updated for '{$info['service_name']}': " . ucwords(str_replace('_', ' ', $svc_status)), 'info');
             logActivity('Workflow Update', "Updated workflow for item #$item_id to $svc_status");
        }
        
        setFlash('success', 'Service workflow updated.');
        redirect("/admin/order_details.php?id=$order_id");
    }
}

// Fetch Order Info
$order = $conn->query("
    SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone, d.name as dboy_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN users d ON o.delivery_boy_id = d.id 
    WHERE o.id = $order_id
")->fetch();

if (!$order) {
    redirect('/admin/orders.php');
}

// Fetch Items
$items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id")->fetchAll();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-8">
    <div class="flex items-center gap-4">
        <a href="orders.php" class="p-2 rounded-lg bg-white border border-gray-200 text-gray-500 hover:text-black transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Order #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h2>
            <p class="text-sm text-gray-500">
                Created on <?= date('M d, Y', strtotime($order['created_at'])) ?> • 
                <span class="font-medium text-black"><?= ucwords(str_replace('_', ' ', $order['order_status'])) ?></span>
            </p>
        </div>
    </div>
    <div class="flex gap-3">
        <a href="invoice.php?id=<?= $order['id'] ?>" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
            <i class="fas fa-print"></i> Invoice
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Left: Order Items & Files -->
    <div class="lg:col-span-2 space-y-6">
        
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-bold text-gray-900 mb-6">Order Items & Files</h3>
            <div class="space-y-8">
                <?php foreach ($items as $item): ?>
                <div class="border-b border-gray-50 pb-8 last:border-0 last:pb-0">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="font-bold text-lg text-gray-900"><?= htmlspecialchars($item['service_name']) ?></h4>
                             <p class="text-sm text-gray-500">
                                Total: <span class="text-black font-semibold">₹<?= $item['price'] * $item['quantity'] ?></span> 
                                (<?= $item['quantity'] ?> units @ ₹<?= $item['price'] ?>)
                             </p>
                             
                             <!-- Advanced Details Tag -->
                             <div class="flex flex-wrap gap-2 mt-2">
                                <?php if($item['print_color']): ?>
                                    <span class="px-2 py-1 rounded bg-gray-100 text-xs text-gray-600 font-medium">
                                        Color: <?= ucwords($item['print_color']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if($item['paper_size']): ?>
                                    <span class="px-2 py-1 rounded bg-gray-100 text-xs text-gray-600 font-medium">
                                        Size: <?= $item['paper_size'] ?>
                                    </span>
                                <?php endif; ?>
                                <?php if($item['binding'] && $item['binding'] != 'none'): ?>
                                    <span class="px-2 py-1 rounded bg-indigo-50 text-xs text-indigo-600 font-medium border border-indigo-100">
                                        Binding: <?= ucwords($item['binding']) ?>
                                    </span>
                                <?php endif; ?>
                                 <?php if($item['page_count'] > 0): ?>
                                    <span class="px-2 py-1 rounded bg-blue-50 text-xs text-blue-600 font-medium border border-blue-100">
                                        <?= $item['page_count'] ?> Pages Detected
                                    </span>
                                <?php endif; ?>
                             </div>
                             
                             <!-- Custom Form Data (Masked) -->
                             <?php if(!empty($item['custom_data'])): ?>
                                <div class="mt-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Form Data</h5>
                                    <div class="space-y-2">
                                    <?php foreach(json_decode($item['custom_data'], true) as $k => $v): 
                                         $masked = maskSensitive($k, $v);
                                         $isMasked = ($masked !== $v);
                                    ?>
                                        <div class="text-sm">
                                            <span class="font-semibold text-gray-700 mr-2"><?= htmlspecialchars($k) ?>:</span>
                                            <span class="font-mono text-gray-900 bg-white px-1.5 py-0.5 rounded border border-gray-200">
                                                <span><?= htmlspecialchars($masked) ?></span>
                                                <?php if($isMasked && hasRole(['admin', 'manager'])): ?>
                                                    <span class="hidden text-red-600 ml-1"><?= htmlspecialchars($v) ?></span>
                                                    <button onclick="this.previousElementSibling.classList.toggle('hidden'); this.previousElementSibling.previousElementSibling.classList.toggle('hidden');" class="ml-2 text-xs text-blue-500 hover:text-blue-700 hover:underline">
                                                        <i class="far fa-eye"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                </div>
                             <?php endif; ?>
                        
                        <!-- Approval Status Badge -->
                         <div class="text-right">
                            <?php if ($item['approval_status'] == 'pending'): ?>
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full font-bold">Pending Review</span>
                            <?php elseif ($item['approval_status'] == 'approved'): ?>
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-bold">Approved</span>
                            <?php elseif ($item['approval_status'] == 'rejected'): ?>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-bold">Rejected</span>
                            <?php endif; ?>
                         </div>
                    </div>

                    <?php if ($item['file_path']): ?>
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h5 class="font-bold text-sm text-gray-700 flex items-center gap-2">
                                <i class="fas fa-paperclip"></i> Attached File
                            </h5>
                            <a href="../uploads/<?= $item['file_path'] ?>" download class="text-xs font-bold text-indigo-600 hover:underline">Download Original</a>
                        </div>
                        
                        <!-- Preview Window -->
                        <div class="bg-white rounded border border-gray-200 overflow-hidden h-96 relative group">
                            <?php 
                                $ext = strtolower(pathinfo($item['file_path'], PATHINFO_EXTENSION));
                                if ($ext === 'pdf'):
                            ?>
                                <iframe src="../uploads/<?= $item['file_path'] ?>" class="w-full h-full"></iframe>
                            <?php elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])): ?>
                                <img src="../uploads/<?= $item['file_path'] ?>" class="w-full h-full object-contain">
                            <?php else: ?>
                                <div class="flex items-center justify-center h-full text-gray-400">
                                    Preview not available for .<?= $ext ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons -->
                        <?php if ($item['approval_status'] == 'pending'): ?>
                        <div class="flex gap-3 mt-4">
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="update_item_status" value="1">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg font-bold text-sm hover:bg-green-700 transition">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <button onclick="openRejectModal(<?= $item['id'] ?>)" class="flex-1 bg-white border border-red-200 text-red-600 py-2 rounded-lg font-bold text-sm hover:bg-red-50 transition">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                        <?php elseif ($item['approval_status'] == 'rejected'): ?>
                            <div class="mt-4 p-3 bg-red-50 border border-red-100 rounded-lg text-sm text-red-700">
                                <span class="font-bold">Reason:</span> <?= htmlspecialchars($item['rejection_reason']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Workflow Section -->
                        <div class="mt-6 border-t border-gray-100 pt-4">
                            <h5 class="font-bold text-xs text-indigo-900 uppercase tracking-wide mb-3 flex items-center gap-2">
                                <i class="fas fa-tasks"></i> Service Workflow
                            </h5>
                            <form method="POST" enctype="multipart/form-data" class="bg-indigo-50/30 p-4 rounded-xl border border-indigo-100 grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                <input type="hidden" name="update_workflow" value="1">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
                                    <select name="service_status" class="w-full text-xs rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                        <?php 
                                        $statuses = ['submitted', 'in_process', 'govt_submitted', 'approved', 'delivered', 'rejected'];
                                        foreach($statuses as $s) {
                                            $sel = ($item['service_status'] ?? 'submitted') == $s ? 'selected' : '';
                                            echo "<option value='$s' $sel>" . ucwords(str_replace('_', ' ', $s)) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Govt App No / Token</label>
                                    <input type="text" name="govt_application_no" value="<?= htmlspecialchars($item['govt_application_no'] ?? '') ?>" class="w-full text-xs rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. MH-12345">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Upload Receipt</label>
                                    <input type="file" name="receipt" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-white file:text-indigo-600 hover:file:bg-indigo-50 font-medium">
                                    <?php if(!empty($item['acknowledgement_file'])): ?>
                                        <div class="mt-1 flex items-center gap-2">
                                            <i class="fas fa-file-check text-green-500 text-xs"></i>
                                            <a href="../uploads/receipts/<?= $item['acknowledgement_file'] ?>" target="_blank" class="text-[10px] text-indigo-600 font-bold hover:underline">View Uploaded Receipt</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="md:col-span-2">
                                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg text-xs font-bold hover:bg-indigo-700 shadow-sm shadow-indigo-200 transition">
                                        Update
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="text-sm text-gray-400 italic">No file uploaded for this item.</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: Customer & Info -->
    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-bold text-gray-900 mb-4">Customer Details</h3>
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold">
                    <?= substr($order['user_name'], 0, 1) ?>
                </div>
                <div>
                    <p class="font-bold text-gray-900"><?= htmlspecialchars($order['user_name']) ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($order['user_email']) ?></p>
                </div>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex gap-3">
                    <i class="fas fa-phone text-gray-400 mt-1"></i>
                    <p><?= htmlspecialchars($order['user_phone']) ?></p>
                </div>
                <div class="flex gap-3">
                    <i class="fas fa-map-marker-alt text-gray-400 mt-1"></i>
                    <p><?= htmlspecialchars($order['shipping_address']) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-bold text-gray-900 mb-4">Delivery</h3>
            <?php if ($order['delivery_boy_id']): ?>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900"><?= htmlspecialchars($order['dboy_name']) ?></p>
                        <p class="text-xs text-green-600 font-bold">Assigned</p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-500 mb-4">No delivery partner assigned.</p>
                <a href="orders.php" class="block text-center w-full bg-black text-white py-2 rounded-xl text-sm font-bold">Assign Now</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('rejectModal').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Item</h3>
                <form method="POST">
                    <input type="hidden" name="update_item_status" value="1">
                    <input type="hidden" name="item_id" id="rejectItemId">
                    <input type="hidden" name="status" value="rejected">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection</label>
                        <textarea name="reason" rows="3" required class="w-full rounded-xl border-gray-200 focus:border-red-500 focus:ring-red-500" placeholder="e.g. Low resolution, formatting issues..."></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-bold">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold">Confirm Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openRejectModal(itemId) {
        document.getElementById('rejectItemId').value = itemId;
        document.getElementById('rejectModal').classList.remove('hidden');
    }
</script>

<?php include 'includes/footer.php'; ?>
