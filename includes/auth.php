<?php
// includes/auth.php
// Session management, Cookie authentication, and Route Guards

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Handle 'Remember Me' Cookie (1 week duration)
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

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
}

function isMember() {
    return isLoggedIn() && isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'member';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: login.php");
        exit();
    }
}

function requireMember() {
    if (!isMember()) {
        header("Location: login.php");
        exit();
    }
}
