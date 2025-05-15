<?php
if (isset($_GET['logout'])) {
  echo '<div class="alert alert-success mt-3 text-center">You have been logged out successfully.</div>';
}

if (!isset($_SESSION['user_id'])) {
  header("Location: index.html");
  exit();
}

$role = $_SESSION['role'];
if ($role == "donor") {
  header("Location: template/dashboard_donor.php");
} elseif ($role == "recipient") {
  header("Location: template/dashboard_recipient.php");
} elseif ($role == "admin") {
  header("Location: template/dashboard_admin.php");
}
?>