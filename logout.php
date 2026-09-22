<?php
/**
 * logout.php
 * ------------------------------------------------------------
 * PURPOSE: Ends the user's session and returns to login page.
 * ------------------------------------------------------------
 */
ob_start(); 
session_start();          // Resume session so we can destroy it
session_unset();          // Remove all session variables
session_destroy();        // Destroy the session itself

// Redirect to login page
header("Location: login.php");
exit;
?>