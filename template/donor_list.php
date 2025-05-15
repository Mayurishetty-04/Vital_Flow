<?php $force_logout = true; ?>
<?php include('header.php'); ?>
<?php $active_page = 'donor'; ?>
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
        <h2 class="mb-4 text-danger">Donor List</h2>
        <?php 
        // Count unique donors by combining identifying information
        $donor_count = $conn->query("SELECT COUNT(DISTINCT CONCAT(name, '_', age, '_', phone, '_', blood_group)) as cnt 
                                   FROM users 
                                   WHERE role='donor' 
                                   AND name IS NOT NULL 
                                   AND name != ''
                                   AND phone IS NOT NULL 
                                   AND phone != ''")->fetch_assoc()['cnt']; 
        ?>
        <h5 class="mb-3">Total Donors: <?php echo $donor_count; ?></h5>
        <table class="table table-bordered table-hover">
          <thead class="table-danger">
            <tr>
              <th>Name</th>
              <th>Age</th>
              <th>Phone</th>
              <th>Blood Group</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Show all donors with their details, ensure no null or empty values
            $donors = $conn->query("SELECT u.id, u.name, u.age, u.phone, u.blood_group,
                                    CASE 
                                      WHEN EXISTS (
                                        SELECT 1 FROM donations d2 
                                        WHERE d2.donor_id = u.id 
                                        AND d2.status = 'Approved'
                                      ) THEN 'Active'
                                      WHEN EXISTS (
                                        SELECT 1 FROM donations d2 
                                        WHERE d2.donor_id = u.id 
                                        AND d2.status = 'Rejected'
                                        AND NOT EXISTS (
                                          SELECT 1 FROM donations d3 
                                          WHERE d3.donor_id = u.id 
                                          AND d3.status = 'Approved'
                                        )
                                      ) THEN 'Inactive'
                                      WHEN EXISTS (
                                        SELECT 1 FROM donations d2 
                                        WHERE d2.donor_id = u.id 
                                        AND d2.status = 'Pending'
                                        AND NOT EXISTS (
                                          SELECT 1 FROM donations d3 
                                          WHERE d3.donor_id = u.id 
                                          AND d3.status IN ('Approved', 'Rejected')
                                        )
                                      ) THEN 'Pending'
                                      ELSE 'New Donor'
                                    END as donor_status
                                  FROM users u
                                  WHERE u.role='donor' 
                                  AND u.name IS NOT NULL 
                                  AND u.name != ''
                                  AND u.phone IS NOT NULL 
                                  AND u.phone != ''
                                  ORDER BY u.name, u.id");
            
            while ($row = $donors->fetch_assoc()): 
              // Skip any rows with empty essential data
              if (empty($row['name']) || empty($row['blood_group'])) continue;
            ?>
              <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['age']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                <td>
                  <?php 
                    $status = $row['donor_status'];
                    if ($status == 'Active') {
                      echo '<span class="badge bg-success">Active</span>';
                    } elseif ($status == 'Pending') {
                      echo '<span class="badge bg-warning text-dark">Pending</span>';
                    } elseif ($status == 'Inactive') {
                      echo '<span class="badge bg-danger">Inactive</span>';
                    } else {
                      echo '<span class="badge bg-info">New Donor</span>';
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