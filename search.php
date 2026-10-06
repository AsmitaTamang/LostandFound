<?php
/**
 * search.php
 * ------------------------------------------------------------
 * PURPOSE: Search and filter all lost and found items.
 *          Users can combine multiple filters.
 *
 * FILTERS:
 *   - Keyword (searches item name + description)
 *   - Category (dropdown)
 *   - Type (lost / found / both)
 *   - Location (text)
 *   - Date range (from / to)
 *   - Status (open / claimed / all)
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

// ------------------------------------------------------------
// Read filter inputs (default values if empty)
// ------------------------------------------------------------
$keyword    = trim($_GET['keyword'] ?? '');
$category   = (int)($_GET['category'] ?? 0);
$type       = $_GET['type'] ?? 'both';       // both / lost / found
$location   = trim($_GET['location'] ?? '');
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to'] ?? '';
$status     = $_GET['status'] ?? 'open';     // open / claimed / all

// ------------------------------------------------------------
// Load categories for the dropdown
// ------------------------------------------------------------
$categories = [];
$cat_res = $conn->query("SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name");
while ($row = $cat_res->fetch_assoc()) $categories[] = $row;

// ------------------------------------------------------------
// Build SQL for LOST items
// ------------------------------------------------------------
$lost_where  = [];
$lost_params = [];
$lost_types  = '';

if ($keyword !== '') {
    $lost_where[] = "(l.item_name LIKE ? OR l.description LIKE ?)";
    $lost_params[] = '%' . $keyword . '%';
    $lost_params[] = '%' . $keyword . '%';
    $lost_types .= 'ss';
}
if ($category > 0) {
    $lost_where[] = "l.category_id = ?";
    $lost_params[] = $category;
    $lost_types .= 'i';
}
if ($location !== '') {
    $lost_where[] = "l.location LIKE ?";
    $lost_params[] = '%' . $location . '%';
    $lost_types .= 's';
}
if ($date_from !== '') {
    $lost_where[] = "l.date_lost >= ?";
    $lost_params[] = $date_from;
    $lost_types .= 's';
}
if ($date_to !== '') {
    $lost_where[] = "l.date_lost <= ?";
    $lost_params[] = $date_to;
    $lost_types .= 's';
}
if ($status !== 'all') {
    $lost_where[] = "l.status = ?";
    $lost_params[] = $status;
    $lost_types .= 's';
}

$lost_sql = "SELECT l.*, c.category_name, u.full_name AS owner_name
             FROM lost_items l
             LEFT JOIN categories c ON l.category_id = c.category_id
             LEFT JOIN users u ON l.user_id = u.user_id";
if (!empty($lost_where)) {
    $lost_sql .= " WHERE " . implode(" AND ", $lost_where);
}
$lost_sql .= " ORDER BY l.created_at DESC LIMIT 100";

// Prepare + execute lost query
$lost_stmt = $conn->prepare($lost_sql);
if (!empty($lost_params)) {
    $lost_stmt->bind_param($lost_types, ...$lost_params);
}
$lost_stmt->execute();
$lost_items = $lost_stmt->get_result();

// ------------------------------------------------------------
// Build SQL for FOUND items
// ------------------------------------------------------------
$found_where  = [];
$found_params = [];
$found_types  = '';

if ($keyword !== '') {
    $found_where[] = "(f.item_name LIKE ? OR f.description LIKE ?)";
    $found_params[] = '%' . $keyword . '%';
    $found_params[] = '%' . $keyword . '%';
    $found_types .= 'ss';
}
if ($category > 0) {
    $found_where[] = "f.category_id = ?";
    $found_params[] = $category;
    $found_types .= 'i';
}
if ($location !== '') {
    $found_where[] = "f.location LIKE ?";
    $found_params[] = '%' . $location . '%';
    $found_types .= 's';
}
if ($date_from !== '') {
    $found_where[] = "f.date_found >= ?";
    $found_params[] = $date_from;
    $found_types .= 's';
}
if ($date_to !== '') {
    $found_where[] = "f.date_found <= ?";
    $found_params[] = $date_to;
    $found_types .= 's';
}
if ($status !== 'all') {
    $found_where[] = "f.status = ?";
    $found_params[] = $status;
    $found_types .= 's';
}

$found_sql = "SELECT f.*, c.category_name, u.full_name AS finder_name
              FROM found_items f
              LEFT JOIN categories c ON f.category_id = c.category_id
              LEFT JOIN users u ON f.user_id = u.user_id";
if (!empty($found_where)) {
    $found_sql .= " WHERE " . implode(" AND ", $found_where);
}
$found_sql .= " ORDER BY f.created_at DESC LIMIT 100";

$found_stmt = $conn->prepare($found_sql);
if (!empty($found_params)) {
    $found_stmt->bind_param($found_types, ...$found_params);
}
$found_stmt->execute();
$found_items = $found_stmt->get_result();

include 'includes/header.php';
?>

<div class="container" style="max-width:1100px;">
    <h2 style="margin-bottom:20px;">Search &amp; Filter</h2>

    <!-- ============================================================
         FILTER FORM
         ============================================================ -->
    <form method="GET" action="search.php" style="background:#f5f5f5; padding:20px; border-radius:8px; margin-bottom:25px;">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">

            <div class="form-group">
                <label>Keyword</label>
                <input type="text" name="keyword" placeholder="e.g., MacBook"
                       value="<?php echo htmlspecialchars($keyword); ?>">
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="0">-- All Categories --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"
                            <?php if ($category == $cat['category_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="both"  <?php if ($type == 'both')  echo 'selected'; ?>>Both Lost &amp; Found</option>
                    <option value="lost"  <?php if ($type == 'lost')  echo 'selected'; ?>>Lost only</option>
                    <option value="found" <?php if ($type == 'found') echo 'selected'; ?>>Found only</option>
                </select>
            </div>

            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" placeholder="e.g., Library"
                       value="<?php echo htmlspecialchars($location); ?>">
            </div>

            <div class="form-group">
                <label>Date From</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>

            <div class="form-group">
                <label>Date To</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="open"    <?php if ($status == 'open')    echo 'selected'; ?>>Open</option>
                    <option value="claimed" <?php if ($status == 'claimed') echo 'selected'; ?>>Claimed</option>
                    <option value="all"     <?php if ($status == 'all')     echo 'selected'; ?>>All</option>
                </select>
            </div>

        </div>

        <div style="margin-top:15px; display:flex; gap:10px;">
            <button type="submit" class="btn">Apply Filters</button>
            <a href="search.php" class="btn" style="background:#888;">Clear</a>
        </div>

    </form>

    <!-- ============================================================
         LOST ITEMS RESULTS
         ============================================================ -->
    <?php if ($type === 'both' || $type === 'lost'): ?>
        <h3 style="margin-top:20px;">Lost Items (<?php echo $lost_items->num_rows; ?>)</h3>

        <?php if ($lost_items->num_rows === 0): ?>
            <p class="helper">No lost items match your search.</p>
        <?php else: ?>
            <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
                <thead>
                    <tr style="background:#041E60; color:white;">
                        <th style="padding:8px; text-align:left;">Image</th>
                        <th style="padding:8px; text-align:left;">Item</th>
                        <th style="padding:8px; text-align:left;">Category</th>
                        <th style="padding:8px; text-align:left;">Location</th>
                        <th style="padding:8px; text-align:left;">Date</th>
                        <th style="padding:8px; text-align:left;">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $lost_items->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid #ddd;">
                        <td style="padding:6px;">
                            <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                                     style="width:50px; height:50px; object-fit:cover; border-radius:6px;">
                            <?php else: ?>
                                <div style="width:50px; height:50px; background:#eee; border-radius:6px;"></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['location'] ?: '—'); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['date_lost']); ?></td>
                        <td style="padding:6px;"><span style="background:#e8efff; padding:2px 8px; border-radius:10px; font-size:12px;"><?php echo htmlspecialchars($row['status']); ?></span></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ============================================================
         FOUND ITEMS RESULTS
         ============================================================ -->
    <?php if ($type === 'both' || $type === 'found'): ?>
        <h3 style="margin-top:40px;">Found Items (<?php echo $found_items->num_rows; ?>)</h3>

        <?php if ($found_items->num_rows === 0): ?>
            <p class="helper">No found items match your search.</p>
        <?php else: ?>
            <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
                <thead>
                    <tr style="background:#1e7e34; color:white;">
                        <th style="padding:8px; text-align:left;">Image</th>
                        <th style="padding:8px; text-align:left;">Item</th>
                        <th style="padding:8px; text-align:left;">Category</th>
                        <th style="padding:8px; text-align:left;">Location</th>
                        <th style="padding:8px; text-align:left;">Date</th>
                        <th style="padding:8px; text-align:left;">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $found_items->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid #ddd;">
                        <td style="padding:6px;">
                            <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                                     style="width:50px; height:50px; object-fit:cover; border-radius:6px;">
                            <?php else: ?>
                                <div style="width:50px; height:50px; background:#eee; border-radius:6px;"></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['location'] ?: '—'); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['date_found']); ?></td>
                        <td style="padding:6px;"><span style="background:#d4edda; padding:2px 8px; border-radius:10px; font-size:12px;"><?php echo htmlspecialchars($row['status']); ?></span></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <p style="margin-top:30px;">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>