<?php $force_logout = true; ?>
<?php include('header.php'); ?>
<?php $active_page = 'history'; ?>
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
.badge {
  font-size: 0.9em;
  padding: 8px 12px;
}
.table th {
  background-color: #f8f9fa;
  font-weight: 600;
}
.request-row {
  background-color: rgba(255, 193, 7, 0.05);
}
.donation-row {
  background-color: rgba(40, 167, 69, 0.05);
}
</style>

<div class="container-fluid">
  <div class="row">
    <?php include('sidebar.php'); ?>
    <main class="col-md-10 ms-sm-auto px-md-4">
      <div class="container mt-4">
        <h2 class="mb-4 text-secondary">Request History</h2>
        <table class="table table-bordered table-hover">
          <thead class="table-secondary">
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Name</th>
              <th>Blood Group</th>
              <th>Units</th>
              <th>Status</th>
              <th>Handled By</th>
              <th>Notes/Reason</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Combined query for both requests and donations
            $combined_query = "(
                SELECT 
                    requested_at as date,
                    'Request' as type,
                    u.name,
                    br.blood_group,
                    br.units,
                    CASE 
                        WHEN br.status = 'admin_approved' THEN 'Approved'
                        WHEN br.status = 'admin_rejected' THEN 'Rejected by Admin'
                        WHEN br.status = 'donor_accepted' THEN 'Waiting Admin Approval'
                        WHEN br.status = 'donor_rejected' THEN 'Rejected by Donor'
                        ELSE 'Pending'
                    END as status,
                    COALESCE(d.name, '-') as handled_by,
                    '-' as notes
                FROM blood_requests br
                JOIN users u ON br.recipient_id = u.id
                LEFT JOIN users d ON br.donor_id = d.id
            )
            UNION ALL
            (
                SELECT 
                    donation_date as date,
                    'Donation' as type,
                    u.name,
                    d.blood_group,
                    d.units,
                    d.status,
                    'Admin' as handled_by,
                    COALESCE(d.disease, 'None') as notes
                FROM donations d
                JOIN users u ON d.donor_id = u.id
            )
            ORDER BY date DESC, type ASC";

            $results = $conn->query($combined_query);

            if ($results && $results->num_rows > 0):
                while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M. d, Y', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['type']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                        <td><?php echo htmlspecialchars($row['units']); ?></td>
                        <td>
                            <?php
                            $status = strtolower($row['status']);
                            if ($status == 'approved') {
                                echo '<span class="badge bg-success">Approved</span>';
                            } elseif (strpos($status, 'rejected') !== false) {
                                echo '<span class="badge bg-danger">' . htmlspecialchars($row['status']) . '</span>';
                            } elseif ($status == 'waiting admin approval') {
                                echo '<span class="badge bg-info">Waiting Admin Approval</span>';
                            } else {
                                echo '<span class="badge bg-warning text-dark">Pending</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['handled_by']); ?></td>
                        <td><?php echo htmlspecialchars($row['notes']); ?></td>
                    </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="8" class="text-center">No history found.</td>
                </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>

<?php include('footer.php'); ?> 