<?php
/**
 * search.php
 * ------------------------------------------------------------
 * PURPOSE: Search and filter all lost and found items.
 *
 * FILTERS (all optional, combinable):
 *   - keyword      (searches item name + description)
 *   - category     (dropdown from categories table)
 *   - type         (lost / found / both)
 *   - location     (partial text match)
 *   - date_from    (date range start)
 *   - date_to      (date range end)
 *   - status       (open / claimed / all)
 *
 * FLOW:
 *   1. Require login.
 *   2. Read filter values from $_GET.
 *   3. Build dynamic WHERE clauses with prepared statements.
 *   4. Run two queries (lost_items + found_items) if needed.
 *   5. Render tables for the results.
 *
 * SECURITY:
 *   - Prepared statements prevent SQL injection.
 *   - Output escaped with htmlspecialchars.
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
// Must be logged in to search.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// ------------------------------------------------------------
// READ FILTER INPUTS FROM THE URL (GET)
// Default values apply when nothing is submitted yet.
// ------------------------------------------------------------
$keyword    = trim($_GET['keyword'] ?? '');
$category   = (int)($_GET['category'] ?? 0);
$type       = $_GET['type'] ?? 'both';     // both / lost / found
$location   = trim($_GET['location'] ?? '');
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to'] ?? '';
$status     = $_GET['status'] ?? 'open';   // open / claimed / all

// ------------------------------------------------------------
// LOAD CATEGORIES FOR THE DROPDOWN
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

// ============================================================
// BUILD THE LOST ITEMS QUERY
// We build WHERE conditions and parameter lists separately,
// then combine them safely with prepared statements.
// ============================================================
$lost_where  = [];
$lost_params = [];
$lost_types  = '';

// Keyword (item name OR description)
if ($keyword !== '') {
    $lost_where[]  = "(l.item_name LIKE ? OR l.description LIKE ?)";
    $lost_params[] = '%' . $keyword . '%';
    $lost_params[] = '%' . $keyword . '%';
    $lost_types   .= 'ss';
}

// Category filter
if ($category > 0) {
    $lost_where[]  = "l.category_id = ?";
    $lost_params[] = $category;
    $lost_types   .= 'i';
}

// Location filter (partial)
if ($location !== '') {
    $lost_where[]  = "l.location LIKE ?";
    $lost_params[] = '%' . $location . '%';
    $lost_types   .= 's';
}

// Date range
if ($date_from !== '') {
    $lost_where[]  = "l.date_lost >= ?";
    $lost_params[] = $date_from;
    $lost_types   .= 's';
}
if ($date_to !== '') {
    $lost_where[]  = "l.date_lost <= ?";
    $lost_params[] = $date_to;
    $lost_types   .= 's';
}

// Status filter (skip if 'all')
if ($status !== 'all') {
    $lost_where[]  = "l.status = ?";
    $lost_params[] = $status;
    $lost_types   .= 's';
}

// Assemble the full lost_items SQL
$lost_sql = "SELECT l.*, c.category_name, u.full_name AS owner_name
             FROM lost_items l
             LEFT JOIN categories c ON l.category_id = c.category_id
             LEFT JOIN users u ON l.user_id = u.user_id";
if (!empty($lost_where)) {
    $lost_sql .= " WHERE " . implode(" AND ", $lost_where);
}
$lost_sql .= " ORDER BY l.created_at DESC LIMIT 100";

// Run the lost query
$lost_stmt = $conn->prepare($lost_sql);
if (!empty($lost_params)) {
    $lost_stmt->bind_param($lost_types, ...$lost_params);
}
$lost_stmt->execute();
$lost_items = $lost_stmt->get_result();

// ============================================================
// BUILD THE FOUND ITEMS QUERY (same pattern as above)
// ============================================================
$found_where  = [];
$found_params = [];
$found_types  = '';

if ($keyword !== '') {
    $found_where[]  = "(f.item_name LIKE ? OR f.description LIKE ?)";
    $found_params[] = '%' . $keyword . '%';
    $found_params[] = '%' . $keyword . '%';
    $found_types   .= 'ss';
}
if ($category > 0) {
    $found_where[]  = "f.category_id = ?";
    $found_params[] = $category;
    $found_types   .= 'i';
}
if ($location !== '') {
    $found_where[]  = "f.location LIKE ?";
    $found_params[] = '%' . $location . '%';
    $found_types   .= 's';
}
if ($date_from !== '') {
    $found_where[]  = "f.date_found >= ?";
    $found_params[] = $date_from;
    $found_types   .= 's';
}
if ($date_to !== '') {
    $found_where[]  = "f.date_found <= ?";
    $found_params[] = $date_to;
    $found_types   .= 's';
}
if ($status !== 'all') {
    $found_where[]  = "f.status = ?";
    $found_params[] = $status;
    $found_types   .= 's';
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

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     SEARCH & FILTER PAGE
     ============================================================ -->
<div class="container" style="max-width:1100px;">

    <h2>Search &amp; Filter</h2>

    <!-- ============================================================
         FILTER FORM
         Uses GET so filters appear in the URL and can be bookmarked.
         ============================================================ -->
    <form method="GET" action="search.php"
          style="background:#fdf6f9; padding:22px; border-radius:14px;
                 margin-bottom:25px; border:1px solid #f0e8f4;">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">

            <!-- Keyword -->
            <div class="form-group">
                <label>Keyword</label>
                <input type="text" name="keyword" placeholder="e.g., MacBook"
                       value="<?php echo htmlspecialchars($keyword); ?>">
            </div>

            <!-- Category -->
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

            <!-- Type -->
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="both"  <?php if ($type == 'both')  echo 'selected'; ?>>Both Lost &amp; Found</option>
                    <option value="lost"  <?php if ($type == 'lost')  echo 'selected'; ?>>Lost only</option>
                    <option value="found" <?php if ($type == 'found') echo 'selected'; ?>>Found only</option>
                </select>
            </div>

            <!-- Location -->
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" placeholder="e.g., Library"
                       value="<?php echo htmlspecialchars($location); ?>">
            </div>

            <!-- Date from -->
            <div class="form-group">
                <label>Date From</label>
                <input type="date" name="date_from"
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>

            <!-- Date to -->
            <div class="form-group">
                <label>Date To</label>
                <input type="date" name="date_to"
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>

            <!-- Status -->
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="open"    <?php if ($status == 'open')    echo 'selected'; ?>>Open</option>
                    <option value="claimed" <?php if ($status == 'claimed') echo 'selected'; ?>>Claimed</option>
                    <option value="all"     <?php if ($status == 'all')     echo 'selected'; ?>>All</option>
                </select>
            </div>
        </div>

        <!-- Apply / clear buttons -->
        <div style="margin-top:15px; display:flex; gap:10px;">
            <button type="submit" class="btn">Apply Filters</button>
            <a href="search.php" class="btn"
               style="background:linear-gradient(120deg,#c9b8dc,#dcd0e8);">Clear</a>
        </div>
    </form>

    <!-- ============================================================
         LOST ITEMS RESULTS
         Only rendered when type is 'both' or 'lost'.
         ============================================================ -->
    <?php if ($type === 'both' || $type === 'lost'): ?>

        <h3 style="margin-top:20px; color:#6b5b82;">
            Lost Items (<?php echo $lost_items->num_rows; ?>)
        </h3>

        <?php if ($lost_items->num_rows === 0): ?>

            <p class="helper">No lost items match your search.</p>

        <?php else: ?>

            <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
                <thead>
                    <tr style="background:linear-gradient(120deg,#b39ddb,#f4b6c5); color:white;">
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
                    <tr style="border-bottom:1px solid #f0e8f4;">
                        <td style="padding:6px;">
                            <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                                     style="width:50px; height:50px; object-fit:cover;
                                            border-radius:8px; border:1px solid #f4b6c5;">
                            <?php else: ?>
                                <div style="width:50px; height:50px; background:#fdf6f9;
                                            border-radius:8px;"></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['location'] ?: '—'); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['date_lost']); ?></td>
                        <td style="padding:6px;">
                            <span style="background:#f5ebf5; color:#6b5b82; padding:2px 10px;
                                         border-radius:12px; font-size:12px;">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

        <?php endif; ?>
    <?php endif; ?>

    <!-- ============================================================
         FOUND ITEMS RESULTS
         Only rendered when type is 'both' or 'found'.
         ============================================================ -->
    <?php if ($type === 'both' || $type === 'found'): ?>

        <h3 style="margin-top:40px; color:#6b5b82;">
            Found Items (<?php echo $found_items->num_rows; ?>)
        </h3>

        <?php if ($found_items->num_rows === 0): ?>

            <p class="helper">No found items match your search.</p>

        <?php else: ?>

            <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
                <thead>
                    <tr style="background:linear-gradient(120deg,#a5d8b8,#c8e6d0); color:#3a6b52;">
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
                    <tr style="border-bottom:1px solid #f0e8f4;">
                        <td style="padding:6px;">
                            <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                                     style="width:50px; height:50px; object-fit:cover;
                                            border-radius:8px; border:1px solid #a5d8b8;">
                            <?php else: ?>
                                <div style="width:50px; height:50px; background:#f0f8f3;
                                            border-radius:8px;"></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['location'] ?: '—'); ?></td>
                        <td style="padding:6px;"><?php echo htmlspecialchars($row['date_found']); ?></td>
                        <td style="padding:6px;">
                            <span style="background:#e8f4ec; color:#3a6b52; padding:2px 10px;
                                         border-radius:12px; font-size:12px;">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
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