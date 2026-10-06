<?php
/**
 * evaluate.php
 * ------------------------------------------------------------
 * PURPOSE: Compare multi-attribute matching vs keyword baseline.
 * Uses TOP-1 evaluation: only the #1 ranked result counts.
 * ------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
require_once 'dbconnect.php';
require_once 'match.php';
require_once 'keyword_baseline.php';

@session_start();

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

$SIM_USER_ID = 999;

// Load simulated data
$lost_items = [];
$found_items = [];

$res = $conn->query("SELECT * FROM lost_items WHERE user_id = $SIM_USER_ID ORDER BY lost_id");
while ($row = $res->fetch_assoc()) $lost_items[] = $row;

$res = $conn->query("SELECT * FROM found_items WHERE user_id = $SIM_USER_ID ORDER BY found_id");
while ($row = $res->fetch_assoc()) $found_items[] = $row;

// Pair lost with found by position
$pairs = [];
for ($i = 0; $i < min(count($lost_items), count($found_items)); $i++) {
    $pairs[] = [
        'lost'  => $lost_items[$i],
        'truth' => $found_items[$i],
    ];
}

// Ground truth: first 30 are matches, next 10 are non-matches, next 20 are matches
// (Based on data order: 25 perfect + 5 partial = 30 matches, then 10 non, then 20 hard)
// For simplicity, we treat: if the pair was DESIGNED as a match, is_true_match = true
// We know pairs 0-24 (perfect), 25-39 (partial), 40-49 (non-match), 50-69 (hard)
// That's 70 total pairs now

$total = count($pairs);

// Ground truth: pair indices
//   0-24  → perfect matches (true)
//   25-39 → partial matches (true)
//   40-49 → non-matches (false)
//   50-69 → hard matches (true) - just added
// But since we don't know exactly how many we have now, use a simpler heuristic:
//   A pair is a "true match" if the truth found item shares category with lost item
$true_match_indices = [];
foreach ($pairs as $i => $pair) {
    if ((int)$pair['lost']['category_id'] === (int)$pair['truth']['category_id']) {
        $true_match_indices[$i] = true;
    } else {
        $true_match_indices[$i] = false;
    }
}

// Metric counters
$ma_tp = $ma_fp = $ma_fn = $ma_tn = 0;
$kw_tp = $kw_fp = $kw_fn = $kw_tn = 0;

$per_item = [];

foreach ($pairs as $index => $pair) {
    $lost     = $pair['lost'];
    $truth    = $pair['truth'];
    $truth_id = (int)$truth['found_id'];
    $is_true  = $true_match_indices[$index];

    // ============================================================
    // MULTI-ATTRIBUTE — TOP 1 only
    // ============================================================
    $ma_matches = find_matches_for_lost($conn, $lost['lost_id']);
    $ma_top_id  = !empty($ma_matches) ? (int)$ma_matches[0]['found']['found_id'] : null;
    $ma_top_scr = !empty($ma_matches) ? (int)$ma_matches[0]['score'] : 0;

    // Top-1 correct: the #1 match must be the truth item
    $ma_top1_correct = ($ma_top_id === $truth_id) && ($ma_top_scr >= 40);

    if ($is_true && $ma_top1_correct)      $ma_tp++;
    elseif ($is_true && !$ma_top1_correct) $ma_fn++;
    elseif (!$is_true && $ma_top1_correct) $ma_fp++;
    else                                   $ma_tn++;

    // ============================================================
    // KEYWORD BASELINE — TOP 1 only
    // ============================================================
    $kw_matches = find_keyword_matches($conn, $lost);
    $kw_top_id  = !empty($kw_matches) ? (int)$kw_matches[0] : null;
    $kw_top1_correct = ($kw_top_id === $truth_id);

    if ($is_true && $kw_top1_correct)      $kw_tp++;
    elseif ($is_true && !$kw_top1_correct) $kw_fn++;
    elseif (!$is_true && $kw_top1_correct) $kw_fp++;
    else                                   $kw_tn++;

    $per_item[] = [
        'index'          => $index + 1,
        'lost_name'      => $lost['item_name'],
        'truth_name'     => $truth['item_name'],
        'is_true_match'  => $is_true,
        'ma_top_id'      => $ma_top_id,
        'ma_top_scr'     => $ma_top_scr,
        'ma_top1_ok'     => $ma_top1_correct,
        'kw_top_id'      => $kw_top_id,
        'kw_top1_ok'     => $kw_top1_correct,
    ];
}

// Compute metrics
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

include 'includes/header.php';
?>

<div class="container" style="max-width:1200px;">
    <h2 style="margin-bottom:10px;">Evaluation — TOP-1 Accuracy</h2>
    <p class="helper" style="margin-bottom:20px;">
        Only the <strong>#1 ranked match</strong> is counted. Total pairs: <?php echo count($pairs); ?>.
    </p>

    <h3 style="margin-top:30px;">Summary Metrics</h3>
    <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
        <thead>
            <tr style="background:#041E60; color:white;">
                <th style="padding:12px; text-align:left;">Metric</th>
                <th style="padding:12px; text-align:center;">Multi-Attribute</th>
                <th style="padding:12px; text-align:center;">Keyword Baseline</th>
                <th style="padding:12px; text-align:center;">Difference</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (['accuracy' => 'Accuracy', 'precision' => 'Precision', 'recall' => 'Recall', 'match_rate' => 'Successful Match Rate'] as $key => $label): ?>
                <?php $diff = round($ma_metrics[$key] - $kw_metrics[$key], 2); ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="padding:10px;"><strong><?php echo $label; ?></strong></td>
                    <td style="padding:10px; text-align:center; color:<?php echo $ma_metrics[$key] >= $kw_metrics[$key] ? '#1e7e34' : '#8b0000'; ?>; font-weight:bold;">
                        <?php echo $ma_metrics[$key]; ?>%
                    </td>
                    <td style="padding:10px; text-align:center; color:#666;">
                        <?php echo $kw_metrics[$key]; ?>%
                    </td>
                    <td style="padding:10px; text-align:center; color:<?php echo $diff >= 0 ? '#1e7e34' : '#8b0000'; ?>; font-weight:bold;">
                        <?php echo $diff >= 0 ? '+' : ''; ?><?php echo $diff; ?>%
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3 style="margin-top:40px;">Confusion Matrices</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:15px;">
        <div>
            <h4>Multi-Attribute</h4>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><th style="padding:8px; border:1px solid #ccc;"></th>
                    <th style="padding:8px; border:1px solid #ccc;">Pred Match</th>
                    <th style="padding:8px; border:1px solid #ccc;">Pred No</th></tr>
                <tr><th style="padding:8px; border:1px solid #ccc;">Actual Match</th>
                    <td style="padding:8px; border:1px solid #ccc; text-align:center; background:#d4edda;">TP=<?php echo $ma_metrics['tp']; ?></td>
                    <td style="padding:8px; border:1px solid #ccc; text-align:center; background:#f8d7da;">FN=<?php echo $ma_metrics['fn']; ?></td></tr>
                <tr><th style="padding:8px; border:1px solid #ccc;">Actual No</th>
                    <td style="padding:8px; border:1px solid #ccc; text-align:center; background:#f8d7da;">FP=<?php echo $ma_metrics['fp']; ?></td>
                    <td style="padding:8px; border:1px solid #ccc; text-align:center; background:#d4edda;">TN=<?php echo $ma_metrics['tn']; ?></td></tr>
            </table>
        </div>
        <div>
            <h4>Keyword Baseline</h4>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><th style="padding:8px; border:1px solid #ccc;"></th>
                    <th style="padding:8px; border:1px solid #ccc;">Pred Match</th>
                    <th style="padding:8px; border:1px solid #ccc;">Pred No</th></tr>
                <tr><th style="padding:8px; border:1px solid #ccc;">Actual Match</th>
                    <td style="padding:8px; border:1px solid #ccc; text-align:center; background:#d4edda;">TP=<?php echo $kw_metrics['tp']; ?></td>
                    <td style="padding:8px; border:1px solid #ccc; text-align:center; background:#f8d7da;">FN=<?php echo $kw_metrics['fn']; ?></td></tr>
                <tr><th style="padding:8px; border:1px solid #ccc;">Actual No</th>
                    <td style="padding:8px; border:1px solid #ccc; text-align:center; background:#f8d7da;">FP=<?php echo $kw_metrics['fp']; ?></td>
                    <td style="padding:8px; border:1px solid #ccc; text-align:center; background:#d4edda;">TN=<?php echo $kw_metrics['tn']; ?></td></tr>
            </table>
        </div>
    </div>

    <h3 style="margin-top:40px;">Detailed Results (<?php echo count($per_item); ?> pairs)</h3>
    <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:12px;">
        <thead>
            <tr style="background:#041E60; color:white;">
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
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px; text-align:center;"><?php echo $r['index']; ?></td>
                <td style="padding:6px;"><?php echo htmlspecialchars($r['lost_name']); ?></td>
                <td style="padding:6px;"><?php echo htmlspecialchars($r['truth_name']); ?></td>
                <td style="padding:6px; text-align:center;"><?php echo $r['is_true_match'] ? '✅' : '❌'; ?></td>
                <td style="padding:6px; text-align:center;"><?php echo $r['ma_top_scr']; ?></td>
                <td style="padding:6px; text-align:center;"><?php echo $r['ma_top1_ok'] ? '✅' : '❌'; ?></td>
                <td style="padding:6px; text-align:center;"><?php echo $r['kw_top1_ok'] ? '✅' : '❌'; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top:30px;">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>