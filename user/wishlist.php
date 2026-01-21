<?php
include 'includes/header.php';

// Handle Remove
if (isset($_POST['remove_id'])) {
    $wid = $_POST['remove_id'];
    $conn->query("DELETE FROM wishlist WHERE id = $wid AND user_id = $user_id");
    setFlash('success', 'Removed from wishlist');
    redirect('user/wishlist.php');
}

$items = $conn->query("SELECT w.id as wid, s.* FROM wishlist w JOIN services s ON w.service_id = s.id WHERE w.user_id = $user_id ORDER BY w.created_at DESC")->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 w-full">
            <div class="bg-white rounded-2xl border border-gray-100 p-8 shadow-sm min-h-[500px]">
                <div class="flex items-center justify-between mb-8 border-b border-gray-50 pb-4">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">My Wishlist</h1>
                    <span class="text-sm text-gray-500"><?= count($items) ?> items</span>
                </div>

                <?php if (count($items) > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($items as $item): ?>
                        <div class="group relative rounded-xl border border-gray-100 bg-white p-5 transition-all hover:shadow-lg hover:border-gray-200">
                            <!-- Image -->
                            <div class="aspect-w-16 aspect-h-9 mb-4 overflow-hidden rounded-lg bg-gray-100">
                                <img src="../uploads/services/<?= !empty($item['image']) ? $item['image'] : 'default_service.png' ?>" class="object-cover object-center w-full h-40">
                            </div>

                            <div class="flex justify-between items-start mb-3">
                                <h3 class="font-semibold text-gray-900 line-clamp-1 pr-4"><?= htmlspecialchars($item['name']) ?></h3>
                                <p class="font-bold text-gray-900">₹<?= $item['price'] ?></p>
                            </div>
                             <p class="text-xs text-gray-500 mb-4 line-clamp-2"><?= htmlspecialchars($item['description']) ?></p>
                            
                            <div class="flex items-center gap-2 mt-auto">
                                 <form action="cart.php" method="POST" class="flex-1">
                                    <input type="hidden" name="service_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="w-full rounded-lg bg-black px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-gray-800 transition">
                                        Add to Cart
                                    </button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="remove_id" value="<?= $item['wid'] ?>">
                                    <button type="submit" class="rounded-lg border border-gray-200 px-3 py-2 text-gray-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center h-64 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="far fa-heart text-2xl text-gray-300"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Your wishlist is empty</h3>
                        <p class="text-gray-500 mb-6 max-w-sm">Save items you want to order later.</p>
                        <a href="../index.php#browse-services" class="text-sm font-semibold text-black hover:underline">Browse Services &rarr;</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
