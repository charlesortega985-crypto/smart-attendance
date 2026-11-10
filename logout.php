<?php
// logout.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. I-clear ang lahat ng session array variables
$_SESSION = array();

// 2. I-expire ang session cookie kung ginagamit ito ng browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. I-destroy ang session sa server side
session_destroy();

// 4. I-redirect pabalik sa login page
header("Location: register.php");
exit;
?>