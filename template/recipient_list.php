<?php $force_logout = true; ?>
<?php include('header.php'); ?>
<?php $active_page = 'patient'; ?>
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
        <h2 class="mb-4 text-primary">Recipient (Patient) List</h2>
        <?php 
        // Count total recipients
        $recipient_count = $conn->query("SELECT COUNT(DISTINCT CONCAT(name, '_', age, '_', phone)) as cnt 
                                       FROM users 
                                       WHERE role='recipient'")->fetch_assoc()['cnt'];
        ?>
        <h5 class="mb-3">Total Recipients: <?php echo $recipient_count; ?></h5>
        <table class="table table-bordered table-hover">
          <thead class="table-primary">
            <tr>
              <th>Name</th>
              <th>Age</th>
              <th>Phone</th>
              <th>Requested Blood Group</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $recipients = $conn->query("SELECT u.*, 
                                      br.blood_group as requested_blood_group,
                                      br.status as request_status,
                                      br.units as requested_units
                                      FROM users u 
                                      LEFT JOIN (
                                          SELECT recipient_id, blood_group, status, units,
                                                 ROW_NUMBER() OVER (PARTITION BY recipient_id ORDER BY id DESC) as rn
                                          FROM blood_requests
                                      ) br ON br.recipient_id = u.id AND br.rn = 1
                                      WHERE u.role='recipient'
                                      ORDER BY u.name");

            while ($row = $recipients->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['age']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td>
                  <?php 
                    if ($row['requested_blood_group']) {
                      echo htmlspecialchars($row['requested_blood_group']) . ' (' . htmlspecialchars($row['requested_units']) . ' ml)';
                    } else {
                      echo 'No requests yet';
                    }
                  ?>
                </td>
                <td>
                  <?php 
                    if ($row['request_status'] == 'accepted') {
                      echo '<span class="badge bg-success">Accepted</span>';
                    } elseif ($row['request_status'] == 'pending') {
                      echo '<span class="badge bg-warning text-dark">Pending</span>';
                    } else {
                      echo '<span class="badge bg-info">New Patient</span>';
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