<?php
/**
 * my_reports.php
 * ------------------------------------------------------------
 * PURPOSE: Shows the logged-in user's own lost and found reports.
 * ------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
require_once 'dbconnect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's lost items (with category name)
$lost_stmt = $conn->prepare(
    "SELECT l.*, c.category_name
     FROM lost_items l
     JOIN categories c ON l.category_id = c.category_id
     WHERE l.user_id = ?
     ORDER BY l.created_at DESC"
);
$lost_stmt->bind_param("i", $user_id);
$lost_stmt->execute();
$lost_items = $lost_stmt->get_result();

// Fetch user's found items
$found_stmt = $conn->prepare(
    "SELECT f.*, c.category_name
     FROM found_items f
     JOIN categories c ON f.category_id = c.category_id
     WHERE f.user_id = ?
     ORDER BY f.created_at DESC"
);
$found_stmt->bind_param("i", $user_id);
$found_stmt->execute();
$found_items = $found_stmt->get_result();

include 'includes/header.php';
?>

<div class="container" style="max-width:900px;">

    <h2 style="margin-bottom:20px;">My Reports</h2>

    <!-- LOST ITEMS -->
    <h3 style="margin-top:20px;">Lost Items (<?php echo $lost_items->num_rows; ?>)</h3>
    <?php if ($lost_items->num_rows === 0): ?>
        <p class="helper">You haven't reported any lost items yet.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse; margin-top:10px;">
            <tr style="background:#eee;">
                <th style="padding:8px; text-align:left;">Category</th>
                <th style="padding:8px; text-align:left;">Item</th>
                <th style="padding:8px; text-align:left;">Location</th>
                <th style="padding:8px; text-align:left;">Date</th>
                <th style="padding:8px; text-align:left;">Status</th>
            </tr>
            <?php while ($row = $lost_items->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['location']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['date_lost']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['status']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>

    <!-- FOUND ITEMS -->
    <h3 style="margin-top:40px;">Found Items (<?php echo $found_items->num_rows; ?>)</h3>
    <?php if ($found_items->num_rows === 0): ?>
        <p class="helper">You haven't reported any found items yet.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse; margin-top:10px;">
            <tr style="background:#eee;">
                <th style="padding:8px; text-align:left;">Category</th>
                <th style="padding:8px; text-align:left;">Item</th>
                <th style="padding:8px; text-align:left;">Location</th>
                <th style="padding:8px; text-align:left;">Date</th>
                <th style="padding:8px; text-align:left;">Status</th>
            </tr>
            <?php while ($row = $found_items->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['location']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['date_found']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['status']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>

    <p style="margin-top:30px;">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>