<?php
/**
 * includes/header.php
 * ------------------------------------------------------------
 * PURPOSE: Shared HTML header + navigation bar.
 *          Included at the top of every page.
 * ------------------------------------------------------------
 */

// Start session if not already started
// (Sessions let us remember who is logged in)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost and Found - Niels Brock</title>
    <!-- Simple inline CSS for now. We'll improve later. -->
    <style>
        /* Reset and base styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            line-height: 1.6;
        }

        /* Navigation bar */
        .navbar {
            background-color: #041E60;   /* Niels Brock dark blue */
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

        /* Container for page content */
        .container {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Form styles */
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

        /* Buttons */
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

        /* Alerts */
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .alert-error   { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }

        /* Small helper text */
        .helper { font-size: 13px; color: #666; margin-top: 10px; }
    </style>
</head>
<body>

<!-- Navigation bar -->
<nav class="navbar">
    <h1>🔍 Lost &amp; Found — Niels Brock</h1>
    <div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Show these links only if user is logged in -->
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)</a>
        <?php else: ?>
            <!-- Show these links if user is NOT logged in -->
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>