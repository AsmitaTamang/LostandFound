<?php
/**
 * suggest_category.php
 * ------------------------------------------------------------
 * PURPOSE: Allows users to suggest a new category when their
 *          item doesn't fit any existing one.
 *
 * FLOW:
 *   1. Require login.
 *   2. On POST: validate the suggestion, check for duplicates
 *      (both in active categories and pending suggestions),
 *      and insert into pending_categories with status='pending'.
 *   3. Admin will later approve or reject via admin_categories.php.
 *
 * PART OF: Dynamic Category Management feature.
 *
 * SECURITY:
 *   - Prepared statements prevent SQL injection.
 *   - Output escaped with htmlspecialchars.
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
$user_id = (int)$_SESSION['user_id'];

// Arrays for messages.
$errors  = [];
$success = '';

// ------------------------------------------------------------
// HANDLE FORM SUBMISSION
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read and clean inputs.
    $cat_name = trim($_POST['category_name'] ?? '');
    $reason   = trim($_POST['reason'] ?? '');

    // Validation.
    if (empty($cat_name)) {
        $errors[] = "Category name is required.";
    }

    // --------------------------------------------------------
    // CHECK: Category already exists in the active list?
    // --------------------------------------------------------
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "SELECT category_id FROM categories WHERE category_name = ?"
        );
        $stmt->bind_param("s", $cat_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "This category already exists.";
        }
        $stmt->close();
    }

    // --------------------------------------------------------
    // CHECK: Same suggestion already pending?
    // --------------------------------------------------------
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "SELECT pending_id FROM pending_categories
             WHERE category_name = ? AND status = 'pending'"
        );
        $stmt->bind_param("s", $cat_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "This category has already been suggested and is awaiting approval.";
        }
        $stmt->close();
    }

    // --------------------------------------------------------
    // INSERT THE SUGGESTION
    // --------------------------------------------------------
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO pending_categories (user_id, category_name, reason)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $user_id, $cat_name, $reason);

        if ($stmt->execute()) {
            $success = "Thank you! Your category suggestion is pending admin approval.";
            $_POST = [];   // clear form after success
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     SUGGEST CATEGORY PAGE
     ============================================================ -->
<div class="container" style="max-width:520px;">

    <h2>Suggest a New Category</h2>

    <p class="helper" style="margin-bottom:20px;">
        If your item doesn't fit any existing category, you can suggest
        a new one. An administrator will review your suggestion.
    </p>

    <!-- Success message -->
    <?php if ($success): ?>
        <div class="alert alert-success">🌸 <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Validation errors -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <div>• <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         SUGGESTION FORM
         ============================================================ -->
    <form method="POST">

        <div class="form-group">
            <label>Category Name *</label>
            <input type="text" name="category_name" required
                   placeholder="e.g., Smartwatch"
                   value="<?php echo htmlspecialchars($_POST['category_name'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Why is this category needed? (optional)</label>
            <textarea name="reason" rows="3"
                      placeholder="e.g., Many students have smartwatches"><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn" style="width:100%;">Submit Suggestion</button>

        <p class="helper" style="text-align:center; margin-top:12px;">
            <a href="dashboard.php">← Back to Dashboard</a>
        </p>
    </form>
</div>

<?php include 'includes/footer.php'; ?>