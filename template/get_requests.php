<?php
if (!isset($conn)) {
    include('../includes/db.php');
    session_start();

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recipient') {
        exit('Unauthorized');
    }
    $recipient_id = $_SESSION['user_id'];
} else {
    // If included from dashboard, use existing connection and session
    if (!isset($recipient_id)) {
        exit('Invalid request');
    }
}

// Get all requests
$requests = $conn->query("SELECT blood_requests.*, users.name as donor_name 
                         FROM blood_requests 
                         LEFT JOIN users ON blood_requests.donor_id = users.id 
                         WHERE blood_requests.recipient_id = $recipient_id 
                         ORDER BY blood_requests.id DESC");

if ($requests->num_rows > 0) {
    echo '<table class="table table-bordered">
            <thead>
                <tr>
                    <th>Blood Group</th>
                    <th>Units (ml)</th>
                    <th>Status</th>
                    <th>Accepted By</th>
                </tr>
            </thead>
            <tbody>';
    
    while($row = $requests->fetch_assoc()) {
        echo '<tr>
                <td>' . htmlspecialchars($row['blood_group']) . '</td>
                <td>' . htmlspecialchars($row['units']) . '</td>
                <td>';
        if ($row['status'] == 'pending') {
            echo '<span class="badge bg-warning text-dark">Pending</span>';
        } elseif ($row['status'] == 'accepted') {
            echo '<span class="badge bg-success">Accepted</span>';
        } else {
            echo htmlspecialchars($row['status']);
        }
        echo '</td>
                <td>' . ($row['donor_name'] ? htmlspecialchars($row['donor_name']) : '-') . '</td>
            </tr>';
    }
    
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-info">You have not made any blood requests yet.</div>';
}
?> 