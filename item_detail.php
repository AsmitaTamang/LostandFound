<?php
/**
 * item_detail.php
 * ------------------------------------------------------------
 * PURPOSE: Shows full details of a single lost or found item,
 *          including all matches against the opposite type.
 *
 * URL: item_detail.php?type=lost&id=5
 * ------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
require_once 'dbconnect.php';
require_once 'match.php';

@session_start();

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

$type = $_GET['type'] ?? 'lost';
$id   = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['lost', 'found']) || $id <= 0) {
    ob_end_clean();
    header("Location: search.php");
    exit;
}

$table     = $type === 'lost' ? 'lost_items'  : 'found_items';
$id_column = $type === 'lost' ? 'lost_id'     : 'found_id';
$date_col  = $type === 'lost' ? 'date_lost'   : 'date_found';

// Fetch item + reporter
$stmt = $conn->prepare(
    "SELECT i.*, c.category_name, u.full_name AS reporter_name, u.email AS reporter_email
     FROM $table i
     LEFT JOIN categories c ON i.category_id = c.category_id
     LEFT JOIN users u ON i.user_id = u.user_id
     WHERE i.$id_column = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    echo "Item not found.";
    exit;
}

// Get matches
if ($type === 'lost') {
    $matches = find_matches_for_lost($conn, $id);
} else {
    $matches = find_matches_for_found($conn, $id);
}

include 'includes/header.php';
?>

<div class="container" style="max-width:900px;">
    <h2 style="margin-bottom:20px;">
        <?php echo $type === 'lost' ? 'Lost Item' : 'Found Item'; ?> Details
    </h2>

    <div style="display:flex; gap:20px; margin-bottom:25px;">
        <div>
            <?php if (!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
                     style="width:200px; height:200px; object-fit:cover; border-radius:8px; border:1px solid #ccc;">
            <?php else: ?>
                <div style="width:200px; height:200px; background:#eee; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#888;">
                    No image
                </div>
            <?php endif; ?>
        </div>

        <div style="flex:1;">
            <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
            <p style="margin-top:8px; color:#555;">
                <strong>Category:</strong> <?php echo htmlspecialchars($item['category_name'] ?? '—'); ?><br>
                <strong>Color:</strong> <?php echo htmlspecialchars($item['color'] ?: '—'); ?><br>
                <strong>Brand:</strong> <?php echo htmlspecialchars($item['brand'] ?: '—'); ?><br>
                <strong>Location:</strong> <?php echo htmlspecialchars($item['location'] ?: '—'); ?><br>
                <strong>Date:</strong> <?php echo htmlspecialchars($item[$date_col]); ?><br>
                <strong>Status:</strong> <?php echo htmlspecialchars($item['status']); ?><br>
                <strong>Reported by:</strong> <?php echo htmlspecialchars($item['reporter_name'] ?? '—'); ?>
            </p>

            <?php if (!empty($item['description'])): ?>
                <p style="margin-top:10px; padding:10px; background:#f5f5f5; border-radius:5px;">
                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                </p>
            <?php endif; ?>

            <?php if ($item['user_id'] == $_SESSION['user_id']): ?>
                <p style="margin-top:15px;">
                    <a href="edit_item.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>" class="btn">Edit This Item</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <h3 style="margin-top:30px;">Potential Matches (<?php echo count($matches); ?>)</h3>

    <?php if (empty($matches)): ?>
        <p class="helper">No matches found for this item.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse; font-size:14px; margin-top:10px;">
            <thead>
                <tr style="background:#041E60; color:white;">
                    <th style="padding:8px;">Image</th>
                    <th style="padding:8px; text-align:left;">Item</th>
                    <th style="padding:8px;">Score</th>
                    <th style="padding:8px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($matches as $m):
                $other = $type === 'lost' ? $m['found'] : $m['lost'];
                $other_id = $type === 'lost' ? $other['found_id'] : $other['lost_id'];
                $other_type = $type === 'lost' ? 'found' : 'lost';
            ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">
                        <?php if (!empty($other['image_path']) && file_exists($other['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($other['image_path']); ?>"
                                 style="width:50px; height:50px; object-fit:cover; border-radius:6px;">
                        <?php else: ?>
                            <div style="width:50px; height:50px; background:#eee; border-radius:6px;"></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:6px;"><?php echo htmlspecialchars($other['item_name']); ?></td>
                    <td style="padding:6px; text-align:center;">
                        <strong><?php echo $m['score']; ?>/100</strong>
                    </td>
                    <td style="padding:6px;">
                        <a href="item_detail.php?type=<?php echo $other_type; ?>&id=<?php echo $other_id; ?>"
                           style="color:#041E60; text-decoration:underline; font-size:13px;">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p style="margin-top:25px;">
        <a href="search.php" class="btn">← Back to Search</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>