<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header('Location: ../login.php');
    exit();
}
header('Location: dashboard.php');
exit();
?>