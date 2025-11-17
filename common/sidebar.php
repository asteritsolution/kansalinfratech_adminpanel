<?php 
require_once __DIR__ . '/../config/session.php';
$activePage = isset($activePage) ? $activePage : ''; 
?>
<!-- Sidebar -->
<aside class="sidebar">
     <div class="sidebar-header">
         <div class="logo">
             <img src="images/logo.png" alt="Kansal Logo" class="logo-img">
         </div>
     </div>

     <nav class="sidebar-nav">
         <ul>
            <li class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                <a href="index.php">
                     <span class="icon"><i class="fas fa-chart-line"></i></span>
                     <span>Dashboard</span>
                 </a>
             </li>
           <li class="<?php echo $activePage === 'leads' ? 'active' : ''; ?>">
                <a href="leads-management.php">
                     <span class="icon"><i class="fas fa-users"></i></span>
                     <span>Leads Management</span>
                 </a>
             </li>
           <li class="<?php echo $activePage === 'all-leads' ? 'active' : ''; ?>">
                <a href="all-leads.php">
                     <span class="icon"><i class="fas fa-address-card"></i></span>
                     <span>All Leads</span>
                 </a>
             </li>
           <?php 
           $loggedInUser = getLoggedInUser();
           $userRole = $loggedInUser['role'] ?? 'Administrator';
           if ($userRole == 'Telecaller'): 
           ?>
           <li class="<?php echo $activePage === 'assign-leads' ? 'active' : ''; ?>">
                <a href="assign-leads.php">
                     <span class="icon"><i class="fas fa-user-friends"></i></span>
                     <span>Assign Leads</span>
                 </a>
             </li>
           <li class="<?php echo $activePage === 'follow-ups' ? 'active' : ''; ?>">
                <a href="follow-ups.php">
                     <span class="icon"><i class="fas fa-phone-alt"></i></span>
                     <span>Follow-Ups</span>
                 </a>
             </li>
           <?php endif; ?>
           <?php 
           if ($userRole == 'Manager'): 
           ?>
           <li class="<?php echo $activePage === 'personal-leads' ? 'active' : ''; ?>">
                <a href="personal-leads.php">
                     <span class="icon"><i class="fas fa-user-tie"></i></span>
                     <span>Personal Leads</span>
                 </a>
             </li>
           <?php endif; ?>
           <li class="<?php echo $activePage === 'reports' ? 'active' : ''; ?>">
            <a href="reports.php">
                     <span class="icon"><i class="fas fa-chart-bar"></i></span>
                     <span>Reports</span>
                 </a>
             </li>
            <?php 
            $loggedInUser = getLoggedInUser();
            $userRole = $loggedInUser['role'] ?? 'Telecaller';
            if ($userRole == 'Manager' || $userRole == 'Administrator'): 
            ?>
            <li class="<?php echo $activePage === 'users' ? 'active' : ''; ?>">
                <a href="users.php">
                     <span class="icon"><i class="fas fa-user-cog"></i></span>
                     <span>Users</span>
                 </a>
             </li>
            <?php endif; ?>
            <li class="<?php echo $activePage === 'settings' ? 'active' : ''; ?>">
                <a href="settings.php">
                     <span class="icon"><i class="fas fa-cog"></i></span>
                     <span>Settings</span>
                 </a>
             </li>
         </ul>
     </nav>

     <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">
             <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
             <span>Logout</span>
         </a>
     </div>
 </aside>