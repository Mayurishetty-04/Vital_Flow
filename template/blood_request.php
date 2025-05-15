<?php $force_logout = true; ?>
<?php include('header.php'); ?>
<?php $active_page = 'blood_request'; ?>
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
</style>

<div class="container-fluid">
  <div class="row">
    <?php include('sidebar.php'); ?>
    <main class="col-md-10 ms-sm-auto px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
        <h2 class="text-info">Blood Requests</h2>
      </div>

      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
          ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-info">
            <tr>
              <th>Patient Name</th>
              <th>Age</th>
              <th>Blood Group</th>
              <th>Unit (in ml)</th>
              <th>Status</th>
              <th>Donor Name</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $requests = $conn->query("SELECT 
                br.*,
                r.name as recipient_name,
                r.age as recipient_age,
                d.name as donor_name
              FROM blood_requests br 
              JOIN users r ON br.recipient_id = r.id 
              LEFT JOIN users d ON br.donor_id = d.id 
              ORDER BY br.id DESC");

            if ($requests->num_rows > 0):
              while ($row = $requests->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['recipient_age']); ?></td>
                  <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                  <td><?php echo htmlspecialchars($row['units']); ?></td>
                  <td>
                    <?php
                      switch($row['status']) {
                          case 'pending':
                              echo '<span class="badge bg-warning text-dark">Pending</span>';
                              break;
                          case 'donor_accepted':
                              echo '<span class="badge bg-info">Waiting Admin Approval</span>';
                              echo '<div class="mt-2">
                                  <button class="btn btn-success btn-sm approve-request" data-request-id="'.$row['id'].'">Approve</button>
                                  <button class="btn btn-danger btn-sm reject-request" data-request-id="'.$row['id'].'">Reject</button>
                              </div>';
                              break;
                          case 'admin_approved':
                              echo '<span class="badge bg-success">Approved</span>';
                              break;
                          case 'admin_rejected':
                              echo '<span class="badge bg-danger">Rejected by Admin</span>';
                              break;
                          case 'donor_rejected':
                              echo '<span class="badge bg-danger">Rejected by Donor</span>';
                              break;
                      }
                    ?>
                  </td>
                  <td><?php echo $row['donor_name'] ? htmlspecialchars($row['donor_name']) : '-'; ?></td>
                </tr>
              <?php endwhile;
            else: ?>
              <tr>
                <td colspan="6" class="text-center">No blood requests found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>

<?php include('footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle approve request
    document.querySelectorAll('.approve-request').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            handleRequest(requestId, 'admin_approve');
        });
    });

    // Handle reject request
    document.querySelectorAll('.reject-request').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            handleRequest(requestId, 'admin_reject');
        });
    });

    function handleRequest(requestId, action) {
        fetch('../includes/handle_blood_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=${action}&request_id=${requestId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh the page to show updated status
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to process request'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to process request. Please try again.');
        });
    }
});
</script>