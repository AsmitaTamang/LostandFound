<?php
/**
 * admin_categories.php
 * ------------------------------------------------------------
 * PURPOSE: Admin reviews user-suggested categories and
 *          approves or rejects them.
 * ------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
require_once 'dbconnect.php';

@session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

$message = '';

// ------------------------------------------------------------
// Handle approve / reject
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pending_id = (int)($_POST['pending_id'] ?? 0);
    $action     = $_POST['action'] ?? '';
    $name       = trim($_POST['category_name'] ?? '');

    if ($pending_id > 0 && in_array($action, ['approve', 'reject'])) {

        if ($action === 'approve') {
            // 1. Insert into active categories
            $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->close();

            // 2. Mark suggestion approved
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
// Fetch pending suggestions
// ------------------------------------------------------------
$pending = $conn->query(
    "SELECT p.*, u.full_name AS suggester
     FROM pending_categories p
     LEFT JOIN users u ON p.user_id = u.user_id
     WHERE p.status = 'pending'
     ORDER BY p.suggested_at ASC"
);

// Fetch all active categories
$active = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");

include 'includes/header.php';
?>

<div class="container" style="max-width:1000px;">
    <h2 style="margin-bottom:20px;">Manage Categories</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- ============================================================
         PENDING SUGGESTIONS
         ============================================================ -->
    <h3 style="margin-top:20px;">Pending Suggestions (<?php echo $pending->num_rows; ?>)</h3>

    <?php if ($pending->num_rows === 0): ?>
        <p class="helper">No pending suggestions right now.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
            <thead>
                <tr style="background:#041E60; color:white;">
                    <th style="padding:10px; text-align:left;">Suggested Name</th>
                    <th style="padding:10px; text-align:left;">Reason</th>
                    <th style="padding:10px; text-align:left;">Suggested By</th>
                    <th style="padding:10px; text-align:left;">Date</th>
                    <th style="padding:10px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $pending->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="padding:8px;"><strong><?php echo htmlspecialchars($row['category_name']); ?></strong></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['reason'] ?: '—'); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['suggester'] ?? 'Unknown'); ?></td>
                    <td style="padding:8px;"><?php echo date('M j, Y', strtotime($row['suggested_at'])); ?></td>
                    <td style="padding:8px; text-align:center;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="pending_id" value="<?php echo $row['pending_id']; ?>">
                            <input type="hidden" name="category_name" value="<?php echo htmlspecialchars($row['category_name']); ?>">
                            <button type="submit" name="action" value="approve"
                                    style="background:#28a745; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer;">
                                ✓ Approve
                            </button>
                            <button type="submit" name="action" value="reject"
                                    style="background:#dc3545; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; margin-left:5px;">
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
         ACTIVE CATEGORIES
         ============================================================ -->
    <h3 style="margin-top:40px;">Active Categories (<?php echo $active->num_rows; ?>)</h3>
    <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
        <thead>
            <tr style="background:#1e7e34; color:white;">
                <th style="padding:10px; text-align:left;">ID</th>
                <th style="padding:10px; text-align:left;">Name</th>
                <th style="padding:10px; text-align:left;">Added</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $active->fetch_assoc()): ?>
            <tr style="border-bottom:1px solid #ddd;">
                <td style="padding:8px;"><?php echo $row['category_id']; ?></td>
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