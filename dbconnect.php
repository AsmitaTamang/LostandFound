<?php
/**
 * dbconnect.php
 * ------------------------------------------------------------
 * PURPOSE: Establishes a connection to the MySQL database.
 *
 * INCLUDED BY: Every PHP page that needs database access.
 *
 * NOTES:
 *   - Uses MySQLi (procedural OOP style) to connect.
 *   - Dies with a readable error if the connection fails.
 *   - Sets the charset to utf8mb4 for full Unicode support
 *     (needed for emojis, Danish characters, etc.).
 *   - The port is explicitly set to 3307 because the local
 *     XAMPP install runs MySQL on a non-default port.
 *   - Do NOT commit real credentials to a public repo.
 *     For a public version, load them from environment vars.
 * ------------------------------------------------------------
 */

// ============================================================
// DATABASE CONFIGURATION
// Adjust these values if your MySQL setup is different.
// ============================================================

// Hostname of the MySQL server (localhost for local XAMPP)
$db_host = 'localhost';

// MySQL username (default XAMPP user)
$db_user = 'root';

// MySQL password (empty by default in a fresh XAMPP install)
$db_pass = '';

// Database name (must match the one created by schema.sql)
$db_name = 'lost_and_found';

// Port (3307 on this machine; default is 3306 elsewhere)
$db_port = 3307;

// ============================================================
// CREATE CONNECTION
// MySQLi constructor: (host, user, pass, dbname, port)
// ============================================================
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// ============================================================
// CHECK CONNECTION
// If the connection failed, stop immediately with a clear error.
// ============================================================
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ============================================================
// SET CHARACTER ENCODING
// utf8mb4 is the widest UTF-8 variant — supports emojis and
// all international characters needed for Danish names.
// ============================================================
$conn->set_charset("utf8mb4");

// ============================================================
// CONNECTION IS READY
// Any script that includes this file can now use $conn to
// run queries: $conn->query(...), $conn->prepare(...), etc.
// ============================================================
?>