<?php
$activePage = 'leads';
$pageTitle = 'Leads Management';
$breadcrumb = 'Home / Leads Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads Management - Kansal Admin Panel</title>
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
                        <h2>Lead Filters</h2>
                        <a href="#" class="view-all-btn">Reset Filters</a>
                    </div>
                    <div class="card-body">
                        <form class="filter-form">
                            <div class="filter-row">
                                <div class="form-group">
                                    <label for="leadStatus">Status</label>
                                    <select id="leadStatus">
                                        <option value="">All Status</option>
                                        <option value="new">New</option>
                                        <option value="active">Active</option>
                                        <option value="followup">Follow Up</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="leadType">Property Type</label>
                                    <select id="leadType">
                                        <option value="">All Types</option>
                                        <option value="plot">Plot</option>
                                        <option value="flat">3BHK Flat</option>
                                        <option value="farmhouse">Farmhouse</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="leadSource">Lead Source</label>
                                    <select id="leadSource">
                                        <option value="">All Sources</option>
                                        <option value="website">Website</option>
                                        <option value="social">Social Media</option>
                                        <option value="referral">Referral</option>
                                        <option value="walkin">Walk-in</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="telecaller">Telecaller</label>
                                    <select id="telecaller">
                                        <option value="">All Telecallers</option>
                                        <option value="priya">Priya Sharma</option>
                                        <option value="amit">Amit Singh</option>
                                        <option value="rohit">Rohit Verma</option>
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

                <div class="content-card">
                    <div class="card-header">
                        <h2>Leads Overview</h2>
                        <a href="#" class="view-all-btn">Create Lead</a>
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
                                        <th>Next Follow-up</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>#L-2045</td>
                                        <td>Ananya Gupta</td>
                                        <td>+91 98765 32010</td>
                                        <td>Priya Sharma</td>
                                        <td>3BHK Flat</td>
                                        <td><span class="badge badge-warning">Follow Up</span></td>
                                        <td>16 Jan 2024</td>
                                        <td>
                                            <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon" title="Notes"><i class="fas fa-sticky-note"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#L-2046</td>
                                        <td>Rahul Mehta</td>
                                        <td>+91 98765 32011</td>
                                        <td>Amit Singh</td>
                                        <td>Premium Plot</td>
                                        <td><span class="badge badge-success">Active</span></td>
                                        <td>15 Jan 2024</td>
                                        <td>
                                            <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon" title="Notes"><i class="fas fa-sticky-note"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#L-2047</td>
                                        <td>Sneha Patel</td>
                                        <td>+91 98765 32012</td>
                                        <td>Rohit Verma</td>
                                        <td>Farmhouse</td>
                                        <td><span class="badge badge-danger">Closed</span></td>
                                        <td>12 Jan 2024</td>
                                        <td>
                                            <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon" title="Notes"><i class="fas fa-sticky-note"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Lead Timeline</h2>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li>
                            <div class="timeline-icon success"><i class="fas fa-plus"></i></div>
                            <div class="timeline-content">
                                <h4>Lead Created</h4>
                                <p>Lead #L-2045 added by Priya Sharma.</p>
                                <span>15 Jan 2024 - 10:30 AM</span>
                            </div>
                        </li>
                        <li>
                            <div class="timeline-icon info"><i class="fas fa-phone-alt"></i></div>
                            <div class="timeline-content">
                                <h4>Call Scheduled</h4>
                                <p>Follow-up call scheduled with Ananya Gupta.</p>
                                <span>15 Jan 2024 - 11:00 AM</span>
                            </div>
                        </li>
                        <li>
                            <div class="timeline-icon warning"><i class="fas fa-user-clock"></i></div>
                            <div class="timeline-content">
                                <h4>Reminder</h4>
                                <p>Reminder set for telecaller to share property brochure.</p>
                                <span>14 Jan 2024 - 05:00 PM</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

