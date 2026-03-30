<?php
// includes/auth.php
session_start();
require_once __DIR__ . '/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isTeacher() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin');
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php");
        exit;
    }
}

function requireTeacher() {
    requireLogin();
    if (!isTeacher()) {
        header("Location: index.php");
        exit;
    }
}

function login($username, $password, $required_role = null) {
    global $db;
    $users = $db->findWhere('users', 'username', $username);
    if (!empty($users)) {
        $user = $users[0];
        
        // Check requested role matching actual role
        if ($required_role !== null && $user['role'] !== $required_role) {
            return 'wrong_role';
        }

        if (password_verify($password, $user['password'])) {
            // Check if teacher is approved
            if ($user['role'] === 'teacher' && isset($user['status']) && $user['status'] === 'pending') {
                return 'pending'; // Signal that they are pending approval
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
}
?>
