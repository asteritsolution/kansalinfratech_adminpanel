<?php
$activePage = 'all-leads';
$pageTitle = 'All Leads';
$breadcrumb = 'Home / All Leads';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Leads - Kansal Admin Panel</title>
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
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Leads</h3>
                        <p class="stat-number">1,248</p>
                        <span class="stat-change positive">+38 this week</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Qualified</h3>
                        <p class="stat-number">756</p>
                        <span class="stat-change positive">+18 from last week</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-phone-volume"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Follow-Ups</h3>
                        <p class="stat-number">218</p>
                        <span class="stat-change">Scheduled today</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Site Visits</h3>
                        <p class="stat-number">96</p>
                        <span class="stat-change positive">+11 confirmed</span>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Add New Lead</h2>
                        <a href="#" class="view-all-btn"><i class="fas fa-cloud-upload-alt"></i> Import Leads</a>
                    </div>
                    <div class="card-body">
                        <form class="settings-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="leadName">Lead Name</label>
                                    <input type="text" id="leadName" placeholder="Enter full name">
                                </div>
                                <div class="form-group">
                                    <label for="leadPhone">Phone Number</label>
                                    <input type="text" id="leadPhone" placeholder="+91 98xxxxxxx">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="leadEmail">Email</label>
                                    <input type="email" id="leadEmail" placeholder="name@example.com">
                                </div>
                                <div class="form-group">
                                    <label for="leadType">Interested In</label>
                                    <select id="leadType">
                                        <option>3BHK Flat</option>
                                        <option>Luxury Villa</option>
                                        <option>Farmhouse Plot</option>
                                        <option>Commercial Plot</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="leadSource">Lead Source</label>
                                    <select id="leadSource">
                                        <option>Website</option>
                                        <option>Facebook Ads</option>
                                        <option>Google Ads</option>
                                        <option>WhatsApp Campaign</option>
                                        <option>Referral</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="leadOwner">Assign To</label>
                                    <select id="leadOwner">
                                        <option>Priya Sharma</option>
                                        <option>Amit Singh</option>
                                        <option>Rohit Verma</option>
                                        <option>Neha Kapoor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="leadBudget">Budget Range</label>
                                    <input type="text" id="leadBudget" placeholder="₹50L - ₹1.5Cr">
                                </div>
                                <div class="form-group">
                                    <label for="followUpDate">Next Follow Up</label>
                                    <input type="date" id="followUpDate">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="leadNotes">Notes</label>
                                <textarea id="leadNotes" rows="3" placeholder="Add brief notes about this lead"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Lead</button>
                                <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Clear</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>Lead Filters</h2>
                        <a href="#" class="view-all-btn">Reset Filters</a>
                    </div>
                    <div class="card-body">
                        <div class="chip-group">
                            <span class="chip active"><i class="fas fa-layer-group"></i> All</span>
                            <span class="chip"><i class="fas fa-check-circle"></i> Qualified</span>
                            <span class="chip"><i class="fas fa-phone"></i> Follow Up</span>
                            <span class="chip"><i class="fas fa-calendar-check"></i> Site Visit</span>
                            <span class="chip"><i class="fas fa-file-contract"></i> Closed Won</span>
                            <span class="chip"><i class="fas fa-times-circle"></i> Closed Lost</span>
                        </div>
                        <form class="filter-form">
                            <div class="filter-row">
                                <div class="form-group">
                                    <label for="filterDate">Date Range</label>
                                    <select id="filterDate">
                                        <option>This Week</option>
                                        <option>This Month</option>
                                        <option>Last 3 Months</option>
                                        <option>Custom Range</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterProject">Project</label>
                                    <select id="filterProject">
                                        <option>All Projects</option>
                                        <option>Kansal Heights</option>
                                        <option>Green Acres Plotting</option>
                                        <option>Farm Villas</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterBudget">Budget</label>
                                    <select id="filterBudget">
                                        <option>All</option>
                                        <option>₹30L - ₹60L</option>
                                        <option>₹60L - ₹1Cr</option>
                                        <option>₹1Cr+</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterOwner">Telecaller</label>
                                    <select id="filterOwner">
                                        <option>All Telecallers</option>
                                        <option>Priya Sharma</option>
                                        <option>Amit Singh</option>
                                        <option>Rohit Verma</option>
                                        <option>Neha Kapoor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                                <button type="button" class="btn btn-secondary"><i class="fas fa-file-export"></i> Export Leads</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Lead List</h2>
                    <div class="report-actions">
                        <a href="#" class="btn btn-secondary"><i class="fas fa-filter"></i> Advanced Filters</a>
                        <a href="#" class="btn btn-primary"><i class="fas fa-plus-circle"></i> New Site Visit</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Lead Name</th>
                                    <th>Contact</th>
                                    <th>Telecaller</th>
                                    <th>Property</th>
                                    <th>Status</th>
                                    <th>Source</th>
                                    <th>Follow Up</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#A-1208</td>
                                    <td>Prateek Arora</td>
                                    <td>
                                        <div class="table-contact">
                                            <span><i class="fas fa-phone"></i> +91 98765 32018</span>
                                            <span><i class="fas fa-envelope"></i> prateek@email.com</span>
                                        </div>
                                    </td>
                                    <td>Priya Sharma</td>
                                    <td>Skyline 3BHK</td>
                                    <td><span class="badge badge-success">Qualified</span></td>
                                    <td>Facebook Ads</td>
                                    <td>16 Jan 2024</td>
                                    <td class="table-actions">
                                        <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon" title="Call"><i class="fas fa-phone-alt"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#A-1209</td>
                                    <td>Divya Bansal</td>
                                    <td>
                                        <div class="table-contact">
                                            <span><i class="fas fa-phone"></i> +91 98765 32019</span>
                                            <span><i class="fas fa-envelope"></i> divya@email.com</span>
                                        </div>
                                    </td>
                                    <td>Amit Singh</td>
                                    <td>Farmhouse Plot</td>
                                    <td><span class="badge badge-warning">Follow Up</span></td>
                                    <td>Google Ads</td>
                                    <td>18 Jan 2024</td>
                                    <td class="table-actions">
                                        <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon" title="Schedule"><i class="fas fa-calendar-plus"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#A-1210</td>
                                    <td>Aakash Mehra</td>
                                    <td>
                                        <div class="table-contact">
                                            <span><i class="fas fa-phone"></i> +91 98765 32020</span>
                                            <span><i class="fas fa-envelope"></i> aakash@email.com</span>
                                        </div>
                                    </td>
                                    <td>Neha Kapoor</td>
                                    <td>Luxury Villa</td>
                                    <td><span class="badge badge-info">Site Visit</span></td>
                                    <td>Referral</td>
                                    <td>19 Jan 2024</td>
                                    <td class="table-actions">
                                        <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon" title="Close"><i class="fas fa-file-signature"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#A-1211</td>
                                    <td>Rahul Rajput</td>
                                    <td>
                                        <div class="table-contact">
                                            <span><i class="fas fa-phone"></i> +91 98765 32021</span>
                                            <span><i class="fas fa-envelope"></i> rahul@email.com</span>
                                        </div>
                                    </td>
                                    <td>Rohit Verma</td>
                                    <td>Commercial Plot</td>
                                    <td><span class="badge badge-danger">Closed Lost</span></td>
                                    <td>Walk-in</td>
                                    <td>12 Jan 2024</td>
                                    <td class="table-actions">
                                        <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon" title="Reassign"><i class="fas fa-random"></i></button>
                                        <button class="btn-icon" title="Archive"><i class="fas fa-archive"></i></button>
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

