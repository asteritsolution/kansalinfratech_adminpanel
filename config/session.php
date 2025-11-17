<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Get logged in user data
function getLoggedInUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'name' => $_SESSION['name'] ?? '',
            'role' => $_SESSION['role'] ?? 'Telecaller',
            'email' => $_SESSION['email'] ?? ''
        ];
    }
    return null;
}
?>

