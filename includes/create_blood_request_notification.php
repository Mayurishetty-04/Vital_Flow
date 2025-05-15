<?php
function createBloodRequestNotification($conn, $request_id) {
    // Get request details
    $request = $conn->query("
        SELECT br.*, u.name as recipient_name 
        FROM blood_requests br
        JOIN users u ON br.recipient_id = u.id
        WHERE br.id = $request_id
    ")->fetch_assoc();
    
    if (!$request) return false;
    
    // Find matching donors
    $donors = $conn->query("
        SELECT id, name 
        FROM users 
        WHERE role = 'donor' 
        AND blood_group = '{$request['blood_group']}'
    ");
    
    // Create notification for each matching donor
    while ($donor = $donors->fetch_assoc()) {
        $message = "Blood request from {$request['recipient_name']} for {$request['units']}ml of {$request['blood_group']} blood";
        
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, message, reference_id) 
            VALUES (?, 'blood_request', ?, ?)
        ");
        $stmt->bind_param("isi", $donor['id'], $message, $request_id);
        $stmt->execute();
    }
    
    return true;
}

function createDonorAcceptanceNotification($conn, $request_id) {
    // Get request details
    $request = $conn->query("
        SELECT br.*, u.name as donor_name, u2.name as recipient_name
        FROM blood_requests br
        JOIN users u ON br.donor_id = u.id
        JOIN users u2 ON br.recipient_id = u2.id
        WHERE br.id = $request_id
    ")->fetch_assoc();
    
    if (!$request) return false;
    
    // Create notification for admin
    $message = "Donor {$request['donor_name']} has accepted blood request from {$request['recipient_name']} for {$request['units']}ml of {$request['blood_group']} blood";
    
    // Get admin user id
    $admin = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();
    
    if ($admin) {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, message, reference_id) 
            VALUES (?, 'donor_acceptance', ?, ?)
        ");
        $stmt->bind_param("isi", $admin['id'], $message, $request_id);
        $stmt->execute();
    }
    
    return true;
}
?> 