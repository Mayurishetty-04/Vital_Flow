<?php
session_start();
include('../includes/db.php');
?>
<style>
  .custom-header {
    background: linear-gradient(to right, #dc3545, #dc3545e6);
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    opacity: 0.95;
  }

  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
  }

  .logo-container {
    display: flex;
    align-items: center;
    text-decoration: none;
  }

  .logo-text {
    font-size: 1.8rem;
    margin: 0;
    color: white;
  }

  .logo-text span:first-child {
    color: #ff0000;
  }

  .logo-text span:last-child {
    color: #0d6efd;
  }

  .tagline {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin: 0;
  }

  .nav-buttons {
    display: flex;
    gap: 1rem;
  }

  .nav-btn {
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
  }

  .nav-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white;
  }

  .logout-btn {
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .logout-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    text-decoration: none;
  }

  /* Adjust main content to account for fixed header */
  .container {
    margin-top: 100px;
    padding-top: 20px;
  }
</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VitalFlow - Recipient Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

<!-- Custom Header -->
<header class="custom-header">
  <div class="header-content">
    <a href="index.php" class="logo-container">
      <div>
        <h1 class="logo-text">
          <span>Vital</span><span>Flow</span>
        </h1>
        <p class="tagline">Connecting Life's Vital Flow</p>
      </div>
    </a>
    <div class="nav-buttons">
      <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>
</header>

<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recipient') {
  header('Location: login_recipient.php');
  exit();
}

$recipient_id = $_SESSION['user_id'];
$recipient = $conn->query("SELECT * FROM users WHERE id=$recipient_id")->fetch_assoc();
$recipient_name = $recipient['name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['blood_group']) && isset($_POST['units'])) {
        $blood_group = $_POST['blood_group'];
        $units = (int)$_POST['units'];
        
        if ($units > 0 && $units <= 5000) {
            $stmt = $conn->prepare("INSERT INTO blood_requests (recipient_id, blood_group, units, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("isi", $recipient_id, $blood_group, $units);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Blood request submitted successfully!";
            } else {
                $_SESSION['error_message'] = "Error submitting request. Please try again.";
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Invalid units value. Please enter between 1 and 5000.";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get count of accepted requests
$accepted_count = $conn->query("SELECT COUNT(*) as cnt FROM blood_requests WHERE recipient_id=$recipient_id AND status='accepted'")->fetch_assoc()['cnt'];

// Get all requests
$requests = $conn->query("SELECT blood_requests.*, users.name as donor_name 
                         FROM blood_requests 
                         LEFT JOIN users ON blood_requests.donor_id = users.id 
                         WHERE blood_requests.recipient_id = $recipient_id 
                         ORDER BY blood_requests.id DESC");
?>

<div class="container">
  <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($recipient_name); ?> (Recipient)</h2>
  
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

  <?php if ($accepted_count > 0): ?>
    <div class="alert alert-success">Good news! <b><?php echo $accepted_count; ?></b> of your blood request(s) have been accepted!</div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">Apply for Blood</h5>
      <form method="POST" class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Blood Group Needed</label>
          <select class="form-select" name="blood_group" required>
            <option value="">Select</option>
            <option value="A+">A+</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="O+">O+</option>
            <option value="O-">O-</option>
            <option value="AB+">AB+</option>
            <option value="AB-">AB-</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Units Needed (ml)</label>
          <input type="number" class="form-control" name="units" required min="1" max="5000" placeholder="Enter amount in ml">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Your Blood Requests</h5>
      <div id="requestsTableContainer">
        <?php if ($requests->num_rows > 0): ?>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Blood Group</th>
                <th>Units (ml)</th>
                <th>Status</th>
                <th>Accepted By</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = $requests->fetch_assoc()): ?>
              <tr>
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
                <td><?php echo $row['donor_name'] ? htmlspecialchars($row['donor_name']) : '-'; ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="alert alert-info">You have not made any blood requests yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>