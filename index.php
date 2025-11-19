<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in
requireLogin();

$activePage = 'dashboard';
$pageTitle = 'Dashboard';
$breadcrumb = 'Home / Dashboard';

// Get logged in user
$loggedInUser = getLoggedInUser();
$userRole = $loggedInUser['role'] ?? 'Administrator';
$userId = $loggedInUser['id'] ?? 0;

// Get database connection
$conn = getDBConnection();

// Check if user is Telecaller - if yes, show only assigned leads
$isTelecaller = ($userRole == 'Telecaller');
// Check if user is Site Manager (Analyst) - if yes, show only Site Visit leads
$isSiteManager = ($userRole == 'Site Manager' || $userRole == 'Analyst');

$assignedFilter = $isTelecaller ? " AND l.assigned_to = $userId" : "";
$siteVisitFilter = $isSiteManager ? " AND l.status = 'Site Visit'" : "";
$combinedFilter = $assignedFilter . $siteVisitFilter;

// Fetch Stats
// Total Leads
$totalLeadsQuery = "SELECT COUNT(*) as total FROM leads l WHERE 1=1 $combinedFilter";
$totalLeadsResult = $conn->query($totalLeadsQuery);
$totalLeads = $totalLeadsResult->fetch_assoc()['total'] ?? 0;

// Active Leads
$activeLeadsQuery = "SELECT COUNT(*) as total FROM leads l WHERE status IN ('Active', 'Follow Up', 'Qualified', 'Site Visit') $combinedFilter";
$activeLeadsResult = $conn->query($activeLeadsQuery);
$activeLeads = $activeLeadsResult->fetch_assoc()['total'] ?? 0;

// Plots Available (leads interested in plots)
$plotsQuery = "SELECT COUNT(*) as total FROM leads l WHERE (property_type LIKE '%Plot%' OR property_type LIKE '%plot%') $combinedFilter";
$plotsResult = $conn->query($plotsQuery);
$plotsAvailable = $plotsResult->fetch_assoc()['total'] ?? 0;

// Flats Available (leads interested in flats)
$flatsQuery = "SELECT COUNT(*) as total FROM leads l WHERE (property_type LIKE '%Flat%' OR property_type LIKE '%flat%' OR property_type LIKE '%3BHK%') $combinedFilter";
$flatsResult = $conn->query($flatsQuery);
$flatsAvailable = $flatsResult->fetch_assoc()['total'] ?? 0;

// Calculate percentage change (comparing this month with last month)
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));

$currentMonthLeads = $conn->query("SELECT COUNT(*) as total FROM leads l WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth' $combinedFilter")->fetch_assoc()['total'] ?? 0;
$lastMonthLeads = $conn->query("SELECT COUNT(*) as total FROM leads l WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth' $combinedFilter")->fetch_assoc()['total'] ?? 0;

$percentageChange = 0;
if ($lastMonthLeads > 0) {
    $percentageChange = round((($currentMonthLeads - $lastMonthLeads) / $lastMonthLeads) * 100);
}

// Fetch Recent Leads (Last 5)
$recentLeadsQuery = "SELECT l.*, u.name as telecaller_name 
                     FROM leads l 
                     LEFT JOIN users u ON l.assigned_to = u.id 
                     WHERE 1=1 $combinedFilter
                     ORDER BY l.created_at DESC 
                     LIMIT 5";
$recentLeadsResult = $conn->query($recentLeadsQuery);
$recentLeads = [];
while ($row = $recentLeadsResult->fetch_assoc()) {
    $recentLeads[] = $row;
}

// Fetch Telecaller Performance
// If telecaller, show only own performance, else show all telecallers
if ($isTelecaller) {
    // Show only logged in telecaller's performance
    $telecallerQuery = "SELECT 
                        u.id,
                        u.name,
                        u.email,
                        COUNT(l.id) as total_leads,
                        SUM(CASE WHEN l.status IN ('Active', 'Follow Up', 'Qualified', 'Site Visit') THEN 1 ELSE 0 END) as active_leads,
                        SUM(CASE WHEN l.status = 'Closed Won' THEN 1 ELSE 0 END) as closed_won
                        FROM users u
                        LEFT JOIN leads l ON u.id = l.assigned_to
                        WHERE u.id = $userId
                        GROUP BY u.id, u.name, u.email";
} else {
    // Show all telecallers for admin/manager
    $telecallerQuery = "SELECT 
                        u.id,
                        u.name,
                        u.email,
                        COUNT(l.id) as total_leads,
                        SUM(CASE WHEN l.status IN ('Active', 'Follow Up', 'Qualified', 'Site Visit') THEN 1 ELSE 0 END) as active_leads,
                        SUM(CASE WHEN l.status = 'Closed Won' THEN 1 ELSE 0 END) as closed_won
                        FROM users u
                        LEFT JOIN leads l ON u.id = l.assigned_to
                        WHERE u.role = 'Telecaller' AND u.status = 'Active'
                        GROUP BY u.id, u.name, u.email
                        ORDER BY total_leads DESC
                        LIMIT 5";
}
$telecallerResult = $conn->query($telecallerQuery);
$telecallers = [];
while ($row = $telecallerResult->fetch_assoc()) {
    // Calculate performance percentage (based on closed won / total leads)
    $performance = 0;
    if ($row['total_leads'] > 0) {
        $performance = round(($row['closed_won'] / $row['total_leads']) * 100);
    } else {
        // If no leads, calculate based on active leads
        $performance = $row['active_leads'] > 0 ? 50 : 0;
    }
    $row['performance'] = $performance;
    $telecallers[] = $row;
}

// Fetch recent timeline activities (last 5 leads created)
$timelineFilter = "";
if ($isTelecaller) {
    $timelineFilter = " AND l.assigned_to = $userId";
} elseif ($isSiteManager) {
    $timelineFilter = " AND l.status = 'Site Visit'";
}
$timelineQuery = "SELECT l.*, u.name as created_by_name
                  FROM leads l
                  LEFT JOIN users u ON l.created_by = u.id
                  WHERE 1=1 $timelineFilter
                  ORDER BY l.created_at DESC
                  LIMIT 5";
$timelineResult = $conn->query($timelineQuery);
$timelineActivities = [];
while ($row = $timelineResult->fetch_assoc()) {
    $timelineActivities[] = $row;
}

$conn->close();
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
                        <p class="stat-number"><?php echo number_format($totalLeads); ?></p>
                        <span class="stat-change <?php echo $percentageChange >= 0 ? 'positive' : ''; ?>">
                            <?php 
                            if ($percentageChange != 0) {
                                echo ($percentageChange >= 0 ? '+' : '') . $percentageChange . '% from last month';
                            } else {
                                echo 'No change from last month';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Leads</h3>
                        <p class="stat-number"><?php echo number_format($activeLeads); ?></p>
                        <span class="stat-change positive">Currently active</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Plots Available</h3>
                        <p class="stat-number"><?php echo number_format($plotsAvailable); ?></p>
                        <span class="stat-change">Ready to sell</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Flats Available</h3>
                        <p class="stat-number"><?php echo number_format($flatsAvailable); ?></p>
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
                        <a href="all-leads.php" class="view-all-btn">View All</a>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentLeads)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                                <p>No leads found. <a href="all-leads.php">Add your first lead</a></p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentLeads as $lead): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($lead['lead_id']); ?></td>
                                                <td><?php echo htmlspecialchars($lead['name']); ?></td>
                                                <td><?php echo htmlspecialchars($lead['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($lead['property_type'] ?? 'N/A'); ?></td>
                                                <td><span class="badge <?php echo getStatusBadgeClass($lead['status']); ?>"><?php echo htmlspecialchars($lead['status']); ?></span></td>
                                                <td><?php echo htmlspecialchars($lead['telecaller_name'] ?? 'Unassigned'); ?></td>
                                                <td><?php echo formatDate($lead['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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
                            <?php if (empty($telecallers)): ?>
                                <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                    <i class="fas fa-user-tie" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                    <p>No telecallers found. <a href="users.php">Add telecallers</a></p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($telecallers as $telecaller): ?>
                                    <div class="telecaller-item">
                                        <div class="telecaller-avatar"><?php echo getInitials($telecaller['name']); ?></div>
                                        <div class="telecaller-info">
                                            <h4><?php echo htmlspecialchars($telecaller['name']); ?></h4>
                                            <p><?php echo $telecaller['total_leads']; ?> Leads | <?php echo $telecaller['active_leads']; ?> Active</p>
                                        </div>
                                        <div class="telecaller-score">
                                            <span class="score-badge"><?php echo $telecaller['performance']; ?>%</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Lead Activities -->
            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Recent Lead Activities</h2>
                    </div>
                    <div class="card-body">
                        <ul class="timeline">
                            <?php if (empty($timelineActivities)): ?>
                                <li style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                    <i class="fas fa-history" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                    <p>No recent activities found.</p>
                                </li>
                            <?php else: ?>
                                <?php foreach ($timelineActivities as $activity): ?>
                                    <li>
                                        <div class="timeline-icon success"><i class="fas fa-plus"></i></div>
                                        <div class="timeline-content">
                                            <h4>Lead Created</h4>
                                            <p>Lead <?php echo htmlspecialchars($activity['lead_id']); ?> - <?php echo htmlspecialchars($activity['name']); ?> 
                                                <?php if (!empty($activity['created_by_name'])): ?>
                                                    added by <?php echo htmlspecialchars($activity['created_by_name']); ?>
                                                <?php endif; ?>
                                            </p>
                                            <span><?php echo formatDateTime($activity['created_at']); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

