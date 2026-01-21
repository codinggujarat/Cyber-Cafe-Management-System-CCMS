<?php
require_once '../config/config.php';
require_once '../config/Appointment.php';
require_once '../config/Chat.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? 0;
$apptObj = new Appointment();
$chatObj = new Chat();

$appt = $apptObj->getAppointment($id);

// Validation
// Validation
$allowed_roles = ['admin', 'staff', 'manager'];
$user_role = $_SESSION['user_role'] ?? 'user';
$is_admin = in_array($user_role, $allowed_roles);

if (!$appt) {
    die("Appointment not found.");
}

if ($appt['user_id'] != $_SESSION['user_id'] && !$is_admin) {
    die("Access Denied.");
}

$is_video_eligible = ($appt['appointment_type'] == 'video' && $appt['status'] == 'confirmed');
// Jitsi Room Name: Secured Hash
$roomName = md5('ccms_video_' . $appt['id'] . '_secure_salt');

// Handle Chat Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    if (!empty($msg)) {
        $chatObj->sendMessage($_SESSION['user_id'], $msg, 'appointment', $id);
        header("Location: appointment_room.php?id=$id"); // PRG
        exit;
    }
}

$messages = $chatObj->getMessages('appointment', $id);

include 'includes/header.php';
?>
<!-- Jitsi Script -->
<script src='https://meet.jit.si/external_api.js'></script>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8 min-h-[600px]">
        
        <!-- Left: Details & Chat -->
        <div class="w-full lg:w-1/3 flex flex-col gap-6">
            
            <!-- Details Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                 <h2 class="text-xl font-bold text-gray-900 mb-2"><?= $appt['service_name'] ?></h2>
                 <p class="text-sm text-gray-500 mb-4">
                    #APT-<?= $id ?> • <?= ucfirst($appt['appointment_type']) ?>
                 </p>
                 
                 <div class="flex items-center justify-between text-sm py-2 border-t border-gray-50">
                     <span class="text-gray-500">Date</span>
                     <span class="font-semibold"><?= date('M d', strtotime($appt['appointment_date'])) ?></span>
                 </div>
                 <div class="flex items-center justify-between text-sm py-2 border-t border-gray-50">
                     <span class="text-gray-500">Time</span>
                     <span class="font-semibold"><?= date('h:i A', strtotime($appt['appointment_time'])) ?></span>
                 </div>
                 <div class="flex items-center justify-between text-sm py-2 border-t border-gray-50">
                     <span class="text-gray-500">Status</span>
                     <span class="px-2 py-0.5 rounded text-xs font-bold uppercase bg-gray-100"><?= $appt['status'] ?></span>
                 </div>
            </div>

            <!-- Chat Box -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex-1 flex flex-col h-[500px]">
                <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-2xl">
                    <h3 class="font-bold text-gray-800"><i class="far fa-comments mr-2"></i> Support Chat</h3>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-container">
                    <?php if (empty($messages)): ?>
                        <p class="text-center text-gray-400 text-xs mt-10">No messages yet. Start a conversation!</p>
                    <?php else: ?>
                        <?php foreach($messages as $m): ?>
                            <?php $isMe = $m['sender_id'] == $_SESSION['user_id']; ?>
                            <div class="flex <?= $isMe ? 'justify-end' : 'justify-start' ?>">
                                <div class="max-w-[80%] <?= $isMe ? 'bg-black text-white' : 'bg-gray-100 text-gray-800' ?> px-4 py-2 rounded-2xl text-sm">
                                    <p><?= htmlspecialchars($m['message']) ?></p>
                                    <span class="text-[10px] opacity-50 block mt-1 text-right"><?= date('h:i A', strtotime($m['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" class="p-4 border-t border-gray-100">
                    <div class="flex gap-2">
                        <input type="text" name="message" required placeholder="Type a message..." class="flex-1 rounded-xl border-gray-200 text-sm focus:ring-black focus:border-black">
                        <button type="submit" class="bg-black text-white w-10 h-10 rounded-xl flex items-center justify-center hover:bg-gray-800 transition">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Right: Video / Info -->
        <div class="w-full lg:w-2/3">
            <?php if($is_video_eligible): ?>
                <div class="bg-black rounded-2xl overflow-hidden shadow-2xl h-full min-h-[600px] flex flex-col relative">
                    <div id="jitsi-container" class="flex-1 flex items-center justify-center">
                        <div class="text-center text-white" id="video-placeholder">
                            <div class="w-20 h-20 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
                                <i class="fas fa-video text-3xl"></i>
                            </div>
                            <h2 class="text-2xl font-bold mb-2">Video Consultation</h2>
                            <p class="text-gray-400 mb-6">Your appointment is confirmed and ready.</p>
                            <button onclick="startCall()" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-full transition transform hover:scale-105">
                                Join Meeting
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-gray-100 rounded-2xl h-full flex items-center justify-center text-center p-10">
                    <div class="max-w-md">
                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/appointment-booking-2537365-2146473.png" alt="Illustration" class="w-64 mx-auto opacity-50 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Wait for Appointment</h2>
                        <p class="text-gray-500">
                            Video call will be available here once your appointment is <b>Confirmed</b> and the time has arrived.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Scroll chat to bottom
    const chatContainer = document.getElementById('chat-container');
    chatContainer.scrollTop = chatContainer.scrollHeight;

    function startCall() {
        document.getElementById('video-placeholder').style.display = 'none';
        const domain = 'meet.jit.si';
        const options = {
            roomName: '<?= $roomName ?>',
            width: '100%',
            height: '100%',
            parentNode: document.querySelector('#jitsi-container'),
            userInfo: {
                displayName: '<?= addslashes($_SESSION['user_name']) ?>'
            },
            configOverwrite: { 
                startWithAudioMuted: true, 
                startWithVideoMuted: true 
            },
            interfaceConfigOverwrite: {
                TOOLBAR_BUTTONS: [
                    'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                    'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
                    'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
                    'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
                    'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone',
                    'security'
                ],
            }
        };
        const api = new JitsiMeetExternalAPI(domain, options);
    }
</script>

<?php include 'includes/footer.php'; ?>
