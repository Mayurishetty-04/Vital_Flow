<?php
session_start();
ob_start(); // Start output buffering
include('../includes/db.php');

// Process login form submission
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'recipient'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'recipient';
            header('Location: dashboard_recipient.php');
            exit();
        } else {
            $error_msg = 'Invalid password';
        }
    } else {
        $error_msg = 'Username not found';
    }
}

include('header.php');
?>
<style>
  body {
    background: linear-gradient(rgba(13, 110, 253, 0.1), rgba(13, 110, 253, 0.05)), 
                url('../images/loginbg.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    min-height: 100vh;
  }
  .card {
    background-color: #f8f9fa !important;
    border: none !important;
    box-shadow: 0 0 20px rgba(13, 110, 253, 0.2) !important;
    backdrop-filter: blur(10px);
  }
  .card-title {
    color: #004085 !important;
    font-size: 2.5rem;
    font-family: 'Times New Roman', serif;
    text-align: center;
    margin-bottom: 2rem;
  }
  .form-control {
    background-color: rgba(255, 255, 255, 0.8) !important;
    border: 1px solid rgba(13, 110, 253, 0.2) !important;
    padding: 0.75rem;
  }
  .form-control:focus {
    background-color: rgba(255, 255, 255, 0.95) !important;
    border-color: rgba(13, 110, 253, 0.5) !important;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15) !important;
  }
  .btn-login {
    background: #0d6efd;
    color: white;
    padding: 0.75rem;
    font-size: 1.1rem;
    border: none;
    width: 100%;
    margin-top: 1rem;
  }
  .btn-login:hover {
    background: #0b5ed7;
  }
  .register-link {
    color: #0d6efd;
    text-decoration: none;
  }
  .register-link:hover {
    text-decoration: underline;
  }
</style>

<div class="container">
  <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-5">
      <div class="card shadow">
        <div class="card-body p-5">
          <h3 class="card-title">Recipient Login</h3>
          <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error_msg); ?></div>
          <?php endif; ?>
          <form method="POST">
            <div class="mb-4">
              <label class="form-label">Username</label>
              <input type="text" class="form-control" name="username" required>
            </div>
            <div class="mb-4">
              <label class="form-label">Password</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <button type="submit" class="btn btn-login" name="login">Login</button>
          </form>
          <div class="mt-4 text-center">
            <a href="register_recipient.php" class="register-link">New Recipient? Register here</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php 
include('footer.php');
ob_end_flush(); // End output buffering
?> 