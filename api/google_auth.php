<?php
// google_auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ⚠️ Palitan ng Client ID mula sa Google Cloud Console
$client_id    = "370645663438-2r5f7hobqfe3p0epmmlpdd3qttphc963.apps.googleusercontent.com";
$redirect_uri = "http://localhost/attendance/google_callback.php";

$params = [
    'client_id'     => $client_id,
    'redirect_uri'  => $redirect_uri,
    'response_type' => 'code',
    'scope'         => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',
    'access_type'   => 'online',
    'prompt'        => 'select_account'
];

$auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query($params);

header("Location: " . $auth_url);
exit;
?>