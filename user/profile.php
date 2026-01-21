<?php
include 'includes/header.php';

// Fetch user data
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch();

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];

    // Handle Profile Pic Upload
    $profile_pic = $user['profile_pic']; // Default to existing
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
        $uploadDir = '../uploads/profiles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadDir . $filename)) {
            $profile_pic = $filename;
        }
    }

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->prepare("UPDATE users SET name = ?, phone = ?, password = ?, profile_pic = ? WHERE id = ?")->execute([$name, $phone, $hash, $profile_pic, $user_id]);
    } else {
        $conn->prepare("UPDATE users SET name = ?, phone = ?, profile_pic = ? WHERE id = ?")->execute([$name, $phone, $profile_pic, $user_id]);
    }
    
    // Update session name just in case
    $_SESSION['user_name'] = $name;
    $_SESSION['user_profile_pic'] = $profile_pic;
    
    setFlash('success', 'Profile updated successfully');
    redirect('user/profile.php');
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 w-full max-w-3xl"> 
             <div class="bg-white rounded-2xl border border-gray-100 p-8 shadow-sm">
                <div class="mb-8 border-b border-gray-50 pb-4">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Account Settings</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage your profile picture and security preferences.</p>
                </div>

                <?php if ($msg = getFlash()): ?>
                    <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm font-medium text-green-800 flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> <?= $msg['message'] ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Profile Picture Section -->
                    <div class="flex items-center gap-6 mb-8 p-4 bg-gray-50/50 rounded-xl border border-gray-100">
                        <div class="relative group">
                            <?php 
                                $imgSrc = $user['profile_pic'] ? '../uploads/profiles/' . $user['profile_pic'] : "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&size=128&background=random";
                            ?>
                            <img id="preview" class="h-20 w-20 rounded-full bg-white ring-4 ring-white shadow-md object-cover" src="<?= $imgSrc ?>" alt="">
                            
                            <label for="fileInput" class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-full opacity-0 group-hover:opacity-100 cursor-pointer transition text-white">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" name="profile_pic" id="fileInput" class="hidden" accept="image/*" onchange="previewImage(this)">
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Profile Picture</h3>
                            <p class="text-xs text-gray-500 mb-2">Allowed: JPG, PNG. Max 2MB.</p>
                            <label for="fileInput" class="text-xs font-bold text-indigo-600 cursor-pointer hover:underline">Change Photo</label>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="far fa-user text-gray-400 text-xs"></i>
                                    </div>
                                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="pl-9 block w-full rounded-lg border-gray-200 focus:border-black focus:ring-black sm:text-sm py-2.5 bg-gray-50/30">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-phone-alt text-gray-400 text-xs"></i>
                                    </div>
                                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required class="pl-9 block w-full rounded-lg border-gray-200 focus:border-black focus:ring-black sm:text-sm py-2.5 bg-gray-50/30">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="far fa-envelope text-gray-400 text-xs"></i>
                                </div>
                                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled class="pl-9 block w-full rounded-lg border-gray-200 bg-gray-100 text-gray-500 cursor-not-allowed sm:text-sm py-2.5">
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Email cannot be changed.</p>
                        </div>

                        <div class="pt-6 border-t border-gray-50 mt-6">
                            <h3 class="text-sm font-medium text-gray-900 mb-4">Security</h3>
                             <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400 text-xs"></i>
                                    </div>
                                    <input type="password" name="password" placeholder="Leave blank to keep current" class="pl-9 block w-full rounded-lg border-gray-200 focus:border-black focus:ring-black sm:text-sm py-2.5 bg-gray-50/30">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end pt-6 border-t border-gray-50">
                        <button type="submit" class="rounded-lg bg-black px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition flex items-center gap-2">
                             <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
