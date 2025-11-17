<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in
requireLogin();

// Get logged in user
$loggedInUser = getLoggedInUser();
$userRole = $loggedInUser['role'] ?? 'Telecaller';

// Only Manager and Administrator can access this page
if ($userRole != 'Manager' && $userRole != 'Administrator') {
    header("Location: index.php");
    exit();
}

$activePage = 'users';
$pageTitle = 'User Management';
$breadcrumb = 'Home / Users';

$error = '';
$success = '';

// Get database connection
$conn = getDBConnection();

// Handle Add User Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    // Verify user has permission
    if ($userRole != 'Manager' && $userRole != 'Administrator') {
        $error = 'You do not have permission to create users.';
    } else {
    $name = trim($_POST['userName'] ?? '');
    $email = trim($_POST['userEmail'] ?? '');
    $phone = trim($_POST['userPhone'] ?? '');
    $role = $_POST['userRole'] ?? 'Telecaller';
    $team = trim($_POST['userTeam'] ?? '');
    $password = $_POST['userPassword'] ?? '';
    $status = 'Active';
    
    // Validation
    if (empty($name) || empty($email)) {
        $error = 'Name and Email are required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Generate password if not provided
        if (empty($password)) {
            $password = bin2hex(random_bytes(4)); // 8 character random password
        }
        
        // Check if email or username already exists
        $checkQuery = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkQuery->bind_param("s", $email);
        $checkQuery->execute();
        $checkResult = $checkQuery->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = 'Email already exists.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate username from email
            $username = explode('@', $email)[0];
            
            // Check if username exists, if yes add number
            $usernameCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $usernameCheck->bind_param("s", $username);
            $usernameCheck->execute();
            $usernameResult = $usernameCheck->get_result();
            
            $counter = 1;
            $originalUsername = $username;
            while ($usernameResult->num_rows > 0) {
                $username = $originalUsername . $counter;
                $usernameCheck->bind_param("s", $username);
                $usernameCheck->execute();
                $usernameResult = $usernameCheck->get_result();
                $counter++;
            }
            $usernameCheck->close();
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, email, phone, role, team, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $username, $hashed_password, $name, $email, $phone, $role, $team, $status);
            
            if ($stmt->execute()) {
                $success = 'User created successfully! Username: ' . $username . ' | Temporary Password: ' . $password;
                header("Location: users.php?success=1&username=" . urlencode($username) . "&password=" . urlencode($password));
                exit();
            } else {
                $error = 'Error creating user: ' . $conn->error;
            }
            
            $stmt->close();
        }
        
        $checkQuery->close();
    }
    }
}

// Handle Role Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    // Verify user has permission
    if ($userRole != 'Manager' && $userRole != 'Administrator') {
        $error = 'You do not have permission to update user roles.';
    } else {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['new_role'];
    
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $newRole, $userId);
    
    if ($stmt->execute()) {
        $success = 'User role updated successfully!';
        header("Location: users.php?success=role");
        exit();
    } else {
        $error = 'Error updating role: ' . $conn->error;
    }
    
    $stmt->close();
    }
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    // Verify user has permission
    if ($userRole != 'Manager' && $userRole != 'Administrator') {
        $error = 'You do not have permission to update user status.';
    } else {
    $userId = (int)$_POST['user_id'];
    $newStatus = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $userId);
    
    if ($stmt->execute()) {
        $success = 'User status updated successfully!';
        header("Location: users.php?success=status");
        exit();
    } else {
        $error = 'Error updating status: ' . $conn->error;
    }
    
    $stmt->close();
    }
}

// Handle Delete User
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Verify user has permission
    if ($userRole != 'Manager' && $userRole != 'Administrator') {
        $error = 'You do not have permission to delete users.';
    } else {
    $userId = (int)$_GET['delete'];
    
    // Don't allow deleting own account
    if ($userId == $loggedInUser['id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $success = 'User deleted successfully!';
            header("Location: users.php?success=delete");
            exit();
        } else {
            $error = 'Error deleting user: ' . $conn->error;
        }
        
        $stmt->close();
    }
    }
}

// Check for success messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1 && isset($_GET['username']) && isset($_GET['password'])) {
        $success = 'User created successfully! Username: ' . htmlspecialchars($_GET['username']) . ' | Temporary Password: ' . htmlspecialchars($_GET['password']);
    } elseif ($_GET['success'] == 'role') {
        $success = 'User role updated successfully!';
    } elseif ($_GET['success'] == 'status') {
        $success = 'User status updated successfully!';
    } elseif ($_GET['success'] == 'delete') {
        $success = 'User deleted successfully!';
    }
}

// Get filter values
$filterRole = $_GET['role'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterTeam = $_GET['team'] ?? '';

// Build WHERE clause
$whereConditions = [];
$params = [];
$paramTypes = '';

if (!empty($filterRole)) {
    $whereConditions[] = "u.role = ?";
    $params[] = $filterRole;
    $paramTypes .= 's';
}

if (!empty($filterStatus)) {
    $whereConditions[] = "u.status = ?";
    $params[] = $filterStatus;
    $paramTypes .= 's';
}

if (!empty($filterTeam)) {
    $whereConditions[] = "u.team LIKE ?";
    $params[] = "%$filterTeam%";
    $paramTypes .= 's';
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Fetch Stats
$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'] ?? 0;
$telecallers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'Telecaller' AND status = 'Active'")->fetch_assoc()['total'] ?? 0;
$managers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'Manager' AND status = 'Active'")->fetch_assoc()['total'] ?? 0;
$pendingUsers = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'Pending'")->fetch_assoc()['total'] ?? 0;

// Calculate month change
$thisMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));
$thisMonthUsers = $conn->query("SELECT COUNT(*) as total FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'")->fetch_assoc()['total'] ?? 0;
$lastMonthUsers = $conn->query("SELECT COUNT(*) as total FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth'")->fetch_assoc()['total'] ?? 0;
$monthChange = $lastMonthUsers > 0 ? round((($thisMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100) : 0;

// Fetch users with filters
$usersQuery = "SELECT u.*, 
               (SELECT COUNT(*) FROM leads WHERE assigned_to = u.id) as assigned_leads
               FROM users u
               $whereClause
               ORDER BY u.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($usersQuery);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $usersResult = $stmt->get_result();
} else {
    $usersResult = $conn->query($usersQuery);
}

$users = [];
while ($row = $usersResult->fetch_assoc()) {
    $users[] = $row;
}

// Fetch unique teams
$teamsQuery = "SELECT DISTINCT team FROM users WHERE team IS NOT NULL AND team != '' ORDER BY team";
$teamsResult = $conn->query($teamsQuery);
$teams = [];
while ($row = $teamsResult->fetch_assoc()) {
    $teams[] = $row['team'];
}

// Fetch recent user activities (last 5 users created)
$activitiesQuery = "SELECT u.*, 
                    (SELECT COUNT(*) FROM leads WHERE assigned_to = u.id) as assigned_leads
                    FROM users u
                    ORDER BY u.created_at DESC
                    LIMIT 5";
$activitiesResult = $conn->query($activitiesQuery);
$recentActivities = [];
while ($row = $activitiesResult->fetch_assoc()) {
    $recentActivities[] = $row;
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Kansal Admin Panel</title>
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
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Users</h3>
                        <p class="stat-number"><?php echo number_format($totalUsers); ?></p>
                        <span class="stat-change <?php echo $monthChange >= 0 ? 'positive' : ''; ?>">
                            <?php echo $monthChange >= 0 ? '+' : ''; ?><?php echo $monthChange; ?>% this month
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Telecallers</h3>
                        <p class="stat-number"><?php echo number_format($telecallers); ?></p>
                        <span class="stat-change positive">Active</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Managers</h3>
                        <p class="stat-number"><?php echo number_format($managers); ?></p>
                        <span class="stat-change">Active managers</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Pending Invites</h3>
                        <p class="stat-number"><?php echo number_format($pendingUsers); ?></p>
                        <span class="stat-change">Awaiting approval</span>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <?php if ($userRole == 'Manager' || $userRole == 'Administrator'): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2>Add New User</h2>
                        <span class="view-all-btn" style="background: var(--primary-color); color: white; padding: 8px 16px; border-radius: 6px; font-size: 12px;">
                            <i class="fas fa-user-shield"></i> Manager/Admin Only
                        </span>
                    </div>
                    <div class="card-body">
                        <form class="settings-form" method="POST" action="users.php">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="userName">Full Name <span style="color: red;">*</span></label>
                                    <input type="text" id="userName" name="userName" placeholder="Enter full name" value="<?php echo htmlspecialchars($_POST['userName'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="userEmail">Email Address <span style="color: red;">*</span></label>
                                    <input type="email" id="userEmail" name="userEmail" placeholder="user@example.com" value="<?php echo htmlspecialchars($_POST['userEmail'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="userPhone">Phone Number</label>
                                    <input type="text" id="userPhone" name="userPhone" placeholder="+91 98xxxxxxx" value="<?php echo htmlspecialchars($_POST['userPhone'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="userRole">Role</label>
                                    <select id="userRole" name="userRole">
                                        <option value="Telecaller" <?php echo (isset($_POST['userRole']) && $_POST['userRole'] == 'Telecaller') ? 'selected' : 'selected'; ?>>Telecaller</option>
                                        <option value="Manager" <?php echo (isset($_POST['userRole']) && $_POST['userRole'] == 'Manager') ? 'selected' : ''; ?>>Manager</option>
                                        <option value="Administrator" <?php echo (isset($_POST['userRole']) && $_POST['userRole'] == 'Administrator') ? 'selected' : ''; ?>>Administrator</option>
                                        <option value="Analyst" <?php echo (isset($_POST['userRole']) && $_POST['userRole'] == 'Analyst') ? 'selected' : ''; ?>>Analyst</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="userPassword">Temporary Password</label>
                                    <input type="text" id="userPassword" name="userPassword" placeholder="Auto-generated if left empty">
                                    <small style="color: var(--text-secondary); font-size: 12px;">Leave empty for auto-generated password</small>
                                </div>
                                <div class="form-group">
                                    <label for="userTeam">Team</label>
                                    <select id="userTeam" name="userTeam">
                                        <option value="">Select Team</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo htmlspecialchars($team); ?>" <?php echo (isset($_POST['userTeam']) && $_POST['userTeam'] == $team) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($team); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="North Zone" <?php echo (isset($_POST['userTeam']) && $_POST['userTeam'] == 'North Zone') ? 'selected' : ''; ?>>North Zone</option>
                                        <option value="South Zone" <?php echo (isset($_POST['userTeam']) && $_POST['userTeam'] == 'South Zone') ? 'selected' : ''; ?>>South Zone</option>
                                        <option value="Plots Team" <?php echo (isset($_POST['userTeam']) && $_POST['userTeam'] == 'Plots Team') ? 'selected' : ''; ?>>Plots Team</option>
                                        <option value="Flats Team" <?php echo (isset($_POST['userTeam']) && $_POST['userTeam'] == 'Flats Team') ? 'selected' : ''; ?>>Flats Team</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_user" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create User</button>
                                <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <div class="content-card">
                    <div class="card-header">
                        <h2>User Filters</h2>
                        <a href="users.php" class="view-all-btn">Reset</a>
                    </div>
                    <div class="card-body">
                        <div class="chip-group">
                            <a href="users.php" class="chip <?php echo empty($filterStatus) ? 'active' : ''; ?>"><i class="fas fa-layer-group"></i> All</a>
                            <a href="users.php?status=Active" class="chip <?php echo $filterStatus == 'Active' ? 'active' : ''; ?>"><i class="fas fa-user-check"></i> Active</a>
                            <a href="users.php?status=Pending" class="chip <?php echo $filterStatus == 'Pending' ? 'active' : ''; ?>"><i class="fas fa-user-clock"></i> Pending</a>
                            <a href="users.php?status=Suspended" class="chip <?php echo $filterStatus == 'Suspended' ? 'active' : ''; ?>"><i class="fas fa-user-lock"></i> Suspended</a>
                        </div>
                        <form class="filter-form" method="GET" action="users.php">
                            <div class="filter-row">
                                <div class="form-group">
                                    <label for="filterRole">Role</label>
                                    <select id="filterRole" name="role">
                                        <option value="">All Roles</option>
                                        <option value="Telecaller" <?php echo $filterRole == 'Telecaller' ? 'selected' : ''; ?>>Telecaller</option>
                                        <option value="Manager" <?php echo $filterRole == 'Manager' ? 'selected' : ''; ?>>Manager</option>
                                        <option value="Administrator" <?php echo $filterRole == 'Administrator' ? 'selected' : ''; ?>>Administrator</option>
                                        <option value="Analyst" <?php echo $filterRole == 'Analyst' ? 'selected' : ''; ?>>Analyst</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterTeam">Team</label>
                                    <select id="filterTeam" name="team">
                                        <option value="">All Teams</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo htmlspecialchars($team); ?>" <?php echo $filterTeam == $team ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($team); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterStatus">Status</label>
                                    <select id="filterStatus" name="status">
                                        <option value="">All Status</option>
                                        <option value="Active" <?php echo $filterStatus == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Pending" <?php echo $filterStatus == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Suspended" <?php echo $filterStatus == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterDateUser">Joined</label>
                                    <select id="filterDateUser">
                                        <option>This Month</option>
                                        <option>Last 3 Months</option>
                                        <option>Last 6 Months</option>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                                <a href="users.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php if ($userRole == 'Administrator'): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2>User Directory</h2>
                    <div class="report-actions">
                        <a href="users.php" class="btn btn-secondary"><i class="fas fa-user-plus"></i> Add User</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Team</th>
                                    <th>Status</th>
                                    <th>Leads</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                            <i class="fas fa-users" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                            <p>No users found. <a href="users.php">Add your first user</a></p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="table-user">
                                                    <div class="avatar-circle"><?php echo getInitials($user['name']); ?></div>
                                                    <div>
                                                        <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                                        <span><?php echo htmlspecialchars($user['role']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                            <td>
                                                <form method="POST" action="users.php" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <select class="role-select" name="new_role" onchange="this.form.submit()">
                                                        <option value="Telecaller" <?php echo $user['role'] == 'Telecaller' ? 'selected' : ''; ?>>Telecaller</option>
                                                        <option value="Manager" <?php echo $user['role'] == 'Manager' ? 'selected' : ''; ?>>Manager</option>
                                                        <option value="Administrator" <?php echo $user['role'] == 'Administrator' ? 'selected' : ''; ?>>Administrator</option>
                                                        <option value="Analyst" <?php echo $user['role'] == 'Analyst' ? 'selected' : ''; ?>>Analyst</option>
                                                    </select>
                                                    <input type="hidden" name="update_role" value="1">
                                                </form>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['team'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($user['status']); ?>"><?php echo htmlspecialchars($user['status']); ?></span>
                                            </td>
                                            <td><?php echo $user['assigned_leads']; ?></td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
                                            <td class="table-actions">
                                                <form method="POST" action="users.php" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $user['status'] == 'Active' ? 'Suspended' : 'Active'; ?>">
                                                    <button type="submit" name="update_status" class="btn-icon" title="<?php echo $user['status'] == 'Active' ? 'Suspend' : 'Activate'; ?>">
                                                        <i class="fas fa-<?php echo $user['status'] == 'Active' ? 'user-slash' : 'user-check'; ?>"></i>
                                                    </button>
                                                </form>
                                                <?php if ($user['id'] != $loggedInUser['id']): ?>
                                                    <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="content-card">
                <div class="card-header">
                    <h2>Recent User Activity</h2>
                    <a href="#" class="view-all-btn">View Logs</a>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <?php if (empty($recentActivities)): ?>
                            <li style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <i class="fas fa-history" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                                <p>No recent activities found.</p>
                            </li>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <li>
                                    <div class="timeline-icon success"><i class="fas fa-user-plus"></i></div>
                                    <div class="timeline-content">
                                        <h4>New user created</h4>
                                        <p><strong><?php echo htmlspecialchars($activity['name']); ?></strong> - <?php echo htmlspecialchars($activity['role']); ?> 
                                            (<?php echo htmlspecialchars($activity['email']); ?>)</p>
                                        <span><?php echo formatDate($activity['created_at']); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
