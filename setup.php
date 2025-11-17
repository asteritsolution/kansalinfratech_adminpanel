<?php
require_once 'config/database.php';

$error = '';
$success = '';
$step = 1;

// Check if database exists and has users
try {
    $conn = getDBConnection();
    
    // Check if users table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'users'");
    
    if ($checkTable->num_rows > 0) {
        // Table exists, check if users exist
        $checkUsers = $conn->query("SELECT COUNT(*) as count FROM users");
        $userCount = $checkUsers->fetch_assoc()['count'];
        
        if ($userCount > 0) {
            $step = 2; // Users exist, show message
        } else {
            $step = 1; // Table exists but no users
        }
    } else {
        // Table doesn't exist, create it
        $step = 1;
    }
    
    $conn->close();
} catch (Exception $e) {
    $error = 'Database connection error: ' . $e->getMessage();
    $step = 0;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($username) || empty($password) || empty($name) || empty($email)) {
        $error = 'Please fill all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $conn = getDBConnection();
            
            // Create users table if it doesn't exist
            $createTable = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                phone VARCHAR(20),
                role ENUM('Administrator', 'Manager', 'Telecaller', 'Analyst') DEFAULT 'Administrator',
                team VARCHAR(50),
                status ENUM('Active', 'Pending', 'Suspended') DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $conn->query($createTable);
            
            // Check if username already exists
            $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $checkUser->bind_param("ss", $username, $email);
            $checkUser->execute();
            $result = $checkUser->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert admin user
                $stmt = $conn->prepare("INSERT INTO users (username, password, name, email, phone, role, status) VALUES (?, ?, ?, ?, ?, 'Administrator', 'Active')");
                $stmt->bind_param("sssss", $username, $hashed_password, $name, $email, $phone);
                
                if ($stmt->execute()) {
                    $success = 'Admin account created successfully! You can now login.';
                    $step = 3;
                } else {
                    $error = 'Error creating account: ' . $conn->error;
                }
                
                $stmt->close();
            }
            
            $checkUser->close();
            $conn->close();
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Kansal Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-box" style="max-width: 500px;">
            <div class="login-header">
                <div class="logo">
                    <img src="images/logo.png" alt="Kansal Logo" class="logo-img">
                </div>
                <h1>Setup Admin Account</h1>
                <p>Create your administrator account</p>
            </div>
            
            <?php if ($step == 2): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> Users already exist in the database. If you want to create a new admin account, please use the Users page after logging in.
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php elseif ($step == 3): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form class="login-form" action="setup.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username <span style="color: red;">*</span></label>
                        <input type="text" id="username" name="username" placeholder="Enter username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name <span style="color: red;">*</span></label>
                        <input type="text" id="name" name="name" placeholder="Enter full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address <span style="color: red;">*</span></label>
                        <input type="email" id="email" name="email" placeholder="Enter email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" placeholder="+91 98765 43210" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password <span style="color: red;">*</span></label>
                        <input type="password" id="password" name="password" placeholder="Enter password (min 6 characters)" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span style="color: red;">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                    </div>
                    
                    <button type="submit" name="create_admin" class="btn btn-primary btn-login">
                        <i class="fas fa-user-plus"></i> Create Admin Account
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="login-footer">
                <p>&copy; 2024 Kansal Admin Panel. All rights reserved.</p>
                <p style="margin-top: 10px; font-size: 11px; color: #999;">
                    <a href="login.php" style="color: var(--primary-color);">Already have an account? Login</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

