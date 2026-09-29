<?php
/**
 * index.php
 * ------------------------------------------------------------
 * PURPOSE: Entry point of the website.
 *          Redirects logged-in users to dashboard,
 *          and guests to the login page.
 *
 * ACCESS: Public.
 * ------------------------------------------------------------
 */

// Buffer output so headers can be sent.
ob_start();

// Start the session to check login state.
session_start();

// ------------------------------------------------------------
// ROUTE BY LOGIN STATE
// ------------------------------------------------------------
if (isset($_SESSION['user_id'])) {
    // Logged in → go to the dashboard.
    header("Location: dashboard.php");
    exit;
}

// Not logged in → go to the login page.
header("Location: login.php");
exit;
?>