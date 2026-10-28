<?php
/**
 * admin_dashboard.php
 * ------------------------------------------------------------
 * PURPOSE: Admin landing page with system statistics and links
 *          to the main admin management pages.
 *
 * FLOW:
 *   1. Require login AND admin role.
 *   2. Gather six statistics (users, items, categories, claims).
 *   3. Render stat cards + management buttons.
 *
 * ACCESS: Admin only. Regular users are redirected to dashboard.php.
 * ------------------------------------------------------------
 */

// Start output buffering so header() calls work later.
ob_start();

// Show all errors during development.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database.
require_once 'dbconnect.php';

// Start session if not already started.
@session_start();

// ------------------------------------------------------------
// AUTHORIZATION CHECK
// Must be logged in AND have role = 'admin'.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

// ------------------------------------------------------------
// GATHER STATISTICS FOR THE DASHBOARD
// Each query returns a single row with one aggregated column.
// ------------------------------------------------------------
$stats = [];

// Total registered users
$stats['users'] = $conn->query(
    "SELECT COUNT(*) AS c FROM users"
)->fetch_assoc()['c'];

// Total lost item reports
$stats['lost'] = $conn->query(
    "SELECT COUNT(*) AS c FROM lost_items"
)->fetch_assoc()['c'];

// Total found item reports
$stats['found'] = $conn->query(
    "SELECT COUNT(*) AS c FROM found_items"
)->fetch_assoc()['c'];

// Pending category suggestions
$stats['pending_cats'] = $conn->query(
    "SELECT COUNT(*) AS c FROM pending_categories WHERE status='pending'"
)->fetch_assoc()['c'];

// Pending claims
$stats['pending_claims'] = $conn->query(
    "SELECT COUNT(*) AS c FROM claims WHERE status='pending'"
)->fetch_assoc()['c'];

// Active (approved) categories
$stats['categories'] = $conn->query(
    "SELECT COUNT(*) AS c FROM categories WHERE is_active=1"
)->fetch_assoc()['c'];

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     ADMIN DASHBOARD PAGE
     ============================================================ -->
<div class="container" style="max-width:900px;">

    <h2>Admin Dashboard</h2>

    <p class="helper" style="margin-bottom:20px;">
        Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>.
        Manage system data below.
    </p>

    
        <!-- ============================================================
         STATISTICS GRID
         Six clickable cards, each links to the relevant admin page.
         ============================================================ -->
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:15px; margin:20px 0;">

        <!-- Total users → admin_users.php (list) -->
        <a href="admin_users.php"
           style="background:#f5ebf5; padding:20px; border-radius:14px; text-align:center;
                  box-shadow:0 3px 10px rgba(200,182,226,0.15); text-decoration:none;
                  transition:transform 0.15s ease;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:32px; font-weight:bold; color:#8b6ba8;">
                <?php echo $stats['users']; ?>
            </div>
            <div style="margin-top:5px; color:#6b5b82;">Total Users</div>
        </a>

        <!-- Lost reports → admin_items.php?type=lost -->
        <a href="admin_items.php?type=lost"
           style="background:#fde8ec; padding:20px; border-radius:14px; text-align:center;
                  box-shadow:0 3px 10px rgba(200,182,226,0.15); text-decoration:none;
                  transition:transform 0.15s ease;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:32px; font-weight:bold; color:#8b3a52;">
                <?php echo $stats['lost']; ?>
            </div>
            <div style="margin-top:5px; color:#8b3a52;">Lost Reports</div>
        </a>

        <!-- Found reports → admin_items.php?type=found -->
        <a href="admin_items.php?type=found"
           style="background:#e8f4ec; padding:20px; border-radius:14px; text-align:center;
                  box-shadow:0 3px 10px rgba(200,182,226,0.15); text-decoration:none;
                  transition:transform 0.15s ease;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:32px; font-weight:bold; color:#3a6b52;">
                <?php echo $stats['found']; ?>
            </div>
            <div style="margin-top:5px; color:#3a6b52;">Found Reports</div>
        </a>

        <!-- Active categories → admin_categories.php -->
        <a href="admin_categories.php"
           style="background:#eef2fb; padding:20px; border-radius:14px; text-align:center;
                  box-shadow:0 3px 10px rgba(200,182,226,0.15); text-decoration:none;
                  transition:transform 0.15s ease;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:32px; font-weight:bold; color:#4a5588;">
                <?php echo $stats['categories']; ?>
            </div>
            <div style="margin-top:5px; color:#4a5588;">Active Categories</div>
        </a>

        <!-- Pending categories → admin_categories.php -->
        <a href="admin_categories.php"
           style="background:#fdf0f5; padding:20px; border-radius:14px; text-align:center;
                  box-shadow:0 3px 10px rgba(200,182,226,0.15); text-decoration:none;
                  transition:transform 0.15s ease;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:32px; font-weight:bold; color:#a85675;">
                <?php echo $stats['pending_cats']; ?>
            </div>
            <div style="margin-top:5px; color:#a85675;">Pending Categories</div>
        </a>

        <!-- Pending claims → admin_claims.php -->
        <a href="admin_claims.php"
           style="background:#fdf6f0; padding:20px; border-radius:14px; text-align:center;
                  box-shadow:0 3px 10px rgba(200,182,226,0.15); text-decoration:none;
                  transition:transform 0.15s ease;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:32px; font-weight:bold; color:#8b6b3a;">
                <?php echo $stats['pending_claims']; ?>
            </div>
            <div style="margin-top:5px; color:#8b6b3a;">Pending Claims</div>
        </a>
    </div>
    <!-- ============================================================
         MANAGEMENT LINKS
         Buttons to open each admin management page.
         ============================================================ -->
    <h3 style="margin-top:30px; color:#6b5b82;">Manage</h3>

    <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; margin-top:15px;">

        <a href="admin_categories.php" class="btn" style="text-align:center;">
            Manage Categories (<?php echo $stats['pending_cats']; ?> pending)
        </a>

        <a href="admin_claims.php" class="btn" style="text-align:center;">
            Manage Claims (<?php echo $stats['pending_claims']; ?> pending)
        </a>

        <a href="admin_items.php" class="btn"
           style="text-align:center; background:linear-gradient(120deg,#c9b8dc,#dcd0e8);">
            Manage All Items
        </a>

        <a href="evaluate.php" class="btn"
           style="text-align:center; background:linear-gradient(120deg,#b39ddb,#a886c5);">
            Evaluation Results
        </a>

        <a href="weighting_comparison.php" class="btn"
           style="text-align:center; background:linear-gradient(120deg,#b39ddb,#a886c5);">
            Weighting Comparison
        </a>
    </div>

    <p style="margin-top:30px;">
        <a href="dashboard.php" class="btn"
           style="background:linear-gradient(120deg,#c9b8dc,#dcd0e8);">
            ← Back to User Dashboard
        </a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>