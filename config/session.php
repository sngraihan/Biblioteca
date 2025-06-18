<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Get current user info
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /biblioteca2/auth/login.php');
        exit();
    }
}

// Check if user has admin role
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// Require admin role
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header('Location: /biblioteca2/index.php?error=access_denied');
        exit();
    }
}
?>