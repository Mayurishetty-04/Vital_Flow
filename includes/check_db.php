<?php
include('db.php');

echo "<h2>Database Check Results:</h2>";

// Check if users table exists
$check_table = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_table->num_rows > 0) {
    echo "✓ Users table exists<br>";
    
    // Check table structure
    $describe = $conn->query("DESCRIBE users");
    echo "<h3>Table Structure:</h3>";
    while ($row = $describe->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}<br>";
    }
    
    // Check admin users
    $admin_check = $conn->query("SELECT id, username, role FROM users WHERE role='admin'");
    echo "<h3>Admin Users:</h3>";
    if ($admin_check->num_rows > 0) {
        while ($admin = $admin_check->fetch_assoc()) {
            echo "ID: {$admin['id']}, Username: {$admin['username']}, Role: {$admin['role']}<br>";
        }
    } else {
        echo "No admin users found!<br>";
    }
} else {
    echo "✗ Users table does not exist!<br>";
    
    // Create users table
    $create_table = "CREATE TABLE IF NOT EXISTS users (
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
    
    if ($conn->query($create_table)) {
        echo "✓ Users table created successfully!<br>";
    } else {
        echo "✗ Error creating users table: " . $conn->error . "<br>";
    }
}

$conn->close();
?> 