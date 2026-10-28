<?php
/**
 * claim.php
 * ------------------------------------------------------------
 * PURPOSE: Allows a user to submit a claim on a found item
 *          that matches one of their lost reports.
 *
 * URL PARAMETERS:
 *   ?lost_id=X&found_id=Y
 *     → X = the user's own lost item
 *     → Y = the found item they want to claim
 *
 * FLOW:
 *   1. Require login.
 *   2. Verify the lost item belongs to the current user.
 *   3. Verify the found item exists.
 *   4. On POST: check for duplicate, then insert into `claims`.
 *
 * SECURITY:
 *   - Ownership check on the lost item.
 *   - Duplicate-claim guard prevents spam.
 *   - Prepared statements prevent SQL injection.
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
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// Cache the current user's ID.
$user_id  = (int)$_SESSION['user_id'];

// Read the two IDs from the URL.
$lost_id  = (int)($_GET['lost_id']  ?? 0);
$found_id = (int)($_GET['found_id'] ?? 0);

// If the IDs are invalid, redirect to browse.
if ($lost_id <= 0 || $found_id <= 0) {
    ob_end_clean();
    header("Location: browse.php");
    exit;
}

// Arrays for messages.
$errors  = [];
$success = '';

// ------------------------------------------------------------
// VERIFY OWNERSHIP OF THE LOST ITEM
// The user can only claim items for their OWN lost reports.
// ------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT * FROM lost_items WHERE lost_id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $lost_id, $user_id);
$stmt->execute();
$lost = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lost) {
    $errors[] = "You can only claim items for your own lost reports.";
}

// ------------------------------------------------------------
// LOAD THE FOUND ITEM BEING CLAIMED
// Also fetch the finder's details for display.
// ------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT f.*, c.category_name,
            u.full_name AS finder_name, u.email AS finder_email
     FROM found_items f
     LEFT JOIN categories c ON f.category_id = c.category_id
     LEFT JOIN users u ON f.user_id = u.user_id
     WHERE f.found_id = ?"
);
$stmt->bind_param("i", $found_id);
$stmt->execute();
$found = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$found) {
    $errors[] = "Found item not found.";
}

// ------------------------------------------------------------
// HANDLE CLAIM SUBMISSION
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {

    // --------------------------------------------------------
    // GUARD: prevent duplicate claims for the same pair.
    // --------------------------------------------------------
    $stmt = $conn->prepare(
        "SELECT claim_id FROM claims
         WHERE lost_id = ? AND found_id = ? AND claimant_id = ?"
    );
    $stmt->bind_param("iii", $lost_id, $found_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $errors[] = "You have already submitted a claim for this item.";
    }
    $stmt->close();

    // --------------------------------------------------------
    // INSERT THE CLAIM (status = 'pending' until admin decides)
    // --------------------------------------------------------
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO claims (lost_id, found_id, claimant_id, status)
             VALUES (?, ?, ?, 'pending')"
        );
        $stmt->bind_param("iii", $lost_id, $found_id, $user_id);

        if ($stmt->execute()) {
            $success = "Claim submitted! An administrator will review it shortly.";
        } else {
            $errors[] = "Could not submit claim. Try again.";
        }
        $stmt->close();
    }
}

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     CLAIM PAGE
     ============================================================ -->
<div class="container" style="max-width:700px;">

    <h2>Claim Found Item</h2>

    <!-- Success state — show message and stop. -->
    <?php if ($success): ?>

        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <p style="margin-top:20px;">
            <a href="dashboard.php" class="btn">← Back to Dashboard</a>
        </p>

    <?php else: ?>

        <!-- Error messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <div>• <?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
            </div>
            <p style="margin-top:20px;">
                <a href="browse.php" class="btn">← Back to Browse</a>
            </p>
        <?php endif; ?>

        <!-- Confirmation view (only shown if no errors) -->
        <?php if (empty($errors) && $lost && $found): ?>

            <!-- Your lost item summary -->
            <div style="background:#f5ebf5; padding:16px; border-radius:14px;
                        margin-bottom:15px; border-left:4px solid #b39ddb;">
                <h3 style="color:#6b5b82; margin-bottom:8px;">Your Lost Item</h3>
                <p style="font-size:14px; color:#6b5b82;">
                    <strong><?php echo htmlspecialchars($lost['item_name']); ?></strong><br>
                    Color: <?php echo htmlspecialchars($lost['color'] ?: '—'); ?> |
                    Brand: <?php echo htmlspecialchars($lost['brand'] ?: '—'); ?> |
                    Location: <?php echo htmlspecialchars($lost['location'] ?: '—'); ?>
                </p>
            </div>

            <!-- The found item being claimed -->
            <div style="background:#e8f4ec; padding:16px; border-radius:14px;
                        margin-bottom:20px; border-left:4px solid #a5d8b8;">
                <h3 style="color:#3a6b52; margin-bottom:8px;">Found Item You Want to Claim</h3>
                <p style="font-size:14px; color:#3a6b52;">
                    <strong><?php echo htmlspecialchars($found['item_name']); ?></strong><br>
                    Category: <?php echo htmlspecialchars($found['category_name'] ?? '—'); ?><br>
                    Color: <?php echo htmlspecialchars($found['color'] ?: '—'); ?> |
                    Brand: <?php echo htmlspecialchars($found['brand'] ?: '—'); ?> |
                    Location: <?php echo htmlspecialchars($found['location'] ?: '—'); ?><br>
                    Date Found: <?php echo htmlspecialchars($found['date_found']); ?><br>
                    Found By: <?php echo htmlspecialchars($found['finder_name'] ?? 'Unknown'); ?>
                </p>
            </div>

            <!-- Explanation + submit -->
            <p class="helper" style="margin-bottom:20px;">
                Clicking <strong>Submit Claim</strong> sends a request to the admin.
                The admin will review and may contact you to verify ownership.
            </p>

            <form method="POST">
                <button type="submit" class="btn" style="width:100%;">Submit Claim</button>
                <p class="helper" style="text-align:center; margin-top:12px;">
                    <a href="browse.php">← Back to Browse</a>
                </p>
            </form>

        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>