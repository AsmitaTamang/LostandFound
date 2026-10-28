<?php
/**
 * report_found.php
 * ------------------------------------------------------------
 * PURPOSE: Form for users to report a FOUND item.
 *
 * FIELDS:
 *   - Category (dropdown, loaded from DB)
 *   - Item name
 *   - Description
 *   - Color, Brand, Location
 *   - Date found
 *   - Optional image upload
 *
 * FLOW:
 *   1. Require login.
 *   2. Load active categories for the dropdown.
 *   3. On POST: validate, optionally upload image,
 *      insert into found_items, notify matched users.
 *
 * SECURITY:
 *   - Prepared statements prevent SQL injection.
 *   - File extension + size validation for uploads.
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
// Must be logged in to report an item.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// Arrays to collect errors and success messages.
$errors  = [];
$success = '';

// ------------------------------------------------------------
// LOAD ACTIVE CATEGORIES FOR THE DROPDOWN
// ------------------------------------------------------------
$categories = [];
$cat_result = $conn->query(
    "SELECT category_id, category_name
     FROM categories
     WHERE is_active = 1
     ORDER BY category_name"
);
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row;
}

// ------------------------------------------------------------
// HANDLE FORM SUBMISSION
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read and clean all inputs.
    $category_id = (int)($_POST['category_id'] ?? 0);
    $item_name   = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color       = trim($_POST['color'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $date_found  = $_POST['date_found'] ?? '';

    // --------------------------------------------------------
    // VALIDATION
    // --------------------------------------------------------
    if ($category_id <= 0) {
        $errors[] = "Please select a category.";
    }
    if (empty($item_name)) {
        $errors[] = "Item name is required.";
    }
    if (empty($date_found)) {
        $errors[] = "Date found is required.";
    }

    // --------------------------------------------------------
    // OPTIONAL IMAGE UPLOAD
    // --------------------------------------------------------
    $image_path = null;

    if (!empty($_FILES['image']['name'])) {

        // Allowed extensions and 5 MB size limit.
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image must be under 5 MB.";
        } else {

            // Ensure the uploads folder exists.
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Build a unique filename so nothing gets overwritten.
            $filename = 'found_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target   = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                // Store only the relative path in the database.
                $image_path = 'uploads/' . $filename;
            } else {
                $errors[] = "Image upload failed.";
            }
        }
    }

    // --------------------------------------------------------
    // INSERT INTO DATABASE
    // --------------------------------------------------------
    if (empty($errors)) {

        $stmt = $conn->prepare(
            "INSERT INTO found_items
             (user_id, category_id, item_name, description,
              color, brand, location, date_found, image_path)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iisssssss",
            $_SESSION['user_id'],
            $category_id,
            $item_name,
            $description,
            $color,
            $brand,
            $location,
            $date_found,
            $image_path
        );

        if ($stmt->execute()) {

            $success = "Found item reported successfully!";
            $_POST = [];   // clear form values after success

            // ----------------------------------------------------
            // NOTIFY USERS WITH LOST ITEMS IN THE SAME CATEGORY
            // Anyone who reported a lost item in this category
            // gets a notification that a matching find is posted.
            // ----------------------------------------------------
            require_once 'notify.php';

            $notify_stmt = $conn->prepare(
                "SELECT user_id, item_name
                 FROM lost_items
                 WHERE status = 'open'
                   AND category_id = ?
                   AND user_id != ?"
            );
            $notify_stmt->bind_param(
                "ii",
                $category_id,
                $_SESSION['user_id']
            );
            $notify_stmt->execute();
            $notify_res = $notify_stmt->get_result();

            while ($row = $notify_res->fetch_assoc()) {
                create_notification(
                    $conn,
                    $row['user_id'],
                    "Someone reported finding an item that might match your lost '{$row['item_name']}'.",
                    "browse.php"
                );
            }
            $notify_stmt->close();

        } else {
            $errors[] = "Database error: " . $conn->error;
        }

        $stmt->close();
    }
}

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     REPORT FOUND ITEM PAGE
     ============================================================ -->
<div class="container" style="max-width:650px;">

    <h2>Report a Found Item</h2>

    <p class="helper" style="margin-bottom:20px;">
        Thank you for helping return lost items. Fill in what you can.
    </p>

    <!-- Success message -->
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Validation errors -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <div>• <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         REPORT FORM
         enctype="multipart/form-data" is required for file uploads.
         ============================================================ -->
    <form method="POST" enctype="multipart/form-data">

        <!-- Category dropdown -->
        <div class="form-group">
            <label>Category *</label>
            <select name="category_id" required>
                <option value="">-- Select a category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>"
                        <?php if (($_POST['category_id'] ?? '') == $cat['category_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="helper">
                Can't find your category? <a href="suggest_category.php">Suggest a new one</a>
            </div>
        </div>

        <!-- Item name -->
        <div class="form-group">
            <label>Item Name *</label>
            <input type="text" name="item_name" required
                   placeholder="e.g., Silver laptop"
                   value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>">
        </div>

        <!-- Description -->
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"
                      placeholder="Anything distinctive you noticed"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <!-- Color and Brand side by side -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" placeholder="e.g., Silver"
                       value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Brand</label>
                <input type="text" name="brand" placeholder="e.g., Apple"
                       value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>">
            </div>
        </div>

        <!-- Location found -->
        <div class="form-group">
            <label>Location Found</label>
            <input type="text" name="location" placeholder="e.g., Cafeteria, near window"
                   value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
        </div>

        <!-- Date found (defaults to today) -->
        <div class="form-group">
            <label>Date Found *</label>
            <input type="date" name="date_found" required
                   value="<?php echo htmlspecialchars($_POST['date_found'] ?? date('Y-m-d')); ?>">
        </div>

        <!-- Optional image upload -->
        <div class="form-group">
            <label>Image (optional, max 5 MB)</label>
            <input type="file" name="image" accept="image/*">
            <div class="helper">JPG, PNG, GIF, or WEBP</div>
        </div>

        <button type="submit" class="btn" style="width:100%;">Submit Found Report</button>

        <p class="helper" style="text-align:center; margin-top:12px;">
            <a href="dashboard.php">← Back to Dashboard</a>
        </p>
    </form>
</div>

<?php include 'includes/footer.php'; ?>