<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie (for security, optional but recommended)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finally destroy the session itself
session_destroy();

// Redirect to login page
header("Location: index.php");
exit;
?>