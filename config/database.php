<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kansaladminpanel');

// Create database connection
function getDBConnection() {
    // First, try to connect to MySQL server (without database)
    $server_conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS);
    
    if (!$server_conn) {
        die("
        <div style='padding: 20px; font-family: Arial; max-width: 600px; margin: 50px auto; border: 2px solid #ef4444; border-radius: 8px; background: #fef2f2;'>
            <h2 style='color: #ef4444; margin-top: 0;'><i class='fas fa-exclamation-triangle'></i> Database Connection Error</h2>
            <p style='color: #991b1b;'><strong>MySQL Server is not running or connection refused.</strong></p>
            <p style='color: #991b1b;'>Please follow these steps:</p>
            <ol style='color: #991b1b;'>
                <li>Open XAMPP Control Panel</li>
                <li>Start <strong>MySQL</strong> service</li>
                <li>Wait for MySQL to start (green indicator)</li>
                <li>Refresh this page</li>
            </ol>
            <p style='color: #991b1b; margin-top: 20px;'>
                <strong>Note:</strong> Make sure MySQL is running on <code>localhost</code> with username <code>root</code>
            </p>
        </div>
        ");
    }
    
    // Check if database exists, if not create it
    $db_check = mysqli_select_db($server_conn, DB_NAME);
    if (!$db_check) {
        // Try to create database
        $create_db = mysqli_query($server_conn, "CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        if (!$create_db) {
            die("
            <div style='padding: 20px; font-family: Arial; max-width: 600px; margin: 50px auto; border: 2px solid #ef4444; border-radius: 8px; background: #fef2f2;'>
                <h2 style='color: #ef4444; margin-top: 0;'><i class='fas fa-exclamation-triangle'></i> Database Error</h2>
                <p style='color: #991b1b;'>Could not create database: " . htmlspecialchars(DB_NAME) . "</p>
                <p style='color: #991b1b;'>Error: " . mysqli_error($server_conn) . "</p>
            </div>
            ");
        }
    }
    
    // Now connect to the specific database
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$conn) {
        die("
        <div style='padding: 20px; font-family: Arial; max-width: 600px; margin: 50px auto; border: 2px solid #ef4444; border-radius: 8px; background: #fef2f2;'>
            <h2 style='color: #ef4444; margin-top: 0;'><i class='fas fa-exclamation-triangle'></i> Database Connection Failed</h2>
            <p style='color: #991b1b;'>Could not connect to database: " . htmlspecialchars(DB_NAME) . "</p>
            <p style='color: #991b1b;'>Error: " . mysqli_connect_error() . "</p>
            <p style='color: #991b1b; margin-top: 20px;'>
                Please check:
                <ul style='color: #991b1b;'>
                    <li>Database name is correct: <strong>" . htmlspecialchars(DB_NAME) . "</strong></li>
                    <li>MySQL service is running in XAMPP</li>
                    <li>Database exists in phpMyAdmin</li>
                </ul>
            </p>
        </div>
        ");
    }
    
    mysqli_set_charset($conn, "utf8");
    return $conn;
}
?>

