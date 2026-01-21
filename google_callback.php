<?php
require_once 'config/config.php';
require_once 'config/db.php';

session_start();

if (isset($_GET['code'])) {
    
    // 1. Exchange Auth Code for Access Token
    $token_url = 'https://oauth2.googleapis.com/token';
    $params = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Disable SSL for Localhost if needed, but google might strictly require SSL. For now we try without disabling execution if possible, but standard XAMPP might need verification off.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        die('Request Error: ' . curl_error($ch));
    }
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['error'])) {
        die('Error fetching token: ' . $token_data['error_description']);
    }
    
    if (!isset($token_data['access_token'])) {
        die('Invalid Token Response');
    }

    // 2. Fetch User Profile
    $access_token = $token_data['access_token'];
    $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_info_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $profile_response = curl_exec($ch);
    curl_close($ch);
    
    $user_info = json_decode($profile_response, true);
    
    if (isset($user_info['error'])) {
        die('Error fetching profile: ' . $user_info['error']['message']);
    }

    // 3. Database Logic
    $google_id = $user_info['id'];
    $email = $user_info['email'];
    $name = $user_info['name'];
    $picture = $user_info['picture'] ?? '';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User Exists - Update Google ID if missing
        if (empty($user['google_id'])) {
            $update = $conn->prepare("UPDATE users SET google_id = ?, login_provider = CASE WHEN login_provider = 'email' THEN 'email' ELSE 'google' END WHERE id = ?");
            $update->execute([$google_id, $user['id']]);
        }
        
        // Login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role']; 
        $_SESSION['user_profile_pic'] = $user['profile_pic'];
        
        header("Location: " . BASE_URL . ($user['role'] == 'admin' ? 'admin/index.php' : 'user/index.php'));
        exit;

    } else {
        // New User - Registration
        // Default role: user, status: 1 (active)
        $stmt = $conn->prepare("INSERT INTO users (name, email, google_id, login_provider, role, status, profile_pic) VALUES (?, ?, ?, 'google', 'user', 1, ?)");
        
        if ($stmt->execute([$name, $email, $google_id, $picture])) {
            $lastId = $conn->lastInsertId();
            
            $_SESSION['user_id'] = $lastId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'user';
            $_SESSION['user_profile_pic'] = $picture;
            
            header("Location: " . BASE_URL . 'user/index.php');
            exit;
        } else {
             die("Registration Failed. Please try again.");
        }
    }

} else {
    // No Code? Redirect to Login
    header("Location: login.php");
    exit;
}
?>
