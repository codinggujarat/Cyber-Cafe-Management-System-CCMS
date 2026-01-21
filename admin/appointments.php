<?php
require_once 'includes/header.php';
require_once '../config/Appointment.php';

$apptObj = new Appointment(); // Need to extend or use raw queries for Admin Listing to get all users

// Custom Admin Query
$db = new Database();
$conn = $db->getConnection();

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    $status = '';
    
    if ($action == 'confirm') $status = 'confirmed';
    if ($action == 'cancel') $status = 'cancelled';
    if ($action == 'complete') $status = 'completed';

    if ($status) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo "<script>window.location.href='appointments.php';</script>";
    }
}

// Fetch All Appointments
$query = "SELECT a.*, u.name as user_name, s.name as service_name 
          FROM appointments a 
          JOIN users u ON a.user_id = u.id 
          JOIN services s ON a.service_id = s.id 
          ORDER BY a.appointment_date DESC";
$appointments = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Appointment Management</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                <tr>
                    <th class="p-4 border-b">ID</th>
                    <th class="p-4 border-b">User</th>
                    <th class="p-4 border-b">Service</th>
                    <th class="p-4 border-b">Type</th>
                    <th class="p-4 border-b">Date & Time</th>
                    <th class="p-4 border-b">Status</th>
                    <th class="p-4 border-b text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 text-sm">
                <?php foreach($appointments as $app): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-mono font-bold text-gray-500">#<?= $app['id'] ?></td>
                        <td class="p-4 font-medium text-gray-900"><?= $app['user_name'] ?></td>
                        <td class="p-4 text-gray-600"><?= $app['service_name'] ?></td>
                        <td class="p-4">
                            <?php if($app['appointment_type'] == 'video'): ?>
                                <span class="inline-flex items-center gap-1 text-purple-600 bg-purple-50 px-2 py-1 rounded text-xs font-bold">
                                    <i class="fas fa-video"></i> Video
                                </span>
                            <?php elseif($app['appointment_type'] == 'call'): ?>
                                <span class="inline-flex items-center gap-1 text-blue-600 bg-blue-50 px-2 py-1 rounded text-xs font-bold">
                                    <i class="fas fa-phone"></i> Call
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-bold">
                                    <i class="fas fa-store"></i> In-Person
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-gray-600">
                            <div><?= date('M d, Y', strtotime($app['appointment_date'])) ?></div>
                            <div class="text-xs text-gray-400"><?= date('h:i A', strtotime($app['appointment_time'])) ?></div>
                        </td>
                        <td class="p-4">
                            <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'confirmed' => 'bg-green-100 text-green-800',
                                'completed' => 'bg-gray-100 text-gray-600',
                                'cancelled' => 'bg-red-100 text-red-800'
                            ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-bold uppercase <?= $statusColors[$app['status']] ?>">
                                <?= $app['status'] ?>
                            </span>
                        </td>
                        <td class="p-4 text-right space-x-2">
                            <?php if($app['status'] == 'pending'): ?>
                                <a href="?action=confirm&id=<?= $app['id'] ?>" class="text-green-600 hover:bg-green-50 p-2 rounded transition" title="Confirm">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="?action=cancel&id=<?= $app['id'] ?>" class="text-red-600 hover:bg-red-50 p-2 rounded transition" title="Cancel">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php elseif($app['status'] == 'confirmed'): ?>
                                <a href="?action=complete&id=<?= $app['id'] ?>" class="text-blue-600 hover:bg-blue-50 p-2 rounded transition" title="Mark Complete">
                                    <i class="fas fa-check-double"></i>
                                </a>
                                <?php if($app['appointment_type'] == 'video'): ?>
                                     <!-- Link to same room page but we need to handle admin view in that page or duplicate. 
                                          Let's point to user view for now, usually admins have separate. 
                                          Simulating admin join via same link, role check logic in user/appointment_room.php needs update if we want admin to access -->
                                    <a href="../user/appointment_room.php?id=<?= $app['id'] ?>" target="_blank" class="text-purple-600 hover:bg-purple-50 p-2 rounded transition" title="Join Video">
                                        <i class="fas fa-video"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
