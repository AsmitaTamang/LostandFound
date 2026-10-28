<?php
/**
 * my_reports.php
 * ------------------------------------------------------------
 * PURPOSE: Shows the logged-in user's own lost and found reports.
 *
 * SHOWS:
 *   - Table of the user's own lost items (with image thumbs)
 *   - Table of the user's own found items (with image thumbs)
 *   - Category, color, brand, location, date, status
 *   - Edit button per row (links to edit_item.php)
 *
 * USES:
 *   LEFT JOIN categories so items still appear even if the
 *   category was deactivated (name shows as "—" instead).
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
// Must be logged in to view this page.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// Cache the current user's ID.
$user_id = (int)$_SESSION['user_id'];

// ------------------------------------------------------------
// FETCH THE USER'S OWN LOST ITEMS
// LEFT JOIN so items without a category still show.
// Newest first.
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

// ------------------------------------------------------------
// FETCH THE USER'S OWN FOUND ITEMS
// Same pattern as above.
// ------------------------------------------------------------
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

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     MY REPORTS PAGE
     ============================================================ -->
<div class="container" style="max-width:1100px;">

    <h2>My Reports</h2>

    <!-- ============================================================
         LOST ITEMS TABLE
         ============================================================ -->
    <h3 style="margin-top:20px; color:#6b5b82;">
        My Lost Items (<?php echo $lost_items->num_rows; ?>)
    </h3>

    <?php if ($lost_items->num_rows === 0): ?>

        <!-- Empty state -->
        <p class="helper">You haven't reported any lost items yet.</p>

    <?php else: ?>

        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
            <thead>
                <tr style="background:linear-gradient(120deg,#b39ddb,#f4b6c5); color:white;">
                    <th style="padding:10px; text-align:left;">Image</th>
                    <th style="padding:10px; text-align:left;">Item</th>
                    <th style="padding:10px; text-align:left;">Category</th>
                    <th style="padding:10px; text-align:left;">Color / Brand</th>
                    <th style="padding:10px; text-align:left;">Location</th>
                    <th style="padding:10px; text-align:left;">Date Lost</th>
                    <th style="padding:10px; text-align:left;">Status</th>
                    <th style="padding:10px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $lost_items->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #f0e8f4;">

                    <!-- Thumbnail or "No image" placeholder -->
                    <td style="padding:8px;">
                        <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                                 style="width:70px; height:70px; object-fit:cover;
                                        border-radius:8px; border:2px solid #f4b6c5;">
                        <?php else: ?>
                            <div style="width:70px; height:70px; background:#fdf6f9;
                                        border-radius:8px; display:flex; align-items:center;
                                        justify-content:center; font-size:11px; color:#c8b6e2;">
                                No image
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- Item name -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['item_name']); ?></td>

                    <!-- Category (LEFT JOIN — may be null) -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>

                    <!-- Color / Brand combined -->
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['color'] ?: '—'); ?> /
                        <?php echo htmlspecialchars($row['brand'] ?: '—'); ?>
                    </td>

                    <!-- Location -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['location'] ?: '—'); ?></td>

                    <!-- Date lost -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['date_lost']); ?></td>

                    <!-- Status badge -->
                    <td style="padding:8px;">
                        <span style="background:#f5ebf5; color:#6b5b82; padding:3px 10px;
                                     border-radius:12px; font-size:12px;">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>

                    <!-- Edit button -->
                    <td style="padding:8px; text-align:center;">
                        <a href="edit_item.php?type=lost&id=<?php echo (int)$row['lost_id']; ?>"
                           style="background:linear-gradient(120deg,#b39ddb,#f4b6c5);
                                  color:white; padding:5px 14px; border-radius:14px;
                                  text-decoration:none; font-size:12px;">
                            Edit
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <!-- ============================================================
         FOUND ITEMS TABLE
         ============================================================ -->
    <h3 style="margin-top:40px; color:#6b5b82;">
        My Found Items (<?php echo $found_items->num_rows; ?>)
    </h3>

    <?php if ($found_items->num_rows === 0): ?>

        <!-- Empty state -->
        <p class="helper">You haven't reported any found items yet.</p>

    <?php else: ?>

        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
            <thead>
                <tr style="background:linear-gradient(120deg,#a5d8b8,#c8e6d0); color:#3a6b52;">
                    <th style="padding:10px; text-align:left;">Image</th>
                    <th style="padding:10px; text-align:left;">Item</th>
                    <th style="padding:10px; text-align:left;">Category</th>
                    <th style="padding:10px; text-align:left;">Color / Brand</th>
                    <th style="padding:10px; text-align:left;">Location</th>
                    <th style="padding:10px; text-align:left;">Date Found</th>
                    <th style="padding:10px; text-align:left;">Status</th>
                    <th style="padding:10px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $found_items->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #f0e8f4;">

                    <!-- Thumbnail or "No image" placeholder -->
                    <td style="padding:8px;">
                        <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                                 style="width:70px; height:70px; object-fit:cover;
                                        border-radius:8px; border:2px solid #a5d8b8;">
                        <?php else: ?>
                            <div style="width:70px; height:70px; background:#f0f8f3;
                                        border-radius:8px; display:flex; align-items:center;
                                        justify-content:center; font-size:11px; color:#a5d8b8;">
                                No image
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- Item name -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['item_name']); ?></td>

                    <!-- Category -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>

                    <!-- Color / Brand combined -->
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['color'] ?: '—'); ?> /
                        <?php echo htmlspecialchars($row['brand'] ?: '—'); ?>
                    </td>

                    <!-- Location -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['location'] ?: '—'); ?></td>

                    <!-- Date found -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['date_found']); ?></td>

                    <!-- Status badge -->
                    <td style="padding:8px;">
                        <span style="background:#e8f4ec; color:#3a6b52; padding:3px 10px;
                                     border-radius:12px; font-size:12px;">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>

                    <!-- Edit button -->
                    <td style="padding:8px; text-align:center;">
                        <a href="edit_item.php?type=found&id=<?php echo (int)$row['found_id']; ?>"
                           style="background:linear-gradient(120deg,#a5d8b8,#c8e6d0);
                                  color:white; padding:5px 14px; border-radius:14px;
                                  text-decoration:none; font-size:12px;">
                            Edit
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <!-- Back link to dashboard -->
    <p style="margin-top:30px;">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>