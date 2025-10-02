<?php
// filepath: src/index.php
session_start();

// Redirect based on login status
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // User is logged in, redirect to dashboard
    header('Location: dashboard.php');
} else {
    // User is not logged in, redirect to login page
    header('Location: login.php');
}
exit();
?>