<?php
/**
 * admin_categories.php
 * ------------------------------------------------------------
 * PURPOSE: Admin reviews user-suggested categories and either
 *          approves or rejects them.
 *
 * FLOW:
 *   1. Require admin role.
 *   2. On POST (approve/reject):
 *        - approve → INSERT into categories + mark suggestion approved
 *        - reject  → mark suggestion rejected
 *   3. Show two tables:
 *        - Pending suggestions with Approve/Reject buttons
 *        - Active categories (read-only list)
 *
 * PART OF: Dynamic Category Management feature.
 *
 * SECURITY:
 *   - Admin-only access.
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
// Admin only.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

// Variable to hold a status message.
$message = '';

// ------------------------------------------------------------
// HANDLE APPROVE / REJECT
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read the target suggestion and the requested action.
    $pending_id = (int)($_POST['pending_id'] ?? 0);
    $action     = $_POST['action'] ?? '';
    $name       = trim($_POST['category_name'] ?? '');

    // Validate action is either approve or reject.
    if ($pending_id > 0 && in_array($action, ['approve', 'reject'])) {

        if ($action === 'approve') {

            // 1. Add the category to the active list.
            $stmt = $conn->prepare(
                "INSERT INTO categories (category_name) VALUES (?)"
            );
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->close();

            // 2. Mark the suggestion as approved.
            $stmt = $conn->prepare(
                "UPDATE pending_categories
                 SET status='approved', reviewed_at=NOW()
                 WHERE pending_id=?"
            );
            $stmt->bind_param("i", $pending_id);
            $stmt->execute();
            $stmt->close();

            $message = "Category \"$name\" approved and added.";

        } elseif ($action === 'reject') {

            // Only update the suggestion status — do NOT add to active.
            $stmt = $conn->prepare(
                "UPDATE pending_categories
                 SET status='rejected', reviewed_at=NOW()
                 WHERE pending_id=?"
            );
            $stmt->bind_param("i", $pending_id);
            $stmt->execute();
            $stmt->close();

            $message = "Category \"$name\" rejected.";
        }
    }
}

// ------------------------------------------------------------
// FETCH PENDING SUGGESTIONS
// Includes the suggester's name via LEFT JOIN.
// ------------------------------------------------------------
$pending = $conn->query(
    "SELECT p.*, u.full_name AS suggester
     FROM pending_categories p
     LEFT JOIN users u ON p.user_id = u.user_id
     WHERE p.status = 'pending'
     ORDER BY p.suggested_at ASC"
);

// ------------------------------------------------------------
// FETCH ACTIVE CATEGORIES
// Read-only list shown at the bottom of the page.
// ------------------------------------------------------------
$active = $conn->query(
    "SELECT * FROM categories
     WHERE is_active = 1
     ORDER BY category_name"
);

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     MANAGE CATEGORIES PAGE
     ============================================================ -->
<div class="container" style="max-width:1000px;">

    <h2>Manage Categories</h2>

    <!-- Status message after an approve/reject -->
    <?php if ($message): ?>
        <div class="alert alert-success">🌸 <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- ============================================================
         PENDING SUGGESTIONS TABLE
         Each row has an Approve and Reject button.
         ============================================================ -->
    <h3 style="margin-top:20px; color:#6b5b82;">
        Pending Suggestions (<?php echo $pending->num_rows; ?>)
    </h3>

    <?php if ($pending->num_rows === 0): ?>

        <p class="helper">No pending suggestions right now.</p>

    <?php else: ?>

        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
            <thead>
                <tr style="background:linear-gradient(120deg,#b39ddb,#f4b6c5); color:white;">
                    <th style="padding:10px; text-align:left;">Suggested Name</th>
                    <th style="padding:10px; text-align:left;">Reason</th>
                    <th style="padding:10px; text-align:left;">Suggested By</th>
                    <th style="padding:10px; text-align:left;">Date</th>
                    <th style="padding:10px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $pending->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #f0e8f4;">

                    <!-- Suggested category name -->
                    <td style="padding:10px;">
                        <strong style="color:#6b5b82;">
                            <?php echo htmlspecialchars($row['category_name']); ?>
                        </strong>
                    </td>

                    <!-- Reason (optional) -->
                    <td style="padding:10px;">
                        <?php echo htmlspecialchars($row['reason'] ?: '—'); ?>
                    </td>

                    <!-- Who suggested it -->
                    <td style="padding:10px;">
                        <?php echo htmlspecialchars($row['suggester'] ?? 'Unknown'); ?>
                    </td>

                    <!-- When -->
                    <td style="padding:10px;">
                        <?php echo date('M j, Y', strtotime($row['suggested_at'])); ?>
                    </td>

                    <!-- Approve / Reject buttons -->
                    <td style="padding:10px; text-align:center;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="pending_id" value="<?php echo $row['pending_id']; ?>">
                            <input type="hidden" name="category_name" value="<?php echo htmlspecialchars($row['category_name']); ?>">

                            <!-- Approve -->
                            <button type="submit" name="action" value="approve"
                                    style="background:linear-gradient(120deg,#a5d8b8,#7bc99a);
                                           color:white; border:none; padding:6px 14px;
                                           border-radius:14px; cursor:pointer; font-size:12px;">
                                ✓ Approve
                            </button>

                            <!-- Reject -->
                            <button type="submit" name="action" value="reject"
                                    style="background:linear-gradient(120deg,#d67b8b,#c85a6e);
                                           color:white; border:none; padding:6px 14px;
                                           border-radius:14px; cursor:pointer; font-size:12px; margin-left:5px;">
                                ✗ Reject
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <!-- ============================================================
         ACTIVE CATEGORIES TABLE
         Read-only. These are available for users to select when
         reporting items.
         ============================================================ -->
    <h3 style="margin-top:40px; color:#6b5b82;">
        Active Categories (<?php echo $active->num_rows; ?>)
    </h3>

    <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
        <thead>
            <tr style="background:linear-gradient(120deg,#a5d8b8,#c8e6d0); color:#3a6b52;">
                <th style="padding:10px; text-align:left;">ID</th>
                <th style="padding:10px; text-align:left;">Name</th>
                <th style="padding:10px; text-align:left;">Added</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $active->fetch_assoc()): ?>
            <tr style="border-bottom:1px solid #f0e8f4;">
                <td style="padding:8px;"><?php echo (int)$row['category_id']; ?></td>
                <td style="padding:8px;"><?php echo htmlspecialchars($row['category_name']); ?></td>
                <td style="padding:8px;"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <p style="margin-top:30px;">
        <a href="admin_dashboard.php" class="btn">← Back to Admin Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>