<?php
include('db.php');

// Update blood_requests table to include more states
$conn->query("ALTER TABLE blood_requests MODIFY COLUMN status 
    ENUM('pending', 'donor_accepted', 'donor_rejected', 'admin_approved', 'admin_rejected') 
    DEFAULT 'pending'");

// Create notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('blood_request', 'donor_acceptance', 'admin_approval') NOT NULL,
    message TEXT NOT NULL,
    reference_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

$conn->query($sql);

echo "Notifications system setup completed";
?> 