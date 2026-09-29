<?php
/**
 * claim.php
 * ------------------------------------------------------------
 * PURPOSE: Allows a user to submit a claim on a found item.
 *          Admin will review and approve/reject.
 *
 * URL: claim.php?lost_id=X&found_id=Y
 *
 * ACCESS: Logged-in user. The lost item MUST belong to them.
 *
 * FLOW:
 *   1. Verify login and validate the URL parameters.
 *   2. Check the lost item belongs to the current user.
 *   3. Load the found item details.
 *   4. On POST: prevent duplicate claims, then insert.
 *   5. Show either a success message or the confirmation form.
 * ------------------------------------------------------------
 */

// Buffer output so headers can be sent later.
ob_start();

// Show all errors while developing.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database.
require_once 'dbconnect.php';

// Start the session.
@session_start();

// ------------------------------------------------------------
// AUTHORIZATION CHECK
// Must be logged in.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// Read the IDs from the query string.
$user_id  = (int)$_SESSION['user_id'];
$lost_id  = (int)($_GET['lost_id'] ?? 0);
$found_id = (int)($_GET['found_id'] ?? 0);

// ------------------------------------------------------------
// VALIDATE URL PARAMETERS
// Invalid IDs → back to browse.
// ------------------------------------------------------------
if ($lost_id <= 0 || $found_id <= 0) {
    ob_end_clean();
    header("Location: browse.php");
    exit;
}

// Holds validation errors and success message.
$errors  = [];
$success = '';

// ------------------------------------------------------------
// VERIFY THE LOST ITEM BELONGS TO THIS USER
// Prevents users from claiming items they didn't report lost.
// ------------------------------------------------------------
$stmt = $conn->prepare("SELECT * FROM lost_items WHERE lost_id = ? AND user_id = ?");
$stmt->bind_param("ii", $lost_id, $user_id);
$stmt->execute();
$lost = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lost) {
    $errors[] = "You can only claim items for your own lost reports.";
}

// ------------------------------------------------------------
// GET THE FOUND ITEM
// Joins category and finder details for display.
// ------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT f.*, c.category_name, u.full_name AS finder_name, u.email AS finder_email
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
// Only runs on POST and when there are no earlier errors.
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {

    // Check if the user already submitted a claim for this pair.
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

    // If still no errors, insert the claim as 'pending'.
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

// Include the shared header/navigation.
include 'includes/header.php';
?>

<!-- ============================================================
     MAIN PAGE CONTAINER
     ============================================================ -->
<div class="container" style="max-width:700px;">
    <h2 style="margin-bottom:20px;">Claim Found Item</h2>

    <?php if ($success): ?>
        <!-- Success state: show message and dashboard link -->
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <p style="margin-top:20px;"><a href="dashboard.php" class="btn">← Back to Dashboard</a></p>
    <?php else: ?>

        <?php if (!empty($errors)): ?>
            <!-- Show any validation errors -->
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <div>• <?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
            </div>
            <p style="margin-top:20px;"><a href="browse.php" class="btn">← Back to Browse</a></p>
        <?php endif; ?>

        <?php if (empty($errors) && $lost && $found): ?>

            <!-- Summary of the user's own lost item -->
            <div style="background:#e8efff; padding:15px; border-radius:8px; margin-bottom:15px;">
                <h3 style="margin-bottom:8px;">Your Lost Item</h3>
                <p style="font-size:14px;">
                    <strong><?php echo htmlspecialchars($lost['item_name']); ?></strong><br>
                    Color: <?php echo htmlspecialchars($lost['color'] ?: '—'); ?> |
                    Brand: <?php echo htmlspecialchars($lost['brand'] ?: '—'); ?> |
                    Location: <?php echo htmlspecialchars($lost['location'] ?: '—'); ?>
                </p>
            </div>

            <!-- Summary of the found item being claimed -->
            <div style="background:#e8fff0; padding:15px; border-radius:8px; margin-bottom:20px;">
                <h3 style="margin-bottom:8px;">Found Item You Want to Claim</h3>
                <p style="font-size:14px;">
                    <strong><?php echo htmlspecialchars($found['item_name']); ?></strong><br>
                    Category: <?php echo htmlspecialchars($found['category_name'] ?? '—'); ?><br>
                    Color: <?php echo htmlspecialchars($found['color'] ?: '—'); ?> |
                    Brand: <?php echo htmlspecialchars($found['brand'] ?: '—'); ?> |
                    Location: <?php echo htmlspecialchars($found['location'] ?: '—'); ?><br>
                    Date Found: <?php echo htmlspecialchars($found['date_found']); ?><br>
                    Found By: <?php echo htmlspecialchars($found['finder_name'] ?? 'Unknown'); ?>
                </p>
            </div>

            <!-- Explanation of what submitting does -->
            <p class="helper" style="margin-bottom:20px;">
                Clicking <strong>Submit Claim</strong> sends a request to the admin.
                The admin will review and may contact you to verify ownership.
            </p>

            <!-- Confirmation form -->
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