<?php
/**
 * edit_item.php
 * ------------------------------------------------------------
 * PURPOSE: Allows users to edit or delete their own reports.
 *
 * URL PARAMETERS:
 *   ?type=lost&id=123
 *   ?type=found&id=456
 *
 * FLOW:
 *   1. Require login.
 *   2. Verify the item exists and belongs to the current user.
 *   3. Handle POST actions:
 *        - update  → validate, optionally upload new image, UPDATE row
 *        - delete  → remove image file, DELETE row
 *   4. Show the edit form (or success/error messages).
 *
 * SECURITY:
 *   - Ownership check on every action.
 *   - Prepared statements prevent SQL injection.
 *   - File extension + size validation for uploads.
 *   - Confirmation prompt on delete (client side).
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
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// Cache the current user's ID.
$user_id = (int)$_SESSION['user_id'];

// ------------------------------------------------------------
// READ type + id FROM GET OR POST
// ------------------------------------------------------------
$type = $_GET['type'] ?? $_POST['type'] ?? 'lost';
$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Only allow the two known types (guards against path traversal
// or arbitrary table names in the SQL below).
if (!in_array($type, ['lost', 'found'])) {
    ob_end_clean();
    header("Location: my_reports.php");
    exit;
}

// Arrays for messages.
$errors  = [];
$success = '';

// ------------------------------------------------------------
// TABLE + COLUMN NAMES DEPEND ON TYPE
// ------------------------------------------------------------
$table       = ($type === 'lost') ? 'lost_items' : 'found_items';
$id_column   = ($type === 'lost') ? 'lost_id'    : 'found_id';
$date_column = ($type === 'lost') ? 'date_lost'  : 'date_found';
$date_label  = ($type === 'lost') ? 'Date Lost'  : 'Date Found';

// ------------------------------------------------------------
// HANDLE DELETE
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

    // Fetch image path first so we can clean up the file.
    $stmt = $conn->prepare(
        "SELECT image_path FROM $table WHERE $id_column = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $errors[] = "Item not found or you don't own it.";
    } else {

        // Delete the image file from disk (if it exists).
        if (!empty($row['image_path']) && file_exists($row['image_path'])) {
            @unlink($row['image_path']);
        }

        // Delete the row itself.
        $stmt = $conn->prepare(
            "DELETE FROM $table WHERE $id_column = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute()) {
            $stmt->close();
            ob_end_clean();
            $_SESSION['flash_success'] = ucfirst($type) . " item deleted successfully.";
            header("Location: my_reports.php");
            exit;
        } else {
            $errors[] = "Failed to delete item.";
        }
        $stmt->close();
    }
}

// ------------------------------------------------------------
// HANDLE EDIT (UPDATE)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {

    // Read and clean inputs.
    $category_id = (int)($_POST['category_id'] ?? 0);
    $item_name   = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color       = trim($_POST['color'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $date_val    = $_POST['date_val'] ?? '';

    // Validation.
    if ($category_id <= 0) $errors[] = "Category is required.";
    if (empty($item_name)) $errors[] = "Item name is required.";
    if (empty($date_val))  $errors[] = "Date is required.";

    // --------------------------------------------------------
    // OPTIONAL NEW IMAGE UPLOAD
    // --------------------------------------------------------
    $new_image_path = null;

    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Only JPG, PNG, GIF, WEBP images allowed.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image must be under 5 MB.";
        } else {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename = $type . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target   = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $new_image_path = 'uploads/' . $filename;
            } else {
                $errors[] = "Image upload failed.";
            }
        }
    }

    // --------------------------------------------------------
    // RUN THE UPDATE
    // If a new image was uploaded, include image_path; otherwise
    // keep the existing one untouched.
    // --------------------------------------------------------
    if (empty($errors)) {

        if ($new_image_path) {
            // Update including new image.
            $sql = "UPDATE $table
                    SET category_id=?, item_name=?, description=?,
                        color=?, brand=?, location=?, $date_column=?,
                        image_path=?
                    WHERE $id_column=? AND user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "isssssssii",
                $category_id, $item_name, $description,
                $color, $brand, $location, $date_val,
                $new_image_path, $id, $user_id
            );
        } else {
            // Update without touching image_path.
            $sql = "UPDATE $table
                    SET category_id=?, item_name=?, description=?,
                        color=?, brand=?, location=?, $date_column=?
                    WHERE $id_column=? AND user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "issssssii",
                $category_id, $item_name, $description,
                $color, $brand, $location, $date_val,
                $id, $user_id
            );
        }

        if ($stmt->execute()) {
            $success = "Item updated successfully!";
        } else {
            $errors[] = "Update failed: " . $conn->error;
        }
        $stmt->close();
    }
}

// ------------------------------------------------------------
// LOAD THE ITEM FOR DISPLAY (or to confirm ownership)
// ------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT * FROM $table WHERE $id_column = ? AND user_id = ?"
);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If no row matched, the item either doesn't exist or isn't
// owned by the current user → redirect away.
if (!$item) {
    ob_end_clean();
    $_SESSION['flash_error'] = "Item not found or you don't own it.";
    header("Location: my_reports.php");
    exit;
}

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

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     EDIT ITEM PAGE
     ============================================================ -->
<div class="container" style="max-width:650px;">

    <h2>Edit <?php echo ucfirst($type); ?> Item</h2>

    <p class="helper" style="margin-bottom:20px;">
        Update the details or delete this report.
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
         EDIT FORM
         enctype="multipart/form-data" is required for file uploads.
         ============================================================ -->
    <form method="POST" enctype="multipart/form-data">

        <!-- Hidden fields: action + type + id -->
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="type"   value="<?php echo htmlspecialchars($type); ?>">
        <input type="hidden" name="id"     value="<?php echo (int)$id; ?>">

        <!-- Category -->
        <div class="form-group">
            <label>Category *</label>
            <select name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>"
                        <?php if ($cat['category_id'] == $item['category_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Item name -->
        <div class="form-group">
            <label>Item Name *</label>
            <input type="text" name="item_name" required
                   value="<?php echo htmlspecialchars($item['item_name']); ?>">
        </div>

        <!-- Description -->
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
        </div>

        <!-- Color and Brand side by side -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color"
                       value="<?php echo htmlspecialchars($item['color'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Brand</label>
                <input type="text" name="brand"
                       value="<?php echo htmlspecialchars($item['brand'] ?? ''); ?>">
            </div>
        </div>

        <!-- Location -->
        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location"
                   value="<?php echo htmlspecialchars($item['location'] ?? ''); ?>">
        </div>

        <!-- Date (label + column differ by type) -->
        <div class="form-group">
            <label><?php echo $date_label; ?> *</label>
            <input type="date" name="date_val" required
                   value="<?php echo htmlspecialchars($item[$date_column]); ?>">
        </div>

        <!-- Current image preview -->
        <div class="form-group">
            <label>Current Image</label><br>
            <?php if (!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
                     style="width:100px; height:100px; object-fit:cover;
                            border-radius:10px; border:2px solid #f4b6c5;">
            <?php else: ?>
                <span class="helper">No image uploaded.</span>
            <?php endif; ?>
        </div>

        <!-- New image (optional) -->
        <div class="form-group">
            <label>Replace Image (optional, max 5 MB)</label>
            <input type="file" name="image" accept="image/*">
        </div>

        <button type="submit" class="btn" style="width:100%;">Save Changes</button>
    </form>

    <!-- ============================================================
         DELETE FORM
         Uses a client-side confirmation prompt before submitting.
         ============================================================ -->
    <form method="POST"
          onsubmit="return confirm('Are you sure you want to delete this item? This cannot be undone.');"
          style="margin-top:15px;">

        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="type"   value="<?php echo htmlspecialchars($type); ?>">
        <input type="hidden" name="id"     value="<?php echo (int)$id; ?>">

        <button type="submit"
                style="width:100%; padding:11px; background:linear-gradient(120deg,#d67b8b,#c85a6e);
                       color:white; border:none; border-radius:24px; cursor:pointer;
                       font-size:15px; font-family:'Georgia',serif;
                       box-shadow:0 3px 10px rgba(200,90,110,0.4);">
            Delete This Item
        </button>
    </form>

    <p style="margin-top:20px; text-align:center;">
        <a href="my_reports.php" class="helper">← Back to My Reports</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>