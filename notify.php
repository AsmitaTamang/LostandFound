<?php
/**
 * notify.php
 * ------------------------------------------------------------
 * PURPOSE: Helper function to create a notification for a user.
 *
 * USED BY:
 *   - report_lost.php   → notifies finders when a lost report
 *                         is posted in their found item's category
 *   - report_found.php  → notifies owners when a found report
 *                         is posted in their lost item's category
 *
 * DESIGN:
 *   Keeping notifications as a single small function avoids
 *   duplicating the INSERT query in multiple files. Later, if
 *   we add email or push notifications, this is the one place
 *   to extend.
 * ------------------------------------------------------------
 */

/**
 * Create a notification row for the given user.
 *
 * @param mysqli $conn      Active database connection
 * @param int    $user_id   Recipient user's ID
 * @param string $message   Human-readable notification text
 * @param string $link      Optional URL the "View" button should open
 * @return void
 */
function create_notification($conn, $user_id, $message, $link = null) {

    // Prepare the INSERT. Returns early if preparation fails.
    $stmt = $conn->prepare(
        "INSERT INTO notifications (user_id, message, link)
         VALUES (?, ?, ?)"
    );
    if (!$stmt) return;

    // Bind all three values. The link may be null; MySQLi
    // will store NULL if we pass null for a string parameter.
    $stmt->bind_param("iss", $user_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}
?>