<?php
// First include database connection
include('../includes/db.php');

// Then include header which starts the session
include('header.php');

$active_page = 'donation_offers';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle offer approval/rejection
if (isset($_POST['action']) && isset($_POST['offer_id'])) {
    $offer_id = (int)$_POST['offer_id'];
    $action = $_POST['action'];
    $comment = isset($_POST['comment']) ? $_POST['comment'] : '';
    
    if ($action === 'approve' || $action === 'reject') {
        $conn->begin_transaction();
        try {
            // Update offer status
            $stmt = $conn->prepare("UPDATE donation_offers SET status=?, admin_comment=? WHERE id=?");
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $stmt->bind_param("ssi", $status, $comment, $offer_id);
            $stmt->execute();
            
            // If approved, create donation record
            if ($action === 'approve') {
                // Get offer details
                $offer = $conn->query("SELECT * FROM donation_offers WHERE id=$offer_id")->fetch_assoc();
                
                // Insert into donations table
                $stmt2 = $conn->prepare("INSERT INTO donations (donor_id, blood_group, units, donation_date, status) VALUES (?, ?, ?, ?, 'Approved')");
                $stmt2->bind_param("isis", $offer['donor_id'], $offer['blood_group'], $offer['units'], $offer['offer_date']);
                $stmt2->execute();
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "Donation offer has been " . ($action === 'approve' ? 'approved' : 'rejected');
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error processing the donation offer.";
        }
    }
    
    header("Location: donation_offers.php");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
?>

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
.container {
    background-color: rgba(255, 255, 255, 0.95);
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
    margin-bottom: 20px;
}
.status-filter {
    margin-bottom: 20px;
}
.status-filter .btn {
    margin-right: 10px;
}
.table {
    background-color: white;
}
.modal-header {
    background-color: #dc3545;
    color: white;
}
</style>

<div class="container-fluid">
    <div class="row">
        <?php include('sidebar.php'); ?>
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="container">
                <h2 class="mb-4">Manage Donation Offers</h2>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                            echo $_SESSION['success_message'];
                            unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php 
                            echo $_SESSION['error_message'];
                            unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="status-filter">
                    <a href="?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Pending
                    </a>
                    <a href="?status=approved" class="btn <?php echo $status_filter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">
                        Approved
                    </a>
                    <a href="?status=rejected" class="btn <?php echo $status_filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                        Rejected
                    </a>
                </div>

                <?php
                $offers = $conn->query("
                    SELECT do.*, u.name as donor_name, u.phone as donor_phone, u.age as donor_age 
                    FROM donation_offers do
                    JOIN users u ON do.donor_id = u.id
                    WHERE do.status = '$status_filter'
                    ORDER BY do.created_at DESC
                ");
                
                if ($offers->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Donor Name</th>
                                    <th>Age</th>
                                    <th>Phone</th>
                                    <th>Blood Group</th>
                                    <th>Units (ml)</th>
                                    <?php if ($status_filter === 'pending' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                        <th>Action</th>
                                    <?php else: ?>
                                        <th>Admin Comment</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $offers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['offer_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['donor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['donor_age']); ?></td>
                                    <td><?php echo htmlspecialchars($row['donor_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                                    <td><?php echo htmlspecialchars($row['units']); ?></td>
                                    <?php if ($status_filter === 'pending' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                        <td>
                                            <button class="btn btn-success btn-sm" onclick="showActionModal(<?php echo $row['id']; ?>, 'approve')">
                                                <i class="fa fa-check"></i> Accept
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="showActionModal(<?php echo $row['id']; ?>, 'reject')">
                                                <i class="fa fa-times"></i> Reject
                                            </button>
                                        </td>
                                    <?php else: ?>
                                        <td><?php echo $row['admin_comment'] ? htmlspecialchars($row['admin_comment']) : '-'; ?></td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No <?php echo $status_filter; ?> donation offers found.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Donation Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="offer_id" id="offer_id">
                    <input type="hidden" name="action" id="action">
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">Comment (optional)</label>
                        <textarea class="form-control" name="comment" id="comment" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showActionModal(offerId, action) {
    document.getElementById('offer_id').value = offerId;
    document.getElementById('action').value = action;
    
    const modal = new bootstrap.Modal(document.getElementById('actionModal'));
    modal.show();
}
</script>

<?php include('footer.php'); ?> 