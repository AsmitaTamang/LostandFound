<?php
/**
 * notifications.php
 * ------------------------------------------------------------
 * PURPOSE: Shows all notifications for the logged-in user.
 * ------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
require_once 'dbconnect.php';

@session_start();

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Mark all as read (when user opens the page)
if (isset($_GET['mark_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    ob_end_clean();
    header("Location: notifications.php");
    exit;
}

// Delete a notification
if (isset($_GET['delete'])) {
    $nid = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $nid, $user_id);
    $stmt->execute();
    $stmt->close();
    ob_end_clean();
    header("Location: notifications.php");
    exit;
}

// Fetch notifications
$stmt = $conn->prepare(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

include 'includes/header.php';
?>

<div class="container" style="max-width:800px;">
    <h2 style="margin-bottom:10px;">Notifications</h2>

    <div style="margin-bottom:20px;">
        <a href="notifications.php?mark_read=1" class="btn" style="background:#1e7e34;">Mark All as Read</a>
    </div>

    <?php if ($notifications->num_rows === 0): ?>
        <p class="helper">You have no notifications.</p>
    <?php else: ?>
        <?php while ($n = $notifications->fetch_assoc()): ?>
            <div style="border-left:4px solid <?php echo $n['is_read'] ? '#ccc' : '#041E60'; ?>;
                        background:<?php echo $n['is_read'] ? '#f9f9f9' : '#e8efff'; ?>;
                        padding:12px; border-radius:5px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:14px;"><?php echo htmlspecialchars($n['message']); ?></div>
                    <div style="font-size:12px; color:#888; margin-top:4px;">
                        <?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?>
                    </div>
                </div>
                <div style="display:flex; gap:8px;">
                    <?php if (!empty($n['link'])): ?>
                        <a href="<?php echo htmlspecialchars($n['link']); ?>"
                           style="background:#041E60; color:white; padding:5px 12px; border-radius:5px; text-decoration:none; font-size:12px;">
                            View
                        </a>
                    <?php endif; ?>
                    <a href="notifications.php?delete=<?php echo $n['notification_id']; ?>"
                       onclick="return confirm('Delete this notification?');"
                       style="background:#dc3545; color:white; padding:5px 12px; border-radius:5px; text-decoration:none; font-size:12px;">
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