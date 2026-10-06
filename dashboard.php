<?php
/**
 * dashboard.php
 * ------------------------------------------------------------
 * PURPOSE: Main hub after login.
 *          Shows user greeting, quick stats, and action buttons.
 *
 * ACCESS: Any logged-in user.
 * ------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
require_once 'dbconnect.php';

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

$user_id = (int)$_SESSION['user_id'];

// ------------------------------------------------------------
// GATHER STATISTICS FOR THE DASHBOARD
// ------------------------------------------------------------

// Count the user's own lost reports
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM lost_items WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_lost_count = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// Count the user's own found reports
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM found_items WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_found_count = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// Count total open lost reports (all users)
$total_lost = $conn->query("SELECT COUNT(*) AS c FROM lost_items WHERE status='open'")->fetch_assoc()['c'];

// Count total open found reports (all users)
$total_found = $conn->query("SELECT COUNT(*) AS c FROM found_items WHERE status='open'")->fetch_assoc()['c'];

include 'includes/header.php';
?>

<!-- ============================================================
     MAIN DASHBOARD CONTAINER
     ============================================================ -->
<div class="container" style="max-width:800px;">
    <h2 style="margin-bottom:15px;">
        Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
    </h2>

    <div class="alert alert-success">
        Logged in as <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>
    </div>

    <!-- ============================================================
         QUICK STATS GRID
         ============================================================ -->
    <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:15px; margin:20px 0;">
        <div style="background:#e8efff; padding:20px; border-radius:8px; text-align:center;">
            <div style="font-size:32px; font-weight:bold; color:#041E60;"><?php echo $my_lost_count; ?></div>
            <div style="margin-top:5px;">My Lost Reports</div>
        </div>
        <div style="background:#e8fff0; padding:20px; border-radius:8px; text-align:center;">
            <div style="font-size:32px; font-weight:bold; color:#1e7e34;"><?php echo $my_found_count; ?></div>
            <div style="margin-top:5px;">My Found Reports</div>
        </div>
        <div style="background:#fff4e6; padding:20px; border-radius:8px; text-align:center;">
            <div style="font-size:32px; font-weight:bold; color:#b35c00;"><?php echo $total_lost; ?></div>
            <div style="margin-top:5px;">Open Lost Items</div>
        </div>
        <div style="background:#f0e8ff; padding:20px; border-radius:8px; text-align:center;">
            <div style="font-size:32px; font-weight:bold; color:#5a2d82;"><?php echo $total_found; ?></div>
            <div style="margin-top:5px;">Open Found Items</div>
        </div>
    </div>

    <!-- ============================================================
         MAIN ACTION BUTTONS
         ============================================================ -->
    <h3 style="margin-top:30px;">Actions</h3>
    <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; margin-top:15px;">
        <a href="report_lost.php" class="btn" style="text-align:center;">Report Lost Item</a>
        <a href="report_found.php" class="btn" style="text-align:center;">Report Found Item</a>
        <a href="my_reports.php" class="btn" style="text-align:center; background:#444;">My Reports</a>
        <a href="search.php" class="btn" style="text-align:center; background:#444;">Search</a>
        <a href="browse.php" class="btn" style="text-align:center; background:#444;">Browse &amp; Match</a>
    </div>

    <!-- ============================================================
         ADMIN SECTION (only visible to admins)
         ============================================================ -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <h3 style="margin-top:30px;">Admin</h3>
        <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; margin-top:15px;">
            <a href="admin_dashboard.php" class="btn" style="text-align:center; background:#8b0000;">Admin Dashboard</a>
            <a href="admin_categories.php" class="btn" style="text-align:center; background:#8b0000;">Manage Categories</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>