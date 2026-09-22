<?php
/**
 * index.php
 * ------------------------------------------------------------
 * PURPOSE: Entry point of the website.
 *          Redirects logged-in users to dashboard,
 *          and guests to the login page.
 * ------------------------------------------------------------
 */
ob_start(); 
session_start();

// If user is logged in → go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Otherwise → go to login page
header("Location: login.php");
exit;
?>