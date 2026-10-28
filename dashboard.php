<?php
/**
 * dashboard.php
 * ------------------------------------------------------------
 * PURPOSE: Main hub after login.
 *
 * SHOWS:
 *   - Greeting with the user's name and role
 *   - Four clickable quick-stat cards
 *   - Action buttons: Report Lost, Report Found,
 *     My Reports, Browse, Search
 *   - Admin section (only visible to admins)
 *
 * ACCESS: Any logged-in user. Guests are redirected to login.
 * ------------------------------------------------------------
 */

// Start output buffering so header() calls work later.
ob_start();

// Show all errors while developing.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database.
require_once 'dbconnect.php';

// Start session if not already started.
@session_start();

// ------------------------------------------------------------
// AUTHORIZATION CHECK
// Must be logged in to view this page.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// Cast the session user ID to int for safety.
$user_id = (int)$_SESSION['user_id'];

// ------------------------------------------------------------
// GATHER STATISTICS FOR THE DASHBOARD
// ------------------------------------------------------------

// 1. Count the logged-in user's own lost reports.
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS c FROM lost_items WHERE user_id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_lost_count = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// 2. Count the logged-in user's own found reports.
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS c FROM found_items WHERE user_id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_found_count = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// 3. Count all open lost reports (all users).
$total_lost = $conn->query(
    "SELECT COUNT(*) AS c FROM lost_items WHERE status = 'open'"
)->fetch_assoc()['c'];

// 4. Count all open found reports (all users).
$total_found = $conn->query(
    "SELECT COUNT(*) AS c FROM found_items WHERE status = 'open'"
)->fetch_assoc()['c'];

// Include the shared header + navbar.
include 'includes/header.php';
?>

<!-- ============================================================
     MAIN DASHBOARD
     ============================================================ -->
<div class="container" style="max-width:800px;">

    <!-- Welcome heading with the user's name -->
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>

    <!-- Role badge (green info box) -->
    <div class="alert alert-success">
        Logged in as <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>
    </div>

    <!-- ============================================================
         QUICK STATS GRID (clickable cards)
         ============================================================ -->
    <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:15px; margin:20px 0;">

        <!-- Own lost reports → My Reports -->
        <a href="my_reports.php"
           style="background:#f5ebf5; padding:20px; border-radius:14px; text-align:center;
                  text-decoration:none; box-shadow:0 3px 10px rgba(200,182,226,0.15);
                  transition:transform 0.15s ease;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:32px; font-weight:bold; color:#8b6ba8;">
                <?php echo $my_lost_count; ?>
            </div>
            <div style="margin-top:5px; color:#6b5b82;">My Lost Reports</div>
        </a>

        <!-- Own found reports → My Reports -->
        <a href="my_reports.php"
           style="background:#e8f4ec; padding:20px; border-radius:14px; text-align:center;
                  text-decoration:none; box-shadow:0 3px 10px rgba(200,182,226,0.15);
                  transition:transform 0.15s ease;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:32px; font-weight:bold; color:#3a6b52;">
                <?php echo $my_found_count; ?>
            </div>
            <div style="margin-top:5px; color:#3a6b52;">My Found Reports</div>
        </a>

        <!-- Open lost → Browse -->
        <a href="browse.php"
           style="background:#fde8ec; padding:20px; border-radius:14px; text-align:center;
                  text-decoration:none; box-shadow:0 3px 10px rgba(200,182,226,0.15);
                  transition:transform 0.15s ease;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:32px; font-weight:bold; color:#8b3a52;">
                <?php echo $total_lost; ?>
            </div>
            <div style="margin-top:5px; color:#8b3a52;">Open Lost Items</div>
        </a>

        <!-- Open found → Search (found filter) -->
        <a href="search.php?type=found&status=open"
           style="background:#eef2fb; padding:20px; border-radius:14px; text-align:center;
                  text-decoration:none; box-shadow:0 3px 10px rgba(200,182,226,0.15);
                  transition:transform 0.15s ease;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:32px; font-weight:bold; color:#4a5588;">
                <?php echo $total_found; ?>
            </div>
            <div style="margin-top:5px; color:#4a5588;">Open Found Items</div>
        </a>
    </div>

    <!-- ============================================================
         ACTION BUTTONS
         Quick links to the main user tasks.
         ============================================================ -->
    <h3 style="margin-top:30px;">Actions</h3>

    <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; margin-top:15px;">
        <a href="report_lost.php"  class="btn" style="text-align:center;">Report Lost Item</a>
        <a href="report_found.php" class="btn" style="text-align:center;">Report Found Item</a>
        <a href="my_reports.php"   class="btn" style="text-align:center; background:linear-gradient(120deg,#c9b8dc,#dcd0e8);">My Reports</a>
        <a href="browse.php"       class="btn" style="text-align:center; background:linear-gradient(120deg,#c9b8dc,#dcd0e8);">Browse &amp; Match</a>
        <a href="search.php"       class="btn" style="text-align:center; background:linear-gradient(120deg,#c9b8dc,#dcd0e8);">Search</a>
    </div>

    <!-- ============================================================
         ADMIN SECTION
         Only shown to users with role = 'admin'.
         ============================================================ -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <h3 style="margin-top:30px;">Admin</h3>
        <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; margin-top:15px;">
            <a href="admin_dashboard.php"  class="btn"
               style="text-align:center; background:linear-gradient(120deg,#d67b8b,#c85a6e);">
                Admin Dashboard
            </a>
            <a href="admin_categories.php" class="btn"
               style="text-align:center; background:linear-gradient(120deg,#d67b8b,#c85a6e);">
                Manage Categories
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>