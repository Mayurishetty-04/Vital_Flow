<?php
include('db.php');

// First, check if bloodbank database exists
$check_db = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'bloodbank'");
if ($check_db->num_rows === 0) {
    die("Error: bloodbank database does not exist. Please create it first.");
}

// Create users table if it doesn't exist
$create_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'donor', 'recipient') NOT NULL,
    name VARCHAR(100),
    age INT,
    blood_group VARCHAR(5),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_users)) {
    die("Error creating users table: " . $conn->error);
}

// Check if admin exists
$check_admin = $conn->query("SELECT id FROM users WHERE role = 'admin'");
if ($check_admin->num_rows === 0) {
    // Create admin user
    $username = 'Admin';
    $password = 'admin123';
    $role = 'admin';
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Username: Admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating admin: " . $conn->error;
    }
} else {
    // Update existing admin
    $admin = $check_admin->fetch_assoc();
    $username = 'Admin';
    $password = 'admin123';
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $update = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
    $update->bind_param("ssi", $username, $hashed_password, $admin['id']);
    
    if ($update->execute()) {
        echo "Admin credentials updated successfully!<br>";
        echo "Username: Admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error updating admin: " . $conn->error;
    }
}

// Display current admin users
$admins = $conn->query("SELECT id, username, role FROM users WHERE role = 'admin'");
echo "<h3>Current Admin Users:</h3>";
while ($admin = $admins->fetch_assoc()) {
    echo "ID: {$admin['id']}, Username: {$admin['username']}, Role: {$admin['role']}<br>";
}

$conn->close();
?> 