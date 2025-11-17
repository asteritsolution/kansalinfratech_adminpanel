<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in
requireLogin();

$activePage = 'settings';
$pageTitle = 'Settings';
$breadcrumb = 'Home / Settings';

// Get logged in user
$loggedInUser = getLoggedInUser();

// Get database connection
$conn = getDBConnection();

$error = '';
$success = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['adminName'] ?? '');
    $email = trim($_POST['adminEmail'] ?? '');
    $phone = trim($_POST['adminPhone'] ?? '');
    
    // Validation
    if (empty($name) || empty($email)) {
        $error = 'Name and Email are required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email already exists for another user
        $checkQuery = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkQuery->bind_param("si", $email, $loggedInUser['id']);
        $checkQuery->execute();
        $checkResult = $checkQuery->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = 'Email already exists for another user.';
        } else {
            // Update user profile
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $phone, $loggedInUser['id']);
            
            if ($stmt->execute()) {
                // Update session
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['phone'] = $phone;
                
                $success = 'Profile updated successfully!';
                header("Location: settings.php?success=profile");
                exit();
            } else {
                $error = 'Error updating profile: ' . $conn->error;
            }
            
            $stmt->close();
        }
        
        $checkQuery->close();
    }
}

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All password fields are required.';
    } elseif ($newPassword != $confirmPassword) {
        $error = 'New password and confirm password do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Verify current password
        $userQuery = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $userQuery->bind_param("i", $loggedInUser['id']);
        $userQuery->execute();
        $userResult = $userQuery->get_result();
        $user = $userResult->fetch_assoc();
        
        if (password_verify($currentPassword, $user['password'])) {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $loggedInUser['id']);
            
            if ($stmt->execute()) {
                $success = 'Password updated successfully!';
                header("Location: settings.php?success=password");
                exit();
            } else {
                $error = 'Error updating password: ' . $conn->error;
            }
            
            $stmt->close();
        } else {
            $error = 'Current password is incorrect.';
        }
        
        $userQuery->close();
    }
}

// Check for success messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'profile') {
        $success = 'Profile updated successfully!';
    } elseif ($_GET['success'] == 'password') {
        $success = 'Password updated successfully!';
    }
}

// Fetch current user data
$userQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userQuery->bind_param("i", $loggedInUser['id']);
$userQuery->execute();
$userResult = $userQuery->get_result();
$currentUser = $userResult->fetch_assoc();
$userQuery->close();

// Fetch telecallers with their assigned leads
$telecallersQuery = "SELECT u.*, 
                     COUNT(l.id) as assigned_leads
                     FROM users u
                     LEFT JOIN leads l ON l.assigned_to = u.id
                     WHERE u.role = 'Telecaller'
                     GROUP BY u.id, u.name, u.email, u.phone, u.role, u.team, u.status, u.created_at
                     ORDER BY u.name ASC";
$telecallersResult = $conn->query($telecallersQuery);
$telecallers = [];
while ($row = $telecallersResult->fetch_assoc()) {
    $telecallers[] = $row;
}


function getAccessLevel($assignedLeads) {
    if ($assignedLeads > 40) {
        return 'Full';
    } elseif ($assignedLeads > 20) {
        return 'Standard';
    } else {
        return 'Limited';
    }
}

function getAccessBadgeClass($accessLevel) {
    if ($accessLevel == 'Full') {
        return 'badge-success';
    } elseif ($accessLevel == 'Standard') {
        return 'badge-info';
    } else {
        return 'badge-warning';
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Kansal Admin Panel</title>
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

            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h2>Profile Settings</h2>
                        <a href="#password-form" class="view-all-btn">Update Password</a>
                    </div>
                    <div class="card-body">
                        <form class="settings-form" method="POST" action="settings.php">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="adminName">Admin Name <span style="color: red;">*</span></label>
                                    <input type="text" id="adminName" name="adminName" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="adminEmail">Email <span style="color: red;">*</span></label>
                                    <input type="email" id="adminEmail" name="adminEmail" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="adminPhone">Phone Number</label>
                                    <input type="text" id="adminPhone" name="adminPhone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="adminRole">Role</label>
                                    <select id="adminRole" disabled>
                                        <option><?php echo htmlspecialchars($currentUser['role'] ?? 'Administrator'); ?></option>
                                    </select>
                                    <small style="color: var(--text-secondary); font-size: 12px;">Role cannot be changed from here</small>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                                <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="content-card" id="password-form">
                    <div class="card-header">
                        <h2>Change Password</h2>
                    </div>
                    <div class="card-body">
                        <form class="settings-form" method="POST" action="settings.php">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="currentPassword">Current Password <span style="color: red;">*</span></label>
                                    <input type="password" id="currentPassword" name="currentPassword" placeholder="Enter current password" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="newPassword">New Password <span style="color: red;">*</span></label>
                                    <input type="password" id="newPassword" name="newPassword" placeholder="Enter new password (min 6 characters)" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirmPassword">Confirm New Password <span style="color: red;">*</span></label>
                                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password" required>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_password" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h2>Notification Preferences</h2>
                    </div>
                    <div class="card-body">
                        <form class="settings-form">
                            <div class="toggle-group">
                                <label class="toggle-item">
                                    <span><i class="fas fa-envelope"></i> Email Alerts</span>
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                                <p>Receive daily summary of new leads and follow-ups.</p>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-item">
                                    <span><i class="fas fa-bell"></i> Push Notifications</span>
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                                <p>Stay updated with instant telecaller performance alerts.</p>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-item">
                                    <span><i class="fas fa-mobile-alt"></i> SMS Alerts</span>
                                    <input type="checkbox">
                                    <span class="toggle-slider"></span>
                                </label>
                                <p>Get SMS reminders for upcoming site visits.</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Telecaller Access Control</h2>
                    <a href="users.php" class="view-all-btn">Manage Roles</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Telecaller</th>
                                    <th>Email</th>
                                    <th>Assigned Leads</th>
                                    <th>Access Level</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($telecallers)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                            <i class="fas fa-user-tie" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                            <p>No telecallers found. <a href="users.php">Add telecallers</a></p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($telecallers as $tc): 
                                        $accessLevel = getAccessLevel($tc['assigned_leads']);
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="table-user">
                                                    <div class="avatar-circle"><?php echo getInitials($tc['name']); ?></div>
                                                    <div>
                                                        <h4><?php echo htmlspecialchars($tc['name']); ?></h4>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($tc['email']); ?></td>
                                            <td><?php echo $tc['assigned_leads']; ?></td>
                                            <td><span class="badge <?php echo getAccessBadgeClass($accessLevel); ?>"><?php echo $accessLevel; ?></span></td>
                                            <td><span class="badge <?php echo getStatusBadgeClass($tc['status']); ?>"><?php echo htmlspecialchars($tc['status']); ?></span></td>
                                            <td>
                                                <a href="users.php?id=<?php echo $tc['id']; ?>" class="btn-icon" title="Edit Access"><i class="fas fa-user-cog"></i></a>
                                                <a href="users.php?status=<?php echo $tc['status'] == 'Active' ? 'Suspended' : 'Active'; ?>&user_id=<?php echo $tc['id']; ?>" class="btn-icon" title="<?php echo $tc['status'] == 'Active' ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas fa-<?php echo $tc['status'] == 'Active' ? 'user-slash' : 'user-check'; ?>"></i>
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
