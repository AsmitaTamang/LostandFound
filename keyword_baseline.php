<?php
/**
 * keyword_baseline.php
 * ------------------------------------------------------------
 * PURPOSE: Implements a keyword-based baseline matching system
 *          for comparison against the multi-attribute approach.
 *
 * This simulates a typical lost-and-found system that relies
 * on simple keyword search (SQL LIKE).
 *
 * APPROACH:
 *   - For a given lost item, extract keywords from its name
 *   - Search found_items where item_name LIKE '%keyword%'
 *   - Return any matches found
 * ------------------------------------------------------------
 */

/**
 * Find keyword-based matches for a given lost item.
 *
 * @param mysqli $conn
 * @param array  $lost  The lost item row (must contain item_name)
 * @return array List of found item IDs that matched
 */
function find_keyword_matches($conn, $lost) {

    // Split the lost item name into keywords (words longer than 2 chars)
    $words = preg_split('/\s+/', strtolower(trim($lost['item_name'])));
    $keywords = array_filter($words, function($w) {
        return strlen($w) > 2;   // Skip short words like "and", "the"
    });

    if (empty($keywords)) {
        return [];
    }

    // Build the SQL with OR conditions for each keyword
    $conditions = [];
    $params = [];
    $types = '';

    foreach ($keywords as $kw) {
        $conditions[] = "LOWER(item_name) LIKE ?";
        $params[] = '%' . $kw . '%';
        $types .= 's';
    }

    $sql = "SELECT found_id
            FROM found_items
            WHERE status = 'open'
              AND (" . implode(" OR ", $conditions) . ")";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $matches = [];
    while ($row = $result->fetch_assoc()) {
        $matches[] = (int)$row['found_id'];
    }
    $stmt->close();

    return $matches;
}
?>