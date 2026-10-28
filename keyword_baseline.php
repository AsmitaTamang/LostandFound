<?php
/**
 * keyword_baseline.php
 * ------------------------------------------------------------
 * PURPOSE: Implements a keyword-based baseline matching system,
 *          used for comparison against the multi-attribute
 *          algorithm.
 *
 * WHY THIS FILE EXISTS:
 *   To measure how much better the multi-attribute approach is,
 *   we need a "control group" — a naive system that uses only
 *   simple text matching. This file simulates that naive system.
 *
 * HOW IT WORKS:
 *   - Given a lost item, extract keywords from its item_name
 *     (ignore short words like "and", "the").
 *   - Build a SQL LIKE query with an OR condition per keyword.
 *   - Return any found items whose item_name contains any of
 *     those keywords.
 *
 * LIMITATION (this is the point of the baseline):
 *   Keyword matching fails when the same item is described
 *   differently — e.g., "MacBook Pro" vs "Silver laptop Apple"
 *   share no keywords even though they refer to the same item.
 *
 * USED BY: evaluate.php
 * ------------------------------------------------------------
 */

/**
 * Find keyword-based matches for a given lost item.
 *
 * @param mysqli $conn   Active DB connection
 * @param array  $lost   Lost item row (must contain 'item_name')
 * @return array         Array of found_id values (unordered)
 */
function find_keyword_matches($conn, $lost) {

    // ------------------------------------------------------------
    // STEP 1: Split the lost item's name into keywords.
    // Lowercase everything, split on whitespace, and drop any
    // "word" shorter than 3 characters (those are usually noise).
    // ------------------------------------------------------------
    $words = preg_split('/\s+/', strtolower(trim($lost['item_name'])));
    $keywords = array_filter($words, function ($w) {
        return strlen($w) > 2;
    });

    // If there are no usable keywords, return no matches.
    if (empty($keywords)) {
        return [];
    }

    // ------------------------------------------------------------
    // STEP 2: Build the SQL query dynamically.
    // One LIKE condition per keyword, combined with OR.
    // We use placeholders (?) so we can safely bind the values.
    // ------------------------------------------------------------
    $conditions = [];
    $params     = [];
    $types      = '';

    foreach ($keywords as $kw) {
        $conditions[] = "LOWER(item_name) LIKE ?";
        $params[]     = '%' . $kw . '%';   // wildcard match on either side
        $types       .= 's';               // 's' = string parameter
    }

    $sql = "SELECT found_id
            FROM found_items
            WHERE status = 'open'
              AND (" . implode(" OR ", $conditions) . ")";

    // ------------------------------------------------------------
    // STEP 3: Prepare + execute with the keyword parameters.
    // ------------------------------------------------------------
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // ------------------------------------------------------------
    // STEP 4: Collect found IDs into a flat array.
    // ------------------------------------------------------------
    $matches = [];
    while ($row = $result->fetch_assoc()) {
        $matches[] = (int)$row['found_id'];
    }
    $stmt->close();

    return $matches;
}
?>