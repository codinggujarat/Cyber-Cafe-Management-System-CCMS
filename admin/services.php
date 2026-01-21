<?php
include 'includes/header.php';
include 'includes/sidebar.php';

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $id = $_POST['service_id'];
    $conn->query("DELETE FROM services WHERE id = $id");
    setFlash('success', 'Service deleted successfully');
    redirect('/admin/services.php');
}

// Handle Add / Update Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_service']) || isset($_POST['update_service']))) {
    $category_id = $_POST['category_id'];
    $subcategory_id = !empty($_POST['subcategory_id']) ? $_POST['subcategory_id'] : null;
    $name = sanitize($_POST['name']);
    $price = $_POST['price'];
    $price_unit = $_POST['price_type'];
    $file_required = isset($_POST['file_required']) ? 1 : 0;
    $form_fields = $_POST['form_fields'] ?? null; // JSON String

    // Tax Info
    $hsn_code = $_POST['hsn_code'] ?? '9983';
    $gst_rate = $_POST['gst_rate'] ?? 18.00;

    // Handle Image Upload
    $image = $_POST['current_image'] ?? 'default_service.png';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../uploads/services/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('svc_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
            $image = $filename;
        }
    }

    if (isset($_POST['update_service'])) {
        $id = $_POST['service_id'];
        $stmt = $conn->prepare("UPDATE services SET category_id=:cat, subcategory_id=:sub, name=:name, image=:img, price=:price, price_type=:unit, file_required=:req, hsn_code=:hsn, gst_rate=:gst, form_fields=:fields WHERE id=:id");
        $stmt->execute([
            ':cat' => $category_id, ':sub' => $subcategory_id, ':name' => $name, ':img' => $image,
            ':price' => $price, ':unit' => $price_unit, ':req' => $file_required, 
            ':hsn' => $hsn_code, ':gst' => $gst_rate, ':fields' => $form_fields,
            ':id' => $id
        ]);
        setFlash('success', 'Service updated successfully');
    } else {
        $stmt = $conn->prepare("INSERT INTO services (category_id, subcategory_id, name, image, price, price_type, file_required, hsn_code, gst_rate, form_fields) VALUES (:cat, :sub, :name, :img, :price, :unit, :req, :hsn, :gst, :fields)");
        $stmt->execute([
            ':cat' => $category_id, ':sub' => $subcategory_id, ':name' => $name, ':img' => $image,
            ':price' => $price, ':unit' => $price_unit, ':req' => $file_required, 
            ':hsn' => $hsn_code, ':gst' => $gst_rate, ':fields' => $form_fields
        ]);
        setFlash('success', 'Service added successfully');
    }
    
    redirect('/admin/services.php');
}

// Check Edit Mode
$editMode = false;
$editData = [];
if (isset($_GET['edit'])) {
    $editMode = true;
    $id = $_GET['edit'];
    $editData = $conn->query("SELECT * FROM services WHERE id = $id")->fetch();
}

$services = $conn->query("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id ORDER BY s.id DESC")->fetchAll();
$categories = $conn->query("SELECT * FROM categories")->fetchAll();
$subcategories = $conn->query("SELECT * FROM subcategories")->fetchAll();
?>

<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Service Management</h2>
        <p class="text-sm text-gray-500">Configure prices, requirements, and custom forms.</p>
    </div>
    <div class="bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
            <i class="fas fa-layer-group"></i>
        </div>
        <div>
            <p class="text-[10px] uppercase font-bold text-gray-500">Total Services</p>
            <p class="text-lg font-bold text-gray-900"><?= count($services) ?></p>
        </div>
    </div>
</div>

<div class="flex flex-col lg:flex-row gap-8">

    <!-- Add Service Form -->
    <div class="w-full lg:w-1/3">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24 max-h-[90vh] overflow-y-auto custom-scrollbar">
            <h2 class="text-lg font-bold text-gray-900 mb-1"><?= $editMode ? 'Edit Service' : 'Add New Service' ?></h2>
            <p class="text-xs text-gray-500 mb-6 font-['Outfit']">Configure service details and custom forms.</p>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-5" id="serviceForm">
                <?php if($editMode): ?>
                    <input type="hidden" name="service_id" value="<?= $editData['id'] ?>">
                    <input type="hidden" name="current_image" value="<?= $editData['image'] ?>">
                <?php endif; ?>
                
                <input type="hidden" name="form_fields" id="formFieldsInput">

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Service Name</label>
                    <input type="text" name="name" value="<?= $editMode ? htmlspecialchars($editData['name']) : '' ?>" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none transition-all placeholder-gray-300" placeholder="e.g. PAN Card Apply">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Service Image</label>
                    <?php if($editMode && !empty($editData['image']) && $editData['image']!='default_service.png'): ?>
                        <div class="mb-2">
                            <img src="../uploads/services/<?= $editData['image'] ?>" class="h-12 w-12 rounded object-cover border border-gray-200">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-black file:text-white hover:file:bg-gray-800">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Category</label>
                    <select name="category_id" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none bg-white">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($editMode && $editData['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Price (₹)</label>
                        <input type="number" step="0.01" name="price" value="<?= $editMode ? $editData['price'] : '' ?>" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none">
                    </div>
                     <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Unit</label>
                        <select name="price_type" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-black focus:ring-1 focus:ring-black outline-none bg-white">
                            <?php foreach(['fixed', 'per_page', 'per_copy', 'per_hour'] as $u): ?>
                            <option value="<?= $u ?>" <?= ($editMode && $editData['price_type'] == $u) ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $u)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                    <input type="checkbox" name="file_required" id="file_req" class="w-4 h-4 rounded text-black focus:ring-black border-gray-300" <?= ($editMode && !$editData['file_required']) ? '' : 'checked' ?>>
                    <label for="file_req" class="text-sm text-gray-700 font-medium">User must upload a file?</label>
                </div>

                <!-- Form Builder Section -->
                <div class="border-t border-gray-100 pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-xs font-semibold text-gray-900 uppercase">Custom Form Fields</label>
                        <button type="button" onclick="addField()" class="px-2 py-1 text-xs bg-black text-white rounded hover:bg-gray-800 transition">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    
                    <div id="formBuilder" class="space-y-3"></div>
                    <p class="text-[10px] text-gray-400 mt-2 italic">Add fields like 'PAN Number', 'Date of Birth', etc.</p>
                </div>

                <div class="flex gap-2 pt-4">
                    <button type="submit" name="<?= $editMode ? 'update_service' : 'add_service' ?>" onclick="submitForm()" class="flex-1 bg-black text-white font-bold py-3 rounded-xl hover:bg-gray-800 transition transform shadow-lg shadow-gray-200">
                        <i class="fas <?= $editMode ? 'fa-save' : 'fa-plus' ?> mr-2"></i> <?= $editMode ? 'Update' : 'Add' ?>
                    </button>
                    <?php if($editMode): ?>
                    <a href="services.php" class="px-4 py-3 bg-gray-100 rounded-xl font-bold text-gray-600 hover:bg-gray-200">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Services List -->
    <div class="w-full lg:w-2/3">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-900">All Services</h2>
                <div class="text-xs text-gray-500">Showing <?= count($services) ?> items</div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Service</th>
                            <th class="px-6 py-4 font-semibold">Category</th>
                            <th class="px-6 py-4 font-semibold">Pricing</th>
                            <th class="px-6 py-4 font-semibold">Form</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                             <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($services as $service): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden border border-gray-200 flex-shrink-0">
                                        <img src="../uploads/services/<?= !empty($service['image']) ? $service['image'] : 'default_service.png' ?>" class="w-full h-full object-cover">
                                    </div>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($service['name']) ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($service['category_name']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-gray-900">₹<?= formatPrice($service['price']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php $flds = json_decode($service['form_fields'] ?? '', true); ?>
                                <?php if(!empty($flds) && is_array($flds)): ?>
                                    <span class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium bg-purple-50 text-purple-700">
                                        <i class="fas fa-list-alt"></i> <?= count($flds) ?> Inputs
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 ring-1 ring-inset ring-green-600/20">Active</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="?edit=<?= $service['id'] ?>" class="p-2 text-gray-400 hover:text-black transition-colors rounded-lg hover:bg-gray-100">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Delete service?');" class="inline">
                                        <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                        <button type="submit" name="delete_service" class="p-2 text-gray-400 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const container = document.getElementById('formBuilder');
    let existingFields = <?= ($editMode && !empty($editData['form_fields'])) ? $editData['form_fields'] : '[]' ?>;

    function addField(data = null) {
        const div = document.createElement('div');
        div.className = 'grid grid-cols-12 gap-2 items-center bg-gray-50 p-2 rounded-lg border border-gray-200 field-row animate-pulse-once';
        
        const labelVal = data ? data.label : '';
        const typeVal = data ? data.type : 'text';
        const reqVal = data ? data.required : false;

        div.innerHTML = `
            <div class="col-span-5">
                <input type="text" placeholder="Label (e.g. DOB)" value="${labelVal}" class="field-label w-full text-xs px-2 py-1.5 rounded border border-gray-300 focus:border-black outline-none">
            </div>
            <div class="col-span-4">
                <select class="field-type w-full text-xs px-2 py-1.5 rounded border border-gray-300 focus:border-black outline-none bg-white">
                    <option value="text" ${typeVal=='text'?'selected':''}>Text</option>
                    <option value="number" ${typeVal=='number'?'selected':''}>Number</option>
                    <option value="date" ${typeVal=='date'?'selected':''}>Date</option>
                    <option value="textarea" ${typeVal=='textarea'?'selected':''}>Long Text</option>
                </select>
            </div>
            <div class="col-span-2 flex items-center justify-center">
                 <input type="checkbox" class="field-req w-4 h-4 rounded text-black focus:ring-black border-gray-300" title="Required?" ${reqVal?'checked':''}>
            </div>
            <div class="col-span-1 text-right">
                <button type="button" onclick="this.closest('.field-row').remove()" class="text-red-400 hover:text-red-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(div);
    }

    function submitForm() {
        const rows = document.querySelectorAll('.field-row');
        const fields = [];
        rows.forEach(row => {
            const label = row.querySelector('.field-label').value.trim();
            if(label) {
                fields.push({
                    label: label,
                    type: row.querySelector('.field-type').value,
                    required: row.querySelector('.field-req').checked
                });
            }
        });
        document.getElementById('formFieldsInput').value = JSON.stringify(fields);
    }

    // Initialize
    if (existingFields && existingFields.length > 0) {
        existingFields.forEach(f => addField(f));
    }
</script>

<?php include 'includes/footer.php'; ?>
