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

$activePage = 'follow-ups';
$pageTitle = 'Follow-Ups';
$breadcrumb = 'Home / Follow-Ups';

// Get database connection
$conn = getDBConnection();

$error = '';
$success = '';

// Handle Add Follow-Up
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_followup'])) {
    $leadId = (int)$_POST['lead_id'];
    $followUpDate = $_POST['follow_up_date'];
    $followUpTime = $_POST['follow_up_time'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    $callDuration = trim($_POST['call_duration'] ?? '');
    $callOutcome = $_POST['call_outcome'] ?? 'Answered';
    $nextFollowUpDate = $_POST['next_follow_up_date'] ?? null;
    
    // Verify that this lead belongs to the logged-in telecaller
    $verifyQuery = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to = ?");
    $verifyQuery->bind_param("ii", $leadId, $userId);
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    
    if ($verifyResult->num_rows > 0) {
        // Insert follow-up
        $stmt = $conn->prepare("INSERT INTO follow_ups (lead_id, telecaller_id, follow_up_date, follow_up_time, notes, call_duration, call_outcome, next_follow_up_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $followUpTimeFormatted = !empty($followUpTime) ? $followUpTime : null;
        $nextFollowUpDateFormatted = !empty($nextFollowUpDate) ? $nextFollowUpDate : null;
        
        $stmt->bind_param("iissssss", $leadId, $userId, $followUpDate, $followUpTimeFormatted, $notes, $callDuration, $callOutcome, $nextFollowUpDateFormatted);
        
        if ($stmt->execute()) {
            // Update lead's follow_up_date and status
            $updateLead = $conn->prepare("UPDATE leads SET follow_up_date = ?, status = 'Follow Up', updated_at = NOW() WHERE id = ?");
            $updateLead->bind_param("si", $nextFollowUpDateFormatted ? $nextFollowUpDateFormatted : $followUpDate, $leadId);
            $updateLead->execute();
            $updateLead->close();
            
            $success = 'Follow-up added successfully!';
            header("Location: follow-ups.php?success=1");
            exit();
        } else {
            $error = 'Error adding follow-up: ' . $conn->error;
        }
        
        $stmt->close();
    } else {
        $error = 'You do not have permission to add follow-up for this lead.';
    }
    
    $verifyQuery->close();
}

// Handle Update Follow-Up
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_followup'])) {
    $followUpId = (int)$_POST['followup_id'];
    $followUpDate = $_POST['follow_up_date'];
    $followUpTime = $_POST['follow_up_time'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    $callDuration = trim($_POST['call_duration'] ?? '');
    $callOutcome = $_POST['call_outcome'] ?? 'Answered';
    $nextFollowUpDate = $_POST['next_follow_up_date'] ?? null;
    
    // Verify that this follow-up belongs to the logged-in telecaller
    $verifyQuery = $conn->prepare("SELECT id FROM follow_ups WHERE id = ? AND telecaller_id = ?");
    $verifyQuery->bind_param("ii", $followUpId, $userId);
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    
    if ($verifyResult->num_rows > 0) {
        // Update follow-up
        $stmt = $conn->prepare("UPDATE follow_ups SET follow_up_date = ?, follow_up_time = ?, notes = ?, call_duration = ?, call_outcome = ?, next_follow_up_date = ? WHERE id = ?");
        
        $followUpTimeFormatted = !empty($followUpTime) ? $followUpTime : null;
        $nextFollowUpDateFormatted = !empty($nextFollowUpDate) ? $nextFollowUpDate : null;
        
        $stmt->bind_param("ssssssi", $followUpDate, $followUpTimeFormatted, $notes, $callDuration, $callOutcome, $nextFollowUpDateFormatted, $followUpId);
        
        if ($stmt->execute()) {
            $success = 'Follow-up updated successfully!';
            header("Location: follow-ups.php?success=update");
            exit();
        } else {
            $error = 'Error updating follow-up: ' . $conn->error;
        }
        
        $stmt->close();
    } else {
        $error = 'You do not have permission to update this follow-up.';
    }
    
    $verifyQuery->close();
}

// Handle Delete Follow-Up
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $followUpId = (int)$_GET['delete'];
    
    // Verify that this follow-up belongs to the logged-in telecaller
    $verifyQuery = $conn->prepare("SELECT id FROM follow_ups WHERE id = ? AND telecaller_id = ?");
    $verifyQuery->bind_param("ii", $followUpId, $userId);
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM follow_ups WHERE id = ?");
        $stmt->bind_param("i", $followUpId);
        
        if ($stmt->execute()) {
            $success = 'Follow-up deleted successfully!';
            header("Location: follow-ups.php?success=delete");
            exit();
        } else {
            $error = 'Error deleting follow-up: ' . $conn->error;
        }
        
        $stmt->close();
    } else {
        $error = 'You do not have permission to delete this follow-up.';
    }
    
    $verifyQuery->close();
}

// Check for success messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $success = 'Follow-up added successfully!';
    } elseif ($_GET['success'] == 'update') {
        $success = 'Follow-up updated successfully!';
    } elseif ($_GET['success'] == 'delete') {
        $success = 'Follow-up deleted successfully!';
    }
}

// Get filter values
$filterDate = $_GET['date'] ?? '';
$filterLead = $_GET['lead'] ?? '';

// Fetch Stats
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$thisWeek = date('Y-m-d', strtotime('monday this week'));
$nextWeek = date('Y-m-d', strtotime('monday next week'));

$todayFollowUps = $conn->query("SELECT COUNT(*) as total FROM follow_ups WHERE telecaller_id = $userId AND follow_up_date = '$today'")->fetch_assoc()['total'] ?? 0;
$tomorrowFollowUps = $conn->query("SELECT COUNT(*) as total FROM follow_ups WHERE telecaller_id = $userId AND follow_up_date = '$tomorrow'")->fetch_assoc()['total'] ?? 0;
$thisWeekFollowUps = $conn->query("SELECT COUNT(*) as total FROM follow_ups WHERE telecaller_id = $userId AND follow_up_date >= '$thisWeek' AND follow_up_date < '$nextWeek'")->fetch_assoc()['total'] ?? 0;
$totalFollowUps = $conn->query("SELECT COUNT(*) as total FROM follow_ups WHERE telecaller_id = $userId")->fetch_assoc()['total'] ?? 0;

// Fetch upcoming follow-ups (next 7 days)
$upcomingQuery = "SELECT fu.*, l.lead_id, l.name as lead_name, l.phone, l.property_type, l.status as lead_status
                  FROM follow_ups fu
                  INNER JOIN leads l ON fu.lead_id = l.id
                  WHERE fu.telecaller_id = $userId 
                  AND fu.follow_up_date >= '$today'
                  ORDER BY fu.follow_up_date ASC, fu.follow_up_time ASC
                  LIMIT 50";

if (!empty($filterDate)) {
    $upcomingQuery = "SELECT fu.*, l.lead_id, l.name as lead_name, l.phone, l.property_type, l.status as lead_status
                      FROM follow_ups fu
                      INNER JOIN leads l ON fu.lead_id = l.id
                      WHERE fu.telecaller_id = $userId 
                      AND fu.follow_up_date = '$filterDate'
                      ORDER BY fu.follow_up_time ASC";
}

if (!empty($filterLead)) {
    $upcomingQuery = "SELECT fu.*, l.lead_id, l.name as lead_name, l.phone, l.property_type, l.status as lead_status
                      FROM follow_ups fu
                      INNER JOIN leads l ON fu.lead_id = l.id
                      WHERE fu.telecaller_id = $userId 
                      AND fu.lead_id = " . (int)$filterLead . "
                      ORDER BY fu.follow_up_date DESC, fu.follow_up_time DESC";
}

$upcomingResult = $conn->query($upcomingQuery);
$upcomingFollowUps = [];
while ($row = $upcomingResult->fetch_assoc()) {
    $upcomingFollowUps[] = $row;
}

// Fetch all assigned leads for dropdown
$leadsQuery = "SELECT id, lead_id, name, phone FROM leads WHERE assigned_to = $userId ORDER BY name";
$leadsResult = $conn->query($leadsQuery);
$assignedLeads = [];
while ($row = $leadsResult->fetch_assoc()) {
    $assignedLeads[] = $row;
}

// Fetch past follow-ups (last 30 days)
$pastQuery = "SELECT fu.*, l.lead_id, l.name as lead_name, l.phone, l.property_type
              FROM follow_ups fu
              INNER JOIN leads l ON fu.lead_id = l.id
              WHERE fu.telecaller_id = $userId 
              AND fu.follow_up_date < '$today'
              ORDER BY fu.follow_up_date DESC, fu.follow_up_time DESC
              LIMIT 50";
$pastResult = $conn->query($pastQuery);
$pastFollowUps = [];
while ($row = $pastResult->fetch_assoc()) {
    $pastFollowUps[] = $row;
}

// Helper function to format time
function formatTime($time) {
    if (empty($time) || $time == '00:00:00') return '-';
    return date('h:i A', strtotime($time));
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Follow-Ups - Kansal Admin Panel</title>
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
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Today's Follow-Ups</h3>
                        <p class="stat-number"><?php echo number_format($todayFollowUps); ?></p>
                        <span class="stat-change positive">Scheduled</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Tomorrow</h3>
                        <p class="stat-number"><?php echo number_format($tomorrowFollowUps); ?></p>
                        <span class="stat-change">Upcoming</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-content">
                        <h3>This Week</h3>
                        <p class="stat-number"><?php echo number_format($thisWeekFollowUps); ?></p>
                        <span class="stat-change positive">Scheduled</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Follow-Ups</h3>
                        <p class="stat-number"><?php echo number_format($totalFollowUps); ?></p>
                        <span class="stat-change">All time</span>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Schedule Follow-Up</h2>
                        <span class="view-all-btn" style="background: var(--primary-color); color: white; padding: 8px 16px; border-radius: 6px; font-size: 12px;">
                            <i class="fas fa-phone"></i> Add Call Notes
                        </span>
                    </div>
                    <div class="card-body">
                        <form class="settings-form" method="POST" action="follow-ups.php">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="lead_id">Select Lead <span style="color: red;">*</span></label>
                                    <select id="lead_id" name="lead_id" required>
                                        <option value="">Select Lead</option>
                                        <?php foreach ($assignedLeads as $lead): ?>
                                            <option value="<?php echo $lead['id']; ?>" <?php echo (isset($_POST['lead_id']) && $_POST['lead_id'] == $lead['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($lead['lead_id'] . ' - ' . $lead['name'] . ' (' . $lead['phone'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="follow_up_date">Follow-Up Date <span style="color: red;">*</span></label>
                                    <input type="date" id="follow_up_date" name="follow_up_date" value="<?php echo htmlspecialchars($_POST['follow_up_date'] ?? date('Y-m-d')); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="follow_up_time">Follow-Up Time</label>
                                    <input type="time" id="follow_up_time" name="follow_up_time" value="<?php echo htmlspecialchars($_POST['follow_up_time'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="call_duration">Call Duration</label>
                                    <input type="text" id="call_duration" name="call_duration" placeholder="e.g., 5 min, 10 min" value="<?php echo htmlspecialchars($_POST['call_duration'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="call_outcome">Call Outcome</label>
                                    <select id="call_outcome" name="call_outcome">
                                        <option value="Answered" <?php echo (isset($_POST['call_outcome']) && $_POST['call_outcome'] == 'Answered') ? 'selected' : 'selected'; ?>>Answered</option>
                                        <option value="No Answer" <?php echo (isset($_POST['call_outcome']) && $_POST['call_outcome'] == 'No Answer') ? 'selected' : ''; ?>>No Answer</option>
                                        <option value="Busy" <?php echo (isset($_POST['call_outcome']) && $_POST['call_outcome'] == 'Busy') ? 'selected' : ''; ?>>Busy</option>
                                        <option value="Call Back Later" <?php echo (isset($_POST['call_outcome']) && $_POST['call_outcome'] == 'Call Back Later') ? 'selected' : ''; ?>>Call Back Later</option>
                                        <option value="Not Interested" <?php echo (isset($_POST['call_outcome']) && $_POST['call_outcome'] == 'Not Interested') ? 'selected' : ''; ?>>Not Interested</option>
                                        <option value="Interested" <?php echo (isset($_POST['call_outcome']) && $_POST['call_outcome'] == 'Interested') ? 'selected' : ''; ?>>Interested</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="next_follow_up_date">Next Follow-Up Date</label>
                                    <input type="date" id="next_follow_up_date" name="next_follow_up_date" value="<?php echo htmlspecialchars($_POST['next_follow_up_date'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="notes">Conversation Notes <span style="color: red;">*</span></label>
                                <textarea id="notes" name="notes" rows="4" placeholder="What did you discuss? What was the customer's response? Any important points to remember?" required><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                                <small style="color: var(--text-secondary); font-size: 12px;">Add detailed notes about your conversation with the lead</small>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_followup" class="btn btn-primary"><i class="fas fa-save"></i> Save Follow-Up</button>
                                <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Clear</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>Quick Filters</h2>
                        <a href="follow-ups.php" class="view-all-btn">Reset</a>
                    </div>
                    <div class="card-body">
                        <div class="chip-group">
                            <a href="follow-ups.php" class="chip <?php echo empty($filterDate) ? 'active' : ''; ?>"><i class="fas fa-layer-group"></i> All</a>
                            <a href="follow-ups.php?date=<?php echo $today; ?>" class="chip <?php echo $filterDate == $today ? 'active' : ''; ?>"><i class="fas fa-calendar-day"></i> Today</a>
                            <a href="follow-ups.php?date=<?php echo $tomorrow; ?>" class="chip <?php echo $filterDate == $tomorrow ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Tomorrow</a>
                            <a href="follow-ups.php?date=<?php echo date('Y-m-d', strtotime('+2 days')); ?>" class="chip"><i class="fas fa-calendar"></i> Day After</a>
                        </div>
                        <form class="filter-form" method="GET" action="follow-ups.php">
                            <div class="filter-row">
                                <div class="form-group">
                                    <label for="filterDate">Date</label>
                                    <input type="date" id="filterDate" name="date" value="<?php echo htmlspecialchars($filterDate); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="filterLead">Lead</label>
                                    <select id="filterLead" name="lead">
                                        <option value="">All Leads</option>
                                        <?php foreach ($assignedLeads as $lead): ?>
                                            <option value="<?php echo $lead['id']; ?>" <?php echo $filterLead == $lead['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($lead['lead_id'] . ' - ' . $lead['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                                <a href="follow-ups.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Upcoming Follow-Ups</h2>
                    <div class="report-actions">
                        <span style="color: var(--text-secondary); font-size: 14px;">
                            <i class="fas fa-info-circle"></i> Scheduled follow-ups
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Lead</th>
                                    <th>Date & Time</th>
                                    <th>Call Outcome</th>
                                    <th>Duration</th>
                                    <th>Notes</th>
                                    <th>Next Follow-Up</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcomingFollowUps)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                            <i class="fas fa-calendar-check" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                            <p>No upcoming follow-ups scheduled. <a href="follow-ups.php">Schedule a follow-up</a></p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($upcomingFollowUps as $followUp): ?>
                                        <tr>
                                            <td>
                                                <div class="table-user">
                                                    <div>
                                                        <h4><?php echo htmlspecialchars($followUp['lead_name']); ?></h4>
                                                        <span><?php echo htmlspecialchars($followUp['lead_id']); ?> · <?php echo htmlspecialchars($followUp['phone']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo formatDate($followUp['follow_up_date']); ?></strong><br>
                                                <span style="color: var(--text-secondary); font-size: 12px;">
                                                    <?php echo formatTime($followUp['follow_up_time']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $followUp['call_outcome'] == 'Interested' ? 'badge-success' : 
                                                        ($followUp['call_outcome'] == 'Not Interested' ? 'badge-danger' : 
                                                        ($followUp['call_outcome'] == 'Call Back Later' ? 'badge-warning' : 'badge-info')); 
                                                ?>">
                                                    <?php echo htmlspecialchars($followUp['call_outcome']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($followUp['call_duration'] ?? '-'); ?></td>
                                            <td>
                                                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                                    <?php echo htmlspecialchars(substr($followUp['notes'] ?? '', 0, 100)); ?>
                                                    <?php if (strlen($followUp['notes'] ?? '') > 100): ?>...<?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo formatDate($followUp['next_follow_up_date']); ?></td>
                                            <td class="table-actions">
                                                <button type="button" class="btn-icon" title="View/Edit" onclick="openFollowUpModal(<?php echo htmlspecialchars(json_encode($followUp)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="follow-ups.php?delete=<?php echo $followUp['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Are you sure you want to delete this follow-up?');">
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

            <div class="content-card">
                <div class="card-header">
                    <h2>Past Follow-Ups (Last 30 Days)</h2>
                    <a href="#" class="view-all-btn">View All History</a>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <?php if (empty($pastFollowUps)): ?>
                            <li style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <i class="fas fa-history" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                <p>No past follow-ups found.</p>
                            </li>
                        <?php else: ?>
                            <?php foreach ($pastFollowUps as $followUp): ?>
                                <li>
                                    <div class="timeline-icon <?php echo $followUp['call_outcome'] == 'Interested' ? 'success' : ($followUp['call_outcome'] == 'Not Interested' ? 'danger' : 'info'); ?>">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h4>
                                            <?php echo htmlspecialchars($followUp['lead_name']); ?> 
                                            <span style="color: var(--text-secondary); font-weight: normal;">
                                                (<?php echo htmlspecialchars($followUp['lead_id']); ?>)
                                            </span>
                                        </h4>
                                        <p><strong>Outcome:</strong> <?php echo htmlspecialchars($followUp['call_outcome']); ?> 
                                            <?php if (!empty($followUp['call_duration'])): ?>
                                                · <strong>Duration:</strong> <?php echo htmlspecialchars($followUp['call_duration']); ?>
                                            <?php endif; ?>
                                        </p>
                                        <p><?php echo nl2br(htmlspecialchars($followUp['notes'] ?? 'No notes')); ?></p>
                                        <?php if (!empty($followUp['next_follow_up_date'])): ?>
                                            <p style="color: var(--primary-color); margin-top: 5px;">
                                                <i class="fas fa-calendar"></i> Next follow-up: <?php echo formatDate($followUp['next_follow_up_date']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <span><?php echo formatDate($followUp['follow_up_date']); ?> <?php echo formatTime($followUp['follow_up_time']); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Follow-Up Modal -->
    <div id="followUpModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
        <div style="max-width: 600px; margin: 50px auto; background: white; border-radius: 8px; padding: 30px; position: relative;">
            <button onclick="closeFollowUpModal()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary);">&times;</button>
            <h2 style="margin-bottom: 20px;">Edit Follow-Up</h2>
            <form method="POST" action="follow-ups.php" id="editFollowUpForm">
                <input type="hidden" name="followup_id" id="edit_followup_id">
                <input type="hidden" name="update_followup" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_follow_up_date">Follow-Up Date <span style="color: red;">*</span></label>
                        <input type="date" id="edit_follow_up_date" name="follow_up_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_follow_up_time">Follow-Up Time</label>
                        <input type="time" id="edit_follow_up_time" name="follow_up_time">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_call_duration">Call Duration</label>
                        <input type="text" id="edit_call_duration" name="call_duration" placeholder="e.g., 5 min, 10 min">
                    </div>
                    <div class="form-group">
                        <label for="edit_call_outcome">Call Outcome</label>
                        <select id="edit_call_outcome" name="call_outcome">
                            <option value="Answered">Answered</option>
                            <option value="No Answer">No Answer</option>
                            <option value="Busy">Busy</option>
                            <option value="Call Back Later">Call Back Later</option>
                            <option value="Not Interested">Not Interested</option>
                            <option value="Interested">Interested</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_next_follow_up_date">Next Follow-Up Date</label>
                    <input type="date" id="edit_next_follow_up_date" name="next_follow_up_date">
                </div>
                <div class="form-group">
                    <label for="edit_notes">Conversation Notes <span style="color: red;">*</span></label>
                    <textarea id="edit_notes" name="notes" rows="4" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Follow-Up</button>
                    <button type="button" class="btn btn-secondary" onclick="closeFollowUpModal()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openFollowUpModal(followUp) {
            document.getElementById('edit_followup_id').value = followUp.id;
            document.getElementById('edit_follow_up_date').value = followUp.follow_up_date;
            document.getElementById('edit_follow_up_time').value = followUp.follow_up_time || '';
            document.getElementById('edit_call_duration').value = followUp.call_duration || '';
            document.getElementById('edit_call_outcome').value = followUp.call_outcome || 'Answered';
            document.getElementById('edit_next_follow_up_date').value = followUp.next_follow_up_date || '';
            document.getElementById('edit_notes').value = followUp.notes || '';
            document.getElementById('followUpModal').style.display = 'block';
        }

        function closeFollowUpModal() {
            document.getElementById('followUpModal').style.display = 'none';
        }

        // Close modal on outside click
        document.getElementById('followUpModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeFollowUpModal();
            }
        });
    </script>
</body>
</html>

