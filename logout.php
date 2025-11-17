<?php
require_once 'config/session.php';

// Destroy session
session_start();
session_unset();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page
header("Location: login.php?logout=success");
exit();
?>

