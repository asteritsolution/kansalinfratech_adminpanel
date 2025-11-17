# Kansal Admin Panel - Setup Instructions

## Database Setup

### Step 1: Create Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Import the `database.sql` file OR run the SQL commands manually

### Step 2: Database Configuration
Edit `config/database.php` and update these values if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password (if any)
define('DB_NAME', 'kansal_admin');
```

### Step 3: Default Login Credentials
After importing the database, you can login with:
- **Username:** `admin`
- **Password:** `admin123`

## Features Implemented

### Login System
- ✅ Secure password hashing using PHP `password_hash()`
- ✅ SQL injection protection using prepared statements
- ✅ Session management
- ✅ Remember me functionality
- ✅ Error handling and validation
- ✅ Auto-redirect if already logged in
- ✅ Logout functionality

### Security Features
- ✅ Password verification using `password_verify()`
- ✅ Prepared statements to prevent SQL injection
- ✅ Session-based authentication
- ✅ Input sanitization using `htmlspecialchars()`

## Next Steps

To add more users, you can:
1. Use the Users page (once implemented)
2. Or insert directly into database:
```sql
INSERT INTO users (username, password, name, email, phone, role, status) 
VALUES ('username', '$2y$10$hashed_password', 'Full Name', 'email@example.com', '+91 98765 43210', 'Telecaller', 'Active');
```

To generate password hash, use:
```php
echo password_hash('your_password', PASSWORD_DEFAULT);
```

## File Structure
```
kansaladminpanel/
├── config/
│   ├── database.php      # Database connection
│   └── session.php       # Session management
├── common/
│   ├── header.php        # Common header
│   └── sidebar.php       # Common sidebar
├── login.php             # Login page (DYNAMIC)
├── logout.php            # Logout handler
├── index.php             # Dashboard (protected)
├── database.sql          # Database schema
└── style.css             # Styles
```

## Notes
- All dashboard pages now require login (using `requireLogin()`)
- Session data is stored in `$_SESSION` array
- User data available: `user_id`, `username`, `name`, `email`, `phone`, `role`

