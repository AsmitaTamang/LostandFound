<?php
/**
 * admin_items.php
 * ------------------------------------------------------------
 * PURPOSE: Admin can view, filter, edit, and delete ANY lost or
 *          found item in the system (not just their own).
 *
 * FLOW:
 *   1. Require admin role.
 *   2. On POST (delete): remove image file + row.
 *   3. Build a filtered query over lost and found items.
 *   4. Apply optional filters: keyword, category, type.
 *   5. Render table with Edit + Delete buttons per row.
 *
 * SECURITY:
 *   - Admin-only access.
 *   - Prepared statements prevent SQL injection.
 *   - Item type + ID are validated before use in SQL.
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

// Status message shown after an action.
$message = '';

// ------------------------------------------------------------
// HANDLE DELETE
// Deletes any lost or found item by ID.
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

    $type = $_POST['type'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);

    // Validate the type before using it in SQL.
    if (in_array($type, ['lost', 'found']) && $id > 0) {

        $table     = $type === 'lost' ? 'lost_items'  : 'found_items';
        $id_column = $type === 'lost' ? 'lost_id'     : 'found_id';

        // Load image path so we can delete the file.
        $stmt = $conn->prepare(
            "SELECT image_path FROM $table WHERE $id_column = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Remove image file if it exists.
        if ($row && !empty($row['image_path']) && file_exists($row['image_path'])) {
            @unlink($row['image_path']);
        }

        // Delete the row.
        $stmt = $conn->prepare(
            "DELETE FROM $table WHERE $id_column = ?"
        );
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = "Item deleted successfully.";
        }
        $stmt->close();
    }
}

// ------------------------------------------------------------
// READ FILTERS FROM URL
// ------------------------------------------------------------
$filter_type     = $_GET['type'] ?? 'all';         // all / lost / found
$filter_category = (int)($_GET['category'] ?? 0);
$filter_keyword  = trim($_GET['keyword'] ?? '');

// ------------------------------------------------------------
// BUILD THE FILTER WHERE CLAUSE
// ------------------------------------------------------------
$where  = [];
$params = [];
$types  = '';

if ($filter_category > 0) {
    $where[]  = "i.category_id = ?";
    $params[] = $filter_category;
    $types   .= 'i';
}
if ($filter_keyword !== '') {
    $where[]  = "i.item_name LIKE ?";
    $params[] = '%' . $filter_keyword . '%';
    $types   .= 's';
}
$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : '';

// ------------------------------------------------------------
// BUILD THE SQL — one branch, the other, or both
// We alias the ID column as `item_id` so both branches have
// a consistent name after the UNION.
// ------------------------------------------------------------
if ($filter_type === 'lost') {

    $sql = "SELECT i.*,
                   i.lost_id AS item_id,
                   c.category_name,
                   u.full_name AS reporter,
                   'lost' AS item_type
            FROM lost_items i
            LEFT JOIN categories c ON i.category_id = c.category_id
            LEFT JOIN users u ON i.user_id = u.user_id
            $where_sql
            ORDER BY i.created_at DESC
            LIMIT 200";

} elseif ($filter_type === 'found') {

    $sql = "SELECT i.*,
                   i.found_id AS item_id,
                   c.category_name,
                   u.full_name AS reporter,
                   'found' AS item_type
            FROM found_items i
            LEFT JOIN categories c ON i.category_id = c.category_id
            LEFT JOIN users u ON i.user_id = u.user_id
            $where_sql
            ORDER BY i.created_at DESC
            LIMIT 200";

} else {

    $sql = "SELECT i.*,
                   i.lost_id AS item_id,
                   c.category_name,
                   u.full_name AS reporter,
                   'lost' AS item_type
            FROM lost_items i
            LEFT JOIN categories c ON i.category_id = c.category_id
            LEFT JOIN users u ON i.user_id = u.user_id
            $where_sql
            UNION ALL
            SELECT i.*,
                   i.found_id AS item_id,
                   c.category_name,
                   u.full_name AS reporter,
                   'found' AS item_type
            FROM found_items i
            LEFT JOIN categories c ON i.category_id = c.category_id
            LEFT JOIN users u ON i.user_id = u.user_id
            $where_sql
            ORDER BY created_at DESC
            LIMIT 200";
}

// ------------------------------------------------------------
// PREPARE + BIND + EXECUTE
// ------------------------------------------------------------
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    if ($filter_type === 'all') {
        // Two UNION branches → bind params twice
        $all_params = array_merge($params, $params);
        $all_types  = $types . $types;
        $stmt->bind_param($all_types, ...$all_params);
    } else {
        // Single branch → bind once
        $stmt->bind_param($types, ...$params);
    }
}

$stmt->execute();
$items = $stmt->get_result();

// ------------------------------------------------------------
// LOAD CATEGORIES FOR THE FILTER DROPDOWN
// ------------------------------------------------------------
$categories = [];
$cat_res = $conn->query(
    "SELECT category_id, category_name
     FROM categories
     WHERE is_active = 1
     ORDER BY category_name"
);
while ($row = $cat_res->fetch_assoc()) {
    $categories[] = $row;
}

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     MANAGE ALL ITEMS PAGE
     ============================================================ -->
<div class="container" style="max-width:1200px;">

    <h2>Manage All Items</h2>

    <!-- Status message -->
    <?php if ($message): ?>
        <div class="alert alert-success">🌸 <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- ============================================================
         FILTER FORM
         ============================================================ -->
    <form method="GET" style="background:#fdf6f9; padding:18px;
                              border-radius:14px; margin-bottom:20px;
                              border:1px solid #f0e8f4;">

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:12px;">

            <div class="form-group">
                <label>Keyword</label>
                <input type="text" name="keyword"
                       value="<?php echo htmlspecialchars($filter_keyword); ?>">
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

            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="all"   <?php if ($filter_type == 'all')   echo 'selected'; ?>>All</option>
                    <option value="lost"  <?php if ($filter_type == 'lost')  echo 'selected'; ?>>Lost only</option>
                    <option value="found" <?php if ($filter_type == 'found') echo 'selected'; ?>>Found only</option>
                </select>
            </div>

            <div class="form-group" style="align-self:end;">
                <button type="submit" class="btn" style="width:100%;">Filter</button>
            </div>
        </div>
    </form>

    <!-- ============================================================
         ITEMS TABLE
         ============================================================ -->
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="background:linear-gradient(120deg,#b39ddb,#f4b6c5); color:white;">
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
            $id_val  = (int)$row['item_id'];
            ?>

            <tr style="border-bottom:1px solid #f0e8f4;">

                <td style="padding:6px; text-align:center;">
                    <?php if ($is_lost): ?>
                        <span style="background:#f5ebf5; color:#6b5b82;
                                     padding:2px 10px; border-radius:12px; font-size:12px;">
                            LOST
                        </span>
                    <?php else: ?>
                        <span style="background:#e8f4ec; color:#3a6b52;
                                     padding:2px 10px; border-radius:12px; font-size:12px;">
                            FOUND
                        </span>
                    <?php endif; ?>
                </td>

                <td style="padding:6px;">
                    <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                             style="width:40px; height:40px; object-fit:cover;
                                    border-radius:8px; border:1px solid #f4b6c5;">
                    <?php else: ?>
                        <div style="width:40px; height:40px; background:#fdf6f9;
                                    border-radius:8px;"></div>
                    <?php endif; ?>
                </td>

                <td style="padding:6px;">
                    <?php echo htmlspecialchars($row['item_name']); ?>
                </td>

                <td style="padding:6px;">
                    <?php echo htmlspecialchars($row['category_name'] ?? '—'); ?>
                </td>

                <td style="padding:6px;">
                    <?php echo htmlspecialchars($row['reporter'] ?? '—'); ?>
                </td>

                <td style="padding:6px;">
                    <?php echo htmlspecialchars($row['status']); ?>
                </td>

                <td style="padding:6px; text-align:center;">
                    <a href="edit_item.php?type=<?php echo $is_lost ? 'lost' : 'found'; ?>&id=<?php echo $id_val; ?>"
                       style="background:linear-gradient(120deg,#b39ddb,#f4b6c5);
                              color:white; padding:4px 12px; border-radius:12px;
                              text-decoration:none; font-size:11px;">
                        Edit
                    </a>

                    <form method="POST" style="display:inline;"
                          onsubmit="return confirm('Delete this item?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="type" value="<?php echo $is_lost ? 'lost' : 'found'; ?>">
                        <input type="hidden" name="id" value="<?php echo $id_val; ?>">
                        <button type="submit"
                                style="background:linear-gradient(120deg,#d67b8b,#c85a6e);
                                       color:white; padding:4px 12px; border:none;
                                       border-radius:12px; cursor:pointer; font-size:11px;">
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