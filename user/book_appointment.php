<?php
require_once '../config/config.php';
require_once '../config/Appointment.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$appointment = new Appointment();
$error = '';
$success = '';

// Fetch Services
$database = new Database();
$conn = $database->getConnection();
$services = $conn->query("SELECT id, name FROM services WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $type = $_POST['type'];
    $notes = $_POST['notes'];

    $result = $appointment->book($_SESSION['user_id'], $service_id, $type, $date, $time, $notes);
    if ($result['status']) {
        $success = "Appointment requested! Wait for confirmation.";
    } else {
        $error = $result['message'];
    }
}

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center text-lg">
                        <i class="far fa-calendar-check"></i>
                    </span>
                    Book Appointment
                </h2>

                <?php if($error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6"><?= $error ?></div>
                <?php endif; ?>
                <?php if($success): ?>
                    <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6 flex justify-between items-center">
                        <span><?= $success ?></span>
                        <a href="appointments.php" class="text-sm font-bold underline">View All</a>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    
                    <!-- Service -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Service</label>
                        <select name="service_id" required class="w-full rounded-xl border-gray-200 focus:ring-black focus:border-black">
                            <?php foreach($services as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Type</label>
                        <div class="grid grid-cols-3 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="in_person" checked class="peer sr-only">
                                <div class="p-4 rounded-xl border border-gray-200 peer-checked:bg-black peer-checked:text-white peer-checked:border-black transition text-center hover:bg-gray-50">
                                    <i class="fas fa-store mb-2 text-xl"></i>
                                    <div class="text-sm font-semibold">At Center</div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="video" class="peer sr-only">
                                <div class="p-4 rounded-xl border border-gray-200 peer-checked:bg-black peer-checked:text-white peer-checked:border-black transition text-center hover:bg-gray-50">
                                    <i class="fas fa-video mb-2 text-xl"></i>
                                    <div class="text-sm font-semibold">Video Call</div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="call" class="peer sr-only">
                                <div class="p-4 rounded-xl border border-gray-200 peer-checked:bg-black peer-checked:text-white peer-checked:border-black transition text-center hover:bg-gray-50">
                                    <i class="fas fa-phone-alt mb-2 text-xl"></i>
                                    <div class="text-sm font-semibold">Voice Call</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Date & Time -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Date</label>
                            <input type="date" name="date" required min="<?= date('Y-m-d') ?>" class="w-full rounded-xl border-gray-200 focus:ring-black focus:border-black">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Time</label>
                            <select name="time" required class="w-full rounded-xl border-gray-200 focus:ring-black focus:border-black">
                                <?php 
                                $start = strtotime('10:00');
                                $end = strtotime('18:00');
                                while($start < $end) {
                                    $timeStr = date('H:i', $start);
                                    $display = date('h:i A', $start);
                                    echo "<option value='$timeStr'>$display</option>";
                                    $start = strtotime('+30 minutes', $start);
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                        <textarea name="notes" rows="3" class="w-full rounded-xl border-gray-200 focus:ring-black focus:border-black" placeholder="Describe your issue or requirement..."></textarea>
                    </div>

                    <button type="submit" class="w-full py-4 bg-black text-white font-bold rounded-xl shadow-lg hover:bg-gray-800 transition">
                        Confirm Booking
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
