<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in
requireLogin();

$activePage = 'reports';
$pageTitle = 'Reports';
$breadcrumb = 'Home / Reports';

// Get logged in user
$loggedInUser = getLoggedInUser();
$userRole = $loggedInUser['role'] ?? 'Administrator';
$userId = $loggedInUser['id'] ?? 0;

// Check if user is Telecaller - if yes, show only assigned leads
$isTelecaller = ($userRole == 'Telecaller');
$assignedFilter = $isTelecaller ? " AND assigned_to = $userId" : "";

// Get database connection
$conn = getDBConnection();

// Calculate Conversion Rate
$totalLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE 1=1 $assignedFilter")->fetch_assoc()['total'] ?? 0;
$convertedLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Converted' $assignedFilter")->fetch_assoc()['total'] ?? 0;
$conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

// Last month conversion rate for comparison
$lastMonth = date('Y-m', strtotime('-1 month'));
$lastMonthLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth' $assignedFilter")->fetch_assoc()['total'] ?? 0;
$lastMonthConverted = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Converted' AND DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth' $assignedFilter")->fetch_assoc()['total'] ?? 0;
$lastMonthRate = $lastMonthLeads > 0 ? round(($lastMonthConverted / $lastMonthLeads) * 100, 1) : 0;
$conversionChange = $conversionRate - $lastMonthRate;

// Revenue Generated (estimated - assuming average deal size)
$avgDealSize = 720000; // ₹7.2 L average
$revenue = $convertedLeads * $avgDealSize;
$currentMonth = date('Y-m');
$currentMonthConverted = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Converted' AND DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth' $assignedFilter")->fetch_assoc()['total'] ?? 0;
$currentMonthRevenue = $currentMonthConverted * $avgDealSize;
$lastMonthRevenue = $lastMonthConverted * $avgDealSize;
$revenueChange = $currentMonthRevenue - $lastMonthRevenue;

// Site Visits Confirmed
$siteVisits = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Site Visit' $assignedFilter")->fetch_assoc()['total'] ?? 0;
$currentMonthSiteVisits = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Site Visit' AND DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth' $assignedFilter")->fetch_assoc()['total'] ?? 0;
$lastMonthSiteVisits = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Site Visit' AND DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth' $assignedFilter")->fetch_assoc()['total'] ?? 0;
$siteVisitChange = $currentMonthSiteVisits - $lastMonthSiteVisits;

// Conversion Performance by Property Type
$propertyTypes = ['3BHK Flats', 'Plots', 'Farmhouse'];
$conversionData = [];
foreach ($propertyTypes as $type) {
    $total = $conn->query("SELECT COUNT(*) as total FROM leads WHERE property_type LIKE '%$type%' $assignedFilter")->fetch_assoc()['total'] ?? 0;
    $converted = $conn->query("SELECT COUNT(*) as total FROM leads WHERE property_type LIKE '%$type%' AND status = 'Converted' $assignedFilter")->fetch_assoc()['total'] ?? 0;
    $rate = $total > 0 ? round(($converted / $total) * 100) : 0;
    $conversionData[$type] = ['total' => $total, 'converted' => $converted, 'rate' => $rate];
}

// Monthly Lead Growth (Last 6 months)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M', strtotime("-$i months"));
    $count = $conn->query("SELECT COUNT(*) as total FROM leads WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' $assignedFilter")->fetch_assoc()['total'] ?? 0;
    $monthlyData[] = ['month' => $monthName, 'count' => $count];
}
$maxLeads = max(array_column($monthlyData, 'count'));
$maxLeads = $maxLeads > 0 ? $maxLeads : 100; // Prevent division by zero

// Lead Source Breakdown
$leadSources = $conn->query("SELECT DISTINCT lead_source FROM leads WHERE lead_source IS NOT NULL AND lead_source != '' $assignedFilter");
$sourceData = [];
$totalSourceLeads = 0;
while ($row = $leadSources->fetch_assoc()) {
    $source = $row['lead_source'];
    $count = $conn->query("SELECT COUNT(*) as total FROM leads WHERE lead_source = '$source' $assignedFilter")->fetch_assoc()['total'] ?? 0;
    $sourceData[$source] = $count;
    $totalSourceLeads += $count;
}

// Calculate percentages and sort
foreach ($sourceData as $source => $count) {
    $sourceData[$source] = [
        'count' => $count,
        'percentage' => $totalSourceLeads > 0 ? round(($count / $totalSourceLeads) * 100) : 0
    ];
}
arsort($sourceData);

// Telecaller Performance
if ($isTelecaller) {
    // Show only logged in telecaller's performance
    $telecallersQuery = "SELECT u.id, u.name, 
                         COUNT(l.id) as total_leads,
                         SUM(CASE WHEN l.status = 'Converted' THEN 1 ELSE 0 END) as converted_leads
                         FROM users u
                         LEFT JOIN leads l ON l.assigned_to = u.id
                         WHERE u.id = $userId
                         GROUP BY u.id, u.name";
} else {
    // Show all telecallers for admin/manager
    $telecallersQuery = "SELECT u.id, u.name, 
                         COUNT(l.id) as total_leads,
                         SUM(CASE WHEN l.status = 'Converted' THEN 1 ELSE 0 END) as converted_leads
                         FROM users u
                         LEFT JOIN leads l ON l.assigned_to = u.id
                         WHERE u.role = 'Telecaller' AND u.status = 'Active'
                         GROUP BY u.id, u.name
                         ORDER BY total_leads DESC
                         LIMIT 5";
}
$telecallersResult = $conn->query($telecallersQuery);
$telecallerPerformance = [];
while ($row = $telecallersResult->fetch_assoc()) {
    $performance = $row['total_leads'] > 0 ? round(($row['converted_leads'] / $row['total_leads']) * 100) : 0;
    $telecallerPerformance[] = [
        'name' => $row['name'],
        'initials' => strtoupper(substr($row['name'], 0, 1) . substr(explode(' ', $row['name'])[1] ?? '', 0, 1)),
        'total_leads' => $row['total_leads'],
        'converted' => $row['converted_leads'],
        'performance' => $performance
    ];
}

// Financial Summary (Last 4 months)
$financialSummary = [];
for ($i = 3; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('F Y', strtotime("-$i months"));
    $totalLeadsMonth = $conn->query("SELECT COUNT(*) as total FROM leads WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' $assignedFilter")->fetch_assoc()['total'] ?? 0;
    $convertedMonth = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Converted' AND DATE_FORMAT(created_at, '%Y-%m') = '$month' $assignedFilter")->fetch_assoc()['total'] ?? 0;
    $revenueMonth = $convertedMonth * $avgDealSize;
    $siteVisitsMonth = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Site Visit' AND DATE_FORMAT(created_at, '%Y-%m') = '$month' $assignedFilter")->fetch_assoc()['total'] ?? 0;
    $avgDealSizeMonth = $convertedMonth > 0 ? round($revenueMonth / $convertedMonth) : 0;
    
    $financialSummary[] = [
        'month' => $monthName,
        'total_leads' => $totalLeadsMonth,
        'conversions' => $convertedMonth,
        'revenue' => $revenueMonth,
        'avg_deal' => $avgDealSizeMonth,
        'site_visits' => $siteVisitsMonth
    ];
}

// Activity Highlights (Recent converted leads and important activities)
$activitiesQuery = "SELECT l.*, u.name as telecaller_name
                    FROM leads l
                    LEFT JOIN users u ON l.assigned_to = u.id
                    WHERE l.status = 'Converted' $assignedFilter
                    ORDER BY l.updated_at DESC
                    LIMIT 3";
$activitiesResult = $conn->query($activitiesQuery);
$activities = [];
while ($row = $activitiesResult->fetch_assoc()) {
    $activities[] = $row;
}

$conn->close();
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
                        <p class="stat-number"><?php echo $conversionRate; ?>%</p>
                        <span class="stat-change <?php echo $conversionChange >= 0 ? 'positive' : ''; ?>">
                            <?php echo $conversionChange >= 0 ? '+' : ''; ?><?php echo number_format($conversionChange, 1); ?>% vs last month
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Revenue Generated</h3>
                        <p class="stat-number"><?php echo formatCurrency($revenue); ?></p>
                        <span class="stat-change <?php echo $revenueChange >= 0 ? 'positive' : ''; ?>">
                            <?php echo $revenueChange >= 0 ? '+' : ''; ?><?php echo formatCurrency($revenueChange); ?> this month
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Site Visits Confirmed</h3>
                        <p class="stat-number"><?php echo number_format($siteVisits); ?></p>
                        <span class="stat-change <?php echo $siteVisitChange >= 0 ? 'positive' : ''; ?>">
                            <?php echo $siteVisitChange >= 0 ? '+' : ''; ?><?php echo $siteVisitChange; ?> scheduled
                        </span>
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
                            <?php 
                            $colors = ['var(--primary-color)', 'var(--success-color)', 'var(--warning-color)'];
                            $i = 0;
                            foreach ($conversionData as $type => $data): 
                            ?>
                                <div class="progress-item">
                                    <div class="progress-label">
                                        <span><?php echo htmlspecialchars($type); ?></span>
                                        <span><?php echo $data['rate']; ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress" style="--progress-color: <?php echo $colors[$i % 3]; ?>; --progress-value: <?php echo $data['rate']; ?>%;"></div>
                                    </div>
                                </div>
                            <?php 
                            $i++;
                            endforeach; 
                            ?>
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
                            <?php foreach ($monthlyData as $data): 
                                $barHeight = $maxLeads > 0 ? round(($data['count'] / $maxLeads) * 100) : 0;
                            ?>
                                <div class="bar" style="--bar-height: <?php echo $barHeight; ?>%;" data-month="<?php echo $data['month']; ?>" data-value="<?php echo $data['count']; ?>"></div>
                            <?php endforeach; ?>
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
                            <?php 
                            $sourceIcons = [
                                'Website' => 'fa-globe',
                                'Campaign' => 'fa-bullhorn',
                                'Referral' => 'fa-user-friends',
                                'Walk-in' => 'fa-store',
                                'Social Media' => 'fa-share-alt',
                                'Other' => 'fa-ellipsis-h'
                            ];
                            $sourceColors = ['primary', 'info', 'warning', 'danger', 'success', 'secondary'];
                            $i = 0;
                            foreach ($sourceData as $source => $data): 
                                $icon = 'fa-ellipsis-h';
                                foreach ($sourceIcons as $key => $iconClass) {
                                    if (stripos($source, $key) !== false) {
                                        $icon = $iconClass;
                                        break;
                                    }
                                }
                                $colorClass = $sourceColors[$i % count($sourceColors)];
                            ?>
                                <li>
                                    <div class="segment-icon <?php echo $colorClass; ?>"><i class="fas <?php echo $icon; ?>"></i></div>
                                    <div class="segment-info">
                                        <h4><?php echo htmlspecialchars($source); ?></h4>
                                        <p><?php echo $data['count']; ?> leads · <?php echo $data['percentage']; ?>%</p>
                                    </div>
                                    <span class="badge badge-success"><?php echo $data['percentage']; ?>%</span>
                                </li>
                            <?php 
                            $i++;
                            endforeach; 
                            ?>
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
                            <?php if (empty($telecallerPerformance)): ?>
                                <p style="text-align: center; padding: 20px; color: var(--text-secondary);">No telecaller data available.</p>
                            <?php else: ?>
                                <?php foreach ($telecallerPerformance as $tc): ?>
                                    <div class="telecaller-item">
                                        <div class="telecaller-avatar"><?php echo $tc['initials']; ?></div>
                                        <div class="telecaller-info">
                                            <h4><?php echo htmlspecialchars($tc['name']); ?></h4>
                                            <p><?php echo $tc['total_leads']; ?> Leads · <?php echo $tc['converted']; ?> Closures</p>
                                        </div>
                                        <div class="telecaller-score">
                                            <span class="score-badge"><?php echo $tc['performance']; ?>%</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
                                <?php if (empty($financialSummary)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                            <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                            <p>No financial data available.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($financialSummary as $summary): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($summary['month']); ?></td>
                                            <td><?php echo number_format($summary['total_leads']); ?></td>
                                            <td><?php echo number_format($summary['conversions']); ?></td>
                                            <td><?php echo formatCurrency($summary['revenue']); ?></td>
                                            <td><?php echo formatCurrency($summary['avg_deal']); ?></td>
                                            <td><?php echo number_format($summary['site_visits']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                        <?php if (empty($activities)): ?>
                            <li style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <i class="fas fa-handshake" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                <p>No recent activities found.</p>
                            </li>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                                <li>
                                    <div class="timeline-icon success"><i class="fas fa-handshake"></i></div>
                                    <div class="timeline-content">
                                        <h4>Deal closed for <?php echo htmlspecialchars($activity['property_type'] ?? 'Property'); ?></h4>
                                        <p><?php echo htmlspecialchars($activity['telecaller_name'] ?? 'Unknown'); ?> closed new <?php echo htmlspecialchars($activity['property_type'] ?? 'property'); ?> sale.</p>
                                        <span><?php echo formatDate($activity['updated_at']); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
