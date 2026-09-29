<?php
/**
 * browse.php
 * ------------------------------------------------------------
 * PURPOSE: Browse all open lost items and see matches with
 *          confidence scores and claim buttons.
 *
 * ACCESS: Any logged-in user.
 *
 * FLOW:
 *   1. Require login.
 *   2. Fetch all open lost items with category and owner info.
 *   3. For each lost item, run the matching algorithm.
 *   4. Render a card per lost item with a matches table.
 *   5. Show a "Claim" button only if the item belongs to the
 *      current user AND the score is high enough.
 * ------------------------------------------------------------
 */

// Show all errors while developing.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buffer output so headers can be sent later if needed.
ob_start();

// Connect to the database and load the matching functions.
require_once 'dbconnect.php';
require_once 'match.php';

// Start the session.
@session_start();

// ------------------------------------------------------------
// AUTHORIZATION CHECK
// Anyone who is logged in can browse.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// Store the current user's ID for later comparison.
$current_user_id = (int)$_SESSION['user_id'];

// ------------------------------------------------------------
// FETCH ALL OPEN LOST ITEMS
// Joins categories and users so we have names available.
// ------------------------------------------------------------
$lost_result = $conn->query(
    "SELECT l.*, c.category_name, u.full_name AS owner_name
     FROM lost_items l
     LEFT JOIN categories c ON l.category_id = c.category_id
     LEFT JOIN users u ON l.user_id = u.user_id
     WHERE l.status = 'open'
     ORDER BY l.created_at DESC"
);

// Include the shared header/navigation.
include 'includes/header.php';
?>

<!-- ============================================================
     MAIN PAGE CONTAINER
     ============================================================ -->
<div class="container" style="max-width:1100px;">
    <h2 style="margin-bottom:10px;">Browse &amp; Match</h2>

    <!-- Explanation of how the matching works -->
    <p class="helper" style="margin-bottom:20px;">
        Below are all open <strong>lost item</strong> reports. The system has automatically
        calculated a match score against every open <strong>found item</strong>.
        Items with <strong>70+ points</strong> are high-confidence matches.
        Click <strong>Claim</strong> on your own lost item to submit a claim.
    </p>

    <?php if ($lost_result->num_rows === 0): ?>
        <!-- Message shown when there are no open lost items -->
        <div class="alert alert-success">No open lost items to display.</div>
    <?php else: ?>

        <!-- Loop through each open lost item -->
        <?php while ($lost = $lost_result->fetch_assoc()): ?>

            <?php
            // Run the matching algorithm for this lost item.
            $matches = find_matches_for_lost($conn, $lost['lost_id']);

            // Check if this lost item belongs to the current user.
            $is_my_lost_item = ((int)$lost['user_id'] === $current_user_id);
            ?>

            <!-- ==================================================
                 LOST ITEM CARD
                 ================================================== -->
            <div style="border:1px solid #ddd; border-radius:8px; padding:15px; margin-bottom:25px; background:#fafafa;">

                <!-- ==================================================
                     LOST ITEM HEADER with IMAGE
                     ================================================== -->
                <div style="display:flex; gap:15px; align-items:flex-start;">

                    <!-- LOST ITEM IMAGE -->
                    <div>
                        <?php if (!empty($lost['image_path']) && file_exists($lost['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($lost['image_path']); ?>"
                                 style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:1px solid #ccc;">
                        <?php else: ?>
                            <!-- Placeholder when no image is uploaded -->
                            <div style="width:100px; height:100px; background:#eee; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:11px; color:#888;">
                                No image
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- LOST ITEM DETAILS -->
                    <div style="flex:1;">
                        <h3 style="margin-bottom:5px;">
                            <?php echo htmlspecialchars($lost['item_name']); ?>

                            <!-- Category badge -->
                            <span style="font-size:12px; background:#041E60; color:white; padding:2px 8px; border-radius:10px; margin-left:8px;">
                                <?php echo htmlspecialchars($lost['category_name'] ?? '—'); ?>
                            </span>

                            <!-- "YOUR REPORT" badge shown only to the owner -->
                            <?php if ($is_my_lost_item): ?>
                                <span style="font-size:11px; background:#28a745; color:white; padding:2px 8px; border-radius:10px; margin-left:5px;">
                                    YOUR REPORT
                                </span>
                            <?php endif; ?>
                        </h3>

                        <!-- Item attributes -->
                        <p style="font-size:13px; color:#555; margin-top:5px;">
                            <strong>Lost by:</strong> <?php echo htmlspecialchars($lost['owner_name'] ?? '—'); ?><br>
                            <strong>Color:</strong> <?php echo htmlspecialchars($lost['color'] ?: '—'); ?> |
                            <strong>Brand:</strong> <?php echo htmlspecialchars($lost['brand'] ?: '—'); ?> |
                            <strong>Location:</strong> <?php echo htmlspecialchars($lost['location'] ?: '—'); ?> |
                            <strong>Date lost:</strong> <?php echo htmlspecialchars($lost['date_lost']); ?>
                        </p>

                        <!-- Optional description -->
                        <?php if (!empty($lost['description'])): ?>
                            <p style="font-size:13px; color:#666; margin-top:5px;">
                                <?php echo htmlspecialchars($lost['description']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ==================================================
                     MATCHES TABLE
                     ================================================== -->
                <?php if (empty($matches)): ?>
                    <!-- No matches found for this lost item -->
                    <p style="color:#888; font-size:13px; margin-top:15px;">No potential matches found yet.</p>
                <?php else: ?>

                    <h4 style="margin-top:15px; font-size:14px;">Potential Matches:</h4>

                    <table style="width:100%; border-collapse:collapse; margin-top:8px; font-size:13px;">
                        <thead>
                            <tr style="background:#eee;">
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
                                // Safe check: only allow claim if:
                                // - the lost item belongs to us
                                // - score is at least 40
                                // - all needed keys exist
                                $can_claim = (
                                    $is_my_lost_item &&
                                    isset($m['score']) && $m['score'] >= 40 &&
                                    !empty($m['found']['found_id']) &&
                                    !empty($lost['lost_id'])
                                );
                                ?>

                                <tr style="border-bottom:1px solid #eee;">

                                    <!-- FOUND ITEM IMAGE -->
                                    <td style="padding:8px;">
                                        <?php if (!empty($m['found']['image_path']) && file_exists($m['found']['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($m['found']['image_path']); ?>"
                                                 style="width:50px; height:50px; object-fit:cover; border-radius:6px; border:1px solid #ccc;">
                                        <?php else: ?>
                                            <div style="width:50px; height:50px; background:#eee; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:10px; color:#888;">
                                                No image
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- FOUND ITEM NAME -->
                                    <td style="padding:8px;"><?php echo htmlspecialchars($m['found']['item_name']); ?></td>

                                    <!-- COLOR -->
                                    <td style="padding:8px;"><?php echo htmlspecialchars($m['found']['color'] ?: '—'); ?></td>

                                    <!-- BRAND -->
                                    <td style="padding:8px;"><?php echo htmlspecialchars($m['found']['brand'] ?: '—'); ?></td>

                                    <!-- LOCATION -->
                                    <td style="padding:8px;"><?php echo htmlspecialchars($m['found']['location'] ?: '—'); ?></td>

                                    <!-- DATE FOUND -->
                                    <td style="padding:8px;"><?php echo htmlspecialchars($m['found']['date_found']); ?></td>

                                    <!-- SCORE -->
                                    <td style="padding:8px; text-align:center;">
                                        <strong><?php echo (int)$m['score']; ?>/100</strong>
                                    </td>

                                    <!-- CONFIDENCE BADGE -->
                                    <td style="padding:8px; text-align:center;">
                                        <?php if ($m['score'] >= 70): ?>
                                            <span style="background:#28a745; color:white; padding:3px 10px; border-radius:10px; font-size:11px;">HIGH</span>
                                        <?php elseif ($m['score'] >= 40): ?>
                                            <span style="background:#ffc107; color:black; padding:3px 10px; border-radius:10px; font-size:11px;">MEDIUM</span>
                                        <?php else: ?>
                                            <span style="background:#ccc; color:black; padding:3px 10px; border-radius:10px; font-size:11px;">LOW</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- CLAIM BUTTON -->
                                    <td style="padding:8px; text-align:center;">
                                        <?php if ($can_claim): ?>
                                            <!-- Link passes both IDs to claim.php via the query string -->
                                            <a href="claim.php?lost_id=<?php echo (int)$lost['lost_id']; ?>&found_id=<?php echo (int)$m['found']['found_id']; ?>"
                                               style="background:#041E60; color:white; padding:5px 12px; border-radius:5px; text-decoration:none; font-size:12px;">
                                                Claim
                                            </a>
                                        <?php else: ?>
                                            <!-- Dash shown when claiming is not allowed -->
                                            <span style="color:#bbb;">—</span>
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

    <!-- Back link to user dashboard -->
    <p style="margin-top:30px;">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?> 