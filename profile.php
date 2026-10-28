<?php
/**
 * profile.php
 * ------------------------------------------------------------
 * PURPOSE: Allows the logged-in user to view and update
 *          their profile (name, email, password).
 *
 * FLOW:
 *   1. Require login.
 *   2. Handle POST based on the 'action' field:
 *        - update_profile  → change name / email
 *        - change_password → verify current password, set new one
 *   3. Fetch current user row and render two forms.
 *
 * SECURITY:
 *   - Prepared statements prevent SQL injection.
 *   - Email uniqueness checked against other users.
 *   - Current password verified before changing it.
 *   - New password re-hashed with bcrypt.
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

// Arrays for messages.
$errors  = [];
$success = '';

// ------------------------------------------------------------
// HANDLE FORM SUBMISSIONS
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Which form was submitted? Defaults to profile update.
    $action = $_POST['action'] ?? 'update_profile';

    // ============================================================
    // ACTION 1: UPDATE PROFILE (name + email)
    // ============================================================
    if ($action === 'update_profile') {

        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');

        // Validation.
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        }
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        // Check the email isn't used by a DIFFERENT user.
        if (empty($errors)) {
            $stmt = $conn->prepare(
                "SELECT user_id FROM users
                 WHERE email = ? AND user_id != ?"
            );
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "Email already used by another account.";
            }
            $stmt->close();
        }

        // Update the row and refresh the session values.
        if (empty($errors)) {
            $stmt = $conn->prepare(
                "UPDATE users SET full_name=?, email=? WHERE user_id=?"
            );
            $stmt->bind_param("ssi", $full_name, $email, $user_id);

            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email']     = $email;
                $success = "Profile updated successfully.";
            } else {
                $errors[] = "Update failed.";
            }
            $stmt->close();
        }
    }

    // ============================================================
    // ACTION 2: CHANGE PASSWORD
    // ============================================================
    elseif ($action === 'change_password') {

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Validation.
        if (strlen($new) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        }
        if ($new !== $confirm) {
            $errors[] = "New passwords do not match.";
        }

        if (empty($errors)) {

            // Look up the current hashed password.
            $stmt = $conn->prepare(
                "SELECT password FROM users WHERE user_id=?"
            );
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Verify the current password before allowing change.
            if (!password_verify($current, $row['password'])) {
                $errors[] = "Current password is incorrect.";
            } else {

                // Hash the new password and update.
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "UPDATE users SET password=? WHERE user_id=?"
                );
                $stmt->bind_param("si", $new_hash, $user_id);

                if ($stmt->execute()) {
                    $success = "Password changed successfully.";
                } else {
                    $errors[] = "Password update failed.";
                }
                $stmt->close();
            }
        }
    }
}

// ------------------------------------------------------------
// FETCH THE CURRENT USER'S DETAILS FOR DISPLAY
// ------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT full_name, email, role, created_at
     FROM users WHERE user_id=?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     PROFILE PAGE
     ============================================================ -->
<div class="container" style="max-width:600px;">

    <h2>My Profile</h2>

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
         READ-ONLY INFO
         Role and account creation date cannot be changed by the user.
         ============================================================ -->
    <div style="background:#fdf6f9; padding:16px; border-radius:14px;
                margin-bottom:25px; font-size:14px;
                border:1px solid #f0e8f4; color:#6b5b82;">
        <p><strong>Role:</strong>
            <?php echo htmlspecialchars($user['role']); ?>
        </p>
        <p><strong>Member since:</strong>
            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
        </p>
    </div>

    <!-- ============================================================
         FORM 1: UPDATE PROFILE
         ============================================================ -->
    <h3 style="margin-bottom:15px; color:#6b5b82;">Update Profile</h3>

    <form method="POST">
        <input type="hidden" name="action" value="update_profile">

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required
                   value="<?php echo htmlspecialchars($user['full_name']); ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required
                   value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>

        <button type="submit" class="btn" style="width:100%;">Save Profile</button>
    </form>

    <!-- ============================================================
         FORM 2: CHANGE PASSWORD
         ============================================================ -->
    <h3 style="margin-top:35px; margin-bottom:15px; color:#6b5b82;">Change Password</h3>

    <form method="POST">
        <input type="hidden" name="action" value="change_password">

        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>

        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" minlength="6" required>
            <div class="helper">At least 6 characters.</div>
        </div>

        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn"
                style="width:100%; background:linear-gradient(120deg,#a5d8b8,#c8e6d0);">
            Change Password
        </button>
    </form>

    <p style="margin-top:25px; text-align:center;">
        <a href="dashboard.php" class="helper">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>