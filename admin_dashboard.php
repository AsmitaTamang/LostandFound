<?php
/**
 * admin_dashboard.php
 * ------------------------------------------------------------
 * PURPOSE: Admin landing page with system statistics and links.
 *
 * ACCESS: Admin only.
 *
 * FLOW:
 *   1. Start session and verify the user is an admin.
 *   2. Gather system-wide statistics using COUNT queries.
 *   3. Render statistics cards and quick action links.
 *
 * NOTE: This is the central hub for admin functionality.
 *       It links to category management and claim management.
 * ------------------------------------------------------------
 */

// Show all errors while developing.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buffer output so headers can be sent later if needed.
ob_start();

// Connect to the database.
require_once 'dbconnect.php';

// Start the session.
@session_start();

// ------------------------------------------------------------
// AUTHORIZATION CHECK
// Must be logged in AND have the admin role.
// Otherwise, redirect to the normal user dashboard.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

// ------------------------------------------------------------
// GATHER STATISTICS
// All counts are stored in a single $stats array so they can
// be reused easily in the HTML below.
// ------------------------------------------------------------
$stats = [];

// Total registered users.
$stats['users'] = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];

// Total lost item reports (all statuses).
$stats['lost'] = $conn->query("SELECT COUNT(*) AS c FROM lost_items")->fetch_assoc()['c'];

// Total found item reports (all statuses).
$stats['found'] = $conn->query("SELECT COUNT(*) AS c FROM found_items")->fetch_assoc()['c'];

// Pending category suggestions awaiting admin review.
$stats['pending_cats'] = $conn->query("SELECT COUNT(*) AS c FROM pending_categories WHERE status='pending'")->fetch_assoc()['c'];

// Pending claims awaiting admin review.
$stats['pending_claims'] = $conn->query("SELECT COUNT(*) AS c FROM claims WHERE status='pending'")->fetch_assoc()['c'];

// Total active categories available for item reports.
$stats['categories'] = $conn->query("SELECT COUNT(*) AS c FROM categories WHERE is_active=1")->fetch_assoc()['c'];

// Include the shared header/navigation.
include 'includes/header.php';
?>

<!-- ============================================================
     MAIN PAGE CONTAINER
     ============================================================ -->
<div class="container" style="max-width:900px;">
    <h2 style="margin-bottom:20px;">Admin Dashboard</h2>

    <!-- Personalized welcome message using the session name -->
    <p class="helper" style="margin-bottom:20px;">
        Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>. Manage system data below.
    </p>

    <!-- ============================================================
         STATISTICS GRID
         Six cards showing key system metrics in a 3-column layout.
         Each card has a colored background and a large number.
         ============================================================ -->
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:15px; margin:20px 0;">

        <!-- Total Users card -->
        <div style="background:#e8efff; padding:20px; border-radius:8px; text-align:center;">
            <div style="font-size:32px; font-weight:bold; color:#041E60;"><?php echo $stats['users']; ?></div>
            <div style="margin-top:5px;">Total Users</div>
        </div>

        <!-- Lost Reports card -->
        <div style="background:#fff4e6; padding:20px; border-radius:8px; text-align:center;">
            <div style="font-size:32px; font-weight:bold; color:#b35c00;"><?php echo $stats['lost']; ?></div>
            <div style="margin-top:5px;">Lost Reports</div>
        </div>

        <!-- Found Reports card -->
        <div style="background:#e8fff0; padding:20px; border-radius:8px; text-align:center;">
            <div style="font-size:32px; font-weight:bold; color:#1e7e34;"><?php echo $stats['found']; ?></div>
            <div style="margin-top:5px;">Found Reports</div>
        </div>

        <!-- Active Categories card -->
        <div style="background:#f0e8ff; padding:20px; border-radius:8px; text-align:center;">
            <div style="font-size:32px; font-weight:bold; color:#5a2d82;"><?php echo $stats['categories']; ?></div>
            <div style="margin-top:5px;">Active Categories</div>
        </div>

        <!-- Pending Categories card -->
        <div style="background:#ffe8e8; padding:20px; border-radius:8px; text-align:center;">
            <div style="font-size:32px; font-weight:bold; color:#8b0000;"><?php echo $stats['pending_cats']; ?></div>
            <div style="margin-top:5px;">Pending Categories</div>
        </div>

        <!-- Pending Claims card -->
        <div style="background:#fff8e8; padding:20px; border-radius:8px; text-align:center;">
            <div style="font-size:32px; font-weight:bold; color:#8b6a00;"><?php echo $stats['pending_claims']; ?></div>
            <div style="margin-top:5px;">Pending Claims</div>
        </div>
    </div>

    <!-- ============================================================
         QUICK LINKS
         Two big buttons linking to the main admin management pages.
         Each button shows how many items are waiting for review.
         ============================================================ -->
    <h3 style="margin-top:30px;">Manage</h3>
    <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; margin-top:15px;">
        <a href="admin_categories.php" class="btn" style="text-align:center;">
            Manage Categories (<?php echo $stats['pending_cats']; ?> pending)
        </a>
        <a href="admin_claims.php" class="btn" style="text-align:center;">
            Manage Claims (<?php echo $stats['pending_claims']; ?> pending)
        </a>
    </div>

    <!-- Back link to the normal user dashboard -->
    <p style="margin-top:30px;">
        <a href="dashboard.php" class="btn" style="background:#444;">← Back to User Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>