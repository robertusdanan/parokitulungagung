<?php

session_start();


define('ADMIN_PASSWORD_HASH', '$2y$10$YXNg5zPOPddZlj/kX2lRy.jq732wGw1B5Zd8.ZQmXJDjb5sfZDvoq');

// Fungsi cek apakah sudah login
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $password = $_POST['password'] ?? '';
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in']  = true;
        $_SESSION['admin_login_time'] = time();
        header('Location: index.php');
        exit;
    } else {
        $loginError = 'Password salah. Silakan coba lagi.';
    }
}

// Proses logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}
