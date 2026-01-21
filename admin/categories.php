<?php
include 'includes/header.php';
include 'includes/sidebar.php';

// Helper function for image upload
function uploadCategoryTraffic($file, $prefix) {
    if (isset($file) && $file['error'] === 0) {
        $uploadDir = '../uploads/categories/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid($prefix . '_') . '.' . $ext;
        
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            return $filename;
        }
    }
    return null;
}

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $type = sanitize($_POST['type']);
    $image = uploadCategoryTraffic($_FILES['image'], 'cat') ?? 'default_category.png';
    
    $stmt = $conn->prepare("INSERT INTO categories (name, type, image) VALUES (:name, :type, :image)");
    $stmt->execute([':name' => $name, ':type' => $type, ':image' => $image]);
    setFlash('success', 'Category added successfully');
    redirect('/admin/categories.php');
}

// Handle Add Subcategory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subcategory'])) {
    $cat_id = $_POST['category_id'];
    $name = sanitize($_POST['name']);
    $image = uploadCategoryTraffic($_FILES['image'], 'sub') ?? 'default_subcategory.png';

    $stmt = $conn->prepare("INSERT INTO subcategories (category_id, name, image) VALUES (:cat_id, :name, :image)");
    $stmt->execute([':cat_id' => $cat_id, ':name' => $name, ':image' => $image]);
    setFlash('success', 'Subcategory added successfully');
    redirect('/admin/categories.php');
}

$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
$subcategories = $conn->query("SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id ORDER BY s.id DESC")->fetchAll();
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Categories & Subcategories</h2>
        <p class="text-sm text-gray-500">Organize your services with proper categorization.</p>
    </div>
    <div class="bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
            <i class="fas fa-bookmark"></i>
        </div>
        <div>
            <p class="text-[10px] uppercase font-bold text-gray-500">Total Categories</p>
            <p class="text-lg font-bold text-gray-900"><?= count($categories) ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- Categories Section -->
    <div class="space-y-6">
        <!-- Add Category Card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Add Category</h3>
            <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-3">
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text" name="name" placeholder="Category Name" required class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none transition-all">
                    
                    <div class="relative w-full sm:w-40">
                        <select name="type" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none appearance-none bg-white">
                            <option value="document">Document</option>
                            <option value="photo">Photo</option>
                            <option value="other">Other</option>
                        </select>
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-chevron-down text-xs"></i></div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Cover Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-black file:text-white hover:file:bg-gray-800">
                </div>

                <button type="submit" name="add_category" class="bg-black text-white px-6 py-2.5 rounded-xl font-bold hover:bg-gray-800 transition shadow-lg shadow-gray-200 w-full sm:w-auto self-end">
                    Add Category
                </button>
            </form>
        </div>

        <!-- Category List -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                 <h3 class="text-lg font-bold text-gray-900">All Categories</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Category</th>
                            <th class="px-6 py-4 font-semibold">Type</th>
                            <th class="px-6 py-4 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($categories as $cat): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden border border-gray-200 flex-shrink-0">
                                        <img src="../uploads/categories/<?= !empty($cat['image']) ? $cat['image'] : 'default_category.png' ?>" class="w-full h-full object-cover">
                                    </div>
                                    <span class="font-medium text-gray-900"><?= htmlspecialchars($cat['name']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800">
                                    <?= ucfirst($cat['type']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-gray-400 hover:text-red-500 transition-colors"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Subcategories Section -->
    <div class="space-y-6">
        <!-- Add Subcategory Card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Add Subcategory</h3>
            <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-3">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative w-full sm:w-48">
                        <select name="category_id" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none appearance-none bg-white">
                            <option value="">Parent Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-chevron-down text-xs"></i></div>
                    </div>

                    <input type="text" name="name" placeholder="Subcategory Name" required class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Cover Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-black file:text-white hover:file:bg-gray-800">
                </div>
                
                <button type="submit" name="add_subcategory" class="bg-black text-white px-6 py-2.5 rounded-xl font-bold hover:bg-gray-800 transition shadow-lg shadow-gray-200 w-full sm:w-auto self-end">
                    Add Subcategory
                </button>
            </form>
        </div>

        <!-- Subcategory List -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                 <h3 class="text-lg font-bold text-gray-900">All Subcategories</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Name</th>
                            <th class="px-6 py-4 font-semibold">Parent Category</th>
                             <th class="px-6 py-4 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($subcategories as $sub): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden border border-gray-200 flex-shrink-0">
                                        <img src="../uploads/categories/<?= !empty($sub['image']) ? $sub['image'] : 'default_subcategory.png' ?>" class="w-full h-full object-cover">
                                    </div>
                                    <span class="font-medium text-gray-900"><?= htmlspecialchars($sub['name']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($sub['category_name']) ?></td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-gray-400 hover:text-red-500 transition-colors"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
