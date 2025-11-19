<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in
requireLogin();

$activePage = 'all-leads';
$pageTitle = 'All Leads';
$breadcrumb = 'Home / All Leads';

// Get logged in user
$loggedInUser = getLoggedInUser();
$userRole = $loggedInUser['role'] ?? 'Administrator';
$userId = $loggedInUser['id'] ?? 0;

// Check if user is Telecaller - if yes, show only assigned leads
$isTelecaller = ($userRole == 'Telecaller');
// Check if user is Site Manager (Analyst) - if yes, show only Site Visit leads
$isSiteManager = ($userRole == 'Site Manager' || $userRole == 'Analyst');

// Get database connection
$conn = getDBConnection();

$error = '';
$success = '';

// Handle Add Lead Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_lead'])) {
    $name = trim($_POST['leadName'] ?? '');
    $phone = trim($_POST['leadPhone'] ?? '');
    $email = trim($_POST['leadEmail'] ?? '');
    $propertyType = trim($_POST['leadType'] ?? '');
    $leadSource = trim($_POST['leadSource'] ?? '');
    $assignedTo = $_POST['leadOwner'] ?? '';
    $budgetRange = trim($_POST['leadBudget'] ?? '');
    $followUpDate = $_POST['followUpDate'] ?? null;
    $notes = trim($_POST['leadNotes'] ?? '');
    $status = 'New';
    
    // Validation
    if (empty($name) || empty($phone)) {
        $error = 'Name and Phone are required fields.';
    } else {
        // Generate unique lead ID
        $leadId = 'L-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if lead_id already exists
        $checkQuery = $conn->prepare("SELECT id FROM leads WHERE lead_id = ?");
        $checkQuery->bind_param("s", $leadId);
        $checkQuery->execute();
        $checkResult = $checkQuery->get_result();
        
        // If exists, generate new one
        while ($checkResult->num_rows > 0) {
            $leadId = 'L-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $checkQuery->bind_param("s", $leadId);
            $checkQuery->execute();
            $checkResult = $checkQuery->get_result();
        }
        $checkQuery->close();
        
        // Insert lead
        $stmt = $conn->prepare("INSERT INTO leads (lead_id, name, phone, email, property_type, lead_source, budget_range, status, assigned_to, follow_up_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $assignedToInt = !empty($assignedTo) ? (int)$assignedTo : null;
        $followUpDateFormatted = !empty($followUpDate) ? $followUpDate : null;
        
        $stmt->bind_param("ssssssssisss", $leadId, $name, $phone, $email, $propertyType, $leadSource, $budgetRange, $status, $assignedToInt, $followUpDateFormatted, $notes, $loggedInUser['id']);
        
        if ($stmt->execute()) {
            $success = 'Lead added successfully! Lead ID: ' . $leadId;
            // Clear form by redirecting
            header("Location: all-leads.php?success=1");
            exit();
        } else {
            $error = 'Error adding lead: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

// Handle Status Update (for Site Managers only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status']) && $isSiteManager) {
    $leadId = (int)$_POST['lead_id'];
    $status = trim($_POST['status'] ?? '');
    
    // Validate status - only allow "Closed Won" or "Closed Lost"
    if ($status != 'Closed Won' && $status != 'Closed Lost') {
        $error = 'Invalid status. Only "Closed Won" or "Closed Lost" are allowed.';
    } else {
        // Check permission - Site Manager can only update leads assigned to them
        $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to = ?");
        $verifyQuery->bind_param("ii", $leadId, $userId);
        $verifyQuery->execute();
        $verifyResult = $verifyQuery->get_result();
        
        if ($verifyResult->num_rows > 0) {
            // Update status
            $stmt = $conn->prepare("UPDATE leads SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $leadId);
            
            if ($stmt->execute()) {
                $success = 'Lead status updated successfully to ' . $status . '!';
                header("Location: all-leads.php?success=status");
                exit();
            } else {
                $error = 'Error updating lead status: ' . $conn->error;
            }
            
            $stmt->close();
        } else {
            $error = 'You do not have permission to update this lead.';
        }
        
        $verifyQuery->close();
    }
}

// Check for success message
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $success = 'Lead added successfully!';
    } elseif ($_GET['success'] == 'status') {
        $success = 'Lead status updated successfully!';
    }
}

// Get filter values
$filterStatus = $_GET['status'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterSource = $_GET['source'] ?? '';
$filterTelecaller = $_GET['telecaller'] ?? '';
$filterBudget = $_GET['budget'] ?? '';

// Build WHERE clause for filters
$whereConditions = [];
$params = [];
$paramTypes = '';

// Exclude personal leads (leads created by Managers) - only show in personal-leads.php
$whereConditions[] = "(l.created_by IS NULL OR (SELECT role FROM users WHERE id = l.created_by) != 'Manager')";

// If telecaller, only show assigned leads
if ($isTelecaller) {
    $whereConditions[] = "l.assigned_to = ?";
    $params[] = $userId;
    $paramTypes .= 'i';
}

// If Site Manager, only show leads assigned to them with Site Visit status
if ($isSiteManager) {
    $whereConditions[] = "l.assigned_to = ?";
    $params[] = $userId;
    $paramTypes .= 'i';
    $whereConditions[] = "l.status = ?";
    $params[] = 'Site Visit';
    $paramTypes .= 's';
}

if (!empty($filterStatus)) {
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

if (!empty($filterTelecaller) && !$isTelecaller && !$isSiteManager) {
    // Only allow telecaller filter if user is not telecaller and not Site Manager
    $whereConditions[] = "l.assigned_to = ?";
    $params[] = $filterTelecaller;
    $paramTypes .= 'i';
}

if (!empty($filterBudget)) {
    $whereConditions[] = "l.budget_range LIKE ?";
    $params[] = "%$filterBudget%";
    $paramTypes .= 's';
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Fetch Stats (with filters) - exclude personal leads
$assignedFilter = $isTelecaller ? " AND assigned_to = $userId" : "";
$siteVisitFilter = $isSiteManager ? " AND assigned_to = $userId AND status = 'Site Visit'" : "";
$personalLeadsFilter = " AND (created_by IS NULL OR (SELECT role FROM users WHERE id = leads.created_by) != 'Manager')";
$combinedFilter = $assignedFilter . $siteVisitFilter . $personalLeadsFilter;

$totalLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE 1=1 $combinedFilter")->fetch_assoc()['total'] ?? 0;
$qualifiedLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Qualified' $combinedFilter")->fetch_assoc()['total'] ?? 0;
$followUpLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Follow Up' $combinedFilter")->fetch_assoc()['total'] ?? 0;
$siteVisitLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Site Visit' $combinedFilter")->fetch_assoc()['total'] ?? 0;

// Calculate week change
$thisWeek = date('Y-m-d', strtotime('monday this week'));
$lastWeek = date('Y-m-d', strtotime('monday last week'));
$thisWeekCount = $conn->query("SELECT COUNT(*) as total FROM leads WHERE DATE(created_at) >= '$thisWeek' $combinedFilter")->fetch_assoc()['total'] ?? 0;
$lastWeekCount = $conn->query("SELECT COUNT(*) as total FROM leads WHERE DATE(created_at) >= '$lastWeek' AND DATE(created_at) < '$thisWeek' $combinedFilter")->fetch_assoc()['total'] ?? 0;
$weekChange = $lastWeekCount > 0 ? round((($thisWeekCount - $lastWeekCount) / $lastWeekCount) * 100) : 0;

// Fetch leads with filters
$leadsQuery = "SELECT l.*, u.name as telecaller_name 
               FROM leads l 
               LEFT JOIN users u ON l.assigned_to = u.id 
               $whereClause
               ORDER BY l.created_at DESC 
               LIMIT 100";

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

// Fetch all telecallers for dropdowns
$telecallersQuery = "SELECT id, name FROM users WHERE role = 'Telecaller' AND status = 'Active' ORDER BY name";
$telecallersResult = $conn->query($telecallersQuery);
$telecallers = [];
while ($row = $telecallersResult->fetch_assoc()) {
    $telecallers[] = $row;
}

// Fetch unique property types
$propertyTypesFilter = "";
if ($isTelecaller) {
    $propertyTypesFilter = " AND assigned_to = $userId";
} elseif ($isSiteManager) {
    $propertyTypesFilter = " AND assigned_to = $userId AND status = 'Site Visit'";
}
$propertyTypesQuery = "SELECT DISTINCT property_type FROM leads WHERE property_type IS NOT NULL AND property_type != '' $propertyTypesFilter ORDER BY property_type";
$propertyTypesResult = $conn->query($propertyTypesQuery);
$propertyTypes = [];
while ($row = $propertyTypesResult->fetch_assoc()) {
    $propertyTypes[] = $row['property_type'];
}

// Fetch unique lead sources
$leadSourcesFilter = "";
if ($isTelecaller) {
    $leadSourcesFilter = " AND assigned_to = $userId";
} elseif ($isSiteManager) {
    $leadSourcesFilter = " AND assigned_to = $userId AND status = 'Site Visit'";
}
$leadSourcesQuery = "SELECT DISTINCT lead_source FROM leads WHERE lead_source IS NOT NULL AND lead_source != '' $leadSourcesFilter ORDER BY lead_source";
$leadSourcesResult = $conn->query($leadSourcesQuery);
$leadSources = [];
while ($row = $leadSourcesResult->fetch_assoc()) {
    $leadSources[] = $row['lead_source'];
}


$conn->close();
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

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin: 20px 0;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin: 20px 0;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-primary">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Leads</h3>
                        <p class="stat-number"><?php echo number_format($totalLeads); ?></p>
                        <span class="stat-change <?php echo $weekChange >= 0 ? 'positive' : ''; ?>">
                            <?php echo $weekChange >= 0 ? '+' : ''; ?><?php echo $weekChange; ?>% this week
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Qualified</h3>
                        <p class="stat-number"><?php echo number_format($qualifiedLeads); ?></p>
                        <span class="stat-change positive">Qualified leads</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-phone-volume"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Follow-Ups</h3>
                        <p class="stat-number"><?php echo number_format($followUpLeads); ?></p>
                        <span class="stat-change">Scheduled</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Site Visits</h3>
                        <p class="stat-number"><?php echo number_format($siteVisitLeads); ?></p>
                        <span class="stat-change positive">Confirmed</span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Lead List</h2>
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
                                    <?php if ($isSiteManager): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leads)): ?>
                                    <tr>
                                        <td colspan="<?php echo $isSiteManager ? 9 : 8; ?>" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                            <p>No leads found. 
                                                <?php if (!empty($filterStatus) || !empty($filterType) || !empty($filterSource) || !empty($filterTelecaller)): ?>
                                                    <a href="all-leads.php">Clear filters</a> or 
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
                                                <div class="table-contact">
                                                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($lead['phone']); ?></span>
                                                    <?php if (!empty($lead['email'])): ?>
                                                        <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($lead['email']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($lead['telecaller_name'] ?? 'Unassigned'); ?></td>
                                            <td><?php echo htmlspecialchars($lead['property_type'] ?? 'N/A'); ?></td>
                                            <td><span class="badge <?php echo getStatusBadgeClass($lead['status']); ?>"><?php echo htmlspecialchars($lead['status']); ?></span></td>
                                            <td><?php echo htmlspecialchars($lead['lead_source'] ?? 'N/A'); ?></td>
                                            <td><?php echo formatDate($lead['follow_up_date']); ?></td>
                                            <?php if ($isSiteManager): ?>
                                            <td class="table-actions">
                                                <button class="btn btn-sm btn-primary" onclick="openStatusModal(<?php echo $lead['id']; ?>, '<?php echo htmlspecialchars($lead['lead_id']); ?>', '<?php echo htmlspecialchars($lead['name']); ?>', '<?php echo htmlspecialchars($lead['status']); ?>')" title="Update Status">
                                                    <i class="fas fa-edit"></i> Update Status
                                                </button>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php if ($isSiteManager): ?>
    <!-- Status Update Modal -->
    <div id="statusModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Lead Status</h2>
                <span class="close" onclick="closeStatusModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="all-leads.php" id="statusForm">
                    <input type="hidden" name="lead_id" id="status_lead_id">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="form-group">
                        <label><strong>Lead ID:</strong> <span id="status_lead_id_display"></span></label>
                    </div>
                    
                    <div class="form-group">
                        <label><strong>Lead Name:</strong> <span id="status_lead_name_display"></span></label>
                    </div>
                    
                    <div class="form-group">
                        <label><strong>Current Status:</strong> <span id="status_current_status_display"></span></label>
                    </div>
                    
                    <div class="form-group">
                        <label for="status_select">Update Status to *</label>
                        <select id="status_select" name="status" required>
                            <option value="">Select Status</option>
                            <option value="Closed Won">Closed Won</option>
                            <option value="Closed Lost">Closed Lost</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openStatusModal(leadId, leadIdDisplay, leadName, currentStatus) {
            document.getElementById('status_lead_id').value = leadId;
            document.getElementById('status_lead_id_display').textContent = leadIdDisplay;
            document.getElementById('status_lead_name_display').textContent = leadName;
            document.getElementById('status_current_status_display').textContent = currentStatus;
            document.getElementById('status_select').value = '';
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('statusModal');
            if (event.target == modal) {
                closeStatusModal();
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>
