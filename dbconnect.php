<?php
/**
 * dbconnect.php
 * ------------------------------------------------------------
 * PURPOSE: Establishes a connection to the MySQL database.
 * ------------------------------------------------------------
 */

// ============================================================
// DATABASE CONFIGURATION
// ============================================================

$db_host = 'localhost';      // XAMPP server
$db_user = 'root';           // Default XAMPP username
$db_pass = '';               // Your MySQL password (empty if none)
$db_name = 'lost_and_found'; // Our database name
$db_port = 3307;             

// ============================================================
// CREATE CONNECTION (with port)
// ============================================================

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// ============================================================
// CHECK CONNECTION
// ============================================================

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ============================================================
// SET CHARACTER ENCODING
// ============================================================

$conn->set_charset("utf8mb4");

// ============================================================
// CONNECTION SUCCESSFUL
// ============================================================
?>