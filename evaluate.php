<?php
/**
 * evaluate.php
 * ------------------------------------------------------------
 * PURPOSE: Runs the comparison between multi-attribute matching
 *          and a keyword-based baseline.
 *
 * OUTPUT:
 *   - Summary metrics table (accuracy, precision, recall, rate)
 *   - Confusion matrices for both systems
 *   - Detailed per-pair breakdown
 *
 * DATASET: The simulated items tagged with user_id = 999.
 *
 * ACCESS: Admin only.
 *
 * USES: match.php (multi-attribute) and keyword_baseline.php
 *       (keyword search)
 * ------------------------------------------------------------
 */

// Start output buffering so header() calls work later.
ob_start();

// Show all errors during development.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database + the two matching systems.
require_once 'dbconnect.php';
require_once 'match.php';
require_once 'keyword_baseline.php';

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

// The simulated dataset owner.
$SIM_USER_ID = 999;

// ------------------------------------------------------------
// LOAD SIMULATED DATA
// Pull every lost and found item owned by the simulated user,
// preserving insertion order (important for the 1:1 pairing).
// ------------------------------------------------------------
$lost_items  = [];
$found_items = [];

$res = $conn->query(
    "SELECT * FROM lost_items
     WHERE user_id = $SIM_USER_ID
     ORDER BY lost_id"
);
while ($row = $res->fetch_assoc()) $lost_items[] = $row;

$res = $conn->query(
    "SELECT * FROM found_items
     WHERE user_id = $SIM_USER_ID
     ORDER BY found_id"
);
while ($row = $res->fetch_assoc()) $found_items[] = $row;

// ------------------------------------------------------------
// BUILD PAIRS BY POSITION
// The simulated dataset is designed so that lost[0] pairs with
// found[0], lost[1] with found[1], etc.
// ------------------------------------------------------------
$pairs = [];
for ($i = 0; $i < min(count($lost_items), count($found_items)); $i++) {
    $pairs[] = [
        'lost'  => $lost_items[$i],
        'truth' => $found_items[$i],
    ];
}

// ------------------------------------------------------------
// GROUND TRUTH
// A pair is a "true match" if the lost item and the truth item
// share the same category. This is how the simulated dataset
// was constructed.
// ------------------------------------------------------------
$true_match_indices = [];
foreach ($pairs as $i => $pair) {
    $true_match_indices[$i] =
        ((int)$pair['lost']['category_id'] === (int)$pair['truth']['category_id']);
}

// ------------------------------------------------------------
// METRIC COUNTERS
// TP / FP / FN / TN for each system.
// ------------------------------------------------------------
$ma_tp = $ma_fp = $ma_fn = $ma_tn = 0;   // multi-attribute
$kw_tp = $kw_fp = $kw_fn = $kw_tn = 0;   // keyword baseline

$per_item = [];   // detailed per-pair results

// ------------------------------------------------------------
// RUN BOTH SYSTEMS ON EACH PAIR
// ------------------------------------------------------------
foreach ($pairs as $index => $pair) {

    $lost     = $pair['lost'];
    $truth    = $pair['truth'];
    $truth_id = (int)$truth['found_id'];
    $is_true  = $true_match_indices[$index];

    // --------------------------------------------------------
    // MULTI-ATTRIBUTE — TOP-1 ONLY
    // The #1 ranked match is considered the system's answer.
    // --------------------------------------------------------
    $ma_matches = find_matches_for_lost($conn, $lost['lost_id']);

    $ma_top_id  = !empty($ma_matches) ? (int)$ma_matches[0]['found']['found_id'] : null;
    $ma_top_scr = !empty($ma_matches) ? (int)$ma_matches[0]['score'] : 0;

    // Was the top-1 result the truth AND above the MEDIUM threshold?
    $ma_top1_correct = ($ma_top_id === $truth_id) && ($ma_top_scr >= 40);

    if ($is_true && $ma_top1_correct)       $ma_tp++;
    elseif ($is_true && !$ma_top1_correct)  $ma_fn++;
    elseif (!$is_true && $ma_top1_correct)  $ma_fp++;
    else                                    $ma_tn++;

    // --------------------------------------------------------
    // KEYWORD BASELINE — TOP-1 ONLY
    // Same rule: judge only the first result returned.
    // --------------------------------------------------------
    $kw_matches = find_keyword_matches($conn, $lost);
    $kw_top_id  = !empty($kw_matches) ? (int)$kw_matches[0] : null;
    $kw_top1_correct = ($kw_top_id === $truth_id);

    if ($is_true && $kw_top1_correct)       $kw_tp++;
    elseif ($is_true && !$kw_top1_correct)  $kw_fn++;
    elseif (!$is_true && $kw_top1_correct)  $kw_fp++;
    else                                    $kw_tn++;

    // --------------------------------------------------------
    // SAVE DETAILS FOR THE BREAKDOWN TABLE
    // --------------------------------------------------------
    $per_item[] = [
        'index'         => $index + 1,
        'lost_name'     => $lost['item_name'],
        'truth_name'    => $truth['item_name'],
        'is_true_match' => $is_true,
        'ma_top_id'     => $ma_top_id,
        'ma_top_scr'    => $ma_top_scr,
        'ma_top1_ok'    => $ma_top1_correct,
        'kw_top_id'     => $kw_top_id,
        'kw_top1_ok'    => $kw_top1_correct,
    ];
}

// ------------------------------------------------------------
// HELPER: compute metrics from TP/FP/FN/TN
// ------------------------------------------------------------
function compute_metrics($tp, $fp, $fn, $tn) {
    $total = $tp + $fp + $fn + $tn;
    return [
        'accuracy'  => $total > 0 ? round(($tp + $tn) / $total * 100, 2) : 0,
        'precision' => ($tp + $fp) > 0 ? round($tp / ($tp + $fp) * 100, 2) : 0,
        'recall'    => ($tp + $fn) > 0 ? round($tp / ($tp + $fn) * 100, 2) : 0,
        'match_rate'=> ($tp + $fn) > 0 ? round($tp / ($tp + $fn) * 100, 2) : 0,
        'tp' => $tp, 'fp' => $fp, 'fn' => $fn, 'tn' => $tn,
    ];
}

$ma_metrics = compute_metrics($ma_tp, $ma_fp, $ma_fn, $ma_tn);
$kw_metrics = compute_metrics($kw_tp, $kw_fp, $kw_fn, $kw_tn);

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     EVALUATION PAGE
     ============================================================ -->
<div class="container" style="max-width:1200px;">

    <h2>Evaluation — Multi-Attribute vs Keyword</h2>

    <p class="helper" style="margin-bottom:20px;">
        Comparing the weighted multi-attribute algorithm against
        a keyword-based baseline. Evaluation is TOP-1: only the
        first ranked match from each system counts.
        Total pairs: <?php echo count($pairs); ?>.
    </p>

    <!-- ============================================================
         SUMMARY METRICS TABLE
         ============================================================ -->
    <h3 style="color:#6b5b82;">Summary Metrics</h3>

    <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
        <thead>
            <tr style="background:linear-gradient(120deg,#b39ddb,#f4b6c5); color:white;">
                <th style="padding:12px; text-align:left;">Metric</th>
                <th style="padding:12px; text-align:center;">Multi-Attribute</th>
                <th style="padding:12px; text-align:center;">Keyword Baseline</th>
                <th style="padding:12px; text-align:center;">Difference</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Loop through the four metric keys and render one row each.
            $labels = [
                'accuracy'   => 'Accuracy',
                'precision'  => 'Precision',
                'recall'     => 'Recall',
                'match_rate' => 'Successful Match Rate',
            ];
            foreach ($labels as $key => $label):
                $diff = round($ma_metrics[$key] - $kw_metrics[$key], 2);
            ?>
                <tr style="border-bottom:1px solid #f0e8f4;">
                    <td style="padding:10px;"><strong><?php echo $label; ?></strong></td>

                    <!-- Multi-attribute value (green if it wins) -->
                    <td style="padding:10px; text-align:center;
                               color:<?php echo $ma_metrics[$key] >= $kw_metrics[$key] ? '#3a6b52' : '#8b3a52'; ?>;
                               font-weight:bold;">
                        <?php echo $ma_metrics[$key]; ?>%
                    </td>

                    <!-- Keyword value -->
                    <td style="padding:10px; text-align:center; color:#7a6b85;">
                        <?php echo $kw_metrics[$key]; ?>%
                    </td>

                    <!-- Difference -->
                    <td style="padding:10px; text-align:center;
                               color:<?php echo $diff >= 0 ? '#3a6b52' : '#8b3a52'; ?>;
                               font-weight:bold;">
                        <?php echo $diff >= 0 ? '+' : ''; ?><?php echo $diff; ?>%
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ============================================================
         CONFUSION MATRICES
         ============================================================ -->
    <h3 style="margin-top:40px; color:#6b5b82;">Confusion Matrices</h3>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:15px;">

        <!-- Multi-attribute matrix -->
        <div>
            <h4 style="color:#6b5b82;">Multi-Attribute</h4>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr>
                    <th style="padding:8px; border:1px solid #f0e8f4;"></th>
                    <th style="padding:8px; border:1px solid #f0e8f4;">Pred Match</th>
                    <th style="padding:8px; border:1px solid #f0e8f4;">Pred No</th>
                </tr>
                <tr>
                    <th style="padding:8px; border:1px solid #f0e8f4;">Actual Match</th>
                    <td style="padding:8px; border:1px solid #f0e8f4; text-align:center; background:#d4edda;">TP=<?php echo $ma_metrics['tp']; ?></td>
                    <td style="padding:8px; border:1px solid #f0e8f4; text-align:center; background:#f8d7da;">FN=<?php echo $ma_metrics['fn']; ?></td>
                </tr>
                <tr>
                    <th style="padding:8px; border:1px solid #f0e8f4;">Actual No</th>
                    <td style="padding:8px; border:1px solid #f0e8f4; text-align:center; background:#f8d7da;">FP=<?php echo $ma_metrics['fp']; ?></td>
                    <td style="padding:8px; border:1px solid #f0e8f4; text-align:center; background:#d4edda;">TN=<?php echo $ma_metrics['tn']; ?></td>
                </tr>
            </table>
        </div>

        <!-- Keyword baseline matrix -->
        <div>
            <h4 style="color:#6b5b82;">Keyword Baseline</h4>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr>
                    <th style="padding:8px; border:1px solid #f0e8f4;"></th>
                    <th style="padding:8px; border:1px solid #f0e8f4;">Pred Match</th>
                    <th style="padding:8px; border:1px solid #f0e8f4;">Pred No</th>
                </tr>
                <tr>
                    <th style="padding:8px; border:1px solid #f0e8f4;">Actual Match</th>
                    <td style="padding:8px; border:1px solid #f0e8f4; text-align:center; background:#d4edda;">TP=<?php echo $kw_metrics['tp']; ?></td>
                    <td style="padding:8px; border:1px solid #f0e8f4; text-align:center; background:#f8d7da;">FN=<?php echo $kw_metrics['fn']; ?></td>
                </tr>
                <tr>
                    <th style="padding:8px; border:1px solid #f0e8f4;">Actual No</th>
                    <td style="padding:8px; border:1px solid #f0e8f4; text-align:center; background:#f8d7da;">FP=<?php echo $kw_metrics['fp']; ?></td>
                    <td style="padding:8px; border:1px solid #f0e8f4; text-align:center; background:#d4edda;">TN=<?php echo $kw_metrics['tn']; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- ============================================================
         DETAILED PER-PAIR BREAKDOWN
         ============================================================ -->
    <h3 style="margin-top:40px; color:#6b5b82;">
        Detailed Results (<?php echo count($per_item); ?> pairs)
    </h3>

    <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:12px;">
        <thead>
            <tr style="background:linear-gradient(120deg,#b39ddb,#f4b6c5); color:white;">
                <th style="padding:8px;">#</th>
                <th style="padding:8px; text-align:left;">Lost</th>
                <th style="padding:8px; text-align:left;">Truth</th>
                <th style="padding:8px; text-align:center;">Match?</th>
                <th style="padding:8px; text-align:center;">MA Score</th>
                <th style="padding:8px; text-align:center;">MA Top1 ✅</th>
                <th style="padding:8px; text-align:center;">KW Top1 ✅</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($per_item as $r): ?>
            <tr style="border-bottom:1px solid #f0e8f4;">
                <td style="padding:6px; text-align:center;"><?php echo $r['index']; ?></td>
                <td style="padding:6px;"><?php echo htmlspecialchars($r['lost_name']); ?></td>
                <td style="padding:6px;"><?php echo htmlspecialchars($r['truth_name']); ?></td>
                <td style="padding:6px; text-align:center;">
                    <?php echo $r['is_true_match'] ? '✅' : '❌'; ?>
                </td>
                <td style="padding:6px; text-align:center;"><?php echo $r['ma_top_scr']; ?></td>
                <td style="padding:6px; text-align:center;">
                    <?php echo $r['ma_top1_ok'] ? '✅' : '❌'; ?>
                </td>
                <td style="padding:6px; text-align:center;">
                    <?php echo $r['kw_top1_ok'] ? '✅' : '❌'; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top:30px;">
        <a href="admin_dashboard.php" class="btn">← Back to Admin Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>