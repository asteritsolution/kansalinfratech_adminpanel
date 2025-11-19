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

$activePage = 'assign-leads';
$pageTitle = 'Assign Leads';
$breadcrumb = 'Home / Assign Leads';

// Get database connection
$conn = getDBConnection();

$error = '';
$success = '';

// Handle Assign Lead
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_lead'])) {
    $leadId = (int)$_POST['lead_id'];
    $assignTo = (int)$_POST['assign_to'];
    
    // Verify that this lead belongs to the logged-in telecaller
    $verifyQuery = $conn->prepare("SELECT id, assigned_to FROM leads WHERE id = ? AND assigned_to = ?");
    $verifyQuery->bind_param("ii", $leadId, $userId);
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    
    if ($verifyResult->num_rows > 0) {
        // Verify the target telecaller exists and is active
        $targetQuery = $conn->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'Telecaller' AND status = 'Active'");
        $targetQuery->bind_param("i", $assignTo);
        $targetQuery->execute();
        $targetResult = $targetQuery->get_result();
        
        if ($targetResult->num_rows > 0) {
            $targetUser = $targetResult->fetch_assoc();
            
            // Update lead assignment
            $stmt = $conn->prepare("UPDATE leads SET assigned_to = ? WHERE id = ? AND assigned_to = ?");
            $stmt->bind_param("iii", $assignTo, $leadId, $userId);
            
            if ($stmt->execute()) {
                $success = 'Lead successfully assigned to ' . htmlspecialchars($targetUser['name']) . '!';
                header("Location: assign-leads.php?success=1");
                exit();
            } else {
                $error = 'Error assigning lead: ' . $conn->error;
            }
            
            $stmt->close();
        } else {
            $error = 'Invalid telecaller selected.';
        }
        
        $targetQuery->close();
    } else {
        $error = 'You do not have permission to assign this lead.';
    }
    
    $verifyQuery->close();
}

// Handle Bulk Assign
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_assign'])) {
    $leadIds = $_POST['lead_ids'] ?? [];
    $assignTo = (int)$_POST['bulk_assign_to'];
    
    if (empty($leadIds)) {
        $error = 'Please select at least one lead to assign.';
    } else {
        // Verify the target telecaller exists
        $targetQuery = $conn->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'Telecaller' AND status = 'Active'");
        $targetQuery->bind_param("i", $assignTo);
        $targetQuery->execute();
        $targetResult = $targetQuery->get_result();
        
        if ($targetResult->num_rows > 0) {
            $targetUser = $targetResult->fetch_assoc();
            $assignedCount = 0;
            $failedCount = 0;
            
            foreach ($leadIds as $leadId) {
                $leadId = (int)$leadId;
                
                // Verify lead belongs to logged-in telecaller
                $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to = ?");
                $verifyQuery->bind_param("ii", $leadId, $userId);
                $verifyQuery->execute();
                $verifyResult = $verifyQuery->get_result();
                
                if ($verifyResult->num_rows > 0) {
                    $stmt = $conn->prepare("UPDATE leads SET assigned_to = ? WHERE id = ?");
                    $stmt->bind_param("ii", $assignTo, $leadId);
                    
                    if ($stmt->execute()) {
                        $assignedCount++;
                    } else {
                        $failedCount++;
                    }
                    
                    $stmt->close();
                } else {
                    $failedCount++;
                }
                
                $verifyQuery->close();
            }
            
            if ($assignedCount > 0) {
                $success = "$assignedCount lead(s) successfully assigned to " . htmlspecialchars($targetUser['name']) . "!";
                if ($failedCount > 0) {
                    $success .= " ($failedCount lead(s) could not be assigned)";
                }
                header("Location: assign-leads.php?success=bulk");
                exit();
            } else {
                $error = 'No leads could be assigned. Please verify the selected leads belong to you.';
            }
        } else {
            $error = 'Invalid telecaller selected.';
        }
        
        $targetQuery->close();
    }
}

// Check for success messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $success = 'Lead assigned successfully!';
    } elseif ($_GET['success'] == 'bulk') {
        $success = 'Leads assigned successfully!';
    }
}

// Get filter values
$filterStatus = $_GET['status'] ?? '';
$filterType = $_GET['type'] ?? '';

// Build WHERE clause for filters - only show leads assigned to this telecaller
$whereConditions = ["l.assigned_to = $userId"];
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

$whereClause = "WHERE " . implode(" AND ", $whereConditions);

// Fetch Stats
$totalLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE assigned_to = $userId")->fetch_assoc()['total'] ?? 0;
$activeLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE assigned_to = $userId AND status IN ('Active', 'Follow Up', 'Qualified', 'Site Visit')")->fetch_assoc()['total'] ?? 0;
$newLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE assigned_to = $userId AND status = 'New'")->fetch_assoc()['total'] ?? 0;
$siteVisitLeads = $conn->query("SELECT COUNT(*) as total FROM leads WHERE assigned_to = $userId AND status = 'Site Visit'")->fetch_assoc()['total'] ?? 0;

// Fetch leads assigned to this telecaller
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

// Fetch all active telecallers (excluding self)
$telecallersQuery = "SELECT id, name FROM users WHERE role = 'Telecaller' AND status = 'Active' AND id != $userId ORDER BY name";
$telecallersResult = $conn->query($telecallersQuery);
$telecallers = [];
while ($row = $telecallersResult->fetch_assoc()) {
    $telecallers[] = $row;
}

// Fetch unique property types from assigned leads
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
    <title>Assign Leads - Kansal Admin Panel</title>
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
                        <h3>My Assigned Leads</h3>
                        <p class="stat-number"><?php echo number_format($totalLeads); ?></p>
                        <span class="stat-change positive">Total leads</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Leads</h3>
                        <p class="stat-number"><?php echo number_format($activeLeads); ?></p>
                        <span class="stat-change positive">In progress</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <h3>New Leads</h3>
                        <p class="stat-number"><?php echo number_format($newLeads); ?></p>
                        <span class="stat-change">Pending</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Site Visits</h3>
                        <p class="stat-number"><?php echo number_format($siteVisitLeads); ?></p>
                        <span class="stat-change positive">Scheduled</span>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Bulk Assign Leads</h2>
                        <span class="view-all-btn" style="background: var(--primary-color); color: white; padding: 8px 16px; border-radius: 6px; font-size: 12px;">
                            <i class="fas fa-users"></i> Assign Multiple
                        </span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="assign-leads.php" id="bulkAssignForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bulk_assign_to">Assign To Telecaller <span style="color: red;">*</span></label>
                                    <select id="bulk_assign_to" name="bulk_assign_to" required>
                                        <option value="">Select Telecaller</option>
                                        <?php foreach ($telecallers as $tc): ?>
                                            <option value="<?php echo $tc['id']; ?>">
                                                <?php echo htmlspecialchars($tc['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" name="bulk_assign" class="btn btn-primary" style="width: 100%;">
                                        <i class="fas fa-paper-plane"></i> Assign Selected Leads
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>Lead Filters</h2>
                        <a href="assign-leads.php" class="view-all-btn">Reset Filters</a>
                    </div>
                    <div class="card-body">
                        <div class="chip-group">
                            <a href="assign-leads.php" class="chip <?php echo empty($filterStatus) ? 'active' : ''; ?>"><i class="fas fa-layer-group"></i> All</a>
                            <a href="assign-leads.php?status=New" class="chip <?php echo $filterStatus == 'New' ? 'active' : ''; ?>"><i class="fas fa-star"></i> New</a>
                            <a href="assign-leads.php?status=Active" class="chip <?php echo $filterStatus == 'Active' ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i> Active</a>
                            <a href="assign-leads.php?status=Follow Up" class="chip <?php echo $filterStatus == 'Follow Up' ? 'active' : ''; ?>"><i class="fas fa-phone"></i> Follow Up</a>
                            <a href="assign-leads.php?status=Site Visit" class="chip <?php echo $filterStatus == 'Site Visit' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Site Visit</a>
                        </div>
                        <form class="filter-form" method="GET" action="assign-leads.php">
                            <div class="filter-row">
                                <div class="form-group">
                                    <label for="filterStatus">Status</label>
                                    <select id="filterStatus" name="status">
                                        <option value="">All Status</option>
                                        <option value="New" <?php echo $filterStatus == 'New' ? 'selected' : ''; ?>>New</option>
                                        <option value="Active" <?php echo $filterStatus == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Follow Up" <?php echo $filterStatus == 'Follow Up' ? 'selected' : ''; ?>>Follow Up</option>
                                        <option value="Qualified" <?php echo $filterStatus == 'Qualified' ? 'selected' : ''; ?>>Qualified</option>
                                        <option value="Site Visit" <?php echo $filterStatus == 'Site Visit' ? 'selected' : ''; ?>>Site Visit</option>
                                    </select>
                                </div>
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
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                                <a href="assign-leads.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>My Assigned Leads</h2>
                    <div class="report-actions">
                        <span style="color: var(--text-secondary); font-size: 14px;">
                            <i class="fas fa-info-circle"></i> Select leads and assign to other telecallers
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($telecallers)): ?>
                        <div class="alert alert-warning" style="margin: 20px 0;">
                            <i class="fas fa-exclamation-triangle"></i> No other telecallers available to assign leads to.
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="selectAll" title="Select All">
                                    </th>
                                    <th>Lead ID</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Property Type</th>
                                    <th>Status</th>
                                    <th>Follow Up</th>
                                    <th>Assign To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leads)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                            <p>No leads assigned to you. <a href="all-leads.php">View all leads</a></p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($leads as $lead): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="lead_ids[]" value="<?php echo $lead['id']; ?>" class="lead-checkbox">
                                            </td>
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
                                            <td><?php echo formatDate($lead['follow_up_date']); ?></td>
                                            <td>
                                                <?php if (!empty($telecallers)): ?>
                                                <form method="POST" action="assign-leads.php" style="display: inline;">
                                                    <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                                    <select class="role-select" name="assign_to" onchange="if(this.value && confirm('Are you sure you want to assign this lead to the selected telecaller?')) { this.form.submit(); }" style="min-width: 150px;">
                                                        <option value="">Keep Assigned to Me</option>
                                                        <?php foreach ($telecallers as $tc): ?>
                                                            <option value="<?php echo $tc['id']; ?>">
                                                                <?php echo htmlspecialchars($tc['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <input type="hidden" name="assign_lead" value="1">
                                                </form>
                                                <?php else: ?>
                                                <span style="color: var(--text-secondary); font-size: 12px;">No other telecallers</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="all-leads.php" class="btn-icon" title="View Details">
                                                    <i class="fas fa-eye"></i>
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

    <script>
        // Select All functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const leadCheckboxes = document.querySelectorAll('.lead-checkbox');
            const bulkAssignForm = document.getElementById('bulkAssignForm');
            
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    leadCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Update bulk assign form when checkboxes change
            leadCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateBulkAssignForm();
                });
            });

            // Handle bulk assign form submission
            if (bulkAssignForm) {
                bulkAssignForm.addEventListener('submit', function(e) {
                    const checkedBoxes = document.querySelectorAll('.lead-checkbox:checked');
                    if (checkedBoxes.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one lead to assign.');
                        return false;
                    }
                    
                    const assignTo = document.getElementById('bulk_assign_to').value;
                    if (!assignTo) {
                        e.preventDefault();
                        alert('Please select a telecaller to assign leads to.');
                        return false;
                    }
                    
                    // Add checked lead IDs to form
                    updateBulkAssignForm();
                });
            }
        });

        function updateBulkAssignForm() {
            const checkedBoxes = document.querySelectorAll('.lead-checkbox:checked');
            const form = document.getElementById('bulkAssignForm');
            
            if (!form) return;
            
            // Remove existing hidden inputs
            const existingInputs = form.querySelectorAll('input[name="lead_ids[]"][type="hidden"]');
            existingInputs.forEach(input => {
                input.remove();
            });
            
            // Add hidden inputs for checked leads
            checkedBoxes.forEach(checkbox => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'lead_ids[]';
                hiddenInput.value = checkbox.value;
                form.appendChild(hiddenInput);
            });
        }
    </script>
</body>
</html>

