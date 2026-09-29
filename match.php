<?php
/**
 * match.php
 * ------------------------------------------------------------
 * PURPOSE: The weighted multi-attribute matching algorithm.
 *
 * This is the CORE CONTRIBUTION of the project.
 *
 * HOW IT WORKS:
 *   For a given lost item, it loops through all open found items
 *   and computes a score (0-100) based on which attributes match:
 *
 *     Same category  → +40 points
 *     Same color     → +20 points
 *     Same brand     → +20 points
 *     Same location  → +10 points
 *     Same date (within 3 days) → +10 points
 *
 *   Total possible score = 100
 *
 *   Items with score >= 70 are considered "high confidence" matches.
 * ------------------------------------------------------------
 */

/**
 * Calculate match score between a lost item and a found item.
 *
 * Compares attributes one by one and adds points for each match.
 * Returns both the total score and a breakdown by attribute.
 *
 * @param array $lost   Associative array of lost item row
 * @param array $found  Associative array of found item row
 * @return array ['score' => int, 'breakdown' => array]
 */
function calculate_match_score($lost, $found) {
    // Start at zero and accumulate points for each matching attribute.
    $score = 0;
    $breakdown = [];

    // 1. CATEGORY (40 points)
    // Category is the most important attribute — same type of item.
    if ((int)$lost['category_id'] === (int)$found['category_id']) {
        $score += 40;
        $breakdown['category'] = 40;
    } else {
        // If categories don't match, score is essentially 0.
        // We still return the other matches for transparency.
        $breakdown['category'] = 0;
    }

    // 2. COLOR (20 points) - case-insensitive comparison
    // Trims whitespace and compares lowercase so "Silver" == "silver".
    if (!empty($lost['color']) && !empty($found['color'])) {
        if (strtolower(trim($lost['color'])) === strtolower(trim($found['color']))) {
            $score += 20;
            $breakdown['color'] = 20;
        } else {
            $breakdown['color'] = 0;
        }
    }

    // 3. BRAND (20 points) - case-insensitive
    // Same normalization approach as color.
    if (!empty($lost['brand']) && !empty($found['brand'])) {
        if (strtolower(trim($lost['brand'])) === strtolower(trim($found['brand']))) {
            $score += 20;
            $breakdown['brand'] = 20;
        } else {
            $breakdown['brand'] = 0;
        }
    }

    // 4. LOCATION (10 points) - partial match (contains)
    // Uses strpos so "Library" matches "Library, 2nd floor".
    if (!empty($lost['location']) && !empty($found['location'])) {
        $lost_loc  = strtolower(trim($lost['location']));
        $found_loc = strtolower(trim($found['location']));

        // Partial match: either string contains the other.
        if (strpos($lost_loc, $found_loc) !== false || strpos($found_loc, $lost_loc) !== false) {
            $score += 10;
            $breakdown['location'] = 10;
        } else {
            $breakdown['location'] = 0;
        }
    }

    // 5. DATE (10 points) - within 3 days
    // Items lost and found close in time are more likely to match.
    if (!empty($lost['date_lost']) && !empty($found['date_found'])) {
        $d1 = strtotime($lost['date_lost']);
        $d2 = strtotime($found['date_found']);
        $diff_days = abs($d2 - $d1) / 86400;   // 86400 seconds per day

        if ($diff_days <= 3) {
            $score += 10;
            $breakdown['date'] = 10;
        } else {
            $breakdown['date'] = 0;
        }
    }

    // Return the final score, per-attribute breakdown, and max.
    return [
        'score'     => $score,
        'breakdown' => $breakdown,
        'max'       => 100,
    ];
}


/**
 * Find all matches for a given lost item.
 * Returns an array of matches sorted by score (highest first).
 *
 * @param mysqli $conn
 * @param int    $lost_id
 * @return array
 */
function find_matches_for_lost($conn, $lost_id) {

    // 1. Get the lost item by ID.
    $stmt = $conn->prepare("SELECT * FROM lost_items WHERE lost_id = ?");
    $stmt->bind_param("i", $lost_id);
    $stmt->execute();
    $lost = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // If the lost item doesn't exist, return empty.
    if (!$lost) return [];

    // 2. Get all open found items IN THE SAME CATEGORY.
    //    Category = 40 pts, so if it doesn't match, the score
    //    would be too low to matter anyway.
    $stmt = $conn->prepare(
        "SELECT f.*, u.full_name AS finder_name, u.email AS finder_email
         FROM found_items f
         JOIN users u ON f.user_id = u.user_id
         WHERE f.status = 'open' AND f.category_id = ?"
    );
    $stmt->bind_param("i", $lost['category_id']);
    $stmt->execute();
    $found_result = $stmt->get_result();

    // Loop through each found item, calculate the score,
    // and keep any that score above zero.
    $matches = [];
    while ($found = $found_result->fetch_assoc()) {
        $result = calculate_match_score($lost, $found);
        if ($result['score'] > 0) {
            $matches[] = [
                'found'     => $found,
                'score'     => $result['score'],
                'breakdown' => $result['breakdown'],
            ];
        }
    }
    $stmt->close();

    // 3. Sort by score descending (highest match first).
    usort($matches, function($a, $b) {
        return $b['score'] - $a['score'];
    });

    return $matches;
}
?>