<?php
// logout.php - DocHelp Logout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Clear Remember Me cookie
if (isset($_COOKIE['dochelp_user_id'])) {
    setcookie('dochelp_user_id', '', time() - 3600, "/");
}

header("Location: index.php");
exit();
