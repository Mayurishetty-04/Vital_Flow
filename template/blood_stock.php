<?php $force_logout = true; ?>
<?php include('header.php'); ?>
<?php $active_page = 'blood_stock'; ?>
<?php include('../includes/db.php'); ?>
<style>
body {
  background: url('../images/adminbg.jpg') !important;
  background-size: cover !important;
  background-position: center !important;
  background-attachment: fixed !important;
  min-height: 100vh;
}
.sidebar {
  min-height: 100vh;
  background: #343a40;
  color: #fff;
}
.sidebar .nav-link {
  color: #fff;
  margin-bottom: 10px;
}
.sidebar .nav-link.active, .sidebar .nav-link:hover {
  background: #dc3545;
  color: #fff;
}
main {
  background-color: rgba(255, 255, 255, 0.9);
  padding: 20px;
  border-radius: 10px;
  margin-top: 20px;
}
.table {
  background-color: rgba(255, 255, 255, 0.95);
}
</style>
<div class="container-fluid">
  <div class="row">
    <?php include('sidebar.php'); ?>
    <main class="col-md-10 ms-sm-auto px-md-4">
      <div class="container mt-4">
        <h2 class="mb-4 text-info">Blood Stock</h2>
        <table class="table table-bordered table-hover">
          <thead class="table-info">
            <tr>
              <th>Blood Group</th>
              <th>Quantity Available</th>
              <th>Donor Names</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Get all blood groups
            $blood_groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
            
            foreach ($blood_groups as $bg):
                // Get total units from approved donations
                $donations_result = $conn->query("
                    SELECT COALESCE(SUM(units), 0) as total_donated
                    FROM donations 
                    WHERE blood_group = '$bg' 
                    AND status = 'Approved'"
                )->fetch_assoc();
                $total_donated = (int)$donations_result['total_donated'];
                
                // Get total units from approved requests
                $requests_result = $conn->query("
                    SELECT COALESCE(SUM(units), 0) as total_used
                    FROM blood_requests 
                    WHERE blood_group = '$bg' 
                    AND status = 'admin_approved'"
                )->fetch_assoc();
                $total_used = (int)$requests_result['total_used'];
                
                // Calculate current available quantity
                $quantity = max(0, $total_donated - $total_used);
                
                // Get count of approved donations
                $approved_count = $conn->query("
                    SELECT COUNT(*) as count 
                    FROM donations 
                    WHERE blood_group = '$bg' 
                    AND status = 'Approved'"
                )->fetch_assoc()['count'];
                
                // Get donors who have approved donations for this blood group
                $donors = $conn->query("
                    SELECT DISTINCT u.name 
                    FROM users u 
                    JOIN donations d ON u.id = d.donor_id 
                    WHERE d.blood_group = '$bg' 
                    AND d.status = 'Approved'
                    ORDER BY u.name
                ");
                
                $donor_names = [];
                while ($d = $donors->fetch_assoc()) {
                    $donor_names[] = htmlspecialchars($d['name']);
                }
            ?>
              <tr>
                <td><?php echo htmlspecialchars($bg); ?></td>
                <td>
                    <?php 
                    echo htmlspecialchars($quantity) . " units";
                    if ($approved_count > 0) {
                        echo ' <span class="badge bg-success">' . $approved_count . ' approved donation' . ($approved_count > 1 ? 's' : '') . '</span>';
                    }
                    if ($quantity <= 100) {
                        echo ' <span class="badge bg-danger">Low Stock</span>';
                    }
                    ?>
                </td>
                <td><?php echo $donor_names ? implode(', ', $donor_names) : '-'; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>
<?php include('footer.php'); ?> 