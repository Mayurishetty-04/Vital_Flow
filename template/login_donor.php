<?php
session_start();
ob_start(); // Start output buffering
include('../includes/db.php');

// Process login form submission
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND role='donor'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'donor';
            header('Location: dashboard_donor.php');
            exit();
        } else {
            $error_msg = 'Invalid password';
        }
    } else {
        $error_msg = 'User not found';
    }
}

include('header.php');
?>
<style>
  body {
    background: linear-gradient(rgba(220, 53, 69, 0.7), rgba(220, 53, 69, 0.4)), 
                url('../images/loginbg.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    min-height: 100vh;
  }
  .card {
    background-color: rgba(255, 255, 255, 0.9) !important;
    border: none !important;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.2) !important;
    backdrop-filter: blur(10px);
  }
  .card-title {
    color: #8b0000 !important;
    font-size: 2.5rem;
    font-family: 'Times New Roman', serif;
    text-align: center;
    margin-bottom: 2rem;
  }
  .form-control {
    background-color: rgba(255, 255, 255, 0.8) !important;
    border: 1px solid rgba(220, 53, 69, 0.2) !important;
    padding: 0.75rem;
  }
  .form-control:focus {
    background-color: rgba(255, 255, 255, 0.95) !important;
    border-color: rgba(220, 53, 69, 0.5) !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
  }
  .btn-login {
    background: #dc3545;
    color: white;
    padding: 0.75rem;
    font-size: 1.1rem;
    border: none;
    width: 100%;
    margin-top: 1rem;
  }
  .btn-login:hover {
    background: #c82333;
  }
  .register-link {
    color: #dc3545;
    text-decoration: none;
  }
  .register-link:hover {
    text-decoration: underline;
  }
</style>
<div class="row justify-content-center align-items-center" style="min-height: 80vh;">
  <div class="col-md-4">
    <div class="card shadow">
      <div class="card-body p-4">
        <h3 class="card-title">Donor Login</h3>
        <?php if (isset($error_msg)): ?>
          <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <button type="submit" class="btn btn-login" name="login">Login</button>
        </form>
        <div class="mt-3 text-center">
          <a href="register_donor.php" class="register-link">Don't have an account? Register</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php 
include('footer.php');
ob_end_flush(); // End output buffering
?> 