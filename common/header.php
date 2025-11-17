<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';

$pageTitle = isset($pageTitle) ? $pageTitle : 'Dashboard';
$breadcrumb = isset($breadcrumb) ? $breadcrumb : 'Home / Dashboard';

// Get logged in user data
$loggedInUser = getLoggedInUser();
$userName = $loggedInUser ? $loggedInUser['name'] : 'Admin User';
$userRole = $loggedInUser ? $loggedInUser['role'] : 'Administrator';
?>
<header class="top-header">
    <div class="header-left">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="breadcrumb"><?php echo htmlspecialchars($breadcrumb); ?></p>
    </div>
    <div class="header-right">
        <div class="user-profile">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                <span class="user-role"><?php echo htmlspecialchars($userRole); ?></span>
            </div>
            <div class="user-avatar">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;">
                    <?php echo getInitials($userName); ?>
                </div>
            </div>
        </div>
    </div>
</header>