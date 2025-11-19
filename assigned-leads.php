<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in
requireLogin();

$activePage = 'assigned-leads';
$pageTitle = 'Assigned Leads';
$breadcrumb = 'Home / Assigned Leads';

// Get logged in user
$loggedInUser = getLoggedInUser();
$userRole = $loggedInUser['role'] ?? 'Administrator';
$userId = $loggedInUser['id'] ?? 0;

// Telecallers and Site Managers cannot access this page
if ($userRole == 'Telecaller' || $userRole == 'Site Manager' || $userRole == 'Analyst') {
    header("Location: index.php");
    exit();
}

// Get database connection
$conn = getDBConnection();

$error = '';
$success = '';

// Handle Delete Lead
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $leadId = (int)$_GET['delete'];
    
    // Check if user has permission
    if ($userRole == 'Telecaller') {
        $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to = ?");
        $verifyQuery->bind_param("ii", $leadId, $userId);
    } else {
        $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to IS NOT NULL");
        $verifyQuery->bind_param("i", $leadId);
    }
    
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->bind_param("i", $leadId);
        
        if ($stmt->execute()) {
            $success = 'Lead deleted successfully!';
            header("Location: assigned-leads.php?success=delete");
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

// Handle Update Lead
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_lead'])) {
    $leadId = (int)$_POST['lead_id'];
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $propertyType = trim($_POST['property_type'] ?? '');
    $leadSource = trim($_POST['lead_source'] ?? '');
    $budgetRange = trim($_POST['budget_range'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
    $followUpDate = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($name) || empty($phone)) {
        $error = 'Name and Phone are required fields.';
    } else {
        // Check permission
        if ($userRole == 'Telecaller') {
            $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to = ?");
            $verifyQuery->bind_param("ii", $leadId, $userId);
        } else {
            $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to IS NOT NULL");
            $verifyQuery->bind_param("i", $leadId);
        }
        
        $verifyQuery->execute();
        $verifyResult = $verifyQuery->get_result();
        
        if ($verifyResult->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE leads SET name = ?, phone = ?, email = ?, property_type = ?, lead_source = ?, budget_range = ?, status = ?, assigned_to = ?, follow_up_date = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("sssssssissi", $name, $phone, $email, $propertyType, $leadSource, $budgetRange, $status, $assignedTo, $followUpDate, $notes, $leadId);
            
            if ($stmt->execute()) {
                $success = 'Lead updated successfully!';
                header("Location: assigned-leads.php?success=update");
                exit();
            } else {
                $error = 'Error updating lead: ' . $conn->error;
            }
            
            $stmt->close();
        } else {
            $error = 'You do not have permission to update this lead.';
        }
        
        $verifyQuery->close();
    }
}

// Check for success messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'update') {
        $success = 'Lead updated successfully!';
    } elseif ($_GET['success'] == 'delete') {
        $success = 'Lead deleted successfully!';
    }
}

// Build WHERE clause - only show assigned leads, exclude personal leads
$whereConditions = [
    "l.assigned_to IS NOT NULL",
    "(l.created_by IS NULL OR (SELECT role FROM users WHERE id = l.created_by) != 'Manager')"
];
$params = [];
$paramTypes = '';

// If telecaller, only show their assigned leads
if ($userRole == 'Telecaller') {
    $whereConditions[] = "l.assigned_to = ?";
    $params[] = $userId;
    $paramTypes .= 'i';
}

$whereClause = "WHERE " . implode(" AND ", $whereConditions);

// Fetch assigned leads
$leadsQuery = "SELECT l.*, u.name as telecaller_name, u2.name as created_by_name
               FROM leads l
               LEFT JOIN users u ON l.assigned_to = u.id
               LEFT JOIN users u2 ON l.created_by = u2.id
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

// Fetch all telecallers for assignment dropdown
$telecallersQuery = "SELECT id, name FROM users WHERE role = 'Telecaller' AND status = 'Active' ORDER BY name";
$telecallersResult = $conn->query($telecallersQuery);
$telecallers = [];
while ($row = $telecallersResult->fetch_assoc()) {
    $telecallers[] = $row;
}

// Fetch unique property types for dropdown
$propertyTypesQuery = "SELECT DISTINCT property_type FROM leads WHERE property_type IS NOT NULL AND property_type != '' ORDER BY property_type";
$propertyTypesResult = $conn->query($propertyTypesQuery);
$propertyTypes = [];
while ($row = $propertyTypesResult->fetch_assoc()) {
    $propertyTypes[] = $row['property_type'];
}

// Get stats
$totalAssignedQuery = "SELECT COUNT(*) as total FROM leads WHERE assigned_to IS NOT NULL";
if ($userRole == 'Telecaller') {
    $totalAssignedQuery = "SELECT COUNT(*) as total FROM leads WHERE assigned_to = $userId";
}
$totalAssigned = $conn->query($totalAssignedQuery)->fetch_assoc()['total'] ?? 0;

$activeAssignedQuery = "SELECT COUNT(*) as total FROM leads WHERE assigned_to IS NOT NULL AND status IN ('Active', 'Follow Up', 'Qualified')";
if ($userRole == 'Telecaller') {
    $activeAssignedQuery = "SELECT COUNT(*) as total FROM leads WHERE assigned_to = $userId AND status IN ('Active', 'Follow Up', 'Qualified')";
}
$activeAssigned = $conn->query($activeAssignedQuery)->fetch_assoc()['total'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Leads - Kansal Admin Panel</title>
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

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-primary">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Assigned</h3>
                        <p class="stat-number"><?php echo number_format($totalAssigned); ?></p>
                        <span class="stat-change">Assigned leads</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Assigned</h3>
                        <p class="stat-number"><?php echo number_format($activeAssigned); ?></p>
                        <span class="stat-change">Currently active</span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Assigned Leads</h2>
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
                                            <p>No assigned leads found. <a href="all-leads.php">Add your first lead</a></p>
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
                                                <button class="btn-icon" title="View" onclick="viewLead(<?php echo htmlspecialchars(json_encode($lead)); ?>)"><i class="fas fa-eye"></i></button>
                                                <button class="btn-icon" title="Edit" onclick="editLead(<?php echo htmlspecialchars(json_encode($lead)); ?>)"><i class="fas fa-edit"></i></button>
                                                <a href="assigned-leads.php?delete=<?php echo $lead['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Are you sure you want to delete this lead? This action cannot be undone.');"><i class="fas fa-trash-alt"></i></a>
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

    <!-- View Lead Modal -->
    <div id="viewModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Lead Details</h2>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Edit Lead Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Lead</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="assigned-leads.php" id="editLeadForm">
                    <input type="hidden" name="lead_id" id="edit_lead_id">
                    <input type="hidden" name="update_lead" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_name">Name *</label>
                            <input type="text" id="edit_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Phone *</label>
                            <input type="text" id="edit_phone" name="phone" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="edit_property_type">Interested In</label>
                            <select id="edit_property_type" name="property_type">
                                <option value="">Select Property Type</option>
                                <option value="1 Acre + Farmhouse">1 Acre + Farmhouse</option>
                                <option value="Luxury 3BHK Flat">Luxury 3BHK Flat</option>
                                <option value="Luxury 4BHK Flat">Luxury 4BHK Flat</option>
                                <option value="Luxury 5BHK Flat">Luxury 5BHK Flat</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_lead_source">Lead Source</label>
                            <input type="text" id="edit_lead_source" name="lead_source">
                        </div>
                        <div class="form-group">
                            <label for="edit_budget_range">Budget Range</label>
                            <input type="text" id="edit_budget_range" name="budget_range">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_status">Status</label>
                            <select id="edit_status" name="status">
                                <option value="New">New</option>
                                <option value="Active">Active</option>
                                <option value="Follow Up">Follow Up</option>
                                <option value="Qualified">Qualified</option>
                                <option value="Site Visit">Site Visit</option>
                                <option value="Closed Won">Closed Won</option>
                                <option value="Closed Lost">Closed Lost</option>
                            </select>
                        </div>
                        <?php if ($userRole != 'Telecaller'): ?>
                        <div class="form-group">
                            <label for="edit_assigned_to">Assign To</label>
                            <select id="edit_assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($telecallers as $telecaller): ?>
                                    <option value="<?php echo $telecaller['id']; ?>"><?php echo htmlspecialchars($telecaller['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_follow_up_date">Follow Up Date</label>
                            <input type="date" id="edit_follow_up_date" name="follow_up_date">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_notes">Notes</label>
                        <textarea id="edit_notes" name="notes" rows="4"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Lead</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function viewLead(lead) {
            const modalBody = document.getElementById('viewModalBody');
            modalBody.innerHTML = `
                <div style="display: grid; gap: 20px;">
                    <div>
                        <strong>Lead ID:</strong> ${lead.lead_id || 'N/A'}
                    </div>
                    <div>
                        <strong>Name:</strong> ${lead.name || 'N/A'}
                    </div>
                    <div>
                        <strong>Phone:</strong> ${lead.phone || 'N/A'}
                    </div>
                    <div>
                        <strong>Email:</strong> ${lead.email || 'N/A'}
                    </div>
                    <div>
                        <strong>Property Type:</strong> ${lead.property_type || 'N/A'}
                    </div>
                    <div>
                        <strong>Lead Source:</strong> ${lead.lead_source || 'N/A'}
                    </div>
                    <div>
                        <strong>Budget Range:</strong> ${lead.budget_range || 'N/A'}
                    </div>
                    <div>
                        <strong>Status:</strong> <span class="badge ${getStatusBadgeClass(lead.status)}">${lead.status || 'N/A'}</span>
                    </div>
                    <div>
                        <strong>Assigned To:</strong> ${lead.telecaller_name || 'Unassigned'}
                    </div>
                    <div>
                        <strong>Follow Up Date:</strong> ${lead.follow_up_date ? new Date(lead.follow_up_date).toLocaleDateString() : 'N/A'}
                    </div>
                    <div>
                        <strong>Created By:</strong> ${lead.created_by_name || 'N/A'}
                    </div>
                    <div>
                        <strong>Created At:</strong> ${lead.created_at ? new Date(lead.created_at).toLocaleString() : 'N/A'}
                    </div>
                    <div>
                        <strong>Notes:</strong><br>
                        <div style="margin-top: 10px; padding: 10px; background: #f3f4f6; border-radius: 4px; white-space: pre-wrap;">${lead.notes || 'No notes available'}</div>
                    </div>
                </div>
            `;
            document.getElementById('viewModal').style.display = 'block';
        }

        function editLead(lead) {
            document.getElementById('edit_lead_id').value = lead.id;
            document.getElementById('edit_name').value = lead.name || '';
            document.getElementById('edit_phone').value = lead.phone || '';
            document.getElementById('edit_email').value = lead.email || '';
            // Set property type - handle both select and input
            const propertyTypeSelect = document.getElementById('edit_property_type');
            if (propertyTypeSelect) {
                propertyTypeSelect.value = lead.property_type || '';
            }
            // Set lead source - handle both select and input
            const leadSourceSelect = document.getElementById('edit_lead_source');
            if (leadSourceSelect) {
                leadSourceSelect.value = lead.lead_source || '';
            }
            document.getElementById('edit_budget_range').value = lead.budget_range || '';
            document.getElementById('edit_status').value = lead.status || 'New';
            document.getElementById('edit_assigned_to').value = lead.assigned_to || '';
            document.getElementById('edit_follow_up_date').value = lead.follow_up_date || '';
            document.getElementById('edit_notes').value = lead.notes || '';
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function getStatusBadgeClass(status) {
            const statusClasses = {
                'New': 'badge-info',
                'Active': 'badge-success',
                'Follow Up': 'badge-warning',
                'Qualified': 'badge-primary',
                'Site Visit': 'badge-info',
                'Closed Won': 'badge-success',
                'Closed Lost': 'badge-danger'
            };
            return statusClasses[status] || 'badge-secondary';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewModal');
            const editModal = document.getElementById('editModal');
            if (event.target == viewModal) {
                viewModal.style.display = 'none';
            }
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>

