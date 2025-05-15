<?php
session_start();

// Store the user role before clearing session
$was_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect based on previous role
if ($was_admin) {
    header('Location: login.php');
} else {
    header('Location: ../index.html');
}
exit();
?>