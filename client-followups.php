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

$activePage = 'client-followups';
$pageTitle = 'Client Follow-Ups';
$breadcrumb = 'Home / Client Follow-Ups';

// Get database connection
$conn = getDBConnection();

// Get selected lead ID from query parameter
$selectedLeadId = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : 0;

// Fetch all assigned leads for this telecaller
$leadsQuery = "SELECT l.*, 
               (SELECT COUNT(*) FROM follow_ups WHERE lead_id = l.id AND telecaller_id = $userId) as followup_count
               FROM leads l
               WHERE l.assigned_to = $userId
               AND (l.created_by IS NULL OR (SELECT role FROM users WHERE id = l.created_by) != 'Manager')
               ORDER BY l.name ASC";
$leadsResult = $conn->query($leadsQuery);
$allLeads = [];
while ($row = $leadsResult->fetch_assoc()) {
    $allLeads[] = $row;
}

// Fetch follow-ups for selected lead
$followUps = [];
$selectedLead = null;
$totalFollowUps = 0;

if ($selectedLeadId > 0) {
    // Verify that this lead belongs to the logged-in telecaller
    $verifyQuery = $conn->prepare("SELECT * FROM leads WHERE id = ? AND assigned_to = ?");
    $verifyQuery->bind_param("ii", $selectedLeadId, $userId);
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $selectedLead = $verifyResult->fetch_assoc();
        
        // Fetch all follow-ups for this lead
        $followUpsQuery = "SELECT * FROM follow_ups 
                          WHERE lead_id = ? AND telecaller_id = ?
                          ORDER BY follow_up_date DESC, follow_up_time DESC";
        $stmt = $conn->prepare($followUpsQuery);
        $stmt->bind_param("ii", $selectedLeadId, $userId);
        $stmt->execute();
        $followUpsResult = $stmt->get_result();
        
        while ($row = $followUpsResult->fetch_assoc()) {
            $followUps[] = $row;
        }
        
        $totalFollowUps = count($followUps);
        $stmt->close();
    }
    $verifyQuery->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Follow-Ups - Kansal Admin Panel</title>
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
                <!-- Client List -->
                <div class="content-card">
                    <div class="card-header">
                        <h2>My Clients</h2>
                        <span class="view-all-btn">
                            <i class="fas fa-users"></i> <?php echo count($allLeads); ?> Clients
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($allLeads)): ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <i class="fas fa-user-friends" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                <p>No clients assigned to you yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="client-list" style="max-height: 600px; overflow-y: auto;">
                                <?php foreach ($allLeads as $lead): ?>
                                    <a href="client-followups.php?lead_id=<?php echo $lead['id']; ?>" 
                                       class="client-item <?php echo $selectedLeadId == $lead['id'] ? 'active' : ''; ?>"
                                       style="display: block; padding: 15px; margin-bottom: 10px; border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.3s;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div style="flex: 1;">
                                                <h4 style="margin: 0 0 5px 0; color: var(--text-primary);">
                                                    <?php echo htmlspecialchars($lead['name']); ?>
                                                </h4>
                                                <p style="margin: 0; color: var(--text-secondary); font-size: 14px;">
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($lead['phone']); ?>
                                                </p>
                                                <?php if (!empty($lead['email'])): ?>
                                                    <p style="margin: 5px 0 0 0; color: var(--text-secondary); font-size: 14px;">
                                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($lead['email']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div style="text-align: right;">
                                                <span class="badge badge-info" style="display: block; margin-bottom: 5px;">
                                                    <?php echo $lead['followup_count']; ?> Calls
                                                </span>
                                                <span class="badge <?php echo getStatusBadgeClass($lead['status']); ?>" style="display: block;">
                                                    <?php echo htmlspecialchars($lead['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Follow-Up History -->
                <div class="content-card">
                    <div class="card-header">
                        <h2>
                            <?php if ($selectedLead): ?>
                                Follow-Up History - <?php echo htmlspecialchars($selectedLead['name']); ?>
                            <?php else: ?>
                                Select a Client
                            <?php endif; ?>
                        </h2>
                        <?php if ($selectedLead): ?>
                            <span class="view-all-btn">
                                <i class="fas fa-phone-alt"></i> <?php echo $totalFollowUps; ?> Total Calls
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (!$selectedLead): ?>
                            <div style="text-align: center; padding: 60px; color: var(--text-secondary);">
                                <i class="fas fa-hand-pointer" style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;"></i>
                                <h3 style="color: var(--text-primary); margin-bottom: 10px;">Select a Client</h3>
                                <p>Click on any client from the list to view their follow-up history</p>
                            </div>
                        <?php elseif (empty($followUps)): ?>
                            <div style="text-align: center; padding: 60px; color: var(--text-secondary);">
                                <i class="fas fa-phone-slash" style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;"></i>
                                <h3 style="color: var(--text-primary); margin-bottom: 10px;">No Follow-Ups Yet</h3>
                                <p>No conversation history found for this client.</p>
                                <a href="follow-ups.php" class="btn btn-primary" style="margin-top: 20px;">
                                    <i class="fas fa-plus"></i> Add Follow-Up
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Client Info Card -->
                            <div style="background: linear-gradient(135deg, var(--primary-color) 0%, #667eea 100%); padding: 20px; border-radius: 10px; margin-bottom: 25px; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                                    <div style="flex: 1; min-width: 200px;">
                                        <h3 style="margin: 0 0 8px 0; color: white; font-size: 20px; font-weight: 600;">
                                            <?php echo htmlspecialchars($selectedLead['name']); ?>
                                        </h3>
                                        <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: 14px; opacity: 0.95;">
                                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($selectedLead['phone']); ?></span>
                                            <?php if (!empty($selectedLead['email'])): ?>
                                                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($selectedLead['email']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="badge" style="background: rgba(255,255,255,0.25); color: white; padding: 8px 16px; border-radius: 20px; font-size: 13px;">
                                            <?php echo htmlspecialchars($selectedLead['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Follow-Ups Timeline -->
                            <div style="position: relative; padding-left: 30px;">
                                <?php foreach ($followUps as $index => $followUp): ?>
                                    <div style="position: relative; padding-bottom: 25px;">
                                        <!-- Timeline Line -->
                                        <?php if ($index < count($followUps) - 1): ?>
                                            <div style="position: absolute; left: -25px; top: 0; bottom: -25px; width: 2px; background: var(--border-color);"></div>
                                        <?php endif; ?>
                                        
                                        <!-- Timeline Dot -->
                                        <div style="position: absolute; left: -30px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: var(--primary-color); border: 3px solid white; box-shadow: 0 0 0 2px var(--primary-color); z-index: 1;"></div>
                                        
                                        <!-- Follow-Up Card -->
                                        <div style="background: white; border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.3s ease;">
                                            <!-- Header Row -->
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                                                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                                    <div>
                                                        <div style="font-weight: 600; color: var(--text-primary); font-size: 15px; margin-bottom: 2px;">
                                                            <i class="fas fa-calendar-alt" style="color: var(--primary-color); margin-right: 6px;"></i>
                                                            <?php echo formatDate($followUp['follow_up_date']); ?>
                                                        </div>
                                                        <?php if (!empty($followUp['follow_up_time'])): ?>
                                                            <div style="font-size: 13px; color: var(--text-secondary);">
                                                                <i class="fas fa-clock" style="margin-right: 4px;"></i>
                                                                <?php echo date('h:i A', strtotime($followUp['follow_up_time'])); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                    <?php if (!empty($followUp['call_duration'])): ?>
                                                        <span class="badge badge-info" style="font-size: 12px; padding: 6px 12px;">
                                                            <i class="fas fa-hourglass-half"></i> <?php echo htmlspecialchars($followUp['call_duration']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="badge <?php 
                                                        echo $followUp['call_outcome'] == 'Interested' ? 'badge-success' : 
                                                            ($followUp['call_outcome'] == 'Not Interested' ? 'badge-danger' : 
                                                            ($followUp['call_outcome'] == 'Call Back Later' ? 'badge-warning' : 'badge-info')); 
                                                    ?>" style="font-size: 12px; padding: 6px 12px;">
                                                        <?php echo htmlspecialchars($followUp['call_outcome']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Conversation Notes -->
                                            <?php if (!empty($followUp['notes'])): ?>
                                                <div style="background: #f8f9fa; padding: 14px; border-radius: 8px; border-left: 4px solid var(--primary-color); margin-top: 12px;">
                                                    <div style="color: var(--text-secondary); margin-bottom: 10px; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                                        <i class="fas fa-comment-alt" style="color: var(--primary-color);"></i> 
                                                        <span>Conversation Notes</span>
                                                    </div>
                                                    <div style="color: var(--text-primary); line-height: 1.7; word-wrap: break-word; overflow-wrap: break-word; white-space: pre-wrap; font-size: 14px;">
                                                        <?php echo htmlspecialchars($followUp['notes']); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Footer Info -->
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px;">
                                                <?php if (!empty($followUp['next_follow_up_date'])): ?>
                                                    <div style="font-size: 12px; color: var(--text-secondary);">
                                                        <i class="fas fa-calendar-check" style="color: var(--primary-color); margin-right: 5px;"></i>
                                                        Next Follow-Up: <strong style="color: var(--primary-color);"><?php echo formatDate($followUp['next_follow_up_date']); ?></strong>
                                                    </div>
                                                <?php else: ?>
                                                    <div></div>
                                                <?php endif; ?>
                                                <div style="font-size: 11px; color: var(--text-secondary);">
                                                    <i class="fas fa-clock"></i> Recorded: <?php echo formatDateTime($followUp['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .client-item {
            transition: all 0.3s ease;
        }
        
        .client-item:hover {
            background: var(--bg-secondary) !important;
            border-color: var(--primary-color) !important;
            transform: translateX(5px);
        }
        
        .client-item.active {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
        }
        
        .client-item.active h4,
        .client-item.active p {
            color: white !important;
        }
        
        .client-item.active .badge {
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
        }
        
        .timeline > div:last-child {
            border-left: none !important;
            padding-bottom: 0 !important;
        }
    </style>
</body>
</html>

