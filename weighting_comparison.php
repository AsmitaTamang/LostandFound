<?php
/**
 * weighting_comparison.php
 * ------------------------------------------------------------
 * PURPOSE: Compare three weighting strategies for the matching
 *          algorithm to determine which best ranks truth items
 *          above distractors.
 *
 * STRATEGIES COMPARED:
 *   1. Equal  — 25 / 25 / 25 / 25
 *   2. Fixed  — 33 / 33 / 17 / 17  (production default)
 *   3. Tuned  — selected by a grid search on the tuning set
 *
 * DATA SPLIT:
 *   The simulated pairs are split INTERLEAVED (even index →
 *   tuning, odd index → test) so both sets contain a mix of
 *   easy, medium, and hard cases.
 *
 * KEY RULE:
 *   Tuning weights are chosen ONLY on the tuning set and then
 *   evaluated on the held-out test set. This prevents
 *   "overfitting" — the tuned strategy cannot peek at the
 *   test data.
 *
 * CATEGORY ROLE:
 *   Category is a hard gate (SQL filter). It is NOT scored.
 *   Only color, brand, location, date are weighted, and the
 *   weights are normalised so they sum to 100.
 *
 * ACCESS: Admin only.
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

// Simulated dataset owner.
$SIM_USER_ID = 999;


// ============================================================
// SCORING FUNCTION — 4 ATTRIBUTES ONLY (no category)
// Accepts a weight array so we can test multiple strategies
// with the same code.
// ============================================================
function score_4_attributes($lost, $found, $w) {

    $score = 0;

    // COLOR
    if (!empty($lost['color']) && !empty($found['color'])) {
        if (strtolower(trim($lost['color'])) === strtolower(trim($found['color']))) {
            $score += $w['color'];
        }
    }

    // BRAND
    if (!empty($lost['brand']) && !empty($found['brand'])) {
        if (strtolower(trim($lost['brand'])) === strtolower(trim($found['brand']))) {
            $score += $w['brand'];
        }
    }

    // LOCATION (partial match)
    if (!empty($lost['location']) && !empty($found['location'])) {
        $ll = strtolower(trim($lost['location']));
        $fl = strtolower(trim($found['location']));
        if (strpos($ll, $fl) !== false || strpos($fl, $ll) !== false) {
            $score += $w['location'];
        }
    }

    // DATE (within 3 days)
    if (!empty($lost['date_lost']) && !empty($found['date_found'])) {
        $d1 = strtotime($lost['date_lost']);
        $d2 = strtotime($found['date_found']);
        if ($d1 && $d2 && abs($d2 - $d1) / 86400 <= 3) {
            $score += $w['date'];
        }
    }

    return $score;
}


// ============================================================
// FIND TOP-1 MATCH USING A GIVEN WEIGHT ARRAY
// Category is applied as a hard filter. Items with score
// below the threshold are discarded (no match).
// ============================================================
function top1_with_weights($conn, $lost, $w, $threshold = 40) {

    $stmt = $conn->prepare(
        "SELECT * FROM found_items
         WHERE status = 'open' AND category_id = ?"
    );
    $stmt->bind_param("i", $lost['category_id']);
    $stmt->execute();
    $res = $stmt->get_result();

    $best_id = null;
    $best_score = 0;

    while ($f = $res->fetch_assoc()) {
        $s = score_4_attributes($lost, $f, $w);
        if ($s > $best_score) {
            $best_score = $s;
            $best_id    = (int)$f['found_id'];
        }
    }
    $stmt->close();

    return ($best_score >= $threshold) ? $best_id : null;
}


// ============================================================
// EVALUATE A WEIGHT ARRAY ON A SET OF PAIRS
// Returns accuracy, precision, recall, and TP/FP/FN/TN.
// ============================================================
function eval_on_set($conn, $pairs, $w, $threshold = 40) {

    $tp = $fp = $fn = $tn = 0;

    foreach ($pairs as $p) {
        $top_id   = top1_with_weights($conn, $p['lost'], $w, $threshold);
        $truth_id = (int)$p['truth']['found_id'];
        $correct  = ($top_id === $truth_id);
        $is_true  = $p['is_true'];

        if ($is_true && $correct)       $tp++;
        elseif ($is_true && !$correct)  $fn++;
        elseif (!$is_true && $correct)  $fp++;
        else                            $tn++;
    }

    $total = $tp + $fp + $fn + $tn;

    return [
        'accuracy'  => $total ? round(($tp + $tn) / $total * 100, 2) : 0,
        'precision' => ($tp + $fp) ? round($tp / ($tp + $fp) * 100, 2) : 0,
        'recall'    => ($tp + $fn) ? round($tp / ($tp + $fn) * 100, 2) : 0,
        'tp' => $tp, 'fp' => $fp, 'fn' => $fn, 'tn' => $tn,
    ];
}


// ============================================================
// LOAD DATA + BUILD PAIRS
// ============================================================
$lost_items = [];
$found_items = [];

$res = $conn->query(
    "SELECT * FROM lost_items WHERE user_id = $SIM_USER_ID ORDER BY lost_id"
);
while ($r = $res->fetch_assoc()) $lost_items[] = $r;

$res = $conn->query(
    "SELECT * FROM found_items WHERE user_id = $SIM_USER_ID ORDER BY found_id"
);
while ($r = $res->fetch_assoc()) $found_items[] = $r;

// Pair each lost item with its truth found item by position.
$pairs = [];
for ($i = 0; $i < min(count($lost_items), count($found_items)); $i++) {
    $lost  = $lost_items[$i];
    $truth = $found_items[$i];
    $pairs[] = [
        'lost'    => $lost,
        'truth'   => $truth,
        'is_true' => ((int)$lost['category_id'] === (int)$truth['category_id']),
    ];
}

// ------------------------------------------------------------
// INTERLEAVED SPLIT
// Even indices go to tuning, odd indices go to test.
// This guarantees both sets contain a mix of easy / medium /
// hard pairs, avoiding bias in either direction.
// ------------------------------------------------------------
$tuning   = [];
$test_set = [];
foreach ($pairs as $i => $p) {
    if ($i % 2 === 0) $tuning[]   = $p;
    else              $test_set[] = $p;
}


// ============================================================
// STRATEGY 1: EQUAL
// Every attribute contributes 25 points.
// ============================================================
$equal_w = ['color' => 25, 'brand' => 25, 'location' => 25, 'date' => 25];


// ============================================================
// STRATEGY 2: FIXED
// Production default. Color and brand get more weight because
// they are the most discriminative attributes for telling
// same-category items apart.
// ============================================================
$fixed_w = ['color' => 33, 'brand' => 33, 'location' => 17, 'date' => 17];


// ============================================================
// STRATEGY 3: TUNED
// Grid search over the tuning set only. We iterate possible
// (color, brand, location) values in 10-point steps and derive
// the date weight as the remainder, keeping the total at 100.
// The best accuracy on the tuning set defines the tuned weights.
// ============================================================
$best_score = -1;
$tuned_w    = $equal_w;   // fallback in case the grid search finds nothing better

for ($c = 10; $c <= 60; $c += 10) {
    for ($b = 10; $b <= 60; $b += 10) {
        for ($l = 10; $l <= 60; $l += 10) {

            $d = 100 - $c - $b - $l;
            if ($d < 10 || $d > 60) continue;

            $candidate = ['color' => $c, 'brand' => $b, 'location' => $l, 'date' => $d];

            $m = eval_on_set($conn, $tuning, $candidate);

            // Prefer higher accuracy; break ties by recall.
            $s = $m['accuracy'] * 1000 + $m['recall'];
            if ($s > $best_score) {
                $best_score = $s;
                $tuned_w    = $candidate;
            }
        }
    }
}


// ============================================================
// FINAL EVALUATION — ON THE HELD-OUT TEST SET ONLY
// ============================================================
$results = [
    'Equal' => [
        'w'       => $equal_w,
        'metrics' => eval_on_set($conn, $test_set, $equal_w),
    ],
    'Fixed' => [
        'w'       => $fixed_w,
        'metrics' => eval_on_set($conn, $test_set, $fixed_w),
    ],
    'Tuned' => [
        'w'       => $tuned_w,
        'metrics' => eval_on_set($conn, $test_set, $tuned_w),
    ],
];

// Determine the winner (highest accuracy; ties are kept as-is
// for the discussion chapter to explain the parsimony rule).
$winner    = '';
$best_acc  = -1;
foreach ($results as $name => $r) {
    if ($r['metrics']['accuracy'] > $best_acc) {
        $best_acc = $r['metrics']['accuracy'];
        $winner   = $name;
    }
}

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     WEIGHTING STRATEGY COMPARISON PAGE
     ============================================================ -->
<div class="container" style="max-width:1100px;">

    <h2>Weighting Strategy Comparison</h2>

    <p class="helper" style="margin-bottom:20px;">
        Category is a hard gate (same category required). Only color,
        brand, location, and date are weighted. Total weights sum to 100.
        Evaluation is TOP-1: only the first ranked match counts.
    </p>

    <!-- ============================================================
         DATA SPLIT SUMMARY
         ============================================================ -->
    <h3 style="color:#6b5b82;">Data Split</h3>

    <table style="width:100%; border-collapse:collapse; font-size:14px; margin-top:10px;">
        <tr style="background:#fdf6f9;">
            <td style="padding:10px; border:1px solid #f0e8f4;">
                Tuning set (used to select tuned weights)
            </td>
            <td style="padding:10px; border:1px solid #f0e8f4; text-align:center;">
                <strong><?php echo count($tuning); ?></strong> pairs
            </td>
        </tr>
        <tr>
            <td style="padding:10px; border:1px solid #f0e8f4;">
                Test set (held out for final comparison)
            </td>
            <td style="padding:10px; border:1px solid #f0e8f4; text-align:center;">
                <strong><?php echo count($test_set); ?></strong> pairs
            </td>
        </tr>
    </table>

    <!-- ============================================================
         STRATEGY WEIGHTS
         ============================================================ -->
    <h3 style="margin-top:30px; color:#6b5b82;">Strategies Tested</h3>

    <table style="width:100%; border-collapse:collapse; font-size:14px; margin-top:10px;">
        <thead>
            <tr style="background:linear-gradient(120deg,#b39ddb,#f4b6c5); color:white;">
                <th style="padding:10px; text-align:left;">Strategy</th>
                <th style="padding:10px; text-align:center;">Color</th>
                <th style="padding:10px; text-align:center;">Brand</th>
                <th style="padding:10px; text-align:center;">Location</th>
                <th style="padding:10px; text-align:center;">Date</th>
                <th style="padding:10px; text-align:center;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $name => $r): ?>
                <tr style="border-bottom:1px solid #f0e8f4;
                           <?php echo $name === $winner ? 'background:#e8f4ec;' : ''; ?>">
                    <td style="padding:10px;"><strong><?php echo $name; ?></strong></td>
                    <td style="padding:10px; text-align:center;"><?php echo $r['w']['color']; ?></td>
                    <td style="padding:10px; text-align:center;"><?php echo $r['w']['brand']; ?></td>
                    <td style="padding:10px; text-align:center;"><?php echo $r['w']['location']; ?></td>
                    <td style="padding:10px; text-align:center;"><?php echo $r['w']['date']; ?></td>
                    <td style="padding:10px; text-align:center;"><strong>100</strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ============================================================
         METRICS ON THE TEST SET
         ============================================================ -->
    <h3 style="margin-top:40px; color:#6b5b82;">Metrics on Test Set</h3>

    <table style="width:100%; border-collapse:collapse; font-size:14px; margin-top:10px;">
        <thead>
            <tr style="background:linear-gradient(120deg,#a5d8b8,#c8e6d0); color:#3a6b52;">
                <th style="padding:12px; text-align:left;">Metric</th>
                <?php foreach ($results as $name => $r): ?>
                    <th style="padding:12px; text-align:center;"><?php echo $name; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            // Render each metric row and highlight the best value.
            foreach (['accuracy'=>'Accuracy','precision'=>'Precision','recall'=>'Recall'] as $k => $lbl):
                $best_v = 0;
                foreach ($results as $r) if ($r['metrics'][$k] > $best_v) $best_v = $r['metrics'][$k];
            ?>
                <tr style="border-bottom:1px solid #f0e8f4;">
                    <td style="padding:10px;"><strong><?php echo $lbl; ?></strong></td>
                    <?php foreach ($results as $name => $r): ?>
                        <td style="padding:10px; text-align:center;
                                   <?php echo $r['metrics'][$k] == $best_v ? 'background:#d4edda; font-weight:bold;' : ''; ?>">
                            <?php echo $r['metrics'][$k]; ?>%
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    
    

    <p style="margin-top:30px;">
        <a href="admin_dashboard.php" class="btn">← Back to Admin Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>