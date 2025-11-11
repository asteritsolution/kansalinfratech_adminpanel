<?php
$pageTitle = isset($pageTitle) ? $pageTitle : 'Dashboard';
$breadcrumb = isset($breadcrumb) ? $breadcrumb : 'Home / Dashboard';
?>
<header class="top-header">
    <div class="header-left">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="breadcrumb"><?php echo htmlspecialchars($breadcrumb); ?></p>
    </div>
    <div class="header-right">
        <div class="user-profile">
            <div class="user-info">
                <span class="user-name">Admin User</span>
                <span class="user-role">Administrator</span>
            </div>
            <div class="user-avatar">
                <img src="images/user-avatar.png" alt="User Avatar">
            </div>
        </div>
    </div>
</header>