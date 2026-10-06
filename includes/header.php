<?php
/**
 * includes/header.php
 * ------------------------------------------------------------
 * PURPOSE: Shared HTML header + navigation bar.
 *
 * INCLUDED BY: Every page in the site.
 *
 * NOTES:
 *   - Starts the session if not already started.
 *   - Contains the global CSS used by all pages.
 *   - Renders different nav links depending on login state
 *     and role (user vs admin).
 * ------------------------------------------------------------
 */

// Start session if it hasn't been started yet.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
// Count unread notifications
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../dbconnect.php';
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $unread_count = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost and Found - Niels Brock</title>

    <!-- ============================================================
         GLOBAL STYLES
         ============================================================ -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            line-height: 1.6;
        }

        /* NAVBAR */
        .navbar {
            background-color: #041E60;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 { font-size: 20px; }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 15px;
        }
        .navbar a:hover { text-decoration: underline; }
        .navbar .admin-link {
            background: #8b0000;
            padding: 6px 12px;
            border-radius: 5px;
        }

        /* CONTAINER */
        .container {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* FORMS */
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        /* BUTTONS */
        .btn {
            display: inline-block;
            background-color: #041E60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            text-decoration: none;
        }
        .btn:hover { background-color: #0a2d80; }

        /* ALERTS */
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .alert-error   { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }

        /* HELPERS */
        .helper { font-size: 13px; color: #666; margin-top: 10px; }
    </style>
</head>
<body>

<!-- ============================================================
     NAVIGATION BAR
     Logged-in users see Dashboard, My Reports, Browse, Search,
     optional Admin link, and Logout. Guests see Login and Register.
     ============================================================ -->
<nav class="navbar">
    <h1>🔍 Lost &amp; Found — Niels Brock</h1>
    <div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Links for logged-in users -->
            <a href="dashboard.php">Dashboard</a>
            <a href="my_reports.php">My Reports</a>
            <a href="browse.php">Browse</a>
            <a href="search.php">Search</a>
            <a href="profile.php">Profile</a>
            <a href="notifications.php" style="position:relative;">
    🔔
         <?php if ($unread_count > 0): ?>
          <span style="background:#dc3545; color:white; font-size:10px; padding:1px 6px; border-radius:10px; margin-left:3px;">
            <?php echo $unread_count; ?>
         </span>
        <?php endif; ?>
         </a>

            <!-- Admin-only link, styled differently -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="admin-link">Admin</a>
            <?php endif; ?>

            <!-- Logout link showing the user's name -->
            <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>)</a>

        <?php else: ?>
            <!-- Links for guests -->
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>