<?php $force_logout = true; ?>
<?php include('header.php'); ?>
<?php include('../includes/db.php'); 

// Get count of pending donation offers
$pending_offers = $conn->query("SELECT COUNT(*) as count FROM donation_offers WHERE status='pending'")->fetch_assoc();
$pending_offers_count = $pending_offers['count'];

// Blood group stats
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
$group_counts = [];
$group_units = [];

foreach ($blood_groups as $bg) {
    // Count unique donors for each blood group
    $res = $conn->query("SELECT COUNT(DISTINCT CONCAT(name, '_', age, '_', phone, '_', blood_group)) as cnt 
                        FROM users 
                        WHERE blood_group='$bg' 
                        AND role='donor'
                        AND name IS NOT NULL 
                        AND name != ''
                        AND phone IS NOT NULL 
                        AND phone != ''");
    $row = $res->fetch_assoc();
    $group_counts[$bg] = $row['cnt'];
    
    // Get total units for each blood group from bloodstock
    $stock = $conn->query("SELECT quantity FROM bloodstock WHERE blood_group='$bg'")->fetch_assoc();
    $group_units[$bg] = $stock ? $stock['quantity'] : 0;
}

// Recalculate blood stock to ensure accuracy
recalculateBloodStock($conn);

// Get current blood stock
$blood_stock = getCurrentBloodStock($conn);

// Calculate total blood unit from blood stock table
$total_blood_unit = $conn->query("SELECT SUM(quantity) as total FROM bloodstock")->fetch_assoc()['total'] ?? 0;

// Get blood stock statistics for display
$blood_stock_stats = $conn->query("SELECT blood_group, quantity FROM bloodstock")->fetch_all(MYSQLI_ASSOC);

// Update group_units array for display
foreach ($blood_stock_stats as $stat) {
    $group_units[$stat['blood_group']] = (int)$stat['quantity'];
}

// Total registered donors (unique)
$total_donors = $conn->query("SELECT COUNT(DISTINCT CONCAT(name, '_', age, '_', phone, '_', blood_group)) as cnt 
                            FROM users 
                            WHERE role='donor' 
                            AND name IS NOT NULL 
                            AND name != ''
                            AND phone IS NOT NULL 
                            AND phone != ''")->fetch_assoc()['cnt'];

// Active donors (with approved donations)
$active_donors = $conn->query("
    SELECT COUNT(*) as cnt 
    FROM users u 
    WHERE u.role='donor' 
    AND EXISTS (
        SELECT 1 
        FROM donations d2 
        WHERE d2.donor_id = u.id 
        AND d2.status = 'Approved'
    )
    AND u.name IS NOT NULL 
    AND u.name != ''
    AND u.phone IS NOT NULL 
    AND u.phone != ''")->fetch_assoc()['cnt'];

// Total requests (all blood requests)
$total_requests = $conn->query("SELECT COUNT(*) as cnt FROM blood_requests")->fetch_assoc()['cnt'];

// Approved requests - count from approved donations
$approved_requests = $conn->query("SELECT COUNT(*) as cnt FROM donations WHERE status='Approved'")->fetch_assoc()['cnt'];

// Total recipients (unique recipients)
$recipient_count = $conn->query("SELECT COUNT(DISTINCT CONCAT(name, '_', age, '_', phone)) as cnt 
                              FROM users 
                              WHERE role='recipient'")->fetch_assoc()['cnt'];
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
  position: relative;
}
.notification-badge {
  position: absolute;
  top: -5px;
  right: -10px;
  background: #dc3545;
  color: white;
  border-radius: 50%;
  padding: 3px 8px;
  font-size: 12px;
  font-weight: bold;
}
.sidebar .nav-link.active, .sidebar .nav-link:hover {
  background: #dc3545;
  color: #fff;
}

.card-stat {
  min-height: 120px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  background-color: rgba(255, 255, 255, 0.95) !important;
}
main {
  background-color: rgba(255, 255, 255, 0.9);
  padding: 20px;
  border-radius: 10px;
  margin-top: 20px;
}
.blood-group-stats {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}
.blood-group-units {
  font-size: 1.2rem;
  color: #dc3545;
}
</style>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav class="col-md-2 d-none d-md-block sidebar py-4">
      <div class="position-sticky">
        <ul class="nav flex-column">
          <li class="nav-item"><a class="nav-link active" href="#"><i class="fa fa-home me-2"></i>Home</a></li>
          <li class="nav-item"><a class="nav-link" href="donor_list.php"><i class="fa fa-user me-2"></i>Donor</a></li>
          <li class="nav-item"><a class="nav-link" href="recipient_list.php"><i class="fa fa-user-injured me-2"></i>Patient</a></li>
          <li class="nav-item"><a class="nav-link" href="donations.php"><i class="fa fa-hand-holding-medical me-2"></i>Donations</a></li>
          <li class="nav-item">
            <a class="nav-link" href="donation_offers.php">
              <i class="fa fa-clipboard-check me-2"></i>Donation Offers
              <?php if ($pending_offers_count > 0): ?>
                <span class="notification-badge"><?php echo $pending_offers_count; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item"><a class="nav-link" href="blood_request.php"><i class="fa fa-tint me-2"></i>Blood Requests</a></li>
          <li class="nav-item"><a class="nav-link" href="#"><i class="fa fa-history me-2"></i>Request History</a></li>
          <li class="nav-item"><a class="nav-link" href="blood_stock.php"><i class="fa fa-database me-2"></i>Blood Stock</a></li>
        </ul>
      </div>
    </nav>
    <!-- Main -->
    <main class="col-md-10 ms-sm-auto px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
        <h1 class="h3">BloodStream Management System</h1>
      </div>

      <?php if ($pending_offers_count > 0): ?>
      <div class="alert alert-info alert-dismissible fade show" role="alert">
        <strong>New Donation Offers!</strong> You have <?php echo $pending_offers_count; ?> pending donation offer(s) to review.
        <a href="donation_offers.php" class="btn btn-sm btn-primary ms-3">Review Now</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php endif; ?>

      <!-- Add Blood Requests Section -->
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title">Pending Blood Request Approvals</h5>
<?php
          $pending_requests = $conn->query("
              SELECT br.*, 
                     r.name as recipient_name, 
                     r.age as recipient_age,
                     d.name as donor_name,
                     bs.quantity as available_units
              FROM blood_requests br
              JOIN users r ON br.recipient_id = r.id
              JOIN users d ON br.donor_id = d.id
              LEFT JOIN bloodstock bs ON bs.blood_group = br.blood_group
              WHERE br.status = 'donor_accepted'
              ORDER BY br.requested_at DESC
          ");

          if ($pending_requests->num_rows > 0): ?>
            <div class="table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="table-info">
                  <tr>
                    <th>Request Date</th>
                    <th>Recipient</th>
                    <th>Age</th>
                    <th>Blood Group</th>
                    <th>Units (ml)</th>
                    <th>Available Stock</th>
                    <th>Donor</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($request = $pending_requests->fetch_assoc()): 
                    $available_units = $request['available_units'] ?? 0;
                    $has_sufficient_blood = $available_units >= $request['units'];
                  ?>
                    <tr>
                      <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                      <td><?php echo htmlspecialchars($request['recipient_name']); ?></td>
                      <td><?php echo htmlspecialchars($request['recipient_age']); ?></td>
                      <td><?php echo htmlspecialchars($request['blood_group']); ?></td>
                      <td><?php echo htmlspecialchars($request['units']); ?></td>
                      <td>
                        <?php if ($has_sufficient_blood): ?>
                          <span class="badge bg-success"><?php echo $available_units; ?> ml Available</span>
                        <?php else: ?>
                          <span class="badge bg-danger">Insufficient Stock (<?php echo $available_units; ?> ml)</span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars($request['donor_name']); ?></td>
                      <td>
                        <?php if ($has_sufficient_blood): ?>
                          <button class="btn btn-success btn-sm approve-request" data-request-id="<?php echo $request['id']; ?>">
                            <i class="fa fa-check"></i> Approve
                          </button>
                        <?php else: ?>
                          <button class="btn btn-secondary btn-sm" disabled title="Insufficient blood stock">
                            <i class="fa fa-check"></i> Approve
                          </button>
                        <?php endif; ?>
                        <button class="btn btn-danger btn-sm reject-request" data-request-id="<?php echo $request['id']; ?>">
                          <i class="fa fa-times"></i> Reject
                        </button>
    </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">No pending blood requests require approval.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <?php foreach ($blood_groups as $bg): ?>
        <div class="col-md-3">
          <div class="card card-stat shadow-sm">
            <div class="blood-group-stats">
              <div><span style="font-size:2.2rem;"><b><?php echo $bg; ?></b> <i class="fa fa-tint text-danger"></i></span></div>
              <div>Donors: <?php echo $group_counts[$bg]; ?></div>
              <div class="blood-group-units">
                Units in Stock: <?php echo $group_units[$bg]; ?> ml
                <?php if ($group_units[$bg] <= 100): ?>
                  <span class="badge bg-danger">Low Stock</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Add debug information in a collapsible section -->
      <div class="card mb-4">
        <div class="card-header" data-bs-toggle="collapse" data-bs-target="#debugInfo" style="cursor: pointer;">
          <i class="fa fa-info-circle"></i> Blood Stock Details
        </div>
        <div id="debugInfo" class="collapse">
          <div class="card-body">
            <h6>Blood Stock Breakdown:</h6>
            <ul>
              <?php foreach ($blood_groups as $bg): ?>
                <li><?php echo $bg; ?>: <?php echo $group_units[$bg]; ?> ml</li>
              <?php endforeach; ?>
            </ul>
            <p>Total from individual stocks: <?php echo $total_blood_unit; ?> ml</p>
            <p>Total from blood stock table: <?php echo $conn->query("SELECT SUM(quantity) as total FROM bloodstock")->fetch_assoc()['total'] ?? 0; ?> ml</p>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <?php foreach (
          [
            ['label' => 'Total Registered Donors', 'value' => $total_donors, 'icon' => 'fa-users text-primary'],
            ['label' => 'Active Donors', 'value' => $active_donors, 'icon' => 'fa-user-check text-success'],
            ['label' => 'Total Recipients', 'value' => $recipient_count, 'icon' => 'fa-user-injured text-info'],
            ['label' => 'Total Blood Unit Available (in ml)', 'value' => $total_blood_unit, 'icon' => 'fa-tint text-danger'],
            ['label' => 'Total Requested Blood', 'value' => $total_requests, 'icon' => 'fa-tasks text-warning'],
            ['label' => 'Approved Blood Requests', 'value' => $approved_requests, 'icon' => 'fa-check-circle text-success']
          ] as $stat): ?>
        <div class="col-md-3">
          <div class="card card-stat shadow-sm">
            <div><i class="fa <?= $stat['icon'] ?>"></i></div>
            <div style="font-size:1.1rem;"><?= $stat['label'] ?></div>
            <div><b><?= $stat['value'] ?></b></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
</div>

<!-- Notification Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-primary text-white">
      <i class="fa fa-bell me-2"></i>
      <strong class="me-auto">New Donation Offer</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="toastMessage"></div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-primary text-white">
      <i class="fa fa-bell me-2"></i>
      <strong class="me-auto">Donor Acceptance</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="toastMessage"></div>
  </div>
</div>

<script>
let lastCount = <?php echo $pending_offers_count; ?>;

function checkNewOffers() {
    fetch('../includes/check_notifications.php')
        .then(response => response.json())
        .then(data => {
            // Update the notification badge
            const badge = document.querySelector('.notification-badge');
            if (data.pending_count > 0) {
                if (badge) {
                    badge.textContent = data.pending_count;
                } else {
                    const link = document.querySelector('a[href="donation_offers.php"]');
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = data.pending_count;
                    link.appendChild(newBadge);
                }
                
                // Show toast for new offers
                if (data.pending_count > lastCount && data.latest_offer) {
                    document.getElementById('toastMessage').textContent = 
                        `New donation offer from ${data.latest_offer.donor_name} (${data.latest_offer.blood_group}) for ${data.latest_offer.units} ml`;
                    const toast = new bootstrap.Toast(document.getElementById('notificationToast'));
                    toast.show();
                    
                    // Play notification sound
                    const audio = new Audio('../sounds/notification.mp3');
                    audio.play().catch(e => console.log('Audio play failed'));
                }
            } else if (badge) {
                badge.remove();
            }
            
            lastCount = data.pending_count;
        })
        .catch(error => console.error('Error checking notifications:', error));
}

// Check for new offers every 30 seconds
setInterval(checkNewOffers, 30000);

let lastNotificationCount = 0;

function checkNewDonorAcceptances() {
    fetch('../includes/check_blood_request_notifications.php')
        .then(response => response.json())
        .then(data => {
            // Update notification badge
            const badge = document.querySelector('.blood-request-badge');
            if (data.unread_count > 0) {
                if (badge) {
                    badge.textContent = data.unread_count;
                } else {
                    const link = document.querySelector('a[href="blood_request.php"]');
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge blood-request-badge';
                    newBadge.textContent = data.unread_count;
                    link.appendChild(newBadge);
                }
                
                // Show toast for new notifications
                if (data.unread_count > lastNotificationCount && data.notifications.length > 0) {
                    const latest = data.notifications[0];
                    document.getElementById('toastMessage').textContent = latest.message;
                    const toast = new bootstrap.Toast(document.getElementById('notificationToast'));
                    toast.show();
                    
                    // Play notification sound
                    const audio = new Audio('../sounds/notification.mp3');
                    audio.play().catch(e => console.log('Audio play failed'));
                }
            } else if (badge) {
                badge.remove();
            }
            
            lastNotificationCount = data.unread_count;
        })
        .catch(error => console.error('Error checking notifications:', error));
}

// Check for new donor acceptances every 30 seconds
setInterval(checkNewDonorAcceptances, 30000);

// Initial check
checkNewDonorAcceptances();

document.addEventListener('DOMContentLoaded', function() {
    // Handle approve request
    document.querySelectorAll('.approve-request').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to approve this blood request?')) {
                const requestId = this.dataset.requestId;
                handleRequest(requestId, 'admin_approve');
            }
        });
    });

    // Handle reject request
    document.querySelectorAll('.reject-request').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to reject this blood request?')) {
                const requestId = this.dataset.requestId;
                handleRequest(requestId, 'admin_reject');
            }
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

<?php include('footer.php'); ?>