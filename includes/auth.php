<?php
// includes/auth.php
// ini file sakti buat ngurusin session, cookie "remember me", sama ngejagain halaman biar ga sembarang orang bisa masuk

// nyalain session kalo belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// cek fitur "Remember Me" lewat cookie (berlaku 1 minggu cuy)
// kalo belum login tapi ada cookie, kita auto-loginin dia
if (!isset($_SESSION['user_id']) && isset($_COOKIE['dochelp_user_id'])) {
    $cookie_user_id = intval($_COOKIE['dochelp_user_id']);
    $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $cookie_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
    }
    $stmt->close();
}

// helper buat ngecek apakah si user udah login atau masih guest
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// helper khusus ngecek apakah role-nya Admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
}

// helper khusus ngecek apakah role-nya Member biasa
function isMember() {
    return isLoggedIn() && isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'member';
}

// satpam buat ngeblok yang belum login, tendang ke login.php
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// satpam khusus halaman Admin, kalo bukan admin langsung lempar ke login
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: login.php");
        exit();
    }
}

// satpam khusus halaman Member (misal booking & dashboard)
function requireMember() {
    if (!isMember()) {
        header("Location: login.php");
        exit();
    }
}
