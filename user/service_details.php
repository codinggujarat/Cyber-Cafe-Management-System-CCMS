<?php
include 'includes/header.php';

if (!isset($_GET['id'])) {
    redirect('user/services.php');
}

$id = $_GET['id'];
$service = $conn->query("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE s.id = $id")->fetch();

if (!$service) {
    redirect('user/services.php');
}

// Fetch related services
$cat_id = $service['category_id'];
$related = $conn->query("SELECT * FROM services WHERE category_id = $cat_id AND id != $id LIMIT 3")->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    
    <!-- Breadcrumb -->
    <nav class="flex mb-8 text-sm text-gray-500">
        <a href="services.php" class="hover:text-black transition">Services</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium truncate"><?= htmlspecialchars($service['name']) ?></span>
    </nav>

    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Image Section -->
        <div class="w-full lg:w-1/2">
            <div class="aspect-w-4 aspect-h-3 overflow-hidden rounded-2xl bg-gray-100 border border-gray-100 shadow-sm relative group">
                <img src="../uploads/services/<?= !empty($service['image']) ? $service['image'] : 'default_service.png' ?>" alt="<?= htmlspecialchars($service['name']) ?>" class="object-cover object-center w-full h-full group-hover:scale-105 transition-transform duration-500">
            </div>
        </div>

        <!-- Details Section -->
        <div class="w-full lg:w-1/2">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 mb-2"><?= htmlspecialchars($service['name']) ?></h1>
            <div class="flex items-center gap-4 mb-6">
                <span class="inline-flex items-center rounded-full bg-black/5 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                    <?= htmlspecialchars($service['category_name']) ?>
                </span>
                <?php if($service['file_required']): ?>
                     <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                        <i class="fas fa-file-upload text-[10px]"></i> File Upload Required
                    </span>
                <?php endif; ?>
            </div>

            <div class="mb-8">
                <p class="text-4xl font-bold text-gray-900">
                    ₹<?= $service['price'] ?>
                    <span class="text-lg font-normal text-gray-500">/ <?= str_replace('_', ' ', $service['price_type']) ?></span>
                </p>
            </div>

            <div class="prose prose-sm text-gray-500 mb-10">
                <p><?= nl2br(htmlspecialchars($service['description'])) ?></p>
            </div>

            <!-- Actions -->
            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                <form method="POST" action="cart.php">
                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="flex items-center gap-6 mb-6">
                        <div class="w-32">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <input type="number" name="quantity" value="1" min="1" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm pl-4 py-2">
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="flex-1 bg-black border border-transparent rounded-xl py-3 px-8 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black shadow-lg shadow-gray-200 transition">
                            Add to Cart
                        </button>
                    </div>
                </form>
                 <form method="POST" action="wishlist_action.php" class="mt-4">
                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white border border-gray-200 rounded-xl py-3 px-8 text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-red-500 hover:border-red-200 transition">
                        <i class="far fa-heart"></i> Add to Wishlist
                    </button>
                </form>
            </div>
            
            <div class="mt-8 flex items-center gap-3 text-sm text-gray-400">
                <i class="fas fa-shield-alt"></i> Secure Payment
                <span class="mx-2">•</span>
                <i class="fas fa-check-circle"></i> Quality Guarantee
                <span class="mx-2">•</span>
                <i class="fas fa-clock"></i> Fast Delivery
            </div>

        </div>
    </div>

    <!-- Related Services -->
    <?php if (count($related) > 0): ?>
    <div class="mt-24 border-t border-gray-100 pt-16">
        <h2 class="text-2xl font-bold text-gray-900 mb-8">Related Services</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($related as $rel): ?>
            <a href="service_details.php?id=<?= $rel['id'] ?>" class="group block">
                <div class="aspect-w-16 aspect-h-10 overflow-hidden rounded-xl bg-gray-100 mb-4 border border-gray-100">
                    <img src="../uploads/services/<?= !empty($rel['image']) ? $rel['image'] : 'default_service.png' ?>" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300">
                </div>
                <h3 class="font-semibold text-gray-900 group-hover:underline"><?= htmlspecialchars($rel['name']) ?></h3>
                <p class="text-gray-500 text-sm mt-1">₹<?= $rel['price'] ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
