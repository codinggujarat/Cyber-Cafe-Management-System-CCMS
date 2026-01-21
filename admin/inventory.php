<?php
include 'includes/header.php';
include 'includes/sidebar.php';

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_name = sanitize($_POST['item_name']);
    $quantity = (int)$_POST['quantity'];
    $unit = sanitize($_POST['unit']);
    $min_threshold = (int)$_POST['min_threshold'];

    $conn->prepare("INSERT INTO inventory (item_name, quantity, unit, min_threshold) VALUES (?, ?, ?, ?)")
         ->execute([$item_name, $quantity, $unit, $min_threshold]);
    
    setFlash('success', 'Item added to inventory');
    redirect('/admin/inventory.php');
}

// Handle Update Stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $id = $_POST['item_id'];
    $new_qty = (int)$_POST['quantity'];
    $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?")->execute([$new_qty, $id]);
    setFlash('success', 'Stock updated successfully');
    redirect('/admin/inventory.php');
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $conn->prepare("DELETE FROM inventory WHERE id = ?")->execute([$_GET['delete_id']]);
    setFlash('success', 'Item removed');
    redirect('/admin/inventory.php');
}

$inventory = $conn->query("SELECT * FROM inventory ORDER BY quantity ASC")->fetchAll();
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Stock Management</h2>
        <p class="text-sm text-gray-500">Track your inventory and supplies.</p>
    </div>
    <div class="flex gap-3">
        <div class="hidden md:flex bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                <i class="fas fa-boxes"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-gray-500">Total Items</p>
                <p class="text-lg font-bold text-gray-900"><?= count($inventory) ?></p>
            </div>
        </div>
        <button onclick="document.getElementById('addItemModal').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-xl text-sm font-bold hover:bg-gray-800 transition shadow-lg shadow-gray-200">
            <i class="fas fa-plus"></i> Add New Item
        </button>
    </div>
</div>

<!-- Inventory Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($inventory as $item): ?>
        <?php 
            $isLow = $item['quantity'] <= $item['min_threshold'];
            $bgClass = $isLow ? 'bg-red-50 border-red-100' : 'bg-white border-gray-100';
            $textClass = $isLow ? 'text-red-600' : 'text-gray-900';
        ?>
        <div class="relative group rounded-2xl border p-6 transition-all hover:shadow-lg <?= $bgClass ?>">
            
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-600 shadow-sm border border-gray-100">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="relative">
                     <button class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('menu-<?= $item['id'] ?>').classList.toggle('hidden')">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <!-- Dropdown -->
                    <div id="menu-<?= $item['id'] ?>" class="hidden absolute right-0 mt-2 w-32 bg-white rounded-lg shadow-xl border border-gray-100 z-10">
                        <a href="?delete_id=<?= $item['id'] ?>" onclick="return confirm('Delete this item?')" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Delete</a>
                    </div>
                </div>
            </div>

            <h3 class="text-lg font-bold text-gray-900 mb-1"><?= htmlspecialchars($item['item_name']) ?></h3>
            <p class="text-xs text-gray-500 mb-4">Last updated: <?= date('M d', strtotime($item['last_updated'])) ?></p>

            <div class="flex items-end justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">In Stock</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold <?= $textClass ?>"><?= $item['quantity'] ?></span>
                        <span class="text-sm text-gray-500"><?= $item['unit'] ?></span>
                    </div>
                </div>
                
                 <?php if($isLow): ?>
                    <span class="px-2 py-1 bg-red-100 text-red-700 text-[10px] font-bold uppercase rounded-full animate-pulse">
                        Low Stock
                    </span>
                 <?php endif; ?>
            </div>

            <!-- Update Form Inline -->
            <form method="POST" class="mt-4 pt-4 border-t border-gray-200/50 flex gap-2">
                <input type="hidden" name="update_stock" value="1">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" class="w-20 px-2 py-1 text-sm border rounded bg-white" placeholder="Qty">
                <button type="submit" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1 rounded">Update</button>
            </form>

        </div>
    <?php endforeach; ?>
    
    <!-- Empty State helper -->
    <?php if(count($inventory) == 0): ?>
        <div class="col-span-full py-12 text-center text-gray-500 bg-white rounded-2xl border border-dashed border-gray-200">
             <i class="fas fa-boxes text-4xl mb-3 text-gray-300"></i>
            <p>No items in inventory. Add your first item!</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('addItemModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Add Inventory Item</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="add_item" value="1">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Item Name</label>
                        <input type="text" name="item_name" required class="w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-black outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Quantity</label>
                            <input type="number" name="quantity" required class="w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-black outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Unit</label>
                             <select name="unit" class="w-full px-3 py-2 border rounded-lg bg-white">
                                <option value="pcs">Pieces</option>
                                <option value="box">Box</option>
                                <option value="kg">Kg</option>
                                <option value="reams">Reams</option>
                                <option value="liters">Liters</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Low Stock Threshold</label>
                         <input type="number" name="min_threshold" value="10" class="w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-black outline-none">
                         <p class="text-[10px] text-gray-400 mt-1">Order alert when stock falls below this number.</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-black hover:bg-gray-800 rounded-lg">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
