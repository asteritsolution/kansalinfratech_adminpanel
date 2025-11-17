-- Kansal Admin Panel Database
-- Create database
CREATE DATABASE IF NOT EXISTS kansaladminpanel;
USE kansaladminpanel;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    role ENUM('Administrator', 'Manager', 'Telecaller', 'Analyst') DEFAULT 'Telecaller',
    team VARCHAR(50),
    status ENUM('Active', 'Pending', 'Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
-- Password is hashed using password_hash() - default admin123
INSERT INTO users (username, password, name, email, phone, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@kansalgroup.com', '+91 98765 43210', 'Administrator', 'Active');

-- Leads table
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    property_type VARCHAR(50),
    lead_source VARCHAR(50),
    budget_range VARCHAR(50),
    status ENUM('New', 'Active', 'Follow Up', 'Qualified', 'Site Visit', 'Closed Won', 'Closed Lost') DEFAULT 'New',
    assigned_to INT,
    follow_up_date DATE,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create index for better performance
CREATE INDEX idx_lead_status ON leads(status);
CREATE INDEX idx_lead_assigned ON leads(assigned_to);
CREATE INDEX idx_lead_created ON leads(created_at);

-- Follow-ups table for tracking conversation history
CREATE TABLE IF NOT EXISTS follow_ups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    telecaller_id INT NOT NULL,
    follow_up_date DATE NOT NULL,
    follow_up_time TIME,
    notes TEXT,
    call_duration VARCHAR(20),
    call_outcome ENUM('Answered', 'No Answer', 'Busy', 'Call Back Later', 'Not Interested', 'Interested') DEFAULT 'Answered',
    next_follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (telecaller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create index for better performance
CREATE INDEX idx_followup_lead ON follow_ups(lead_id);
CREATE INDEX idx_followup_telecaller ON follow_ups(telecaller_id);
CREATE INDEX idx_followup_date ON follow_ups(follow_up_date);

