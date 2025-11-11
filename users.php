<?php
$activePage = 'users';
$pageTitle = 'User Management';
$breadcrumb = 'Home / Users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Kansal Admin Panel</title>
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

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Users</h3>
                        <p class="stat-number">24</p>
                        <span class="stat-change positive">+3 this month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Telecallers</h3>
                        <p class="stat-number">12</p>
                        <span class="stat-change positive">Active</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Managers</h3>
                        <p class="stat-number">6</p>
                        <span class="stat-change">2 on leave</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Pending Invites</h3>
                        <p class="stat-number">4</p>
                        <span class="stat-change">Awaiting approval</span>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Add New User</h2>
                        <a href="#" class="view-all-btn"><i class="fas fa-upload"></i> Bulk Upload</a>
                    </div>
                    <div class="card-body">
                        <form class="settings-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="userName">Full Name</label>
                                    <input type="text" id="userName" placeholder="Enter full name">
                                </div>
                                <div class="form-group">
                                    <label for="userEmail">Email Address</label>
                                    <input type="email" id="userEmail" placeholder="user@example.com">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="userPhone">Phone Number</label>
                                    <input type="text" id="userPhone" placeholder="+91 98xxxxxxx">
                                </div>
                                <div class="form-group">
                                    <label for="userRole">Role</label>
                                    <select id="userRole">
                                        <option value="telecaller">Telecaller</option>
                                        <option value="manager">Manager</option>
                                        <option value="admin">Administrator</option>
                                        <option value="analyst">Analyst</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="userPassword">Temporary Password</label>
                                    <input type="text" id="userPassword" placeholder="Auto-generated">
                                </div>
                                <div class="form-group">
                                    <label for="userTeam">Team</label>
                                    <select id="userTeam">
                                        <option>North Zone</option>
                                        <option>South Zone</option>
                                        <option>Plots Team</option>
                                        <option>Flats Team</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create User</button>
                                <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>User Filters</h2>
                        <a href="#" class="view-all-btn">Reset</a>
                    </div>
                    <div class="card-body">
                        <div class="chip-group">
                            <span class="chip active"><i class="fas fa-layer-group"></i> All</span>
                            <span class="chip"><i class="fas fa-user-check"></i> Active</span>
                            <span class="chip"><i class="fas fa-user-clock"></i> Pending</span>
                            <span class="chip"><i class="fas fa-user-lock"></i> Suspended</span>
                        </div>
                        <form class="filter-form">
                            <div class="filter-row">
                                <div class="form-group">
                                    <label for="filterRole">Role</label>
                                    <select id="filterRole">
                                        <option>All Roles</option>
                                        <option>Telecaller</option>
                                        <option>Manager</option>
                                        <option>Administrator</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterTeam">Team</label>
                                    <select id="filterTeam">
                                        <option>All Teams</option>
                                        <option>Plots</option>
                                        <option>Flats</option>
                                        <option>Farmhouse</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterStatus">Status</label>
                                    <select id="filterStatus">
                                        <option>All Status</option>
                                        <option>Active</option>
                                        <option>Pending</option>
                                        <option>Suspended</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterDateUser">Joined</label>
                                    <select id="filterDateUser">
                                        <option>This Month</option>
                                        <option>Last 3 Months</option>
                                        <option>Last 6 Months</option>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                                <button type="button" class="btn btn-secondary"><i class="fas fa-file-export"></i> Export</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>User Directory</h2>
                    <div class="report-actions">
                        <a href="#" class="btn btn-secondary"><i class="fas fa-envelope"></i> Send Invite</a>
                        <a href="#" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Multiple</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Team</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="table-user">
                                            <div class="avatar-circle">PS</div>
                                            <div>
                                                <h4>Priya Sharma</h4>
                                                <span>Telecaller Lead</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>priya@kansalgroup.com</td>
                                    <td>+91 98765 43210</td>
                                    <td>
                                        <select class="role-select">
                                            <option selected>Telecaller</option>
                                            <option>Manager</option>
                                            <option>Administrator</option>
                                        </select>
                                    </td>
                                    <td>Flats Team</td>
                                    <td><span class="badge badge-success">Active</span></td>
                                    <td>12 Mar 2023</td>
                                    <td class="table-actions">
                                        <button class="btn-icon" title="Permissions"><i class="fas fa-user-shield"></i></button>
                                        <button class="btn-icon" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                        <button class="btn-icon" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="table-user">
                                            <div class="avatar-circle">AS</div>
                                            <div>
                                                <h4>Amit Singh</h4>
                                                <span>Senior Telecaller</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>amit@kansalgroup.com</td>
                                    <td>+91 98765 43211</td>
                                    <td>
                                        <select class="role-select">
                                            <option>Telecaller</option>
                                            <option selected>Manager</option>
                                            <option>Administrator</option>
                                        </select>
                                    </td>
                                    <td>Plots Team</td>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                    <td>05 Jul 2023</td>
                                    <td class="table-actions">
                                        <button class="btn-icon" title="Permissions"><i class="fas fa-user-shield"></i></button>
                                        <button class="btn-icon" title="Activate"><i class="fas fa-user-check"></i></button>
                                        <button class="btn-icon" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="table-user">
                                            <div class="avatar-circle">RV</div>
                                            <div>
                                                <h4>Rohit Verma</h4>
                                                <span>Telecaller</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>rohit@kansalgroup.com</td>
                                    <td>+91 98765 43212</td>
                                    <td>
                                        <select class="role-select">
                                            <option selected>Telecaller</option>
                                            <option>Manager</option>
                                            <option>Administrator</option>
                                        </select>
                                    </td>
                                    <td>Farmhouse Team</td>
                                    <td><span class="badge badge-success">Active</span></td>
                                    <td>18 Sep 2023</td>
                                    <td class="table-actions">
                                        <button class="btn-icon" title="Permissions"><i class="fas fa-user-shield"></i></button>
                                        <button class="btn-icon" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                        <button class="btn-icon" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="table-user">
                                            <div class="avatar-circle">NK</div>
                                            <div>
                                                <h4>Neha Kapoor</h4>
                                                <span>Sales Manager</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>neha@kansalgroup.com</td>
                                    <td>+91 98765 43213</td>
                                    <td>
                                        <select class="role-select">
                                            <option>Telecaller</option>
                                            <option selected>Manager</option>
                                            <option>Administrator</option>
                                        </select>
                                    </td>
                                    <td>Luxury Villas</td>
                                    <td><span class="badge badge-danger">Suspended</span></td>
                                    <td>28 Dec 2022</td>
                                    <td class="table-actions">
                                        <button class="btn-icon" title="Permissions"><i class="fas fa-user-shield"></i></button>
                                        <button class="btn-icon" title="Activate"><i class="fas fa-user-check"></i></button>
                                        <button class="btn-icon" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Recent User Activity</h2>
                    <a href="#" class="view-all-btn">View Logs</a>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li>
                            <div class="timeline-icon success"><i class="fas fa-user-plus"></i></div>
                            <div class="timeline-content">
                                <h4>New user invited</h4>
                                <p>Invite sent to <strong>simran@kansalgroup.com</strong> for Telecaller role.</p>
                                <span>15 Jan 2024 · 09:45 AM</span>
                            </div>
                        </li>
                        <li>
                            <div class="timeline-icon info"><i class="fas fa-user-tag"></i></div>
                            <div class="timeline-content">
                                <h4>Role updated</h4>
                                <p><strong>Amit Singh</strong> promoted from Telecaller to Manager.</p>
                                <span>14 Jan 2024 · 06:10 PM</span>
                            </div>
                        </li>
                        <li>
                            <div class="timeline-icon warning"><i class="fas fa-user-lock"></i></div>
                            <div class="timeline-content">
                                <h4>Account suspended</h4>
                                <p>Access restricted for <strong>neha@kansalgroup.com</strong>.</p>
                                <span>14 Jan 2024 · 02:30 PM</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

