<?php $activePage = isset($activePage) ? $activePage : ''; ?>
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
           <li class="<?php echo $activePage === 'reports' ? 'active' : ''; ?>">
            <a href="reports.php">
                     <span class="icon"><i class="fas fa-chart-bar"></i></span>
                     <span>Reports</span>
                 </a>
             </li>
            <li class="<?php echo $activePage === 'users' ? 'active' : ''; ?>">
                <a href="users.php">
                     <span class="icon"><i class="fas fa-user-cog"></i></span>
                     <span>Users</span>
                 </a>
             </li>
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