<?php
/**
 * notifications.php
 * ------------------------------------------------------------
 * PURPOSE: Shows all notifications for the logged-in user.
 *
 * FEATURES:
 *   - List of notifications, newest first
 *   - Unread notifications highlighted
 *   - "Mark all as read" button
 *   - Delete individual notifications
 *   - "View" button to jump to the linked page
 *
 * FLOW:
 *   1. Require login.
 *   2. Handle ?mark_read=1 (mark all) or ?delete=N (single).
 *   3. Fetch the user's notifications and render them.
 *
 * SECURITY:
 *   - Every action restricted to the logged-in user's own rows.
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

// ------------------------------------------------------------
// ACTION 1: MARK ALL AS READ
// Triggered by the "Mark All as Read" button (?mark_read=1).
// ------------------------------------------------------------
if (isset($_GET['mark_read'])) {
    $stmt = $conn->prepare(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ?"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    ob_end_clean();
    header("Location: notifications.php");
    exit;
}

// ------------------------------------------------------------
// ACTION 2: DELETE ONE NOTIFICATION
// Triggered by ?delete=N. Ownership is enforced so a user
// cannot delete another user's notifications.
// ------------------------------------------------------------
if (isset($_GET['delete'])) {
    $nid = (int)$_GET['delete'];

    $stmt = $conn->prepare(
        "DELETE FROM notifications
         WHERE notification_id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $nid, $user_id);
    $stmt->execute();
    $stmt->close();

    ob_end_clean();
    header("Location: notifications.php");
    exit;
}

// ------------------------------------------------------------
// FETCH THE USER'S NOTIFICATIONS
// Newest first. Capped at 100 to keep the page fast.
// ------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT * FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 100"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     NOTIFICATIONS PAGE
     ============================================================ -->
<div class="container" style="max-width:800px;">

    <h2>Notifications</h2>

    <!-- Mark all as read — appears above the list -->
    <div style="margin-bottom:20px;">
        <a href="notifications.php?mark_read=1" class="btn"
           style="background:linear-gradient(120deg,#a5d8b8,#c8e6d0);">
            Mark All as Read
        </a>
    </div>

    <?php if ($notifications->num_rows === 0): ?>

        <!-- Empty state -->
        <p class="helper">You have no notifications.</p>

    <?php else: ?>

        <?php while ($n = $notifications->fetch_assoc()): ?>

            <?php
            // Unread notifications get a stronger left border
            // and a soft pink background to stand out.
            $is_unread = !$n['is_read'];
            ?>

            <div style="
                border-left: 4px solid <?php echo $is_unread ? '#b39ddb' : '#e6d5e8'; ?>;
                background: <?php echo $is_unread ? '#fdf0f5' : '#fdfafc'; ?>;
                padding: 14px 16px;
                border-radius: 12px;
                margin-bottom: 12px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 8px rgba(200,182,226,0.12);
            ">

                <!-- Message + timestamp -->
                <div>
                    <div style="font-size:14px; color:#4a3b52;">
                        <?php echo htmlspecialchars($n['message']); ?>
                    </div>
                    <div style="font-size:12px; color:#9b8ba8; margin-top:4px;">
                        <?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?>
                    </div>
                </div>

                <!-- Action buttons -->
                <div style="display:flex; gap:8px;">

                    <!-- View button (only if a link exists) -->
                    <?php if (!empty($n['link'])): ?>
                        <a href="<?php echo htmlspecialchars($n['link']); ?>"
                           style="background:linear-gradient(120deg,#b39ddb,#f4b6c5);
                                  color:white; padding:6px 14px; border-radius:14px;
                                  text-decoration:none; font-size:12px;">
                            View
                        </a>
                    <?php endif; ?>

                    <!-- Delete button -->
                    <a href="notifications.php?delete=<?php echo (int)$n['notification_id']; ?>"
                       onclick="return confirm('Delete this notification?');"
                       style="background:linear-gradient(120deg,#d67b8b,#c85a6e);
                              color:white; padding:6px 14px; border-radius:14px;
                              text-decoration:none; font-size:12px;">
                        Delete
                    </a>
                </div>
            </div>

        <?php endwhile; ?>

    <?php endif; ?>

    <p style="margin-top:25px;">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>