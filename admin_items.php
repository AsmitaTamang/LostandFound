<?php
/**
 * admin_items.php
 * ------------------------------------------------------------
 * PURPOSE: Admin can view / edit / delete ANY lost or found item.
 * ------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
require_once 'dbconnect.php';

@session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

$message = '';

// ------------------------------------------------------------
// Handle delete
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $type = $_POST['type'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);

    if (in_array($type, ['lost','found']) && $id > 0) {
        $table     = $type === 'lost' ? 'lost_items'  : 'found_items';
        $id_column = $type === 'lost' ? 'lost_id'     : 'found_id';

        // Fetch image to delete
        $stmt = $conn->prepare("SELECT image_path FROM $table WHERE $id_column = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && !empty($row['image_path']) && file_exists($row['image_path'])) {
            @unlink($row['image_path']);
        }

        $stmt = $conn->prepare("DELETE FROM $table WHERE $id_column = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $message = "Item deleted successfully.";
        $stmt->close();
    }
}

// ------------------------------------------------------------
// Filters
// ------------------------------------------------------------
$filter_type     = $_GET['type'] ?? 'all';
$filter_category = (int)($_GET['category'] ?? 0);
$filter_keyword  = trim($_GET['keyword'] ?? '');

// Build WHERE
$where = [];
$params = [];
$types = '';

if ($filter_category > 0) {
    $where[] = "i.category_id = ?";
    $params[] = $filter_category;
    $types .= 'i';
}
if ($filter_keyword !== '') {
    $where[] = "i.item_name LIKE ?";
    $params[] = '%' . $filter_keyword . '%';
    $types .= 's';
}
$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : '';

// Build UNION query
$sql = "SELECT i.*, c.category_name, u.full_name AS reporter, 'lost' AS item_type
        FROM lost_items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        LEFT JOIN users u ON i.user_id = u.user_id
        $where_sql
        UNION ALL
        SELECT i.*, c.category_name, u.full_name AS reporter, 'found' AS item_type
        FROM found_items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        LEFT JOIN users u ON i.user_id = u.user_id
        $where_sql
        ORDER BY created_at DESC
        LIMIT 200";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    // params get bound twice (once for each UNION branch)
    $all_params = array_merge($params, $params);
    $all_types  = $types . $types;
    $stmt->bind_param($all_types, ...$all_params);
}
$stmt->execute();
$items = $stmt->get_result();

// Load categories for filter
$categories = [];
$cat_res = $conn->query("SELECT category_id, category_name FROM categories WHERE is_active=1 ORDER BY category_name");
while ($row = $cat_res->fetch_assoc()) $categories[] = $row;

include 'includes/header.php';
?>

<div class="container" style="max-width:1200px;">
    <h2 style="margin-bottom:20px;">Manage All Items</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- FILTERS -->
    <form method="GET" style="background:#f5f5f5; padding:15px; border-radius:8px; margin-bottom:20px;">
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
            <div class="form-group">
                <label>Keyword</label>
                <input type="text" name="keyword" value="<?php echo htmlspecialchars($filter_keyword); ?>">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="0">-- All --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"
                            <?php if ($filter_category == $cat['category_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="align-self:end;">
                <button type="submit" class="btn" style="width:100%;">Filter</button>
            </div>
        </div>
    </form>

    <!-- TABLE -->
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="background:#041E60; color:white;">
                <th style="padding:8px;">Type</th>
                <th style="padding:8px;">Image</th>
                <th style="padding:8px; text-align:left;">Item</th>
                <th style="padding:8px; text-align:left;">Category</th>
                <th style="padding:8px; text-align:left;">Reporter</th>
                <th style="padding:8px; text-align:left;">Status</th>
                <th style="padding:8px; text-align:center;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $items->fetch_assoc()): ?>
            <?php
            $is_lost = $row['item_type'] === 'lost';
            $id_val  = $is_lost ? $row['lost_id'] : $row['found_id'];
            $date    = $is_lost ? $row['date_lost'] : $row['date_found'];
            ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px; text-align:center;">
                    <?php if ($is_lost): ?>
                        <span style="background:#e8efff; padding:2px 8px; border-radius:10px;">LOST</span>
                    <?php else: ?>
                        <span style="background:#d4edda; padding:2px 8px; border-radius:10px;">FOUND</span>
                    <?php endif; ?>
                </td>
                <td style="padding:6px;">
                    <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                             style="width:40px; height:40px; object-fit:cover; border-radius:5px;">
                    <?php else: ?>
                        <div style="width:40px; height:40px; background:#eee; border-radius:5px;"></div>
                    <?php endif; ?>
                </td>
                <td style="padding:6px;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                <td style="padding:6px;"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>
                <td style="padding:6px;"><?php echo htmlspecialchars($row['reporter'] ?? '—'); ?></td>
                <td style="padding:6px;"><?php echo htmlspecialchars($row['status']); ?></td>
                <td style="padding:6px; text-align:center;">
                    <a href="edit_item.php?type=<?php echo $is_lost ? 'lost' : 'found'; ?>&id=<?php echo $id_val; ?>"
                       style="background:#041E60; color:white; padding:3px 8px; border-radius:5px; text-decoration:none; font-size:11px;">
                        Edit
                    </a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this item?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="type" value="<?php echo $is_lost ? 'lost' : 'found'; ?>">
                        <input type="hidden" name="id" value="<?php echo $id_val; ?>">
                        <button type="submit" style="background:#dc3545; color:white; padding:3px 8px; border:none; border-radius:5px; cursor:pointer; font-size:11px;">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <p style="margin-top:25px;">
        <a href="admin_dashboard.php" class="btn">← Back to Admin Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>