<?php
/**
 * item_detail.php
 * ------------------------------------------------------------
 * PURPOSE: Shows the full details of a single lost or found item,
 *          plus all potential matches against the opposite type.
 *
 * URL PARAMETERS:
 *   ?type=lost&id=5    → show a lost item + its found matches
 *   ?type=found&id=8   → show a found item + its lost matches
 *
 * FLOW:
 *   1. Require login.
 *   2. Validate type and id.
 *   3. Load the item + reporter details.
 *   4. Run the matching algorithm (find_matches_for_lost/found).
 *   5. Render the item card + matches table.
 *
 * USES: match.php
 * ------------------------------------------------------------
 */

// Start output buffering so header() calls work later.
ob_start();

// Show all errors during development.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database.
require_once 'dbconnect.php';

// Load the matching algorithm.
require_once 'match.php';

// Start session if not already started.
@session_start();

// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// ------------------------------------------------------------
// READ type + id FROM URL
// ------------------------------------------------------------
$type = $_GET['type'] ?? 'lost';
$id   = (int)($_GET['id'] ?? 0);

// Validate type. Anything other than lost/found redirects to search.
if (!in_array($type, ['lost', 'found']) || $id <= 0) {
    ob_end_clean();
    header("Location: search.php");
    exit;
}

// ------------------------------------------------------------
// TABLE + COLUMN NAMES DEPEND ON TYPE
// ------------------------------------------------------------
$table     = ($type === 'lost') ? 'lost_items' : 'found_items';
$id_column = ($type === 'lost') ? 'lost_id'    : 'found_id';
$date_col  = ($type === 'lost') ? 'date_lost'  : 'date_found';

// ------------------------------------------------------------
// LOAD THE ITEM + REPORTER DETAILS
// LEFT JOINs so the page still renders if category or user
// rows are missing.
// ------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT i.*, c.category_name,
            u.full_name AS reporter_name,
            u.email AS reporter_email
     FROM $table i
     LEFT JOIN categories c ON i.category_id = c.category_id
     LEFT JOIN users u ON i.user_id = u.user_id
     WHERE i.$id_column = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If item doesn't exist, stop here.
if (!$item) {
    echo "Item not found.";
    exit;
}

// ------------------------------------------------------------
// RUN THE MATCHING ALGORITHM
// If this is a lost item, find found matches (and vice versa).
// ------------------------------------------------------------
if ($type === 'lost') {
    $matches = find_matches_for_lost($conn, $id);
} else {
    $matches = find_matches_for_found($conn, $id);
}

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     ITEM DETAIL PAGE
     ============================================================ -->
<div class="container" style="max-width:900px;">

    <h2><?php echo $type === 'lost' ? 'Lost Item' : 'Found Item'; ?> Details</h2>

    <!-- ============================================================
         ITEM CARD: image + attributes
         ============================================================ -->
    <div style="display:flex; gap:20px; margin-bottom:25px;">

        <!-- Image -->
        <div>
            <?php if (!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
                     style="width:200px; height:200px; object-fit:cover;
                            border-radius:14px; border:3px solid #f4b6c5;
                            box-shadow:0 4px 14px rgba(200,182,226,0.25);">
            <?php else: ?>
                <div style="width:200px; height:200px; background:#fdf6f9;
                            border-radius:14px; display:flex; align-items:center;
                            justify-content:center; color:#c8b6e2;
                            border:3px solid #f4b6c5; font-style:italic;">
                    No image
                </div>
            <?php endif; ?>
        </div>

        <!-- Attributes -->
        <div style="flex:1;">
            <h3 style="color:#6b5b82; margin-bottom:8px;">
                <?php echo htmlspecialchars($item['item_name']); ?>
            </h3>

            <p style="margin-top:8px; color:#7a6b85; line-height:1.9;">
                <strong>Category:</strong> <?php echo htmlspecialchars($item['category_name'] ?? '—'); ?><br>
                <strong>Color:</strong>    <?php echo htmlspecialchars($item['color']    ?: '—'); ?><br>
                <strong>Brand:</strong>    <?php echo htmlspecialchars($item['brand']    ?: '—'); ?><br>
                <strong>Location:</strong> <?php echo htmlspecialchars($item['location'] ?: '—'); ?><br>
                <strong>Date:</strong>     <?php echo htmlspecialchars($item[$date_col]); ?><br>
                <strong>Status:</strong>   <?php echo htmlspecialchars($item['status']); ?><br>
                <strong>Reported by:</strong> <?php echo htmlspecialchars($item['reporter_name'] ?? '—'); ?>
            </p>

            <!-- Description (only if present) -->
            <?php if (!empty($item['description'])): ?>
                <p style="margin-top:12px; padding:12px; background:#fdf6f9;
                          border-radius:12px; color:#6b5b82; font-size:14px;
                          border-left:4px solid #f4b6c5;">
                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                </p>
            <?php endif; ?>

            <!-- Edit link (only if this is the current user's item) -->
            <?php if ($item['user_id'] == $_SESSION['user_id']): ?>
                <p style="margin-top:15px;">
                    <a href="edit_item.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>"
                       class="btn">
                        Edit This Item
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================
         MATCHES TABLE
         ============================================================ -->
    <h3 style="margin-top:30px; color:#6b5b82;">
        Potential Matches (<?php echo count($matches); ?>)
    </h3>

    <?php if (empty($matches)): ?>

        <p class="helper">No matches found for this item.</p>

    <?php else: ?>

        <table style="width:100%; border-collapse:collapse; font-size:14px; margin-top:10px;">
            <thead>
                <tr style="background:linear-gradient(120deg,#b39ddb,#f4b6c5); color:white;">
                    <th style="padding:8px; text-align:left;">Image</th>
                    <th style="padding:8px; text-align:left;">Item</th>
                    <th style="padding:8px; text-align:center;">Score</th>
                    <th style="padding:8px; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($matches as $m): ?>

                <?php
                // The "other" item is the opposite type of what we're viewing.
                // If we're viewing a lost item, matches come from found_items.
                $other      = $type === 'lost' ? $m['found']      : $m['lost'];
                $other_id   = $type === 'lost' ? $other['found_id'] : $other['lost_id'];
                $other_type = $type === 'lost' ? 'found'          : 'lost';
                ?>

                <tr style="border-bottom:1px solid #f0e8f4;">

                    <!-- Thumbnail -->
                    <td style="padding:6px;">
                        <?php if (!empty($other['image_path']) && file_exists($other['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($other['image_path']); ?>"
                                 style="width:50px; height:50px; object-fit:cover;
                                        border-radius:8px; border:1px solid #f4b6c5;">
                        <?php else: ?>
                            <div style="width:50px; height:50px; background:#fdf6f9;
                                        border-radius:8px;"></div>
                        <?php endif; ?>
                    </td>

                    <!-- Name -->
                    <td style="padding:6px;">
                        <?php echo htmlspecialchars($other['item_name']); ?>
                    </td>

                    <!-- Score -->
                    <td style="padding:6px; text-align:center;">
                        <strong style="color:#6b5b82;">
                            <?php echo (int)$m['score']; ?>/100
                        </strong>
                    </td>

                    <!-- View link to the matched item's detail page -->
                    <td style="padding:6px; text-align:center;">
                        <a href="item_detail.php?type=<?php echo $other_type; ?>&id=<?php echo $other_id; ?>"
                           style="color:#8b6ba8; text-decoration:underline; font-size:13px;">
                            View
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <p style="margin-top:25px;">
        <a href="search.php" class="btn">← Back to Search</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>