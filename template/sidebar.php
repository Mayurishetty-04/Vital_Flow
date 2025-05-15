<nav class="col-md-2 d-none d-md-block sidebar py-4">
  <div class="position-sticky">
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link <?php if(isset($active_page) && $active_page=='home') echo 'active'; ?>" href="dashboard_admin.php"><i class="fa fa-home me-2"></i>Home</a></li>
      <li class="nav-item"><a class="nav-link <?php if(isset($active_page) && $active_page=='donor') echo 'active'; ?>" href="donor_list.php"><i class="fa fa-user me-2"></i>Donor</a></li>
      <li class="nav-item"><a class="nav-link <?php if(isset($active_page) && $active_page=='patient') echo 'active'; ?>" href="recipient_list.php"><i class="fa fa-user-injured me-2"></i>Patient</a></li>
      <li class="nav-item"><a class="nav-link <?php if(isset($active_page) && $active_page=='donations') echo 'active'; ?>" href="donations.php"><i class="fa fa-hand-holding-medical me-2"></i>Donations</a></li>
      <li class="nav-item"><a class="nav-link <?php if(isset($active_page) && $active_page=='donation_offers') echo 'active'; ?>" href="donation_offers.php"><i class="fa fa-clipboard-check me-2"></i>Donation Offers</a></li>
      <li class="nav-item"><a class="nav-link <?php if(isset($active_page) && $active_page=='blood_request') echo 'active'; ?>" href="blood_request.php"><i class="fa fa-tint me-2"></i>Blood Requests</a></li>
      <li class="nav-item"><a class="nav-link <?php if(isset($active_page) && $active_page=='history') echo 'active'; ?>" href="request_history.php"><i class="fa fa-history me-2"></i>Request History</a></li>
      <li class="nav-item"><a class="nav-link <?php if(isset($active_page) && $active_page=='blood_stock') echo 'active'; ?>" href="blood_stock.php"><i class="fa fa-database me-2"></i>Blood Stock</a></li>
    </ul>
  </div>
</nav> 