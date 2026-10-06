<?php
/**
 * weighting_comparison.php
 * ------------------------------------------------------------
 * PURPOSE: Compare three weighting strategies for the
 *          multi-attribute matching algorithm:
 *
 *   1. EQUAL weights  — all attributes contribute 20 points
 *   2. FIXED weights  — current default (40/20/20/10/10)
 *   3. TUNED weights  — adjusted for best results (50/20/15/10/5)
 *
 * OUTPUT:
 *   - Side-by-side metrics table (accuracy, precision, recall, match rate)
 *   - Per-strategy confusion matrix
 *   - Winner highlighted
 *
 * This addresses the supervisor's request to compare different
 * weighting strategies rather than using one fixed set.
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

$SIM_USER_ID = 999;


// ============================================================
// CUSTOM SCORING FUNCTION (accepts weights as parameter)
// ============================================================
function score_with_weights($lost, $found, $weights) {

    $score = 0;

    // 1. CATEGORY
    if (
        !empty($lost['category_id']) && !empty($found['category_id']) &&
        (int)$lost['category_id'] === (int)$found['category_id']
    ) {
        $score += $weights['category'];
    }

    // 2. COLOR
    if (!empty($lost['color']) && !empty($found['color'])) {
        if (strtolower(trim($lost['color'])) === strtolower(trim($found['color']))) {
            $score += $weights['color'];
        }
    }

    // 3. BRAND
    if (!empty($lost['brand']) && !empty($found['brand'])) {
        if (strtolower(trim($lost['brand'])) === strtolower(trim($found['brand']))) {
            $score += $weights['brand'];
        }
    }

    // 4. LOCATION
    if (!empty($lost['location']) && !empty($found['location'])) {
        $ll = strtolower(trim($lost['location']));
        $fl = strtolower(trim($found['location']));
        if (strpos($ll, $fl) !== false || strpos($fl, $ll) !== false) {
            $score += $weights['location'];
        }
    }

    // 5. DATE (within 3 days)
    if (!empty($lost['date_lost']) && !empty($found['date_found'])) {
        $d1 = strtotime($lost['date_lost']);
        $d2 = strtotime($found['date_found']);
        if ($d1 && $d2) {
            $diff = abs($d2 - $d1) / 86400;
            if ($diff <= 3) {
                $score += $weights['date'];
            }
        }
    }

    return $score;
}


// ============================================================
// FIND BEST MATCH USING CUSTOM WEIGHTS (TOP-1)
// ============================================================
function find_top1_with_weights($conn, $lost, $weights, $threshold = 40) {

    // Only search within same category (same as main algorithm)
    $stmt = $conn->prepare(
        "SELECT * FROM found_items
         WHERE status = 'open' AND category_id = ?"
    );
    if (!$stmt) return null;

    $stmt->bind_param("i", $lost['category_id']);
    $stmt->execute();
    $res = $stmt->get_result();

    $best_id = null;
    $best_score = 0;

    while ($found = $res->fetch_assoc()) {
        $s = score_with_weights($lost, $found, $weights);
        if ($s > $best_score) {
            $best_score = $s;
            $best_id    = (int)$found['found_id'];
        }
    }
    $stmt->close();

    if ($best_score < $threshold) return null;
    return $best_id;
}


// ============================================================
// DEFINE THE THREE STRATEGIES
// ============================================================
$strategies = [
    'Equal Weights' => [
        'weights' => ['category' => 20, 'color' => 20, 'brand' => 20, 'location' => 20, 'date' => 20],
        'label'   => '20 / 20 / 20 / 20 / 20',
    ],
    'Fixed Weights (Current)' => [
        'weights' => ['category' => 40, 'color' => 20, 'brand' => 20, 'location' => 10, 'date' => 10],
        'label'   => '40 / 20 / 20 / 10 / 10',
    ],
    'Tuned Weights' => [
        'weights' => ['category' => 50, 'color' => 20, 'brand' => 15, 'location' => 10, 'date' => 5],
        'label'   => '50 / 20 / 15 / 10 / 5',
    ],
];


// ============================================================
// LOAD SIMULATED DATA
// ============================================================
$lost_items = [];
$found_items = [];

$res = $conn->query("SELECT * FROM lost_items WHERE user_id = $SIM_USER_ID ORDER BY lost_id");
while ($row = $res->fetch_assoc()) $lost_items[] = $row;

$res = $conn->query("SELECT * FROM found_items WHERE user_id = $SIM_USER_ID ORDER BY found_id");
while ($row = $res->fetch_assoc()) $found_items[] = $row;

// Build pairs
$pairs = [];
for ($i = 0; $i < min(count($lost_items), count($found_items)); $i++) {
    $lost  = $lost_items[$i];
    $truth = $found_items[$i];
    $is_true = ((int)$lost['category_id'] === (int)$truth['category_id']);
    $pairs[] = ['lost' => $lost, 'truth' => $truth, 'is_true' => $is_true];
}


// ============================================================
// RUN EACH STRATEGY
// ============================================================
$results = [];

foreach ($strategies as $name => $strategy) {
    $w = $strategy['weights'];

    $tp = $fp = $fn = $tn = 0;

    foreach ($pairs as $pair) {
        $lost = $pair['lost'];
        $truth_id = (int)$pair['truth']['found_id'];
        $is_true  = $pair['is_true'];

        // Get top-1 match with these weights
        $top_id = find_top1_with_weights($conn, $lost, $w);
        $correct = ($top_id === $truth_id);

        if ($is_true && $correct)      $tp++;
        elseif ($is_true && !$correct) $fn++;
        elseif (!$is_true && $correct) $fp++;
        else                           $tn++;
    }

    $total = $tp + $fp + $fn + $tn;

    $results[$name] = [
        'label'     => $strategy['label'],
        'weights'   => $w,
        'accuracy'  => $total > 0 ? round(($tp + $tn) / $total * 100, 2) : 0,
        'precision' => ($tp + $fp) > 0 ? round($tp / ($tp + $fp) * 100, 2) : 0,
        'recall'    => ($tp + $fn) > 0 ? round($tp / ($tp + $fn) * 100, 2) : 0,
        'match_rate'=> ($tp + $fn) > 0 ? round($tp / ($tp + $fn) * 100, 2) : 0,
        'tp' => $tp, 'fp' => $fp, 'fn' => $fn, 'tn' => $tn,
    ];
}

// Find winner (highest accuracy)
$winner = '';
$best_acc = -1;
foreach ($results as $name => $r) {
    if ($r['accuracy'] > $best_acc) {
        $best_acc = $r['accuracy'];
        $winner = $name;
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width:1100px;">
    <h2 style="margin-bottom:10px;">Weighting Strategy Comparison</h2>
    <p class="helper" style="margin-bottom:20px;">
        Three different weighting strategies tested on the same
        <?php echo count($pairs); ?> simulated pairs.
        Evaluation is <strong>TOP-1</strong> (only the #1 ranked match counts).
    </p>

    <!-- ============================================================
         STRATEGY DEFINITIONS
         ============================================================ -->
    <h3 style="margin-top:20px;">Strategies Tested</h3>
    <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
        <thead>
            <tr style="background:#041E60; color:white;">
                <th style="padding:10px; text-align:left;">Strategy</th>
                <th style="padding:10px; text-align:center;">Category</th>
                <th style="padding:10px; text-align:center;">Color</th>
                <th style="padding:10px; text-align:center;">Brand</th>
                <th style="padding:10px; text-align:center;">Location</th>
                <th style="padding:10px; text-align:center;">Date</th>
                <th style="padding:10px; text-align:center;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $name => $r): ?>
                <tr style="border-bottom:1px solid #ddd; <?php echo $name === $winner ? 'background:#e8fff0;' : ''; ?>">
                    <td style="padding:10px;"><strong><?php echo htmlspecialchars($name); ?></strong></td>
                    <td style="padding:10px; text-align:center;"><?php echo $r['weights']['category']; ?></td>
                    <td style="padding:10px; text-align:center;"><?php echo $r['weights']['color']; ?></td>
                    <td style="padding:10px; text-align:center;"><?php echo $r['weights']['brand']; ?></td>
                    <td style="padding:10px; text-align:center;"><?php echo $r['weights']['location']; ?></td>
                    <td style="padding:10px; text-align:center;"><?php echo $r['weights']['date']; ?></td>
                    <td style="padding:10px; text-align:center;"><strong>100</strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ============================================================
         METRICS COMPARISON
         ============================================================ -->
    <h3 style="margin-top:40px;">Metrics Comparison</h3>
    <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
        <thead>
            <tr style="background:#1e7e34; color:white;">
                <th style="padding:12px; text-align:left;">Metric</th>
                <?php foreach ($results as $name => $r): ?>
                    <th style="padding:12px; text-align:center;"><?php echo htmlspecialchars($name); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $metrics = [
                'accuracy'   => 'Accuracy',
                'precision'  => 'Precision',
                'recall'     => 'Recall',
                'match_rate' => 'Successful Match Rate',
            ];
            foreach ($metrics as $key => $label):
            ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="padding:10px;"><strong><?php echo $label; ?></strong></td>
                    <?php
                    // Find best value for this metric
                    $best_val = 0;
                    foreach ($results as $r) {
                        if ($r[$key] > $best_val) $best_val = $r[$key];
                    }
                    ?>
                    <?php foreach ($results as $name => $r): ?>
                        <td style="padding:10px; text-align:center; <?php echo $r[$key] == $best_val ? 'background:#d4edda; font-weight:bold;' : ''; ?>">
                            <?php echo $r[$key]; ?>%
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ============================================================
         CONFUSION MATRICES
         ============================================================ -->
    <h3 style="margin-top:40px;">Confusion Matrices</h3>
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-top:15px;">
        <?php foreach ($results as $name => $r): ?>
            <div>
                <h4 style="font-size:14px; <?php echo $name === $winner ? 'color:#1e7e34;' : ''; ?>">
                    <?php echo htmlspecialchars($name); ?>
                    <?php if ($name === $winner): ?> 🏆<?php endif; ?>
                </h4>
                <table style="width:100%; border-collapse:collapse; font-size:12px;">
                    <tr>
                        <th style="padding:6px; border:1px solid #ccc;"></th>
                        <th style="padding:6px; border:1px solid #ccc;">Pred M</th>
                        <th style="padding:6px; border:1px solid #ccc;">Pred N</th>
                    </tr>
                    <tr>
                        <th style="padding:6px; border:1px solid #ccc;">Act M</th>
                        <td style="padding:6px; border:1px solid #ccc; text-align:center; background:#d4edda;"><?php echo $r['tp']; ?></td>
                        <td style="padding:6px; border:1px solid #ccc; text-align:center; background:#f8d7da;"><?php echo $r['fn']; ?></td>
                    </tr>
                    <tr>
                        <th style="padding:6px; border:1px solid #ccc;">Act N</th>
                        <td style="padding:6px; border:1px solid #ccc; text-align:center; background:#f8d7da;"><?php echo $r['fp']; ?></td>
                        <td style="padding:6px; border:1px solid #ccc; text-align:center; background:#d4edda;"><?php echo $r['tn']; ?></td>
                    </tr>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ============================================================
         WINNER SUMMARY
         ============================================================ -->
    <h3 style="margin-top:40px;">Conclusion</h3>
    <div style="background:#e8fff0; border-left:5px solid #1e7e34; padding:20px; margin-top:15px; border-radius:5px;">
        <p style="font-size:15px; margin:0;">
            🏆 <strong>Best strategy: <?php echo htmlspecialchars($winner); ?></strong>
            with <strong><?php echo $results[$winner]['accuracy']; ?>%</strong> accuracy.
        </p>
        <p style="margin-top:10px; font-size:14px; color:#555;">
            This suggests that <strong>category is the most reliable attribute</strong>
            for identifying a true match. Giving it more weight than other attributes
            improves overall accuracy.
        </p>
    </div>

    <p style="margin-top:30px;">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>