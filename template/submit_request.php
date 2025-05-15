<?php
include('../includes/db.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recipient') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['blood_group']) || !isset($_POST['units'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

$recipient_id = $_SESSION['user_id'];
$blood_group = $_POST['blood_group'];
$units = (int)$_POST['units'];

if ($units <= 0 || $units > 5000) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid units value']);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO blood_requests (recipient_id, blood_group, units, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("isi", $recipient_id, $blood_group, $units);
    
    if ($stmt->execute()) {
        // Get ALL requests after successful insertion
        $requests = $conn->query("SELECT blood_requests.*, users.name as donor_name 
                                FROM blood_requests 
                                LEFT JOIN users ON blood_requests.donor_id = users.id 
                                WHERE blood_requests.recipient_id = $recipient_id 
                                ORDER BY blood_requests.id DESC");
        
        $tableHtml = '';
        if ($requests->num_rows > 0) {
            $tableHtml .= '<table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Blood Group</th>
                                    <th>Units (ml)</th>
                                    <th>Status</th>
                                    <th>Accepted By</th>
                                </tr>
                            </thead>
                            <tbody>';
            
            while ($row = $requests->fetch_assoc()) {
                $tableHtml .= '<tr>
                                <td>' . htmlspecialchars($row['blood_group']) . '</td>
                                <td>' . htmlspecialchars($row['units']) . '</td>
                                <td>';
                if ($row['status'] == 'pending') {
                    $tableHtml .= '<span class="badge bg-warning text-dark">Pending</span>';
                } elseif ($row['status'] == 'accepted') {
                    $tableHtml .= '<span class="badge bg-success">Accepted</span>';
                } else {
                    $tableHtml .= htmlspecialchars($row['status']);
                }
                $tableHtml .= '</td>
                                <td>' . ($row['donor_name'] ? htmlspecialchars($row['donor_name']) : '-') . '</td>
                            </tr>';
            }
            
            $tableHtml .= '</tbody></table>';
        } else {
            $tableHtml = '<div class="alert alert-info">You have not made any blood requests yet.</div>';
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Blood request submitted successfully', 'tableHtml' => $tableHtml]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 