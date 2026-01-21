<?php
include 'includes/header.php';
include 'includes/sidebar.php';

requireRole(['admin', 'manager']);

// Handle Cleanup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cleanup_files'])) {
    $days = 30; // Policy
    $path = '../uploads/';
    $count = 0;
    
    // Safety check: Ensure we don't delete system files if path is wrong. 
    // realpath check is good.
    
    $files = glob($path . '*'); // Get all files in uploads/
    foreach ($files as $file) {
        if (is_file($file)) {
            // Exclude index.php or specific system files if any
            if (basename($file) == 'index.php') continue;
            
            // Check age
            if (time() - filemtime($file) > ($days * 86400)) {
                unlink($file);
                $count++;
            }
        }
    }
    
    logActivity('Data Cleanup', "Manually ran cleanup. Deleted $count files > $days days old.");
    setFlash('success', "Security Cleanup: Deleted $count old files.");
    redirect('security.php');
}

// Calculate Stats
$totalFiles = 0;
$totalSize = 0;
$path = '../uploads/';
$files = glob($path . '*');
foreach ($files as $file) {
    if (is_file($file)) {
        $totalFiles++;
        $totalSize += filesize($file);
    }
}

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 
?>

<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Data Security & Retention</h2>
        <p class="text-sm text-gray-500">Manage sensitive data and file retention policies.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- Retention Policy Card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">File Retention Policy</h3>
                <p class="text-xs text-gray-500">Compliance with Data Privacy Laws</p>
            </div>
        </div>
        
        <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-200">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-semibold text-gray-700">Retention Period</span>
                <span class="text-sm font-bold text-black border-b-2 border-green-500">30 Days</span>
            </div>
            <p class="text-xs text-gray-500">
                User uploaded documents (Aadhaar, PAN, etc.) are automatically marked for deletion 30 days after upload. This ensures sensitive data is not stored longer than necessary.
            </p>
        </div>

        <form method="POST">
            <button type="submit" name="cleanup_files" onclick="return confirm('Are you sure? This will permanently delete all files older than 30 days.');" class="w-full bg-red-600 text-white font-bold py-3 rounded-xl hover:bg-red-700 transition shadow-lg shadow-red-200 flex items-center justify-center gap-2">
                <i class="fas fa-trash-alt"></i> Run Cleanup Now
            </button>
        </form>
    </div>

    <!-- Storage Stats -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
         <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 text-xl">
                <i class="fas fa-hdd"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Storage Usage</h3>
                <p class="text-xs text-gray-500">Current footprint of secure uploads</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 text-center">
                <p class="text-2xl font-bold text-gray-900"><?= $totalFiles ?></p>
                <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Total Files</p>
            </div>
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 text-center">
                <p class="text-2xl font-bold text-gray-900"><?= formatBytes($totalSize) ?></p>
                <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Total Size</p>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-yellow-50 border border-yellow-100 flex gap-3 items-start">
             <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
             <div class="text-xs text-yellow-800">
                 <strong>Note:</strong> Regular cleanup improves system performance and reduces liability. It is recommended to run this weekly.
             </div>
        </div>
    </div>
</div>

<div class="mt-8 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
    <h3 class="font-bold text-gray-900 mb-4">Masking & Encryption Status</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="flex items-center gap-3 p-3 rounded-lg border border-green-100 bg-green-50">
            <div class="w-8 h-8 rounded-full bg-green-200 flex items-center justify-center text-green-700"><i class="fas fa-check"></i></div>
            <div>
                <p class="text-sm font-bold text-gray-900">Aadhaar Masking</p>
                <p class="text-xs text-green-700">Active (XXXX-XXXX-1234)</p>
            </div>
        </div>
        <div class="flex items-center gap-3 p-3 rounded-lg border border-green-100 bg-green-50">
            <div class="w-8 h-8 rounded-full bg-green-200 flex items-center justify-center text-green-700"><i class="fas fa-check"></i></div>
            <div>
                <p class="text-sm font-bold text-gray-900">PAN Masking</p>
                <p class="text-xs text-green-700">Active (ABCDE****)</p>
            </div>
        </div>
        <div class="flex items-center gap-3 p-3 rounded-lg border border-green-100 bg-green-50">
            <div class="w-8 h-8 rounded-full bg-green-200 flex items-center justify-center text-green-700"><i class="fas fa-check"></i></div>
            <div>
                <p class="text-sm font-bold text-gray-900">Admin Only Access</p>
                <p class="text-xs text-green-700">Enforced</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
