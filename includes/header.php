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
 *   - Contains the global CSS used by all pages (flowery theme).
 *   - Renders different nav links depending on login state
 *     and role (user vs admin).
 *   - Counts unread notifications for the bell icon.
 *
 * STYLE THEME: Elegant Floral 🌸
 *   Soft pinks, lavenders, cream backgrounds, rounded corners,
 *   gentle shadows, and serif headings for a warm feel.
 * ------------------------------------------------------------
 */

// Start session if it hasn't been started yet.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// ------------------------------------------------------------
// Count unread notifications for the logged-in user.
// Only runs if the user is logged in (guests don't have a bell).
// ------------------------------------------------------------
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    // Load DB connection safely (path differs from page location)
    require_once __DIR__ . '/../dbconnect.php';
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS c FROM notifications
         WHERE user_id = ? AND is_read = 0"
    );
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
         GLOBAL FLORAL STYLES
         Shared across every page. Uses soft pastels, rounded
         corners, and serif headings for an elegant feel.
         ============================================================ -->
    <style>

        /* ------------------------------------------------------------
           RESET + BASE
           Remove default browser margins, set the base font and
           background gradient (cream → soft pink).
           ------------------------------------------------------------ */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: linear-gradient(135deg, #fdf6f0 0%, #fbeef2 100%);
            color: #4a3b52;
            line-height: 1.7;
            min-height: 100vh;
        }

        /* ------------------------------------------------------------
           NAVIGATION BAR
           Soft lavender-to-pink gradient with a thin floral border.
           Title uses the serif font for a warm, elegant feel.
           ------------------------------------------------------------ */
        .navbar {
            background: linear-gradient(120deg, #c8b6e2 0%, #f4b6c5 100%);
            color: #4a3b52;
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 12px rgba(200, 182, 226, 0.35);
            border-bottom: 2px solid rgba(255, 255, 255, 0.6);
        }

        .navbar h1 {
            font-size: 22px;
            font-family: 'Georgia', serif;
            letter-spacing: 0.5px;
            color: #4a3b52;
        }

        /* Navigation links — hidden until hover reveals underline */
        .navbar a {
            color: #4a3b52;
            text-decoration: none;
            margin-left: 22px;
            font-size: 15px;
            padding: 6px 4px;
            transition: all 0.2s ease;
            font-family: 'Georgia', serif;
        }

        .navbar a:hover {
            color: #8b6ba8;
            text-decoration: underline;
            text-decoration-color: #f4b6c5;
            text-decoration-thickness: 2px;
            text-underline-offset: 4px;
        }

        /* Admin link — red badge preserved but softened */
        .navbar .admin-link {
            background: linear-gradient(120deg, #d67b8b 0%, #c85a6e 100%);
            color: #fff;
            padding: 6px 14px;
            border-radius: 20px;
            box-shadow: 0 2px 6px rgba(200, 90, 110, 0.4);
        }

        /* Notification bell badge — soft pink circle */
        .navbar .notif-badge {
            background: #f4b6c5;
            color: #4a3b52;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 4px;
            font-weight: bold;
        }

        /* ------------------------------------------------------------
           CONTAINER
           White card with rounded corners, soft shadow, and a
           thin floral top border.
           ------------------------------------------------------------ */
        .container {
            max-width: 500px;
            margin: 50px auto;
            background: #ffffff;
            padding: 36px 34px;
            border-radius: 20px;
            box-shadow: 0 6px 24px rgba(200, 182, 226, 0.25);
            border-top: 4px solid #f4b6c5;
        }

        /* Headings inside the container */
        .container h2 {
            font-family: 'Georgia', serif;
            color: #6b5b82;
            margin-bottom: 22px;
            border-bottom: 1px dashed #e6d5e8;
            padding-bottom: 12px;
        }

        /* ------------------------------------------------------------
           FORM ELEMENTS
           Soft rounded inputs with focus glow in pink.
           ------------------------------------------------------------ */
        .form-group { margin-bottom: 18px; }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            font-size: 14px;
            color: #6b5b82;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e6d5e8;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            background: #fdfafc;
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #c8b6e2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(244, 182, 197, 0.25);
        }

        /* ------------------------------------------------------------
           BUTTONS
           Soft rounded pill buttons with a gentle hover lift.
           ------------------------------------------------------------ */
        .btn {
            display: inline-block;
            background: linear-gradient(120deg, #b39ddb 0%, #f4b6c5 100%);
            color: #fff;
            padding: 11px 24px;
            border: none;
            border-radius: 24px;
            cursor: pointer;
            font-size: 15px;
            font-family: 'Georgia', serif;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 3px 10px rgba(200, 182, 226, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(200, 182, 226, 0.55);
        }

        /* ------------------------------------------------------------
           ALERTS
           Soft pastel messages (success = mint, error = rose).
           ------------------------------------------------------------ */
        .alert {
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
            font-size: 14px;
            border-left: 5px solid;
        }

        .alert-error {
            background: #fde8ec;
            color: #8b3a52;
            border-left-color: #d67b8b;
        }

        .alert-success {
            background: #e8f4ec;
            color: #3a6b52;
            border-left-color: #7bc99a;
        }

        /* ------------------------------------------------------------
           HELPER TEXT
           Smaller, muted text under fields and forms.
           ------------------------------------------------------------ */
        .helper {
            font-size: 13px;
            color: #9b8ba8;
            margin-top: 10px;
        }

        /* ------------------------------------------------------------
           TABLE STYLES
           Softer tables across the whole site.
           ------------------------------------------------------------ */
        table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(200, 182, 226, 0.15);
        }

        table th {
            font-family: 'Georgia', serif;
            letter-spacing: 0.3px;
        }

        table tr:hover td {
            background: #fdf6f9;
        }
    </style>
</head>
<body>

<!-- ============================================================
     NAVIGATION BAR
     Logged-in users see: Dashboard, My Reports, Browse, Search,
     Profile, Notifications bell (with badge), Admin link (if
     admin), and Logout with their name. Guests see: Login, Register.
     ============================================================ -->
<nav class="navbar">

    <!-- Site title — decorative flower emoji prefix -->
    <h1>🌸 Lost &amp; Found — Niels Brock</h1>

    <div>
        <?php if (isset($_SESSION['user_id'])): ?>

            <!-- ============================================================
                 LOGGED-IN NAVIGATION
                 Regular links for all logged-in users.
                 ============================================================ -->
            <a href="dashboard.php">Dashboard</a>
            <a href="my_reports.php">My Reports</a>
            <a href="browse.php">Browse</a>
            <a href="search.php">Search</a>
            <a href="profile.php">Profile</a>

            <!-- Notifications bell with optional unread badge -->
            <a href="notifications.php" style="position:relative;">
                🔔
                <?php if ($unread_count > 0): ?>
                    <span class="notif-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>

            <!-- Admin-only link, styled as a soft red pill -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="admin-link">Admin</a>
            <?php endif; ?>

            <!-- Logout link showing the user's name -->
            <a href="logout.php">
                Logout (<?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>)
            </a>

        <?php else: ?>

            <!-- ============================================================
                 GUEST NAVIGATION
                 Only shows Login and Register.
                 ============================================================ -->
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>

        <?php endif; ?>
    </div>
</nav>