<?php
session_start();
include('../includes/db.php');

// Check if user is logged in and is a donor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header('Location: login_donor.php');
    exit();
}

$donor_id = $_SESSION['user_id'];
$donor = $conn->query("SELECT * FROM users WHERE id=$donor_id")->fetch_assoc();
$donor_name = $donor['name'];
$donor_blood_group = $donor['blood_group'];

// Handle donation units submission
if (isset($_POST['submit_donation']) && isset($_POST['donation_units'])) {
    $units = (int)$_POST['donation_units'];
    
    // Validate units
    if ($units >= 100 && $units <= 500) {
        // Start transaction
        $conn->begin_transaction();
        try {
            $offer_date = date('Y-m-d');
            
            $stmt = $conn->prepare("INSERT INTO donation_offers (donor_id, blood_group, units, offer_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $donor_id, $donor_blood_group, $units, $offer_date);
            $stmt->execute();
            
            $conn->commit();
            $_SESSION['success_message'] = "Thank you! Your blood donation offer has been submitted for admin approval.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error submitting your donation offer. Please try again.";
        }
    } else {
        $_SESSION['error_message'] = "Please enter a valid amount between 100 and 500 ml.";
    }
    
    header("Location: dashboard_donor.php");
    exit();
}

// Now include the header after all redirects
include('header.php');
?>

<style>
  body {
    background: linear-gradient(rgba(220, 53, 69, 0.3), rgba(220, 53, 69, 0.2)), 
                url('../images/dashboard.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    min-height: 100vh;
  }
  .container {
    padding-top: 2rem;
    padding-bottom: 2rem;
  }
  .card {
    background-color: rgba(255, 255, 255, 0.95) !important;
    border: none !important;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1) !important;
    backdrop-filter: blur(5px);
    margin-bottom: 2rem;
  }
  .card-title {
    color: #721c24 !important;
    font-size: 1.8rem;
    margin-bottom: 1.5rem;
  }
  .welcome-title {
    color: #4a1115;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    margin-bottom: 2rem;
  }
  .table {
    background-color: rgba(255, 255, 255, 0.95);
  }
  .badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
  }
  .blood-group-badge {
    background-color: #dc3545;
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 5px;
    font-size: 1.2rem;
    margin-left: 10px;
    display: inline-block;
    vertical-align: middle;
  }
  .donation-form-card {
    max-width: 400px;
    margin-right: auto;
    margin-left: 0;
  }
  .donation-form-card .card-title {
    font-size: 1.5rem;
    margin-bottom: 1rem;
  }
  .donation-form-card .form-label {
    font-size: 0.95rem;
  }
  .donation-form-card .form-text {
    font-size: 0.85rem;
  }
</style>

<div class="container">
    <h2 class="welcome-title">
        Welcome, <?php echo htmlspecialchars($donor_name); ?> (Donor)
        <span class="blood-group-badge">Blood Group: <?php echo htmlspecialchars($donor_blood_group); ?></span>
    </h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="card donation-form-card">
        <div class="card-body">
            <h5 class="card-title">Specify Blood Donation Units</h5>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="donation_units" class="form-label">How many units of blood would you like to donate? (in ml)</label>
                    <input type="number" class="form-control" id="donation_units" name="donation_units" min="100" max="500" step="50" required>
                    <div class="form-text">Standard blood donation is typically between 200-350 ml.</div>
                </div>
                <button type="submit" name="submit_donation" class="btn btn-danger w-100">Submit Donation Offer</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Available Blood Requests</h5>
            <?php
            $requests = $conn->query("SELECT blood_requests.*, users.name as recipient_name 
                                    FROM blood_requests 
                                    LEFT JOIN users ON blood_requests.recipient_id = users.id 
                                    WHERE blood_requests.status = 'pending' 
                                    AND blood_requests.blood_group = '$donor_blood_group'
                                    ORDER BY blood_requests.id DESC");
            
            if ($requests->num_rows > 0): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Blood Group</th>
                            <th>Units (ml)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $requests->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
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
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                <button type="button" 
                                        class="btn btn-success btn-sm accept-request" 
                                        data-request-id="<?php echo $row['id']; ?>">
                                    Accept Request
                                </button>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No pending blood requests available.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Your Donation Offers</h5>
            <?php
            $offers = $conn->query("SELECT * FROM donation_offers 
                                  WHERE donor_id = $donor_id 
                                  ORDER BY created_at DESC");
            
            if ($offers->num_rows > 0): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Units (ml)</th>
                            <th>Status</th>
                            <th>Admin Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $offers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['offer_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['units']); ?></td>
                            <td>
                                <?php 
                                    $badge_class = [
                                        'pending' => 'bg-warning text-dark',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger'
                                    ][$row['status']];
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $row['admin_comment'] ? htmlspecialchars($row['admin_comment']) : '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">You haven't made any donation offers yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Your Accepted Donations</h5>
            <?php
            $accepted = $conn->query("SELECT blood_requests.*, users.name as recipient_name 
                                    FROM blood_requests 
                                    LEFT JOIN users ON blood_requests.recipient_id = users.id 
                                    WHERE blood_requests.donor_id = $donor_id 
                                    ORDER BY blood_requests.id DESC");
            
            if ($accepted->num_rows > 0): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Blood Group</th>
                            <th>Units (ml)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $accepted->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td><?php echo htmlspecialchars($row['units']); ?></td>
                            <td>
                                <span class="badge bg-success">Accepted</span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">You haven't accepted any donation requests yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add this before the closing </main> tag -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-primary text-white">
      <i class="fa fa-bell me-2"></i>
      <strong class="me-auto">New Blood Request</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="toastMessage"></div>
  </div>
</div>

<!-- Add this before the closing </body> tag -->
<script>
let lastNotificationCount = 0;

function checkNewRequests() {
    fetch('../includes/check_blood_request_notifications.php')
        .then(response => response.json())
        .then(data => {
            // Update notification badge
            const badge = document.querySelector('.notification-badge');
            if (data.unread_count > 0) {
                if (badge) {
                    badge.textContent = data.unread_count;
                } else {
                    const link = document.querySelector('a[href="#blood-requests"]');
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = data.unread_count;
                    link.appendChild(newBadge);
                }
                
                // Show toast for new notifications
                if (data.unread_count > lastNotificationCount && data.notifications.length > 0) {
                    const latest = data.notifications[0];
                    document.getElementById('toastMessage').textContent = 
                        `New blood request from ${latest.recipient_name} for ${latest.units}ml of ${latest.blood_group} blood`;
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

// Check for new requests every 30 seconds
setInterval(checkNewRequests, 30000);

// Initial check
checkNewRequests();

// Add request acceptance handling
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.accept-request').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            
            fetch('../includes/handle_blood_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=donor_accept&request_id=${requestId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the page to show updated status
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to accept request'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to process request. Please try again.');
            });
        });
    });
});
</script>

<?php include('footer.php'); ?>