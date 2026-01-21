<?php
include '../config/db.php';
include '../config/config.php';

$database = new Database();
$conn = $database->getConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$userId = $input['user_id'] ?? null;

if (empty($message)) {
    echo json_encode(['reply' => 'Please ask a question.']);
    exit;
}

// 1. Check Local FAQs (Simple Keyword Match)
$stmt = $conn->prepare("SELECT answer FROM service_faqs WHERE question LIKE :exact OR keywords LIKE :keyword LIMIT 1");
$stmt->execute([
    ':exact' => "%$message%", 
    ':keyword' => "%$message%" 
]); // Very basic matching, usually you'd iterate keywords
// Let's do a better keyword search in PHP for now to avoid complex SQL
$stmt = $conn->query("SELECT * FROM service_faqs");
$faqs = $stmt->fetchAll();

foreach ($faqs as $faq) {
    $keywords = array_map('trim', explode(',', $faq['keywords']));
    foreach ($keywords as $k) {
        if (stripos($message, $k) !== false) {
            // Found a match
            logChat($conn, $userId, $message, $faq['answer'], 'local');
            echo json_encode(['reply' => $faq['answer']]);
            exit;
        }
    }
}

// 2. Call Google Gemini API
$reply = callGemini($message, $conn);
logChat($conn, $userId, $message, $reply, 'ai');
echo json_encode(['reply' => $reply]);

// ---------------------------------------------------
// Helper Functions
// ---------------------------------------------------

function callGemini($userMsg, $conn) {
    $apiKey = GEMINI_API_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";

    // Build Context from DB (Services List)
    $services = $conn->query("SELECT name, price, price_type FROM services WHERE status=1")->fetchAll(PDO::FETCH_ASSOC);
    $serviceContext = "Available Services:\n";
    foreach ($services as $s) {
        $serviceContext .= "- {$s['name']}: ₹{$s['price']} ({$s['price_type']})\n";
    }

    $systemPrompt = "
    You are a helpful Service Assistant for a Cyber Cafe.
    
    RULES:
    1. Answer ONLY questions related to cyber cafe services, government documents (PAN, Aadhaar, Passport), and our specific services.
    2. If the user asks about politics, health, medical advice, legal cases, or coding, say: 'I can only help with Cyber Cafe related services.'
    3. Keep answers SHORT (max 3 sentences) and simple.
    4. MASK sensitive numbers if user provides them (e.g. Aadhaar 1234 -> XXXX).
    5. Here is our Price List:\n$serviceContext\n
    6. If unsure, say: 'Please visit the cyber cafe for confirmation.'
    ";

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $systemPrompt . "\nUser: " . $userMsg]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // DISABLE SSL FOR LOCALHOST TESTS
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return "Network Error: " . curl_error($ch);
    }
    curl_close($ch);

    $json = json_decode($response, true);
    
    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        return $json['candidates'][0]['content']['parts'][0]['text'];
    } else {
        // Fallback for safety blocks or errors
        return "I couldn't process that. Please contact the cafe directly.";
    }
}

function logChat($conn, $userId, $msg, $reply, $source) {
    if (!$userId) $userId = null;
    $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, user_message, bot_response, source) VALUES (:uid, :msg, :reply, :src)");
    $stmt->execute([':uid' => $userId, ':msg' => $msg, ':reply' => $reply, ':src' => $source]);
}
?>
