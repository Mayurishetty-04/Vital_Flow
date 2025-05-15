<?php
include('includes/db.php');

// Check approved donations
$result = $conn->query("
    SELECT blood_group, COUNT(*) as approved_count, SUM(units) as total_units 
    FROM donations 
    WHERE status='Approved' 
    GROUP BY blood_group
");

echo "Approved Donations:\n";
while ($row = $result->fetch_assoc()) {
    echo "Blood Group: " . $row['blood_group'] . 
         ", Count: " . $row['approved_count'] . 
         ", Total Units: " . $row['total_units'] . "\n";
}

// Check bloodstock table
$stock = $conn->query("SELECT * FROM bloodstock");
echo "\nCurrent Blood Stock:\n";
while ($row = $stock->fetch_assoc()) {
    echo "Blood Group: " . $row['blood_group'] . 
         ", Quantity: " . $row['quantity'] . "\n";
}
?> 