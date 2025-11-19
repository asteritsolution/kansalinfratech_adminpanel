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

// Only Telecallers can access this page
if ($userRole != 'Telecaller') {
    header("Location: index.php");
    exit();
}

$activePage = 'transfer-leads';
$pageTitle = 'Transfer Leads to Site Manager';
$breadcrumb = 'Home / Transfer Leads';

// Get database connection
$conn = getDBConnection();

$error = '';
$success = '';

// Handle Transfer Lead
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transfer_lead'])) {
    $leadId = (int)$_POST['lead_id'];
    $siteManagerId = !empty($_POST['site_manager_id']) ? (int)$_POST['site_manager_id'] : null;
    
    if (empty($siteManagerId)) {
        $error = 'Please select a Site Manager.';
    } else {
        // Verify that this lead belongs to the logged-in telecaller
        $verifyQuery = $conn->prepare("SELECT id, name FROM leads WHERE id = ? AND assigned_to = ?");
        $verifyQuery->bind_param("ii", $leadId, $userId);
        $verifyQuery->execute();
        $verifyResult = $verifyQuery->get_result();
        
        if ($verifyResult->num_rows > 0) {
            // Verify the target site manager exists and is active
            $targetQuery = $conn->prepare("SELECT id, name FROM users WHERE id = ? AND (role = 'Site Manager' OR role = 'Analyst') AND status = 'Active'");
            $targetQuery->bind_param("i", $siteManagerId);
            $targetQuery->execute();
            $targetResult = $targetQuery->get_result();
            
            if ($targetResult->num_rows > 0) {
                // Update lead status to 'Site Visit' and assign to site manager
                $stmt = $conn->prepare("UPDATE leads SET assigned_to = ?, status = 'Site Visit', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ii", $siteManagerId, $leadId);
                
                if ($stmt->execute()) {
                    $success = 'Lead transferred to Site Manager successfully!';
                    header("Location: transfer-leads.php?success=transfer");
                    exit();
                } else {
                    $error = 'Error transferring lead: ' . $conn->error;
                }
                
                $stmt->close();
            } else {
                $error = 'Invalid Site Manager selected.';
            }
            
            $targetQuery->close();
        } else {
            $error = 'This lead does not belong to you or does not exist.';
        }
        
        $verifyQuery->close();
    }
}

// Check for success messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'transfer') {
        $success = 'Lead transferred to Site Manager successfully!';
    }
}

// Get filter values
$filterStatus = $_GET['status'] ?? '';
$filterType = $_GET['type'] ?? '';

// Build WHERE clause for filters
$whereConditions = ["l.assigned_to = $userId"];
$whereConditions[] = "(l.created_by IS NULL OR (SELECT role FROM users WHERE id = l.created_by) != 'Manager')";

if (!empty($filterStatus)) {
    $whereConditions[] = "l.status = '$filterStatus'";
}

if (!empty($filterType)) {
    $whereConditions[] = "l.property_type LIKE '%$filterType%'";
}

$whereClause = "WHERE " . implode(" AND ", $whereConditions);

// Fetch Stats
$totalLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE assigned_to = $userId AND (created_by IS NULL OR (SELECT role FROM users WHERE id = leads.created_by) != 'Manager')")->fetch_assoc()['total'] ?? 0;
$activeLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE assigned_to = $userId AND status IN ('Active', 'Follow Up', 'Qualified') AND (created_by IS NULL OR (SELECT role FROM users WHERE id = leads.created_by) != 'Manager')")->fetch_assoc()['total'] ?? 0;
$transferredLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE assigned_to IN (SELECT id FROM users WHERE role = 'Site Manager' OR role = 'Analyst') AND (SELECT role FROM users WHERE id = leads.created_by) != 'Manager'")->fetch_assoc()['total'] ?? 0;

// Fetch assigned leads
$leadsQuery = "SELECT l.*, u.name as site_manager_name
               FROM leads l
               LEFT JOIN users u ON l.assigned_to = u.id
               $whereClause
               ORDER BY l.created_at DESC
               LIMIT 100";
$leadsResult = $conn->query($leadsQuery);
$leads = [];
while ($row = $leadsResult->fetch_assoc()) {
    $leads[] = $row;
}

// Fetch all Site Managers
$siteManagersQuery = "SELECT id, name, email FROM users WHERE (role = 'Site Manager' OR role = 'Analyst') AND status = 'Active' ORDER BY name";
$siteManagersResult = $conn->query($siteManagersQuery);
$siteManagers = [];
while ($row = $siteManagersResult->fetch_assoc()) {
    $siteManagers[] = $row;
}

// Fetch unique property types
$propertyTypesQuery = "SELECT DISTINCT property_type FROM leads WHERE property_type IS NOT NULL AND property_type != '' AND assigned_to = $userId ORDER BY property_type";
$propertyTypesResult = $conn->query($propertyTypesQuery);
$propertyTypes = [];
while ($row = $propertyTypesResult->fetch_assoc()) {
    $propertyTypes[] = $row['property_type'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Leads - Kansal Admin Panel</title>
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
                        <h3>My Leads</h3>
                        <p class="stat-number"><?php echo number_format($totalLeads); ?></p>
                        <span class="stat-change positive">Assigned to you</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Leads</h3>
                        <p class="stat-number"><?php echo number_format($activeLeads); ?></p>
                        <span class="stat-change">Ready to transfer</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Transferred</h3>
                        <p class="stat-number"><?php echo number_format($transferredLeads); ?></p>
                        <span class="stat-change positive">To Site Managers</span>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Quick Filters</h2>
                        <a href="transfer-leads.php" class="view-all-btn">Reset</a>
                    </div>
                    <div class="card-body">
                        <div class="chip-group">
                            <a href="transfer-leads.php" class="chip <?php echo empty($filterStatus) ? 'active' : ''; ?>"><i class="fas fa-layer-group"></i> All</a>
                            <a href="transfer-leads.php?status=Active" class="chip <?php echo $filterStatus == 'Active' ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i> Active</a>
                            <a href="transfer-leads.php?status=Qualified" class="chip <?php echo $filterStatus == 'Qualified' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Qualified</a>
                            <a href="transfer-leads.php?status=Follow Up" class="chip <?php echo $filterStatus == 'Follow Up' ? 'active' : ''; ?>"><i class="fas fa-phone"></i> Follow Up</a>
                        </div>
                        <form class="filter-form" method="GET" action="transfer-leads.php">
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
                                    <label for="filterStatus">Status</label>
                                    <select id="filterStatus" name="status">
                                        <option value="">All Status</option>
                                        <option value="New" <?php echo $filterStatus == 'New' ? 'selected' : ''; ?>>New</option>
                                        <option value="Active" <?php echo $filterStatus == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Follow Up" <?php echo $filterStatus == 'Follow Up' ? 'selected' : ''; ?>>Follow Up</option>
                                        <option value="Qualified" <?php echo $filterStatus == 'Qualified' ? 'selected' : ''; ?>>Qualified</option>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                                <a href="transfer-leads.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>My Leads - Transfer to Site Manager</h2>
                    <div class="report-actions">
                        <span style="color: var(--text-secondary); font-size: 14px;">
                            <i class="fas fa-info-circle"></i> Select leads to transfer to Site Managers
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($siteManagers)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                            <p>No Site Managers available. Please contact administrator.</p>
                        </div>
                    <?php elseif (empty($leads)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                            <p>No leads found to transfer.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="transfer-leads.php" id="transferForm">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Lead ID</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Property Type</th>
                                            <th>Status</th>
                                            <th>Transfer To</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
                                                <td>
                                                    <span class="badge <?php echo getStatusBadgeClass($lead['status']); ?>">
                                                        <?php echo htmlspecialchars($lead['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <select name="site_manager_<?php echo $lead['id']; ?>" class="site-manager-select" style="min-width: 180px; padding: 6px 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px;">
                                                        <option value="">Select Site Manager</option>
                                                        <?php foreach ($siteManagers as $sm): ?>
                                                            <option value="<?php echo $sm['id']; ?>">
                                                                <?php echo htmlspecialchars($sm['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td class="table-actions">
                                                    <button type="button" class="btn-icon" title="Transfer" onclick="transferSingleLead(<?php echo $lead['id']; ?>)">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function transferSingleLead(leadId) {
            const select = document.querySelector(`select[name="site_manager_${leadId}"]`);
            const siteManagerId = select.value;
            
            if (!siteManagerId) {
                alert('Please select a Site Manager first.');
                return;
            }
            
            if (confirm('Are you sure you want to transfer this lead to the selected Site Manager?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'transfer-leads.php';
                
                const leadIdInput = document.createElement('input');
                leadIdInput.type = 'hidden';
                leadIdInput.name = 'lead_id';
                leadIdInput.value = leadId;
                form.appendChild(leadIdInput);
                
                const siteManagerInput = document.createElement('input');
                siteManagerInput.type = 'hidden';
                siteManagerInput.name = 'site_manager_id';
                siteManagerInput.value = siteManagerId;
                form.appendChild(siteManagerInput);
                
                const submitInput = document.createElement('input');
                submitInput.type = 'hidden';
                submitInput.name = 'transfer_lead';
                submitInput.value = '1';
                form.appendChild(submitInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

