<?php
// logout.php - ini file buat bersihin session dan cookie pas user logout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// kosongkan array session
$_SESSION = [];

// hapus cookie session bawaan browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// hancurkan session
session_destroy();

// buang cookie "Remember Me" biar ga auto-login lagi
if (isset($_COOKIE['dochelp_user_id'])) {
    setcookie('dochelp_user_id', '', time() - 3600, "/");
}

// balikin user ke halaman beranda sebagai tamu (guest)
header("Location: index.php");
exit();
