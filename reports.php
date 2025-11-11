<?php
$activePage = 'reports';
$pageTitle = 'Reports';
$breadcrumb = 'Home / Reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Kansal Admin Panel</title>
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
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Conversion Rate</h3>
                        <p class="stat-number">32.4%</p>
                        <span class="stat-change positive">+4.2% vs last month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Revenue Generated</h3>
                        <p class="stat-number">₹2.8 Cr</p>
                        <span class="stat-change positive">+₹35L this month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Site Visits Confirmed</h3>
                        <p class="stat-number">189</p>
                        <span class="stat-change positive">+26 scheduled</span>
                    </div>
                </div>
            </div>

            <div class="report-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Conversion Performance</h2>
                        <a href="#" class="view-all-btn">Download Report</a>
                    </div>
                    <div class="card-body">
                        <div class="progress-list">
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>3BHK Flats</span>
                                    <span>68%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="--progress-color: var(--primary-color); --progress-value: 68%;"></div>
                                </div>
                            </div>
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Plots</span>
                                    <span>54%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="--progress-color: var(--success-color); --progress-value: 54%;"></div>
                                </div>
                            </div>
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Farmhouse</span>
                                    <span>31%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="--progress-color: var(--warning-color); --progress-value: 31%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>Monthly Lead Growth</h2>
                        <a href="#" class="view-all-btn">View Trends</a>
                    </div>
                    <div class="card-body">
                        <div class="bar-chart">
                            <div class="bar" style="--bar-height: 48%;" data-month="Aug" data-value="312"></div>
                            <div class="bar" style="--bar-height: 60%;" data-month="Sep" data-value="389"></div>
                            <div class="bar" style="--bar-height: 72%;" data-month="Oct" data-value="456"></div>
                            <div class="bar" style="--bar-height: 80%;" data-month="Nov" data-value="502"></div>
                            <div class="bar" style="--bar-height: 92%;" data-month="Dec" data-value="578"></div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>Lead Source Breakdown</h2>
                        <a href="#" class="view-all-btn">Manage Sources</a>
                    </div>
                    <div class="card-body">
                        <ul class="segment-list">
                            <li>
                                <div class="segment-icon primary"><i class="fas fa-globe"></i></div>
                                <div class="segment-info">
                                    <h4>Website Landing Pages</h4>
                                    <p>248 leads · 38%</p>
                                </div>
                                <span class="badge badge-success">+12%</span>
                            </li>
                            <li>
                                <div class="segment-icon info"><i class="fas fa-bullhorn"></i></div>
                                <div class="segment-info">
                                    <h4>Campaigns & Ads</h4>
                                    <p>186 leads · 28%</p>
                                </div>
                                <span class="badge badge-success">+6%</span>
                            </li>
                            <li>
                                <div class="segment-icon warning"><i class="fas fa-user-friends"></i></div>
                                <div class="segment-info">
                                    <h4>Referral Network</h4>
                                    <p>128 leads · 20%</p>
                                </div>
                                <span class="badge badge-warning">Stable</span>
                            </li>
                            <li>
                                <div class="segment-icon danger"><i class="fas fa-store"></i></div>
                                <div class="segment-info">
                                    <h4>Walk-in Enquiries</h4>
                                    <p>92 leads · 14%</p>
                                </div>
                                <span class="badge badge-warning">-2%</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>Telecaller Performance</h2>
                        <a href="#" class="view-all-btn">View Team</a>
                    </div>
                    <div class="card-body">
                        <div class="telecaller-list compact">
                            <div class="telecaller-item">
                                <div class="telecaller-avatar">PS</div>
                                <div class="telecaller-info">
                                    <h4>Priya Sharma</h4>
                                    <p>45 Leads · 12 Closures</p>
                                </div>
                                <div class="telecaller-score">
                                    <span class="score-badge">92%</span>
                                </div>
                            </div>
                            <div class="telecaller-item">
                                <div class="telecaller-avatar">AS</div>
                                <div class="telecaller-info">
                                    <h4>Amit Singh</h4>
                                    <p>38 Leads · 9 Closures</p>
                                </div>
                                <div class="telecaller-score">
                                    <span class="score-badge">81%</span>
                                </div>
                            </div>
                            <div class="telecaller-item">
                                <div class="telecaller-avatar">RV</div>
                                <div class="telecaller-info">
                                    <h4>Rohit Verma</h4>
                                    <p>32 Leads · 7 Closures</p>
                                </div>
                                <div class="telecaller-score">
                                    <span class="score-badge">74%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Recent Financial Summary</h2>
                    <div class="report-actions">
                        <a href="#" class="btn btn-secondary"><i class="fas fa-file-export"></i> Export CSV</a>
                        <a href="#" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Download PDF</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Total Leads</th>
                                    <th>Conversions</th>
                                    <th>Revenue</th>
                                    <th>Avg. Deal Size</th>
                                    <th>Site Visits</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>December 2023</td>
                                    <td>578</td>
                                    <td>188</td>
                                    <td>₹1.08 Cr</td>
                                    <td>₹7.2 L</td>
                                    <td>236</td>
                                </tr>
                                <tr>
                                    <td>November 2023</td>
                                    <td>502</td>
                                    <td>165</td>
                                    <td>₹95 L</td>
                                    <td>₹6.8 L</td>
                                    <td>210</td>
                                </tr>
                                <tr>
                                    <td>October 2023</td>
                                    <td>456</td>
                                    <td>149</td>
                                    <td>₹88 L</td>
                                    <td>₹6.4 L</td>
                                    <td>198</td>
                                </tr>
                                <tr>
                                    <td>September 2023</td>
                                    <td>389</td>
                                    <td>128</td>
                                    <td>₹72 L</td>
                                    <td>₹5.6 L</td>
                                    <td>175</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Activity Highlights</h2>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li>
                            <div class="timeline-icon success"><i class="fas fa-handshake"></i></div>
                            <div class="timeline-content">
                                <h4>Farmhouse deal closed for ₹2.4 Cr</h4>
                                <p>Priya Sharma closed new farmhouse sale at Chattarpur Farms.</p>
                                <span>15 Jan 2024 · 05:40 PM</span>
                            </div>
                        </li>
                        <li>
                            <div class="timeline-icon info"><i class="fas fa-chart-line"></i></div>
                            <div class="timeline-content">
                                <h4>Monthly performance review published</h4>
                                <p>Dashboard insights shared with all telecallers for Q1 2024 goals.</p>
                                <span>15 Jan 2024 · 11:30 AM</span>
                            </div>
                        </li>
                        <li>
                            <div class="timeline-icon warning"><i class="fas fa-bell"></i></div>
                            <div class="timeline-content">
                                <h4>Site visit reminders scheduled</h4>
                                <p>21 follow-up reminders created for upcoming weekend site tours.</p>
                                <span>14 Jan 2024 · 06:15 PM</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

