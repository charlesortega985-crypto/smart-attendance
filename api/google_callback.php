<?php
// google_callback.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config_file = __DIR__ . '/config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

// ⚠️ Palitan ng Client ID at Client Secret mula sa Google Cloud Console
$client_id     = "370645663438-2r5f7hobqfe3p0epmmlpdd3qttphc963.apps.googleusercontent.com";
$client_secret = "GOCSPX-1234567890abcdef1234567890abcd";
$redirect_uri  = "http://localhost/attendance/google_callback.php";

if (!isset($_GET['code'])) {
    header("Location: login.php");
    exit;
}

// 1. Exchange Auth Code for Access Token
$token_url = "https://oauth2.googleapis.com/token";
$post_data = [
    'code'          => $_GET['code'],
    'client_id'     => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri'  => $redirect_uri,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    $_SESSION['msg'] = "<div class='alert alert-danger py-2'><small>Google Auth Failed. Please try again.</small></div>";
    header("Location: login.php");
    exit;
}

// 2. Fetch User Profile Details from Google API
$userinfo_url = "https://www.googleapis.com/oauth2/v2/userinfo?access_token=" . $token_data['access_token'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userinfo_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$userinfo_response = curl_exec($ch);
curl_close($ch);

$google_user = json_decode($userinfo_response, true);

$google_email = trim($google_user['email'] ?? '');
$google_name  = trim($google_user['name'] ?? '');

if (empty($google_email)) {
    $_SESSION['msg'] = "<div class='alert alert-danger py-2'><small>Unable to retrieve Google account info.</small></div>";
    header("Location: login.php");
    exit;
}

// 3. Database Check & Login Logic
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$google_email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Existing user -> Auto-login & redirect
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['id_number'] = $user['student_faculty_id'] ?? $user['id_number'];

            if ($user['role'] === 'faculty') {
                header("Location: faculty/index.php");
            } else {
                header("Location: student/index.php");
            }
            exit;
        } else {
            // New user -> Prompt for Student/Faculty ID Number
            $_SESSION['google_reg'] = [
                'email'     => $google_email,
                'full_name' => $google_name
            ];
            header("Location: complete_google_reg.php");
            exit;
        }
    } catch (Exception $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>