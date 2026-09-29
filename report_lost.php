<?php
/**
 * report_lost.php
 * ------------------------------------------------------------
 * PURPOSE: Form for users to report a LOST item.
 *          Includes: category dropdown, attributes (color,
 *          brand, location, date), image upload, and a link
 *          to suggest a new category.
 *
 * ACCESS: Logged-in user.
 *
 * FLOW:
 *   1. Require login.
 *   2. Load active categories for the dropdown.
 *   3. Validate form input and handle image upload.
 *   4. Insert the lost item into the database.
 *   5. Show either errors or a success message.
 * ------------------------------------------------------------
 */

// Show all errors while developing.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buffer output so headers can be sent later if needed.
ob_start();

// Connect to the database.
require_once 'dbconnect.php';

// Start session if not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// AUTHORIZATION CHECK
// Must be logged in.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// Holds validation errors and success message.
$errors  = [];
$success = '';

// ------------------------------------------------------------
// FETCH ACTIVE CATEGORIES FOR THE DROPDOWN
// ------------------------------------------------------------
$categories = [];
$cat_result = $conn->query("SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name");
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row;
}

// ------------------------------------------------------------
// HANDLE FORM SUBMISSION
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and clean all form inputs.
    $category_id = (int)($_POST['category_id'] ?? 0);
    $item_name   = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color       = trim($_POST['color'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $date_lost   = $_POST['date_lost'] ?? '';

    // Basic validation.
    if ($category_id <= 0)   $errors[] = "Please select a category.";
    if (empty($item_name))   $errors[] = "Item name is required.";
    if (empty($date_lost))   $errors[] = "Date lost is required.";

    // --------------------------------------------------------
    // HANDLE IMAGE UPLOAD (optional)
    // --------------------------------------------------------
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image must be under 5 MB.";
        } else {
            // Use absolute path — safer than relative.
            $upload_dir = __DIR__ . '/uploads/';

            // Create the folder if it doesn't exist.
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Make sure the folder is writable before saving.
            if (!is_writable($upload_dir)) {
                $errors[] = "Uploads folder is not writable. Check permissions.";
            } else {
                // Build a unique filename to avoid collisions.
                $filename = 'lost_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $target   = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    // Store only the relative path in the database.
                    $image_path = 'uploads/' . $filename;
                } else {
                    $errors[] = "Image upload failed.";
                }
            }
        }
    }

    // --------------------------------------------------------
    // INSERT INTO DATABASE (only if no errors)
    // --------------------------------------------------------
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO lost_items
             (user_id, category_id, item_name, description, color, brand, location, date_lost, image_path)
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
            $date_lost,
            $image_path
        );

        if ($stmt->execute()) {
            $success = "Lost item reported successfully!";
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Include the shared header/navigation.
include 'includes/header.php';
?>

<!-- ============================================================
     MAIN PAGE CONTAINER
     ============================================================ -->
<div class="container" style="max-width:600px;">
    <h2 style="margin-bottom:20px;">Report a Lost Item</h2>

    <?php if ($success): ?>
        <!-- Success message -->
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <!-- Validation errors -->
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <div>• <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- enctype is required for file uploads -->
    <form method="POST" enctype="multipart/form-data">

        <!-- Category dropdown -->
        <div class="form-group">
            <label>Category *</label>
            <select name="category_id" required>
                <option value="">-- Select a category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>">
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="helper">
                Can't find your category?
                <a href="suggest_category.php">Suggest a new one</a>
            </div>
        </div>

        <!-- Item name -->
        <div class="form-group">
            <label>Item Name *</label>
            <input type="text" name="item_name" required
                   placeholder="e.g., MacBook Pro 13-inch"
                   value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>">
        </div>

        <!-- Description -->
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"
                      placeholder="Any details that might help identify it"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <!-- Color -->
        <div class="form-group">
            <label>Color</label>
            <input type="text" name="color" placeholder="e.g., Silver"
                   value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>">
        </div>

        <!-- Brand -->
        <div class="form-group">
            <label>Brand</label>
            <input type="text" name="brand" placeholder="e.g., Apple"
                   value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>">
        </div>

        <!-- Location -->
        <div class="form-group">
            <label>Where did you lose it?</label>
            <input type="text" name="location" placeholder="e.g., Library, 2nd floor"
                   value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
        </div>

        <!-- Date lost -->
        <div class="form-group">
            <label>Date Lost *</label>
            <input type="date" name="date_lost" required
                   value="<?php echo htmlspecialchars($_POST['date_lost'] ?? date('Y-m-d')); ?>">
        </div>

        <!-- Optional image upload -->
        <div class="form-group">
            <label>Image (optional, max 5 MB)</label>
            <input type="file" name="image" accept="image/*">
        </div>

        <button type="submit" class="btn" style="width:100%;">Submit Report</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>