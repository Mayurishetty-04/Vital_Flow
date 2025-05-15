<?php
include('../includes/db.php');

// Admin user details
$admin_username = 'Admin';
$admin_password = 'admin123';
$admin_email = 'admin@vitalflow.com';
$admin_role = 'admin';

// First check if an admin user already exists
$check_admin = $conn->prepare("SELECT id FROM users WHERE role = 'admin'");
$check_admin->execute();
$admin_result = $check_admin->get_result();

if ($admin_result->num_rows > 0) {
    // Update existing admin user
    $admin_user = $admin_result->fetch_assoc();
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    $update_stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, email = ? WHERE id = ?");
    $update_stmt->bind_param("sssi", $admin_username, $hashed_password, $admin_email, $admin_user['id']);
    
    if ($update_stmt->execute()) {
        echo "Admin credentials updated successfully!<br>";
        echo "Username: " . $admin_username . "<br>";
        echo "Password: " . $admin_password . "<br>";
        echo "Please use these new credentials to login.";
    } else {
        echo "Error updating admin credentials: " . $conn->error;
    }
} else {
    // Create new admin user if none exists
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $admin_username, $hashed_password, $admin_email, $admin_role);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Username: " . $admin_username . "<br>";
        echo "Password: " . $admin_password . "<br>";
        echo "Please use these credentials to login.";
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
}

// Close the connection
$conn->close();
?> 