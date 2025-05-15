<?php
include('db.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$response = [
    'notifications' => [],
    'unread_count' => 0
];

// Get unread notifications based on role
if ($role === 'donor') {
    // Get blood requests that match donor's blood group
    $donor = $conn->query("SELECT blood_group FROM users WHERE id = $user_id")->fetch_assoc();
    $blood_group = $donor['blood_group'];
    
    $notifications = $conn->query("
        SELECT n.*, br.blood_group, br.units, u.name as recipient_name
        FROM notifications n
        JOIN blood_requests br ON n.reference_id = br.id
        JOIN users u ON br.recipient_id = u.id
        WHERE n.user_id = $user_id 
        AND n.type = 'blood_request'
        AND n.is_read = FALSE
        ORDER BY n.created_at DESC
    ");
} elseif ($role === 'admin') {
    // Get notifications for donor accepted requests
    $notifications = $conn->query("
        SELECT n.*, br.blood_group, br.units, u.name as donor_name, u2.name as recipient_name
        FROM notifications n
        JOIN blood_requests br ON n.reference_id = br.id
        JOIN users u ON br.donor_id = u.id
        JOIN users u2 ON br.recipient_id = u2.id
        WHERE n.user_id = $user_id 
        AND n.type = 'donor_acceptance'
        AND n.is_read = FALSE
        ORDER BY n.created_at DESC
    ");
}

while ($row = $notifications->fetch_assoc()) {
    $response['notifications'][] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'blood_group' => $row['blood_group'],
        'units' => $row['units'],
        'donor_name' => isset($row['donor_name']) ? $row['donor_name'] : null,
        'recipient_name' => $row['recipient_name'],
        'created_at' => $row['created_at']
    ];
}

$response['unread_count'] = count($response['notifications']);

echo json_encode($response);
?> 