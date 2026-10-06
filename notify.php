<?php
/**
 * notify.php
 * ------------------------------------------------------------
 * PURPOSE: Helper function to create a notification for a user.
 *          Used by report_lost.php and report_found.php to
 *          alert users when a match is found.
 * ------------------------------------------------------------
 */

/**
 * Create a notification for a user.
 *
 * @param mysqli $conn
 * @param int    $user_id
 * @param string $message
 * @param string $link  Optional link (e.g., "browse.php")
 */
function create_notification($conn, $user_id, $message, $link = null) {
    $stmt = $conn->prepare(
        "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)"
    );
    if (!$stmt) return;
    $stmt->bind_param("iss", $user_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}
?>