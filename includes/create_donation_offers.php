<?php
include('db.php');

$sql = "CREATE TABLE IF NOT EXISTS donation_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    units INT NOT NULL,
    offer_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table donation_offers created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 