<?php
/**
 * debug_claim.php
 * ------------------------------------------------------------
 * TEMPORARY DEBUG FILE — delete after testing.
 * Shows whether the claim button should appear.
 * ------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';
@session_start();

echo "<h2>Session</h2>";
echo "user_id from session: <strong>" . ($_SESSION['user_id'] ?? 'NOT SET') . "</strong><br>";
echo "role: <strong>" . ($_SESSION['role'] ?? 'NOT SET') . "</strong><br>";

$current = (int)($_SESSION['user_id'] ?? 0);

echo "<h2>Your Lost Items</h2>";
$res = $conn->query(
    "SELECT lost_id, user_id, item_name FROM lost_items ORDER BY created_at DESC LIMIT 30"
);
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>lost_id</th><th>user_id</th><th>item_name</th><th>is_mine?</th><th>match_count</th><th>top_score</th></tr>";

require_once 'match.php';

while ($row = $res->fetch_assoc()) {
    $is_mine = ((int)$row['user_id'] === $current);
    $matches = find_matches_for_lost($conn, $row['lost_id']);
    $top = !empty($matches) ? $matches[0]['score'] : 0;

    echo "<tr>";
    echo "<td>" . (int)$row['lost_id'] . "</td>";
    echo "<td>" . (int)$row['user_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
    echo "<td>" . ($is_mine ? '✅ YES' : '❌ no') . "</td>";
    echo "<td>" . count($matches) . "</td>";
    echo "<td>" . $top . "</td>";
    echo "</tr>";
}
echo "</table>";
?>