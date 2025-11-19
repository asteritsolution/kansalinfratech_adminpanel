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

$activePage = 'add-user';
$pageTitle = 'Add New User';
$breadcrumb = 'Home / Users / Add New User';

$error = '';
$success = '';

// Get database connection
$conn = getDBConnection();

// Handle Add User Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
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
                header("Location: add-user.php?success=1&username=" . urlencode($username) . "&password=" . urlencode($password));
                exit();
            } else {
                $error = 'Error creating user: ' . $conn->error;
            }
            
            $stmt->close();
        }
        
        $checkQuery->close();
    }
}

// Check for success messages
if (isset($_GET['success']) && $_GET['success'] == 1 && isset($_GET['username']) && isset($_GET['password'])) {
    $success = 'User created successfully! Username: ' . htmlspecialchars($_GET['username']) . ' | Temporary Password: ' . htmlspecialchars($_GET['password']);
}

// Fetch unique teams
$teamsQuery = "SELECT DISTINCT team FROM users WHERE team IS NOT NULL AND team != '' ORDER BY team";
$teamsResult = $conn->query($teamsQuery);
$teams = [];
while ($row = $teamsResult->fetch_assoc()) {
    $teams[] = $row['team'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - Kansal Admin Panel</title>
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
                    <h2>Add New User</h2>
                    <a href="users.php" class="view-all-btn"><i class="fas fa-list"></i> View All Users</a>
                </div>
                <div class="card-body">
                    <form class="settings-form" method="POST" action="add-user.php">
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
                                    <option value="Analyst" <?php echo (isset($_POST['userRole']) && $_POST['userRole'] == 'Analyst') ? 'selected' : ''; ?>>Site Visitor</option>
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
                            <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Clear</button>
                            <a href="users.php" class="btn btn-secondary"><i class="fas fa-list"></i> View All Users</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

