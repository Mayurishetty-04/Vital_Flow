<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if accessing admin pages without admin role
$current_page = basename($_SERVER['PHP_SELF']);
$admin_pages = ['dashboard_admin.php', 'donation_offers.php', 'donor_list.php', 'recipient_list.php', 'blood_stock.php', 'blood_request.php'];

if (in_array($current_page, $admin_pages) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VitalFlow - Blood Bank Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/blood_bank_project/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .nav-link.btn {
      transition: all 0.3s ease;
      padding: 8px 20px;
      border-radius: 30px;
      background-color: #5a6268;
      border: none;
      min-width: 100px;
      text-align: center;
    }
    .nav-link.btn:hover {
      transform: scale(1.1);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      background-color: #6c757d;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="/blood_bank_project/index.html">
      <div class="vf-logo-container">
        <img src="/blood_bank_project/images/logo.svg" alt="VitalFlow Logo" class="me-2">
      </div>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center" style="gap: 0.5rem;">
        <?php if (isset($_SESSION['user_id'])): ?>
          <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
              <span class="nav-link text-white">Welcome, Admin</span>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link btn text-white" href="logout.php">Logout <i class="fa fa-sign-out-alt"></i></a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link btn text-white" href="login.php">Login</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container"> 