<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in
requireLogin();

$activePage = 'leads';
$pageTitle = 'Leads Management';
$breadcrumb = 'Home / Leads Management';

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

// Get filter values from GET/POST
$filterStatus = $_GET['status'] ?? $_POST['status'] ?? '';
$filterType = $_GET['type'] ?? $_POST['type'] ?? '';
$filterSource = $_GET['source'] ?? $_POST['source'] ?? '';
$filterTelecaller = $_GET['telecaller'] ?? $_POST['telecaller'] ?? '';

// Build WHERE clause for filters
$whereConditions = [];
$params = [];
$paramTypes = '';

// If telecaller, only show assigned leads
if ($isTelecaller) {
    $whereConditions[] = "l.assigned_to = ?";
    $params[] = $userId;
    $paramTypes .= 'i';
}

// If Site Manager, only show Site Visit leads
if ($isSiteManager) {
    $whereConditions[] = "l.status = ?";
    $params[] = 'Site Visit';
    $paramTypes .= 's';
} elseif (!empty($filterStatus)) {
    // Only apply status filter if not Site Manager (Site Manager is already filtered to Site Visit)
    $whereConditions[] = "l.status = ?";
    $params[] = $filterStatus;
    $paramTypes .= 's';
}

if (!empty($filterType)) {
    $whereConditions[] = "l.property_type LIKE ?";
    $params[] = "%$filterType%";
    $paramTypes .= 's';
}

if (!empty($filterSource)) {
    $whereConditions[] = "l.lead_source = ?";
    $params[] = $filterSource;
    $paramTypes .= 's';
}

if (!empty($filterTelecaller) && !$isTelecaller) {
    // Only allow telecaller filter if user is not telecaller
    $whereConditions[] = "l.assigned_to = ?";
    $params[] = $filterTelecaller;
    $paramTypes .= 'i';
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Fetch leads with filters
$leadsQuery = "SELECT l.*, u.name as telecaller_name, u2.name as created_by_name
               FROM leads l
               LEFT JOIN users u ON l.assigned_to = u.id
               LEFT JOIN users u2 ON l.created_by = u2.id
               $whereClause
               ORDER BY l.created_at DESC
               LIMIT 50";

if (!empty($params)) {
    $stmt = $conn->prepare($leadsQuery);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $leadsResult = $stmt->get_result();
} else {
    $leadsResult = $conn->query($leadsQuery);
}

$leads = [];
while ($row = $leadsResult->fetch_assoc()) {
    $leads[] = $row;
}

// Fetch all telecallers for filter dropdown
$telecallersQuery = "SELECT id, name FROM users WHERE role = 'Telecaller' AND status = 'Active' ORDER BY name";
$telecallersResult = $conn->query($telecallersQuery);
$telecallers = [];
while ($row = $telecallersResult->fetch_assoc()) {
    $telecallers[] = $row;
}

// Fetch unique property types for filter
$propertyTypesFilter = "";
if ($isTelecaller) {
    $propertyTypesFilter = " AND assigned_to = $userId";
} elseif ($isSiteManager) {
    $propertyTypesFilter = " AND status = 'Site Visit'";
}
$propertyTypesQuery = "SELECT DISTINCT property_type FROM leads WHERE property_type IS NOT NULL AND property_type != '' $propertyTypesFilter ORDER BY property_type";
$propertyTypesResult = $conn->query($propertyTypesQuery);
$propertyTypes = [];
while ($row = $propertyTypesResult->fetch_assoc()) {
    $propertyTypes[] = $row['property_type'];
}

// Fetch unique lead sources for filter
$leadSourcesFilter = "";
if ($isTelecaller) {
    $leadSourcesFilter = " AND assigned_to = $userId";
} elseif ($isSiteManager) {
    $leadSourcesFilter = " AND status = 'Site Visit'";
}
$leadSourcesQuery = "SELECT DISTINCT lead_source FROM leads WHERE lead_source IS NOT NULL AND lead_source != '' $leadSourcesFilter ORDER BY lead_source";
$leadSourcesResult = $conn->query($leadSourcesQuery);
$leadSources = [];
while ($row = $leadSourcesResult->fetch_assoc()) {
    $leadSources[] = $row['lead_source'];
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
                        <a href="leads-management.php" class="view-all-btn">Reset Filters</a>
                    </div>
                    <div class="card-body">
                        <form class="filter-form" method="GET" action="leads-management.php">
                            <div class="filter-row">
                                <?php if (!$isSiteManager): ?>
                                <div class="form-group">
                                    <label for="leadStatus">Status</label>
                                    <select id="leadStatus" name="status">
                                        <option value="">All Status</option>
                                        <option value="New" <?php echo $filterStatus == 'New' ? 'selected' : ''; ?>>New</option>
                                        <option value="Active" <?php echo $filterStatus == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Follow Up" <?php echo $filterStatus == 'Follow Up' ? 'selected' : ''; ?>>Follow Up</option>
                                        <option value="Qualified" <?php echo $filterStatus == 'Qualified' ? 'selected' : ''; ?>>Qualified</option>
                                        <option value="Site Visit" <?php echo $filterStatus == 'Site Visit' ? 'selected' : ''; ?>>Site Visit</option>
                                        <option value="Closed Won" <?php echo $filterStatus == 'Closed Won' ? 'selected' : ''; ?>>Closed Won</option>
                                        <option value="Closed Lost" <?php echo $filterStatus == 'Closed Lost' ? 'selected' : ''; ?>>Closed Lost</option>
                                    </select>
                                </div>
                                <?php else: ?>
                                <div class="form-group">
                                    <label for="leadStatus">Status</label>
                                    <input type="text" value="Site Visit" disabled style="background: #f3f4f6; cursor: not-allowed;">
                                    <small style="color: var(--text-secondary); font-size: 12px;">Site Manager can only view Site Visit leads</small>
                                </div>
                                <?php endif; ?>
                                <div class="form-group">
                                    <label for="leadType">Property Type</label>
                                    <select id="leadType" name="type">
                                        <option value="">All Types</option>
                                        <?php foreach ($propertyTypes as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filterType == $type ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="leadSource">Lead Source</label>
                                    <select id="leadSource" name="source">
                                        <option value="">All Sources</option>
                                        <?php foreach ($leadSources as $source): ?>
                                            <option value="<?php echo htmlspecialchars($source); ?>" <?php echo $filterSource == $source ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($source); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if (!$isTelecaller && !$isSiteManager): ?>
                                <div class="form-group">
                                    <label for="telecaller">Telecaller</label>
                                    <select id="telecaller" name="telecaller">
                                        <option value="">All Telecallers</option>
                                        <?php foreach ($telecallers as $telecaller): ?>
                                            <option value="<?php echo $telecaller['id']; ?>" <?php echo $filterTelecaller == $telecaller['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($telecaller['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                                <a href="leads-management.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>Leads Overview</h2>
                        <a href="all-leads.php" class="view-all-btn">Create Lead</a>
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
                                    <?php if (empty($leads)): ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                                <p>No leads found. 
                                                    <?php if (!empty($filterStatus) || !empty($filterType) || !empty($filterSource) || !empty($filterTelecaller)): ?>
                                                        <a href="leads-management.php">Clear filters</a> or 
                                                    <?php endif; ?>
                                                    <a href="all-leads.php">Add your first lead</a>
                                                </p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($leads as $lead): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($lead['lead_id']); ?></td>
                                                <td><?php echo htmlspecialchars($lead['name']); ?></td>
                                                <td>
                                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                                        <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($lead['phone']); ?></span>
                                                        <?php if (!empty($lead['email'])): ?>
                                                            <span style="font-size: 12px; color: var(--text-secondary);">
                                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($lead['email']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($lead['telecaller_name'] ?? 'Unassigned'); ?></td>
                                                <td><?php echo htmlspecialchars($lead['property_type'] ?? 'N/A'); ?></td>
                                                <td><span class="badge <?php echo getStatusBadgeClass($lead['status']); ?>"><?php echo htmlspecialchars($lead['status']); ?></span></td>
                                                <td><?php echo formatDate($lead['follow_up_date']); ?></td>
                                                <td>
                                                    <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-icon" title="Notes"><i class="fas fa-sticky-note"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

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
        </main>
    </div>
</body>
</html>

