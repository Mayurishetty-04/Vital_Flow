<?php
include('db.php');

// Return JSON response
header('Content-Type: application/json');

// Get count of pending donation offers
$pending_offers = $conn->query("SELECT COUNT(*) as count FROM donation_offers WHERE status='pending'")->fetch_assoc();
$pending_offers_count = $pending_offers['count'];

// Get the most recent pending offer
$latest_offer = $conn->query("
    SELECT do.*, u.name as donor_name, u.blood_group 
    FROM donation_offers do
    JOIN users u ON do.donor_id = u.id
    WHERE do.status = 'pending'
    ORDER BY do.created_at DESC
    LIMIT 1
")->fetch_assoc();

$response = [
    'pending_count' => $pending_offers_count,
    'latest_offer' => $latest_offer ? [
        'donor_name' => $latest_offer['donor_name'],
        'blood_group' => $latest_offer['blood_group'],
        'units' => $latest_offer['units'],
        'offer_date' => $latest_offer['offer_date']
    ] : null
];

echo json_encode($response);
?> 