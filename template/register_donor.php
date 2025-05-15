<?php include('header.php'); ?>
<?php include('../includes/db.php'); ?>
<style>
  body {
    background: linear-gradient(rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05)), 
                url('../images/loginbg.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    min-height: 100vh;
  }
  .card {
    background-color: #fff6e6 !important; /* Light yellow background */
    border: none !important;
    box-shadow: 0 0 20px rgba(220, 53, 69, 0.2) !important; /* Red radiating shadow */
    transition: box-shadow 0.3s ease;
  }
  .card:hover {
    box-shadow: 0 0 30px rgba(220, 53, 69, 0.3) !important;
  }
  .card-title {
    color: #8b0000 !important; /* Darker red for the title */
    font-size: 2.5rem;
    font-family: 'Times New Roman', serif;
  }
  .form-control, .form-select {
    background-color: rgba(255, 255, 255, 0.7) !important;
    border: 1px solid rgba(220, 53, 69, 0.2) !important;
  }
  .form-control:focus, .form-select:focus {
    background-color: rgba(255, 255, 255, 0.9) !important;
    border-color: rgba(220, 53, 69, 0.5) !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
  }
</style>
<div class="row justify-content-center align-items-center" style="min-height: 70vh;">
  <div class="col-md-6">
    <div class="card shadow-lg">
      <div class="card-body">
        <h3 class="card-title text-center mb-4">Donor Registration</h3>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="phone" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Age</label>
            <input type="number" class="form-control" name="age" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Blood Group</label>
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
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <button type="submit" class="btn btn-danger w-100" name="register">Register</button>
        </form>
        <div class="mt-3 text-center">
          <a href="login_donor.php">Already have an account? Login</a>
        </div>
        <?php
        if (isset($_POST['register'])) {
          $name = $_POST['name'];
          $phone = $_POST['phone'];
          $age = $_POST['age'];
          $blood_group = $_POST['blood_group'];
          $username = $_POST['username'];
          $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
          $role = 'donor';

          // Check if username already exists
          $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
          $check_username->bind_param("s", $username);
          $check_username->execute();
          $username_result = $check_username->get_result();

          if ($username_result->num_rows > 0) {
            echo '<div class="alert alert-danger mt-3">Username already exists. Please choose a different username.</div>';
          } else {
            // Insert the new donor
            $stmt = $conn->prepare("INSERT INTO users (name, phone, age, blood_group, username, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissss", $name, $phone, $age, $blood_group, $username, $password, $role);
            
            if ($stmt->execute()) {
              $donor_id = $conn->insert_id;
              echo '<div class="alert alert-success mt-3">Registration successful! Redirecting to login...</div>';
              echo '<script>setTimeout(function(){ window.location.href = "login_donor.php"; }, 1500);</script>';
            } else {
              echo '<div class="alert alert-danger mt-3">Registration failed: ' . $stmt->error . '</div>';
            }
          }
        }
        ?>
      </div>
    </div>
  </div>
</div>
<?php include('footer.php'); ?> 