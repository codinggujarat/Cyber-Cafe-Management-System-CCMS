<?php
include 'includes/header.php';

// Filter Parameters
$search = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build Query
$sql = "SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (s.name LIKE :search OR s.description LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($category_id) {
    $sql .= " AND s.category_id = :cat";
    $params[':cat'] = $category_id;
}
if ($min_price) {
    $sql .= " AND s.price >= :min";
    $params[':min'] = $min_price;
}
if ($max_price) {
    $sql .= " AND s.price <= :max";
    $params[':max'] = $max_price;
}

// Sort
switch ($sort) {
    case 'price_low': $sql .= " ORDER BY s.price ASC"; break;
    case 'price_high': $sql .= " ORDER BY s.price DESC"; break;
    case 'name_asc': $sql .= " ORDER BY s.name ASC"; break;
    default: $sql .= " ORDER BY s.id DESC"; break;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

// Fetch Categories for Sidebar
$categories = $conn->query("SELECT * FROM categories")->fetchAll();

// Fetch User Wishlist
$wishlistIds = [];
if (isset($_SESSION['user_id'])) {
    $wishlistIds = $conn->query("SELECT service_id FROM wishlist WHERE user_id = {$_SESSION['user_id']}")->fetchAll(PDO::FETCH_COLUMN);
}
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Browse Services</h1>
        <p class="text-gray-500 mt-2">Find the perfect service for your needs.</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <div class="w-full lg:w-1/4">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24">
                <form method="GET" class="space-y-6">
                    <!-- Search -->
                    <div>
                        <label class="block text-xs font-bold text-gray-900 uppercase mb-2">Search</label>
                        <div class="relative">
                             <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Keywords..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none text-sm transition-all">
                             <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400 text-xs"></i>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div>
                        <label class="block text-xs font-bold text-gray-900 uppercase mb-3">Category</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="category" value="" class="w-4 h-4 text-black border-gray-300 focus:ring-black" <?= empty($category_id) ? 'checked' : '' ?>>
                                <span class="text-sm text-gray-600 group-hover:text-black transition">All Categories</span>
                            </label>
                            <?php foreach($categories as $cat): ?>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="category" value="<?= $cat['id'] ?>" class="w-4 h-4 text-black border-gray-300 focus:ring-black" <?= ($category_id == $cat['id']) ? 'checked' : '' ?>>
                                <span class="text-sm text-gray-600 group-hover:text-black transition"><?= htmlspecialchars($cat['name']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div>
                         <label class="block text-xs font-bold text-gray-900 uppercase mb-2">Price Range</label>
                         <div class="flex items-center gap-2">
                             <input type="number" name="min_price" value="<?= htmlspecialchars($min_price) ?>" placeholder="Min" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:border-black outline-none">
                             <span class="text-gray-400">-</span>
                             <input type="number" name="max_price" value="<?= htmlspecialchars($max_price) ?>" placeholder="Max" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:border-black outline-none">
                         </div>
                    </div>

                    <!-- Sort -->
                    <div>
                        <label class="block text-xs font-bold text-gray-900 uppercase mb-2">Sort By</label>
                        <div class="relative">
                            <select name="sort" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none appearance-none bg-white text-sm">
                                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                                <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                                <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                            </select>
                            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-chevron-down text-xs"></i></div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-2 flex flex-col gap-2">
                        <button type="submit" class="w-full bg-black text-white font-bold py-3 rounded-xl hover:bg-gray-800 transition shadow-lg shadow-gray-200 text-sm">
                            Apply Filters
                        </button>
                        <a href="services.php" class="w-full bg-gray-50 text-gray-600 font-bold py-3 rounded-xl hover:bg-gray-100 transition text-sm text-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Grid -->
        <div class="w-full lg:w-3/4">
            <?php if (count($services) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($services as $service): ?>
                        <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white transition hover:shadow-xl hover:border-gray-200">
                             
                             <!-- Image & Badge -->
                             <div class="aspect-[16/9] bg-gray-100 overflow-hidden relative">
                                <a href="service_details.php?id=<?= $service['id'] ?>">
                                    <img src="../uploads/services/<?= !empty($service['image']) ? $service['image'] : 'default_service.png' ?>" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                </a>
                                <div class="absolute top-4 left-4">
                                    <span class="inline-flex items-center rounded-lg bg-white/90 backdrop-blur px-2.5 py-1 text-xs font-bold text-gray-900 shadow-sm border border-gray-100">
                                        <?= ucwords(str_replace('_', ' ', $service['price_type'])) ?>
                                    </span>
                                </div>
                             </div>
                             
                             <div class="p-5 flex-1 flex flex-col">
                                <div class="mb-1">
                                    <span class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"><?= htmlspecialchars($service['category_name']) ?></span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 hover:text-blue-600 transition mb-2">
                                    <a href="service_details.php?id=<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></a>
                                </h3>
                                <p class="text-sm text-gray-500 line-clamp-2 mb-4 flex-1"><?= ($service['description'] ?? 'High quality service.') ?></p>
                                
                                <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
                                    <span class="text-lg font-bold text-gray-900">₹<?= formatPrice($service['price']) ?></span>
                                    
                                    <div class="flex gap-2">
                                        <form action="wishlist_action.php" method="POST">
                                            <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                            <?php $inWishlist = in_array($service['id'], $wishlistIds); ?>
                                            <button type="submit" class="p-2.5 rounded-xl bg-gray-50 hover:bg-gray-100 transition border border-transparent <?= $inWishlist ? 'text-black' : 'text-gray-400 hover:text-black' ?>" title="<?= $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?>">
                                                <i class="<?= $inWishlist ? 'fas' : 'far' ?> fa-heart"></i>
                                            </button>
                                        </form>

                                        <!-- Add to Cart (Dynamic Form Trigger) -->
                                        <div>
                                            <button type="button" 
                                                    onclick='initAddToCart(<?= $service['id'] ?>, "<?= htmlspecialchars($service['name']) ?>", <?= json_encode($service['form_fields'] ?? null) ?>)' 
                                                    class="p-2.5 rounded-xl bg-black text-white hover:bg-gray-800 transition shadow-md shadow-gray-200">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                             </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State (Same as before) -->
                <div class="text-center py-20 bg-white rounded-2xl border border-dashed border-gray-200">
                     <p class="text-gray-500">No services found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Dynamic Service Form Modal -->
<div id="serviceFormModal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeServiceModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900" id="modalServiceTitle">Service Details</h3>
                    <button onclick="closeServiceModal()" class="text-gray-400 hover:text-black"><i class="fas fa-times"></i></button>
                </div>
                
                <form action="cart.php" method="POST" id="dynamicForm">
                    <input type="hidden" name="service_id" id="modalServiceId">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="quantity" value="1">
                    <input type="hidden" name="custom_data" id="modalCustomData">

                    <div id="dynamicFieldsContainer" class="space-y-4 mb-6">
                        <!-- Fields injected here -->
                    </div>

                    <button type="submit" class="w-full bg-black text-white font-bold py-3 rounded-xl hover:bg-gray-800 transition shadow-lg shadow-gray-200">
                        Add to Cart
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function initAddToCart(id, name, formFieldsJson) {
        // If no fields, submit directly
        if (!formFieldsJson || formFieldsJson === 'null' || formFieldsJson.length === 0) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'cart.php';
            form.innerHTML = `<input type="hidden" name="service_id" value="${id}">
                              <input type="hidden" name="action" value="add">
                              <input type="hidden" name="quantity" value="1">`;
            document.body.appendChild(form);
            form.submit();
            return;
        }

        // Parse fields
        let fields = [];
        try {
            fields = typeof formFieldsJson === 'string' ? JSON.parse(formFieldsJson) : formFieldsJson;
        } catch(e) {
            fields = [];
        }

        if (fields.length === 0) {
             // Fallback if parsing failed but variable wasn't null
             const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'cart.php';
            form.innerHTML = `<input type="hidden" name="service_id" value="${id}">
                              <input type="hidden" name="action" value="add">
                              <input type="hidden" name="quantity" value="1">`;
            document.body.appendChild(form);
            form.submit();
            return;
        }

        // Build Form
        const container = document.getElementById('dynamicFieldsContainer');
        container.innerHTML = '';
        
        fields.forEach(field => {
            const wrapper = document.createElement('div');
            
            const label = document.createElement('label');
            label.className = 'block text-xs font-bold text-gray-700 uppercase mb-1';
            label.innerText = field.label;
            if(field.required) label.innerText += ' *';
            
            let input;
            if (field.type === 'textarea') {
                input = document.createElement('textarea');
                input.className = 'w-full px-4 py-2 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none';
                input.rows = 3;
            } else {
                input = document.createElement('input');
                input.type = field.type;
                input.className = 'w-full px-4 py-2 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none';
            }
            
            input.dataset.label = field.label; // Store label for JSON mapping
            if (field.required) input.required = true;
            
            wrapper.appendChild(label);
            wrapper.appendChild(input);
            container.appendChild(wrapper);
        });

        document.getElementById('modalServiceId').value = id;
        document.getElementById('modalServiceTitle').innerText = 'Details for ' + name;
        document.getElementById('serviceFormModal').classList.remove('hidden');
    }

    function closeServiceModal() {
        document.getElementById('serviceFormModal').classList.add('hidden');
    }

    // Intercept Modal Submit to Bundle Data
    document.getElementById('dynamicForm').addEventListener('submit', function(e) {
        const container = document.getElementById('dynamicFieldsContainer');
        const inputs = container.querySelectorAll('input, textarea, select');
        const data = {};
        
        inputs.forEach(input => {
            if (input.value) {
                data[input.dataset.label] = input.value;
            }
        });
        
        document.getElementById('modalCustomData').value = JSON.stringify(data);
    });
</script>

<?php include 'includes/footer.php'; ?>
