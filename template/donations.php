<?php
ob_start();
$force_logout = true;
session_start();
include('../includes/db.php');

// Handle Approve action only
if (isset($_GET['approve'])) {
  $id = intval($_GET['approve']);
  
  // Start transaction
  $conn->begin_transaction();
  
  try {
      // Get donation details
      $donation = $conn->query("SELECT * FROM donations WHERE id=$id")->fetch_assoc();
      
      if ($donation && $donation['status'] !== 'Approved') {
          // Update status
          $conn->query("UPDATE donations SET status='Approved' WHERE id=$id");
          
          // Update bloodstock
          $bg = $donation['blood_group'];
          $units = $donation['units']; // Get the actual units from donation
          $stock = $conn->query("SELECT * FROM bloodstock WHERE blood_group='$bg'");
          if ($stock->num_rows > 0) {
              $conn->query("UPDATE bloodstock SET quantity = quantity + $units WHERE blood_group='$bg'");
          } else {
              $conn->query("INSERT INTO bloodstock (blood_group, quantity) VALUES ('$bg', $units)");
          }
          
          $conn->commit();
          
          // Redirect to refresh the page
          header("Location: " . $_SERVER['PHP_SELF']);
          exit();
      }
  } catch (Exception $e) {
      $conn->rollback();
      $error_message = 'Error updating donation status. Please try again.';
  }
}

$force_logout = true;
$active_page = 'donations';
?>
<?php include('header.php'); ?>
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
      <div class="mt-4">
        <h2 class="mb-4 text-success">BLOOD DONATION DETAILS</h2>
        
        <table class="table table-bordered table-hover">
          <thead class="table-success">
            <tr>
              <th>Donor Name</th>
              <th>Disease</th>
              <th>Age</th>
              <th>Blood Group</th>
              <th>Unit</th>
              <th>Request Date</th>
              <th>Status</th>
              <th>Action</th>
              <th>Stock Update</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Only show approved and pending donations
            $donations = $conn->query("SELECT d.*, u.name, u.age FROM donations d 
                                     JOIN users u ON d.donor_id = u.id 
                                     WHERE d.status IN ('Approved', 'Pending')
                                     ORDER BY d.id DESC");
            while ($row = $donations->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['disease']); ?></td>
                <td><?php echo htmlspecialchars($row['age']); ?></td>
                <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                <td><?php echo htmlspecialchars($row['units']); ?></td>
                <td><?php echo date('M. d, Y', strtotime($row['donation_date'])); ?></td>
                <td>
                  <?php
                    if ($row['status'] == 'Approved') {
                      echo '<span class="badge bg-success">Approved</span>';
                    } else {
                      echo '<span class="badge bg-warning text-dark">Pending</span>';
                    }
                  ?>
                </td>
                <td>
                  <?php if ($row['status'] == 'Pending'): ?>
                    <a href="?approve=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">Approve</a>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                    if ($row['status'] == 'Approved') {
                      echo '<span class="badge bg-warning">' . htmlspecialchars($row['units']) . ' Unit Added To Stock</span>';
                    } else {
                      echo '<span class="badge bg-info">Pending Approval</span>';
                    }
                  ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>
<?php include('footer.php'); ?> 