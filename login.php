<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Check for logout success message
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = 'You have been successfully logged out.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getDBConnection();
        
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, name, email, phone, role, status FROM users WHERE username = ? AND status = 'Active'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['phone'] = $user['phone'];
                $_SESSION['role'] = $user['role'];
                
                // Remember me functionality (set cookie for 30 days)
                if ($remember) {
                    setcookie('remember_token', base64_encode($user['id'] . ':' . $user['username']), time() + (30 * 24 * 60 * 60), '/');
                }
                
                // Redirect to dashboard
                header("Location: index.php");
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kansal Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo">
                    <img src="images/logo.png" alt="Kansal Logo" class="logo-img">
                </div>
            </div>
            
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
            
            <form class="login-form" action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="form-group remember-me">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">Login</button>
            </form>
            
            <div class="login-footer">
                <p>&copy; 2024 Kansal Admin Panel. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>

