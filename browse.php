<?php
/**
 * browse.php
 * ------------------------------------------------------------
 * PURPOSE: Browse all open lost items and see their matches
 *          ranked by confidence score. Users can claim a
 *          matching found item when the score is at least 40.
 *
 * FLOW:
 *   1. Require login.
 *   2. Fetch all open lost items with category and owner info.
 *   3. For each lost item, run the matching algorithm.
 *   4. Show matches sorted by score with a HIGH/MEDIUM/LOW badge.
 *   5. Show a "Claim" button only for the user's own lost items
 *      when there is at least one match with score >= 40.
 *
 * USES: match.php → find_matches_for_lost()
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

// Cache the current user's ID.
$current_user_id = (int)$_SESSION['user_id'];

// ------------------------------------------------------------
// FETCH ALL OPEN LOST ITEMS
// ------------------------------------------------------------
$lost_result = $conn->query(
    "SELECT l.*, c.category_name, u.full_name AS owner_name
     FROM lost_items l
     LEFT JOIN categories c ON l.category_id = c.category_id
     LEFT JOIN users u ON l.user_id = u.user_id
     WHERE l.status = 'open'
     ORDER BY l.created_at DESC"
);

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     BROWSE & MATCH PAGE
     ============================================================ -->
<div class="container" style="max-width:1100px;">

    <h2>Browse &amp; Match</h2>

    <p class="helper" style="margin-bottom:20px;">
        Below are all open <strong>lost item</strong> reports. The system has
        automatically calculated a match score against every open
        <strong>found item</strong> in the same category. Items with
        <strong>70+ points</strong> are high-confidence matches.
        Click <strong>Claim</strong> on your own lost item to submit a claim.
    </p>

    <?php if ($lost_result->num_rows === 0): ?>

        <div class="alert alert-success">No open lost items to display.</div>

    <?php else: ?>

        <?php while ($lost = $lost_result->fetch_assoc()): ?>

            <?php
            // Run the matching algorithm for this lost item.
            $matches = find_matches_for_lost($conn, $lost['lost_id']);

            // Does this lost item belong to the current user?
            $is_my_lost_item = ((int)$lost['user_id'] === $current_user_id);

            // Is there at least one claimable match (>= 40)?
            $has_claimable_match = false;
            foreach ($matches as $m) {
                if (($m['score'] ?? 0) >= 40) {
                    $has_claimable_match = true;
                    break;
                }
            }
            ?>

            <!-- ============================================================
                 LOST ITEM CARD
                 ============================================================ -->
            <div style="border:1px solid #e6d5e8; border-radius:14px;
                        padding:18px; margin-bottom:25px; background:#ffffff;
                        box-shadow:0 4px 14px rgba(200,182,226,0.15);">

                <!-- Header: image + details -->
                <div style="display:flex; gap:15px; align-items:flex-start;">

                    <!-- Image or placeholder -->
                    <div>
                        <?php if (!empty($lost['image_path']) && file_exists($lost['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($lost['image_path']); ?>"
                                 style="width:100px; height:100px; object-fit:cover;
                                        border-radius:10px; border:2px solid #f4b6c5;">
                        <?php else: ?>
                            <div style="width:100px; height:100px; background:#fdf6f9;
                                        border-radius:10px; display:flex; align-items:center;
                                        justify-content:center; font-size:11px; color:#9b8ba8;
                                        border:2px solid #f4b6c5;">
                                No image
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Details -->
                    <div style="flex:1;">
                        <h3 style="margin-bottom:5px; color:#6b5b82;">
                            <?php echo htmlspecialchars($lost['item_name']); ?>

                            <span style="font-size:12px; background:linear-gradient(120deg,#b39ddb,#f4b6c5);
                                         color:white; padding:3px 10px; border-radius:12px; margin-left:8px;">
                                <?php echo htmlspecialchars($lost['category_name'] ?? '—'); ?>
                            </span>

                            <?php if ($is_my_lost_item): ?>
                                <span style="font-size:11px; background:#7bc99a; color:white;
                                             padding:3px 10px; border-radius:12px; margin-left:5px;">
                                    YOUR REPORT
                                </span>
                            <?php endif; ?>
                        </h3>

                        <p style="font-size:13px; color:#7a6b85; margin-top:5px;">
                            <strong>Lost by:</strong> <?php echo htmlspecialchars($lost['owner_name'] ?? '—'); ?><br>
                            <strong>Color:</strong> <?php echo htmlspecialchars($lost['color'] ?: '—'); ?> |
                            <strong>Brand:</strong> <?php echo htmlspecialchars($lost['brand'] ?: '—'); ?> |
                            <strong>Location:</strong> <?php echo htmlspecialchars($lost['location'] ?: '—'); ?> |
                            <strong>Date lost:</strong> <?php echo htmlspecialchars($lost['date_lost']); ?>
                        </p>
                    </div>
                </div>

                <!-- ============================================================
                     MATCHES TABLE
                     ============================================================ -->
                <?php if (empty($matches)): ?>

                    <p style="color:#9b8ba8; font-size:13px; margin-top:15px;">
                        No potential matches found yet.
                    </p>

                <?php else: ?>

                    <h4 style="margin-top:18px; font-size:14px; color:#6b5b82;">
                        Potential Matches:
                    </h4>

                    <table style="width:100%; border-collapse:collapse; margin-top:8px; font-size:13px;">
                        <thead>
                            <tr style="background:#f5ebf5; color:#6b5b82;">
                                <th style="padding:8px; text-align:left;">Image</th>
                                <th style="padding:8px; text-align:left;">Found Item</th>
                                <th style="padding:8px; text-align:left;">Color</th>
                                <th style="padding:8px; text-align:left;">Brand</th>
                                <th style="padding:8px; text-align:left;">Location</th>
                                <th style="padding:8px; text-align:left;">Date</th>
                                <th style="padding:8px; text-align:center;">Score</th>
                                <th style="padding:8px; text-align:center;">Confidence</th>
                                <th style="padding:8px; text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $m): ?>

                                <?php
                                // The claim button appears only when:
                                //   - the lost item belongs to the current user
                                //   - the score is at least 40 (MEDIUM)
                                $can_claim = ($is_my_lost_item && ($m['score'] ?? 0) >= 40);
                                ?>

                                <tr style="border-bottom:1px solid #f0e8f4;">

                                    <!-- Found item image -->
                                    <td style="padding:8px;">
                                        <?php if (!empty($m['found']['image_path']) && file_exists($m['found']['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($m['found']['image_path']); ?>"
                                                 style="width:50px; height:50px; object-fit:cover;
                                                        border-radius:8px; border:1px solid #e6d5e8;">
                                        <?php else: ?>
                                            <div style="width:50px; height:50px; background:#fdf6f9;
                                                        border-radius:8px; display:flex; align-items:center;
                                                        justify-content:center; font-size:10px; color:#c8b6e2;">
                                                No image
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Found name -->
                                    <td style="padding:8px;">
                                        <?php echo htmlspecialchars($m['found']['item_name']); ?>
                                    </td>

                                    <!-- Color -->
                                    <td style="padding:8px;">
                                        <?php echo htmlspecialchars($m['found']['color'] ?: '—'); ?>
                                    </td>

                                    <!-- Brand -->
                                    <td style="padding:8px;">
                                        <?php echo htmlspecialchars($m['found']['brand'] ?: '—'); ?>
                                    </td>

                                    <!-- Location -->
                                    <td style="padding:8px;">
                                        <?php echo htmlspecialchars($m['found']['location'] ?: '—'); ?>
                                    </td>

                                    <!-- Date -->
                                    <td style="padding:8px;">
                                        <?php echo htmlspecialchars($m['found']['date_found']); ?>
                                    </td>

                                    <!-- Score -->
                                    <td style="padding:8px; text-align:center;">
                                        <strong style="color:#6b5b82;">
                                            <?php echo (int)($m['score'] ?? 0); ?>/100
                                        </strong>
                                    </td>

                                    <!-- Confidence badge -->
                                    <td style="padding:8px; text-align:center;">
                                        <?php if (($m['score'] ?? 0) >= 70): ?>
                                            <span style="background:#7bc99a; color:white;
                                                         padding:3px 10px; border-radius:12px;
                                                         font-size:11px;">HIGH</span>
                                        <?php elseif (($m['score'] ?? 0) >= 40): ?>
                                            <span style="background:#f4d58d; color:#4a3b52;
                                                         padding:3px 10px; border-radius:12px;
                                                         font-size:11px;">MEDIUM</span>
                                        <?php else: ?>
                                            <span style="background:#e6d5e8; color:#6b5b82;
                                                         padding:3px 10px; border-radius:12px;
                                                         font-size:11px;">LOW</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Claim button -->
                                    <td style="padding:8px; text-align:center;">
                                        <?php if ($can_claim): ?>
                                            <a href="claim.php?lost_id=<?php echo (int)$lost['lost_id']; ?>&found_id=<?php echo (int)$m['found']['found_id']; ?>"
                                               style="background:linear-gradient(120deg,#b39ddb,#f4b6c5);
                                                      color:white; padding:6px 14px; border-radius:14px;
                                                      text-decoration:none; font-size:12px;">
                                                Claim
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#d0c4dc;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>
            </div>

        <?php endwhile; ?>

    <?php endif; ?>

    <p style="margin-top:30px;">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>