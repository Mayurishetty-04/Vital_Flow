<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "bloodbank");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create blood_requests table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS blood_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    donor_id INT,
    blood_group VARCHAR(5) NOT NULL,
    units INT NOT NULL,
    status ENUM('pending', 'donor_accepted', 'donor_rejected', 'admin_approved', 'admin_rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    FOREIGN KEY (donor_id) REFERENCES users(id)
)";

$conn->query($sql);

// Create notifications table if it doesn't exist
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

// Add columns if they don't exist
$check_columns = $conn->query("SHOW COLUMNS FROM blood_requests LIKE 'requested_at'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE blood_requests 
                 ADD COLUMN requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                 ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}

// Function to recalculate blood stock
function recalculateBloodStock($conn) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, clear existing blood stock
        $conn->query("DELETE FROM bloodstock");
        
        // Initialize all blood groups
        $blood_groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
        
        foreach ($blood_groups as $blood_group) {
            // Get total units from approved donations
            $donations_query = "SELECT COALESCE(SUM(units), 0) as total_donated 
                              FROM donations 
                              WHERE blood_group = ? 
                              AND status = 'Approved'";
            $stmt = $conn->prepare($donations_query);
            $stmt->bind_param("s", $blood_group);
            $stmt->execute();
            $total_donated = (int)$stmt->get_result()->fetch_assoc()['total_donated'];
            
            // Get total units from approved requests
            $requests_query = "SELECT COALESCE(SUM(units), 0) as total_used 
                             FROM blood_requests 
                             WHERE blood_group = ? 
                             AND status = 'admin_approved'";
            $stmt = $conn->prepare($requests_query);
            $stmt->bind_param("s", $blood_group);
            $stmt->execute();
            $total_used = (int)$stmt->get_result()->fetch_assoc()['total_used'];
            
            // Calculate current stock (ensure non-negative)
            $current_stock = max(0, $total_donated - $total_used);
            
            // Insert or update blood stock
            $insert_query = "INSERT INTO bloodstock (blood_group, quantity) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("si", $blood_group, $current_stock);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in recalculateBloodStock: " . $e->getMessage());
        return false;
    }
}

// Function to update blood stock after donation approval
function updateBloodStockAfterDonation($conn, $blood_group, $units, $is_approval = true) {
    try {
        $conn->begin_transaction();
        
        // Get current stock
        $stmt = $conn->prepare("SELECT quantity FROM bloodstock WHERE blood_group = ?");
        $stmt->bind_param("s", $blood_group);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_stock = $result->fetch_assoc()['quantity'] ?? 0;
        
        // Calculate new stock
        $new_stock = $is_approval ? $current_stock + $units : $current_stock - $units;
        $new_stock = max(0, $new_stock); // Ensure non-negative
        
        // Update stock
        $stmt = $conn->prepare("INSERT INTO bloodstock (blood_group, quantity) 
                              VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE quantity = ?");
        $stmt->bind_param("sii", $blood_group, $new_stock, $new_stock);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating blood stock: " . $e->getMessage());
        return false;
    }
}

// Function to update blood stock after request approval
function updateBloodStockAfterRequest($conn, $blood_group, $units) {
    try {
        $conn->begin_transaction();
        
        // Get current stock
        $stmt = $conn->prepare("SELECT quantity FROM bloodstock WHERE blood_group = ?");
        $stmt->bind_param("s", $blood_group);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_stock = $result->fetch_assoc()['quantity'] ?? 0;
        
        // Check if enough stock available
        if ($current_stock < $units) {
            throw new Exception("Insufficient blood stock");
        }
        
        // Calculate new stock
        $new_stock = max(0, $current_stock - $units);
        
        // Update stock
        $stmt = $conn->prepare("UPDATE bloodstock SET quantity = ? WHERE blood_group = ?");
        $stmt->bind_param("is", $new_stock, $blood_group);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating blood stock after request: " . $e->getMessage());
        return false;
    }
}

// Function to get current blood stock
function getCurrentBloodStock($conn) {
    $stock = [];
    $result = $conn->query("SELECT blood_group, quantity FROM bloodstock ORDER BY blood_group");
    while ($row = $result->fetch_assoc()) {
        $stock[$row['blood_group']] = (int)$row['quantity']; // Ensure integer values
    }
    return $stock;
}
?>