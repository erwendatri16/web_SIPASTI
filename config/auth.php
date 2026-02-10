<?php
// config/auth.php
session_start();

// Cek apakah sudah login
function isLoggedin() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Cek role mahasiswa
function isMahasiswaLoggedIn() {
    return isLoggedin() && $_SESSION['role'] === 'mahasiswa';
}

// Cek role admin
function isAdminLoggedIn() {
    return isLoggedin() && $_SESSION['role'] === 'admin';
}

// Redirect jika belum login
function requireLogin() {
    if (!isLoggedin()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

// Redirect ke dashboard jika sudah login
function redirectIfLoggedIn() {
    if (isLoggedin()) {
        if ($_SESSION['role'] === 'mahasiswa') {
            header('Location: ' . BASE_URL . 'mahasiswa/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
        }
        exit;
    }
}

// Redirect berdasarkan role
function requireRole($role) {
    requireLogin();
    
    if ($_SESSION['role'] !== $role) {
        if ($_SESSION['role'] === 'mahasiswa') {
            header('Location: ' . BASE_URL . 'mahasiswa/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
        }
        exit;
    }
}

// Logout
function logout() {
    session_destroy();
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// Set base URL dinamis
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptPath === '/') {
    $scriptPath = '';
}
define('BASE_URL', $protocol . '://' . $host . $scriptPath . '/');
?>