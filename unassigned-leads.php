<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in
requireLogin();

$activePage = 'unassigned-leads';
$pageTitle = 'Unassigned Leads';
$breadcrumb = 'Home / Unassigned Leads';

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

// Handle Assign Lead
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_lead'])) {
    $leadId = (int)$_POST['lead_id'];
    $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
    
    if (empty($assignedTo)) {
        $error = 'Please select a telecaller to assign.';
    } else {
        // Verify that this lead is unassigned
        $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to IS NULL");
        $verifyQuery->bind_param("i", $leadId);
        $verifyQuery->execute();
        $verifyResult = $verifyQuery->get_result();
        
        if ($verifyResult->num_rows > 0) {
            // Verify the target telecaller exists and is active
            $targetQuery = $conn->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'Telecaller' AND status = 'Active'");
            $targetQuery->bind_param("i", $assignedTo);
            $targetQuery->execute();
            $targetResult = $targetQuery->get_result();
            
            if ($targetResult->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE leads SET assigned_to = ? WHERE id = ?");
                $stmt->bind_param("ii", $assignedTo, $leadId);
                
                if ($stmt->execute()) {
                    $success = 'Lead assigned successfully!';
                    header("Location: unassigned-leads.php?success=assign");
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
            $error = 'This lead is already assigned or does not exist.';
        }
        
        $verifyQuery->close();
    }
}

// Handle Delete Lead
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $leadId = (int)$_GET['delete'];
    
    // Verify that this lead is unassigned
    $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to IS NULL");
    $verifyQuery->bind_param("i", $leadId);
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->bind_param("i", $leadId);
        
        if ($stmt->execute()) {
            $success = 'Lead deleted successfully!';
            header("Location: unassigned-leads.php?success=delete");
            exit();
        } else {
            $error = 'Error deleting lead: ' . $conn->error;
        }
        
        $stmt->close();
    } else {
        $error = 'You can only delete unassigned leads.';
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
    $followUpDate = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($name) || empty($phone)) {
        $error = 'Name and Phone are required fields.';
    } else {
        // Verify that this lead is unassigned
        $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to IS NULL");
        $verifyQuery->bind_param("i", $leadId);
        $verifyQuery->execute();
        $verifyResult = $verifyQuery->get_result();
        
        if ($verifyResult->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE leads SET name = ?, phone = ?, email = ?, property_type = ?, lead_source = ?, budget_range = ?, status = ?, follow_up_date = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("sssssssssi", $name, $phone, $email, $propertyType, $leadSource, $budgetRange, $status, $followUpDate, $notes, $leadId);
            
            if ($stmt->execute()) {
                $success = 'Lead updated successfully!';
                header("Location: unassigned-leads.php?success=update");
                exit();
            } else {
                $error = 'Error updating lead: ' . $conn->error;
            }
            
            $stmt->close();
        } else {
            $error = 'You can only update unassigned leads.';
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
    } elseif ($_GET['success'] == 'assign') {
        $success = 'Lead assigned successfully!';
    }
}

// Fetch unassigned leads - exclude personal leads
$leadsQuery = "SELECT l.*, u2.name as created_by_name
               FROM leads l
               LEFT JOIN users u2 ON l.created_by = u2.id
               WHERE l.assigned_to IS NULL 
               AND (l.created_by IS NULL OR (SELECT role FROM users WHERE id = l.created_by) != 'Manager')
               ORDER BY l.created_at DESC
               LIMIT 100";

$leadsResult = $conn->query($leadsQuery);
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

// Fetch unique lead sources for dropdown
$leadSourcesQuery = "SELECT DISTINCT lead_source FROM leads WHERE lead_source IS NOT NULL AND lead_source != '' ORDER BY lead_source";
$leadSourcesResult = $conn->query($leadSourcesQuery);
$leadSources = [];
while ($row = $leadSourcesResult->fetch_assoc()) {
    $leadSources[] = $row['lead_source'];
}

// Get stats
// Exclude personal leads from stats
$personalLeadsFilter = " AND (created_by IS NULL OR (SELECT role FROM users WHERE id = leads.created_by) != 'Manager')";
$totalUnassigned = $conn->query("SELECT COUNT(*) as total FROM leads WHERE assigned_to IS NULL $personalLeadsFilter")->fetch_assoc()['total'] ?? 0;
$activeUnassigned = $conn->query("SELECT COUNT(*) as total FROM leads WHERE assigned_to IS NULL AND status IN ('Active', 'Follow Up', 'Qualified') $personalLeadsFilter")->fetch_assoc()['total'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unassigned Leads - Kansal Admin Panel</title>
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
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Unassigned</h3>
                        <p class="stat-number"><?php echo number_format($totalUnassigned); ?></p>
                        <span class="stat-change">Unassigned leads</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Unassigned</h3>
                        <p class="stat-number"><?php echo number_format($activeUnassigned); ?></p>
                        <span class="stat-change">Awaiting assignment</span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Unassigned Leads</h2>
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
                                    <th>Property</th>
                                    <th>Status</th>
                                    <th>Next Follow-up</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leads)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                            <p>No unassigned leads found. <a href="all-leads.php">Add your first lead</a></p>
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
                                            <td><?php echo htmlspecialchars($lead['property_type'] ?? 'N/A'); ?></td>
                                            <td><span class="badge <?php echo getStatusBadgeClass($lead['status']); ?>"><?php echo htmlspecialchars($lead['status']); ?></span></td>
                                            <td><?php echo formatDate($lead['follow_up_date']); ?></td>
                                            <td>
                                                <button class="btn-icon" title="View" onclick="viewLead(<?php echo htmlspecialchars(json_encode($lead)); ?>)"><i class="fas fa-eye"></i></button>
                                                <button class="btn-icon" title="Edit" onclick="editLead(<?php echo htmlspecialchars(json_encode($lead)); ?>)"><i class="fas fa-edit"></i></button>
                                                <button class="btn-icon" title="Assign" onclick="assignLead(<?php echo $lead['id']; ?>)"><i class="fas fa-user-plus"></i></button>
                                                <a href="unassigned-leads.php?delete=<?php echo $lead['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Are you sure you want to delete this lead? This action cannot be undone.');"><i class="fas fa-trash-alt"></i></a>
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
                <form class="settings-form" method="POST" action="unassigned-leads.php" id="editLeadForm">
                    <input type="hidden" name="lead_id" id="edit_lead_id">
                    <input type="hidden" name="update_lead" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_name">Lead Name <span style="color: red;">*</span></label>
                            <input type="text" id="edit_name" name="name" placeholder="Enter full name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Phone Number <span style="color: red;">*</span></label>
                            <input type="text" id="edit_phone" name="phone" placeholder="+91 98xxxxxxx" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email" placeholder="name@example.com">
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
                            <select id="edit_lead_source" name="lead_source">
                                <option value="">Select Source</option>
                                <?php foreach ($leadSources as $source): ?>
                                    <option value="<?php echo htmlspecialchars($source); ?>">
                                        <?php echo htmlspecialchars($source); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Website">Website</option>
                                <option value="Facebook Ads">Facebook Ads</option>
                                <option value="Google Ads">Google Ads</option>
                                <option value="WhatsApp Campaign">WhatsApp Campaign</option>
                                <option value="Referral">Referral</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_budget_range">Budget Range</label>
                            <input type="text" id="edit_budget_range" name="budget_range" placeholder="₹50L - ₹1.5Cr">
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
                        <div class="form-group">
                            <label for="edit_follow_up_date">Next Follow Up</label>
                            <input type="date" id="edit_follow_up_date" name="follow_up_date">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_notes">Notes</label>
                        <textarea id="edit_notes" name="notes" rows="3" placeholder="Add brief notes about this lead"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Lead</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Lead Modal -->
    <div id="assignModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Assign Lead</h2>
                <span class="close" onclick="closeModal('assignModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="unassigned-leads.php" id="assignLeadForm">
                    <input type="hidden" name="lead_id" id="assign_lead_id">
                    <input type="hidden" name="assign_lead" value="1">
                    
                    <div class="form-group">
                        <label for="assign_to">Assign To Telecaller *</label>
                        <select id="assign_to" name="assigned_to" required>
                            <option value="">Select Telecaller</option>
                            <?php foreach ($telecallers as $telecaller): ?>
                                <option value="<?php echo $telecaller['id']; ?>"><?php echo htmlspecialchars($telecaller['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Assign Lead</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancel</button>
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
                        <strong>Assigned To:</strong> Unassigned
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
            document.getElementById('edit_follow_up_date').value = lead.follow_up_date || '';
            document.getElementById('edit_notes').value = lead.notes || '';
            document.getElementById('editModal').style.display = 'block';
        }

        function assignLead(leadId) {
            document.getElementById('assign_lead_id').value = leadId;
            document.getElementById('assign_to').value = '';
            document.getElementById('assignModal').style.display = 'block';
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
            const assignModal = document.getElementById('assignModal');
            if (event.target == viewModal) {
                viewModal.style.display = 'none';
            }
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
            if (event.target == assignModal) {
                assignModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>

