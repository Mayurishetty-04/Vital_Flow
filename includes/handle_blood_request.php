<?php
session_start();
include('db.php');
include('create_blood_request_notification.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if (!isset($_POST['action']) || !isset($_POST['request_id'])) {
    echo json_encode(['error' => 'Invalid request parameters']);
    exit;
}

$action = $_POST['action'];
$request_id = (int)$_POST['request_id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Start transaction
$conn->begin_transaction();

try {
    switch ($action) {
        case 'donor_accept':
            if ($role !== 'donor') {
                throw new Exception('Unauthorized - Only donors can accept requests');
            }
            
            // Verify the request is pending and matches donor's blood group
            $donor = $conn->query("SELECT blood_group FROM users WHERE id = $user_id")->fetch_assoc();
            $request = $conn->query("
                SELECT * FROM blood_requests 
                WHERE id = $request_id 
                AND status = 'pending' 
                AND blood_group = '{$donor['blood_group']}'
            ")->fetch_assoc();
            
            if (!$request) {
                throw new Exception('Invalid request or blood group mismatch');
            }
            
            // Update request status and set donor
            $stmt = $conn->prepare("
                UPDATE blood_requests 
                SET status = 'donor_accepted', donor_id = ? 
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->bind_param("ii", $user_id, $request_id);
            $stmt->execute();
            
            // Create notification for admin
            createDonorAcceptanceNotification($conn, $request_id);
            break;
            
        case 'admin_approve':
            if ($role !== 'admin') {
                throw new Exception('Unauthorized - Only admins can approve requests');
            }
            
            // Get request details
            $request = $conn->query("
                SELECT br.*, bs.quantity as available_units 
                FROM blood_requests br
                LEFT JOIN bloodstock bs ON bs.blood_group = br.blood_group
                WHERE br.id = $request_id AND br.status = 'donor_accepted'
            ")->fetch_assoc();
            
            if (!$request) {
                throw new Exception('Invalid request or request not in correct state');
            }
            
            // Check if enough blood is available
            $available_units = $request['available_units'] ?? 0;
            if ($available_units < $request['units']) {
                // Create notification for when blood becomes available
                $stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message, reference_id) 
                    VALUES (?, 'blood_request', ?, ?)
                ");
                $message = "Insufficient blood stock for request ID {$request_id}. Needed: {$request['units']}ml of {$request['blood_group']}. Available: {$available_units}ml";
                $stmt->bind_param("isi", $user_id, $message, $request_id);
                $stmt->execute();
                
                throw new Exception("Insufficient blood stock. Available: {$available_units}ml, Required: {$request['units']}ml");
            }
            
            // Update blood stock
            if (!updateBloodStockAfterRequest($conn, $request['blood_group'], $request['units'])) {
                throw new Exception("Failed to update blood stock");
            }
            
            // Update request status
            $stmt = $conn->prepare("
                UPDATE blood_requests 
                SET status = 'admin_approved'
                WHERE id = ? AND status = 'donor_accepted'
            ");
            $stmt->bind_param("i", $request_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update request status");
            }
            
            // Create notification for recipient
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message, reference_id)
                VALUES (?, 'blood_request', ?, ?)
            ");
            $message = "Your blood request for {$request['units']}ml of {$request['blood_group']} has been approved";
            $stmt->bind_param("isi", $request['recipient_id'], $message, $request_id);
            $stmt->execute();
            break;
            
        case 'admin_reject':
            if ($role !== 'admin') {
                throw new Exception('Unauthorized - Only admins can reject requests');
            }
            
            // Update request status
            $stmt = $conn->prepare("
                UPDATE blood_requests 
                SET status = 'admin_rejected'
                WHERE id = ? AND status = 'donor_accepted'
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?> 