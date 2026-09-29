<?php
/**
 * my_reports.php
 * ------------------------------------------------------------
 * PURPOSE: Shows the logged-in user's own lost and found reports
 *          WITH images, category names, and status.
 * ------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
require_once 'dbconnect.php';

@session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ------------------------------------------------------------
// Fetch user's lost items
// ------------------------------------------------------------
$lost_stmt = $conn->prepare(
    "SELECT l.*, c.category_name
     FROM lost_items l
     LEFT JOIN categories c ON l.category_id = c.category_id
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
     LEFT JOIN categories c ON f.category_id = c.category_id
     WHERE f.user_id = ?
     ORDER BY f.created_at DESC"
);
$found_stmt->bind_param("i", $user_id);
$found_stmt->execute();
$found_items = $found_stmt->get_result();

include 'includes/header.php';
?>

<div class="container" style="max-width:1100px;">

    <h2 style="margin-bottom:20px;">My Reports</h2>

    <!-- ============================================================
         LOST ITEMS
         ============================================================ -->
    <h3 style="margin-top:20px;">My Lost Items (<?php echo $lost_items->num_rows; ?>)</h3>

    <?php if ($lost_items->num_rows === 0): ?>
        <p class="helper">You haven't reported any lost items yet.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
            <thead>
                <tr style="background:#041E60; color:white;">
                    <th style="padding:10px; text-align:left;">Image</th>
                    <th style="padding:10px; text-align:left;">Item</th>
                    <th style="padding:10px; text-align:left;">Category</th>
                    <th style="padding:10px; text-align:left;">Color / Brand</th>
                    <th style="padding:10px; text-align:left;">Location</th>
                    <th style="padding:10px; text-align:left;">Date Lost</th>
                    <th style="padding:10px; text-align:left;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $lost_items->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="padding:8px;">
                        <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                                 style="width:70px; height:70px; object-fit:cover; border-radius:6px; border:1px solid #ccc;">
                        <?php else: ?>
                            <div style="width:70px; height:70px; background:#eee; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:11px; color:#888;">
                                No image
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['color'] ?: '—'); ?> /
                        <?php echo htmlspecialchars($row['brand'] ?: '—'); ?>
                    </td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['location'] ?: '—'); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['date_lost']); ?></td>
                    <td style="padding:8px;">
                        <span style="background:#e8efff; padding:3px 8px; border-radius:10px; font-size:12px;">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- ============================================================
         FOUND ITEMS
         ============================================================ -->
    <h3 style="margin-top:40px;">My Found Items (<?php echo $found_items->num_rows; ?>)</h3>

    <?php if ($found_items->num_rows === 0): ?>
        <p class="helper">You haven't reported any found items yet.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
            <thead>
                <tr style="background:#1e7e34; color:white;">
                    <th style="padding:10px; text-align:left;">Image</th>
                    <th style="padding:10px; text-align:left;">Item</th>
                    <th style="padding:10px; text-align:left;">Category</th>
                    <th style="padding:10px; text-align:left;">Color / Brand</th>
                    <th style="padding:10px; text-align:left;">Location</th>
                    <th style="padding:10px; text-align:left;">Date Found</th>
                    <th style="padding:10px; text-align:left;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $found_items->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="padding:8px;">
                        <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                                 style="width:70px; height:70px; object-fit:cover; border-radius:6px; border:1px solid #ccc;">
                        <?php else: ?>
                            <div style="width:70px; height:70px; background:#eee; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:11px; color:#888;">
                                No image
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['color'] ?: '—'); ?> /
                        <?php echo htmlspecialchars($row['brand'] ?: '—'); ?>
                    </td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['location'] ?: '—'); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['date_found']); ?></td>
                    <td style="padding:8px;">
                        <span style="background:#d4edda; padding:3px 8px; border-radius:10px; font-size:12px;">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p style="margin-top:30px;">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>