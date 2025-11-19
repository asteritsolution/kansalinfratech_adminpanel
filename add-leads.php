<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in
requireLogin();

$activePage = 'add-leads';
$pageTitle = 'Add Leads';
$breadcrumb = 'Home / Add Leads';

// Get logged in user
$loggedInUser = getLoggedInUser();
$userRole = $loggedInUser['role'] ?? 'Administrator';
$userId = $loggedInUser['id'] ?? 0;

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
            header("Location: add-leads.php?success=1");
            exit();
        } else {
            $error = 'Error adding lead: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

// Check for success message
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = 'Lead added successfully!';
}

// Fetch all telecallers for dropdown
$telecallersQuery = "SELECT id, name FROM users WHERE role = 'Telecaller' AND status = 'Active' ORDER BY name";
$telecallersResult = $conn->query($telecallersQuery);
$telecallers = [];
while ($row = $telecallersResult->fetch_assoc()) {
    $telecallers[] = $row;
}

// Fetch unique property types
$propertyTypesQuery = "SELECT DISTINCT property_type FROM leads WHERE property_type IS NOT NULL AND property_type != '' ORDER BY property_type";
$propertyTypesResult = $conn->query($propertyTypesQuery);
$propertyTypes = [];
while ($row = $propertyTypesResult->fetch_assoc()) {
    $propertyTypes[] = $row['property_type'];
}

// Fetch unique lead sources
$leadSourcesQuery = "SELECT DISTINCT lead_source FROM leads WHERE lead_source IS NOT NULL AND lead_source != '' ORDER BY lead_source";
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
    <title>Add Leads - Kansal Admin Panel</title>
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

            <div class="content-card">
                <div class="card-header">
                    <h2>Add New Lead</h2>
                    <a href="all-leads.php" class="view-all-btn"><i class="fas fa-list"></i> View All Leads</a>
                </div>
                <div class="card-body">
                    <form class="settings-form" method="POST" action="add-leads.php">
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
                                    <option value="1 Acre + Farmhouse" <?php echo (isset($_POST['leadType']) && $_POST['leadType'] == '1 Acre + Farmhouse') ? 'selected' : ''; ?>>1 Acre + Farmhouse</option>
                                    <option value="Luxury 3BHK Flat" <?php echo (isset($_POST['leadType']) && $_POST['leadType'] == 'Luxury 3BHK Flat') ? 'selected' : ''; ?>>Luxury 3BHK Flat</option>
                                    <option value="Luxury 4BHK Flat" <?php echo (isset($_POST['leadType']) && $_POST['leadType'] == 'Luxury 4BHK Flat') ? 'selected' : ''; ?>>Luxury 4BHK Flat</option>
                                    <option value="Luxury 5BHK Flat" <?php echo (isset($_POST['leadType']) && $_POST['leadType'] == 'Luxury 5BHK Flat') ? 'selected' : ''; ?>>Luxury 5BHK Flat</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="leadSource">Lead Source</label>
                                <select id="leadSource" name="leadSource">
                                    <option value="">Select Source</option>
                                    <option value="Website" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'Website') ? 'selected' : ''; ?>>Website</option>
                                    <option value="Facebook Ads" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'Facebook Ads') ? 'selected' : ''; ?>>Facebook Ads</option>
                                    <option value="Google Ads" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'Google Ads') ? 'selected' : ''; ?>>Google Ads</option>
                                    <option value="WhatsApp Campaign" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'WhatsApp Campaign') ? 'selected' : ''; ?>>WhatsApp Campaign</option>
                                    <option value="Referral" <?php echo (isset($_POST['leadSource']) && $_POST['leadSource'] == 'Referral') ? 'selected' : ''; ?>>Referral</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="leadOwner">Assign To</label>
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
                            <button type="submit" name="add_lead" class="btn btn-primary"><i class="fas fa-save"></i> Save Lead</button>
                            <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Clear</button>
                            <a href="all-leads.php" class="btn btn-secondary"><i class="fas fa-list"></i> View All Leads</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

