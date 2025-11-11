<?php
$activePage = 'settings';
$pageTitle = 'Settings';
$breadcrumb = 'Home / Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Kansal Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
       
    <!-- Header start -->
     <?php include('common/sidebar.php')?>
     <!-- Header end  -->
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <?php include('common/header.php')?>
            <!-- Top header end -->

            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Profile Settings</h2>
                        <a href="#" class="view-all-btn">Update Password</a>
                    </div>
                    <div class="card-body">
                        <form class="settings-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="adminName">Admin Name</label>
                                    <input type="text" id="adminName" value="Rakesh Kansal">
                                </div>
                                <div class="form-group">
                                    <label for="adminEmail">Email</label>
                                    <input type="email" id="adminEmail" value="admin@kansalgroup.com">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="adminPhone">Phone Number</label>
                                    <input type="text" id="adminPhone" value="+91 98765 43210">
                                </div>
                                <div class="form-group">
                                    <label for="adminRole">Role</label>
                                    <select id="adminRole">
                                        <option>Administrator</option>
                                        <option>Manager</option>
                                        <option>Telecaller</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                                <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>Notification Preferences</h2>
                    </div>
                    <div class="card-body">
                        <form class="settings-form">
                            <div class="toggle-group">
                                <label class="toggle-item">
                                    <span><i class="fas fa-envelope"></i> Email Alerts</span>
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                                <p>Receive daily summary of new leads and follow-ups.</p>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-item">
                                    <span><i class="fas fa-bell"></i> Push Notifications</span>
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                                <p>Stay updated with instant telecaller performance alerts.</p>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-item">
                                    <span><i class="fas fa-mobile-alt"></i> SMS Alerts</span>
                                    <input type="checkbox">
                                    <span class="toggle-slider"></span>
                                </label>
                                <p>Get SMS reminders for upcoming site visits.</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Telecaller Access Control</h2>
                    <a href="#" class="view-all-btn">Manage Roles</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Telecaller</th>
                                    <th>Email</th>
                                    <th>Assigned Leads</th>
                                    <th>Access Level</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Priya Sharma</td>
                                    <td>priya@kansalgroup.com</td>
                                    <td>45</td>
                                    <td><span class="badge badge-success">Full</span></td>
                                    <td><span class="badge badge-success">Active</span></td>
                                    <td>
                                        <button class="btn-icon" title="Edit Access"><i class="fas fa-user-cog"></i></button>
                                        <button class="btn-icon" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Amit Singh</td>
                                    <td>amit@kansalgroup.com</td>
                                    <td>38</td>
                                    <td><span class="badge badge-warning">Limited</span></td>
                                    <td><span class="badge badge-success">Active</span></td>
                                    <td>
                                        <button class="btn-icon" title="Edit Access"><i class="fas fa-user-cog"></i></button>
                                        <button class="btn-icon" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Rohit Verma</td>
                                    <td>rohit@kansalgroup.com</td>
                                    <td>32</td>
                                    <td><span class="badge badge-warning">Limited</span></td>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                    <td>
                                        <button class="btn-icon" title="Edit Access"><i class="fas fa-user-cog"></i></button>
                                        <button class="btn-icon" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

