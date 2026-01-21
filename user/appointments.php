<?php
require_once '../config/config.php';
require_once '../config/Appointment.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$appt = new Appointment();
$appointments = $appt->getUserAppointments($_SESSION['user_id']);

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">My Appointments</h2>
                <a href="book_appointment.php" class="bg-black text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-800 transition">
                    + Book New
                </a>
            </div>

            <div class="space-y-4">
                <?php if (empty($appointments)): ?>
                    <div class="bg-white p-12 rounded-2xl text-center border border-gray-100">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="far fa-calendar-times text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500 font-medium">No appointments scheduled.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($appointments as $app): ?>
                        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                                        <?= $app['appointment_type'] == 'video' ? 'bg-purple-50 text-purple-600' : 'bg-blue-50 text-blue-600' ?>">
                                        <i class="fas fa-<?= $app['appointment_type'] == 'video' ? 'video' : 'store' ?>"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900"><?= $app['service_name'] ?></h3>
                                        <div class="flex items-center gap-3 text-sm text-gray-500 mt-1">
                                            <span><i class="far fa-clock mr-1"></i> <?= date('M d, Y', strtotime($app['appointment_date'])) ?> at <?= date('h:i A', strtotime($app['appointment_time'])) ?></span>
                                            <?php if($app['staff_name']): ?>
                                                <span><i class="far fa-user mr-1"></i> <?= $app['staff_name'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 self-end md:self-auto">
                                    <?php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'confirmed' => 'bg-green-100 text-green-800',
                                        'completed' => 'bg-gray-100 text-gray-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusColor = $statusColors[$app['status']] ?? 'bg-gray-100';
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $statusColor ?>">
                                        <?= $app['status'] ?>
                                    </span>

                                    <!-- Actions -->
                                    <a href="appointment_room.php?id=<?= $app['id'] ?>" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-black transition">
                                        View & Chat
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
