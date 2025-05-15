<?php
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
header("Location: /blood_bank_project/index.html?logout=1");
exit();
?> 