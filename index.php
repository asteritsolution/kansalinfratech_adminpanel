<?php
$activePage = 'dashboard';
$pageTitle = 'Dashboard';
$breadcrumb = 'Home / Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kansal Admin Panel</title>
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
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Leads</h3>
                        <p class="stat-number">1,234</p>
                        <span class="stat-change positive">+12% from last month</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Leads</h3>
                        <p class="stat-number">856</p>
                        <span class="stat-change positive">+8% from last month</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Plots Available</h3>
                        <p class="stat-number">342</p>
                        <span class="stat-change">Ready to sell</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Flats Available</h3>
                        <p class="stat-number">128</p>
                        <span class="stat-change">3BHK & Others</span>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Grid -->
            <div class="content-grid">
                <!-- Recent Leads -->
                <div class="content-card">
                    <div class="card-header">
                        <h2>Recent Leads</h2>
                        <a href="#" class="view-all-btn">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Property Type</th>
                                        <th>Status</th>
                                        <th>Telecaller</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>#1001</td>
                                        <td>Rajesh Kumar</td>
                                        <td>9876543210</td>
                                        <td>3BHK Flat</td>
                                        <td><span class="badge badge-success">Active</span></td>
                                        <td>Priya Sharma</td>
                                        <td>2024-01-15</td>
                                        <td>
                                            <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#1002</td>
                                        <td>Sunita Devi</td>
                                        <td>9876543211</td>
                                        <td>Plot</td>
                                        <td><span class="badge badge-warning">Pending</span></td>
                                        <td>Amit Singh</td>
                                        <td>2024-01-14</td>
                                        <td>
                                            <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#1003</td>
                                        <td>Vikram Mehta</td>
                                        <td>9876543212</td>
                                        <td>Farmhouse</td>
                                        <td><span class="badge badge-success">Active</span></td>
                                        <td>Priya Sharma</td>
                                        <td>2024-01-13</td>
                                        <td>
                                            <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#1004</td>
                                        <td>Anjali Patel</td>
                                        <td>9876543213</td>
                                        <td>3BHK Flat</td>
                                        <td><span class="badge badge-danger">Closed</span></td>
                                        <td>Rohit Verma</td>
                                        <td>2024-01-12</td>
                                        <td>
                                            <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#1005</td>
                                        <td>Mohit Agarwal</td>
                                        <td>9876543214</td>
                                        <td>Plot</td>
                                        <td><span class="badge badge-success">Active</span></td>
                                        <td>Amit Singh</td>
                                        <td>2024-01-11</td>
                                        <td>
                                            <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Team Performance -->
            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Telecaller Performance</h2>
                    </div>
                    <div class="card-body">
                        <div class="telecaller-list">
                            <div class="telecaller-item">
                                <div class="telecaller-avatar">PS</div>
                                <div class="telecaller-info">
                                    <h4>Priya Sharma</h4>
                                    <p>45 Leads | 12 Active</p>
                                </div>
                                <div class="telecaller-score">
                                    <span class="score-badge">85%</span>
                                </div>
                            </div>
                            <div class="telecaller-item">
                                <div class="telecaller-avatar">AS</div>
                                <div class="telecaller-info">
                                    <h4>Amit Singh</h4>
                                    <p>38 Leads | 10 Active</p>
                                </div>
                                <div class="telecaller-score">
                                    <span class="score-badge">78%</span>
                                </div>
                            </div>
                            <div class="telecaller-item">
                                <div class="telecaller-avatar">RV</div>
                                <div class="telecaller-info">
                                    <h4>Rohit Verma</h4>
                                    <p>32 Leads | 8 Active</p>
                                </div>
                                <div class="telecaller-score">
                                    <span class="score-badge">72%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

