<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in
requireLogin();

// Get logged in user
$loggedInUser = getLoggedInUser();
$userRole = $loggedInUser['role'] ?? 'Administrator';
$userId = $loggedInUser['id'] ?? 0;

// Only Managers can access this page
if ($userRole != 'Manager') {
    header("Location: index.php");
    exit();
}

$activePage = 'personal-leads';
$pageTitle = 'Personal Leads';
$breadcrumb = 'Home / Personal Leads';

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
        $leadId = 'PL-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if lead_id already exists
        $checkQuery = $conn->prepare("SELECT id FROM leads WHERE lead_id = ?");
        $checkQuery->bind_param("s", $leadId);
        $checkQuery->execute();
        $checkResult = $checkQuery->get_result();
        
        // If exists, generate new one
        while ($checkResult->num_rows > 0) {
            $leadId = 'PL-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $checkQuery->bind_param("s", $leadId);
            $checkQuery->execute();
            $checkResult = $checkQuery->get_result();
        }
        $checkQuery->close();
        
        // Insert lead - created_by will be the manager's ID
        $stmt = $conn->prepare("INSERT INTO leads (lead_id, name, phone, email, property_type, lead_source, budget_range, status, assigned_to, follow_up_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $assignedToInt = !empty($assignedTo) ? (int)$assignedTo : null;
        $followUpDateFormatted = !empty($followUpDate) ? $followUpDate : null;
        
        $stmt->bind_param("ssssssssisss", $leadId, $name, $phone, $email, $propertyType, $leadSource, $budgetRange, $status, $assignedToInt, $followUpDateFormatted, $notes, $userId);
        
        if ($stmt->execute()) {
            $success = 'Personal lead added successfully! Lead ID: ' . $leadId;
            header("Location: personal-leads.php?success=1");
            exit();
        } else {
            $error = 'Error adding lead: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

// Handle Update Lead Status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $leadId = (int)$_POST['lead_id'];
    $newStatus = $_POST['new_status'];
    
    // Verify that this lead belongs to the manager
    $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND created_by = ?");
    $verifyQuery->bind_param("ii", $leadId, $userId);
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE leads SET status = ? WHERE id = ? AND created_by = ?");
        $stmt->bind_param("sii", $newStatus, $leadId, $userId);
        
        if ($stmt->execute()) {
            $success = 'Lead status updated successfully!';
            header("Location: personal-leads.php?success=status");
            exit();
        } else {
            $error = 'Error updating status: ' . $conn->error;
        }
        
        $stmt->close();
    } else {
        $error = 'You do not have permission to update this lead.';
    }
    
    $verifyQuery->close();
}

// Handle Delete Lead
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $leadId = (int)$_GET['delete'];
    
    // Verify that this lead belongs to the manager
    $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND created_by = ?");
    $verifyQuery->bind_param("ii", $leadId, $userId);
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM leads WHERE id = ? AND created_by = ?");
        $stmt->bind_param("ii", $leadId, $userId);
        
        if ($stmt->execute()) {
            $success = 'Lead deleted successfully!';
            header("Location: personal-leads.php?success=delete");
            exit();
        } else {
            $error = 'Error deleting lead: ' . $conn->error;
        }
        
        $stmt->close();
    } else {
        $error = 'You do not have permission to delete this lead.';
    }
    
    $verifyQuery->close();
}

// Check for success messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $success = 'Personal lead added successfully!';
    } elseif ($_GET['success'] == 'status') {
        $success = 'Lead status updated successfully!';
    } elseif ($_GET['success'] == 'delete') {
        $success = 'Lead deleted successfully!';
    }
}

// Get filter values
$filterStatus = $_GET['status'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterSource = $_GET['source'] ?? '';
$filterBudget = $_GET['budget'] ?? '';

// Build WHERE clause for filters - only show leads created by this manager
$whereConditions = ["l.created_by = $userId"];
$params = [];
$paramTypes = '';

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

if (!empty($filterBudget)) {
    $whereConditions[] = "l.budget_range LIKE ?";
    $params[] = "%$filterBudget%";
    $paramTypes .= 's';
}

$whereClause = "WHERE " . implode(" AND ", $whereConditions);

// Fetch Stats (only manager's personal leads)
$totalLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE created_by = $userId")->fetch_assoc()['total'] ?? 0;
$qualifiedLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Qualified' AND created_by = $userId")->fetch_assoc()['total'] ?? 0;
$followUpLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Follow Up' AND created_by = $userId")->fetch_assoc()['total'] ?? 0;
$siteVisitLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE status = 'Site Visit' AND created_by = $userId")->fetch_assoc()['total'] ?? 0;

// Calculate week change
$thisWeek = date('Y-m-d', strtotime('monday this week'));
$lastWeek = date('Y-m-d', strtotime('monday last week'));
$thisWeekCount = $conn->query("SELECT COUNT(*) as total FROM leads WHERE DATE(created_at) >= '$thisWeek' AND created_by = $userId")->fetch_assoc()['total'] ?? 0;
$lastWeekCount = $conn->query("SELECT COUNT(*) as total FROM leads WHERE DATE(created_at) >= '$lastWeek' AND DATE(created_at) < '$thisWeek' AND created_by = $userId")->fetch_assoc()['total'] ?? 0;
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

// Fetch unique property types (only from manager's leads)
$propertyTypesQuery = "SELECT DISTINCT property_type FROM leads WHERE property_type IS NOT NULL AND property_type != '' AND created_by = $userId ORDER BY property_type";
$propertyTypesResult = $conn->query($propertyTypesQuery);
$propertyTypes = [];
while ($row = $propertyTypesResult->fetch_assoc()) {
    $propertyTypes[] = $row['property_type'];
}

// Fetch unique lead sources (only from manager's leads)
$leadSourcesQuery = "SELECT DISTINCT lead_source FROM leads WHERE lead_source IS NOT NULL AND lead_source != '' AND created_by = $userId ORDER BY lead_source";
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
    <title>Personal Leads - Kansal Admin Panel</title>
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
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3>My Personal Leads</h3>
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

            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Add Personal Lead</h2>
                        <span class="view-all-btn" style="background: var(--primary-color); color: white; padding: 8px 16px; border-radius: 6px; font-size: 12px;">
                            <i class="fas fa-user-shield"></i> Manager Only
                        </span>
                    </div>
                    <div class="card-body">
                        <form class="settings-form" method="POST" action="personal-leads.php">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="leadName">Lead Name <span style="color: red;">*</span></label>
                                    <input type="text" id="leadName" name="leadName" placeholder="Enter full name" value="<?php echo htmlspecialchars($_POST['leadName'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="leadPhone">Phone Number <span style="color: red;">*</span></label>
                                    <input type="text" id="leadPhone" name="leadPhone" placeholder="+91 98xxxxxxx" value="<?php echo htmlspecialchars($_POST['leadPhone'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="leadEmail">Email</label>
                                    <input type="email" id="leadEmail" name="leadEmail" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['leadEmail'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="leadType">Interested In</label>
                                    <select id="leadType" name="leadType">
                                        <option value="">Select Property Type</option>
                                        <?php foreach ($propertyTypes as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($_POST['leadType']) && $_POST['leadType'] == $type) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="3BHK Flat" <?php echo (isset($_POST['leadType']) && $_POST['leadType'] == '3BHK Flat') ? 'selected' : ''; ?>>3BHK Flat</option>
                                        <option value="Luxury Villa" <?php echo (isset($_POST['leadType']) && $_POST['leadType'] == 'Luxury Villa') ? 'selected' : ''; ?>>Luxury Villa</option>
                                        <option value="Farmhouse Plot" <?php echo (isset($_POST['leadType']) && $_POST['leadType'] == 'Farmhouse Plot') ? 'selected' : ''; ?>>Farmhouse Plot</option>
                                        <option value="Commercial Plot" <?php echo (isset($_POST['leadType']) && $_POST['leadType'] == 'Commercial Plot') ? 'selected' : ''; ?>>Commercial Plot</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="leadSource">Lead Source</label>
                                    <select id="leadSource" name="leadSource">
                                        <option value="">Select Source</option>
                                        <?php foreach ($leadSources as $source): ?>
                                            <option value="<?php echo htmlspecialchars($source); ?>" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == $source) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($source); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="Website" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'Website') ? 'selected' : ''; ?>>Website</option>
                                        <option value="Facebook Ads" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'Facebook Ads') ? 'selected' : ''; ?>>Facebook Ads</option>
                                        <option value="Google Ads" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'Google Ads') ? 'selected' : ''; ?>>Google Ads</option>
                                        <option value="WhatsApp Campaign" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'WhatsApp Campaign') ? 'selected' : ''; ?>>WhatsApp Campaign</option>
                                        <option value="Referral" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'Referral') ? 'selected' : ''; ?>>Referral</option>
                                        <option value="Personal Network" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'Personal Network') ? 'selected' : ''; ?>>Personal Network</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="leadOwner">Assign To Telecaller</label>
                                    <select id="leadOwner" name="leadOwner">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($telecallers as $telecaller): ?>
                                            <option value="<?php echo $telecaller['id']; ?>" <?php echo (isset($_POST['leadOwner']) && $_POST['leadOwner'] == $telecaller['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($telecaller['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="leadBudget">Budget Range</label>
                                    <input type="text" id="leadBudget" name="leadBudget" placeholder="₹50L - ₹1.5Cr" value="<?php echo htmlspecialchars($_POST['leadBudget'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="followUpDate">Next Follow Up</label>
                                    <input type="date" id="followUpDate" name="followUpDate" value="<?php echo htmlspecialchars($_POST['followUpDate'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="leadNotes">Notes</label>
                                <textarea id="leadNotes" name="leadNotes" rows="3" placeholder="Add brief notes about this lead"><?php echo htmlspecialchars($_POST['leadNotes'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_lead" class="btn btn-primary"><i class="fas fa-save"></i> Save Personal Lead</button>
                                <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Clear</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>Lead Filters</h2>
                        <a href="personal-leads.php" class="view-all-btn">Reset Filters</a>
                    </div>
                    <div class="card-body">
                        <div class="chip-group">
                            <a href="personal-leads.php" class="chip <?php echo empty($filterStatus) ? 'active' : ''; ?>"><i class="fas fa-layer-group"></i> All</a>
                            <a href="personal-leads.php?status=Qualified" class="chip <?php echo $filterStatus == 'Qualified' ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i> Qualified</a>
                            <a href="personal-leads.php?status=Follow Up" class="chip <?php echo $filterStatus == 'Follow Up' ? 'active' : ''; ?>"><i class="fas fa-phone"></i> Follow Up</a>
                            <a href="personal-leads.php?status=Site Visit" class="chip <?php echo $filterStatus == 'Site Visit' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Site Visit</a>
                            <a href="personal-leads.php?status=Closed Won" class="chip <?php echo $filterStatus == 'Closed Won' ? 'active' : ''; ?>"><i class="fas fa-file-contract"></i> Closed Won</a>
                            <a href="personal-leads.php?status=Closed Lost" class="chip <?php echo $filterStatus == 'Closed Lost' ? 'active' : ''; ?>"><i class="fas fa-times-circle"></i> Closed Lost</a>
                        </div>
                        <form class="filter-form" method="GET" action="personal-leads.php">
                            <div class="filter-row">
                                <div class="form-group">
                                    <label for="filterType">Property Type</label>
                                    <select id="filterType" name="type">
                                        <option value="">All Types</option>
                                        <?php foreach ($propertyTypes as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filterType == $type ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterSource">Lead Source</label>
                                    <select id="filterSource" name="source">
                                        <option value="">All Sources</option>
                                        <?php foreach ($leadSources as $source): ?>
                                            <option value="<?php echo htmlspecialchars($source); ?>" <?php echo $filterSource == $source ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($source); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterStatus">Status</label>
                                    <select id="filterStatus" name="status">
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
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                                <a href="personal-leads.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>My Personal Leads List</h2>
                    <div class="report-actions">
                        <span style="color: var(--text-secondary); font-size: 14px;">
                            <i class="fas fa-info-circle"></i> Only leads created by you
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Property Type</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                    <th>Follow Up</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leads)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                            <p>No personal leads found. <a href="personal-leads.php">Add your first personal lead</a></p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($leads as $lead): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($lead['lead_id']); ?></strong></td>
                                            <td>
                                                <div class="table-user">
                                                    <div>
                                                        <h4><?php echo htmlspecialchars($lead['name']); ?></h4>
                                                        <?php if (!empty($lead['email'])): ?>
                                                            <span><?php echo htmlspecialchars($lead['email']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="table-contact">
                                                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($lead['phone']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($lead['property_type'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($lead['telecaller_name'] ?? 'Unassigned'); ?></td>
                                            <td>
                                                <form method="POST" action="personal-leads.php" style="display: inline;">
                                                    <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                                    <select class="role-select" name="new_status" onchange="this.form.submit()" style="min-width: 120px;">
                                                        <option value="New" <?php echo $lead['status'] == 'New' ? 'selected' : ''; ?>>New</option>
                                                        <option value="Active" <?php echo $lead['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="Follow Up" <?php echo $lead['status'] == 'Follow Up' ? 'selected' : ''; ?>>Follow Up</option>
                                                        <option value="Qualified" <?php echo $lead['status'] == 'Qualified' ? 'selected' : ''; ?>>Qualified</option>
                                                        <option value="Site Visit" <?php echo $lead['status'] == 'Site Visit' ? 'selected' : ''; ?>>Site Visit</option>
                                                        <option value="Closed Won" <?php echo $lead['status'] == 'Closed Won' ? 'selected' : ''; ?>>Closed Won</option>
                                                        <option value="Closed Lost" <?php echo $lead['status'] == 'Closed Lost' ? 'selected' : ''; ?>>Closed Lost</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            </td>
                                            <td><?php echo formatDate($lead['follow_up_date']); ?></td>
                                            <td><?php echo formatDate($lead['created_at']); ?></td>
                                            <td class="table-actions">
                                                <a href="personal-leads.php?delete=<?php echo $lead['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Are you sure you want to delete this personal lead? This action cannot be undone.');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
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
</body>
</html>

