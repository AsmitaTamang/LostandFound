<?php
/**
 * profile.php
 * ------------------------------------------------------------
 * PURPOSE: User can view and update their profile.
 *          Change name, email, or password.
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

$user_id = (int)$_SESSION['user_id'];
$errors  = [];
$success = '';

// ------------------------------------------------------------
// Handle form submission
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');

        if (empty($full_name)) $errors[] = "Full name is required.";
        if (empty($email))     $errors[] = "Email is required.";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";

        // Check email not used by another user
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "Email already used by another account.";
            $stmt->close();
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE user_id=?");
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

    elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($new) < 6) $errors[] = "New password must be at least 6 characters.";
        if ($new !== $confirm) $errors[] = "New passwords do not match.";

        if (empty($errors)) {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id=?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!password_verify($current, $row['password'])) {
                $errors[] = "Current password is incorrect.";
            } else {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
                $stmt->bind_param("si", $new_hash, $user_id);
                if ($stmt->execute()) $success = "Password changed successfully.";
                else $errors[] = "Password update failed.";
                $stmt->close();
            }
        }
    }
}

// ------------------------------------------------------------
// Fetch current user data
// ------------------------------------------------------------
$stmt = $conn->prepare("SELECT full_name, email, role, created_at FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

include 'includes/header.php';
?>

<div class="container" style="max-width:600px;">
    <h2 style="margin-bottom:20px;">My Profile</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <div>• <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- PROFILE INFO -->
    <div style="background:#f5f5f5; padding:15px; border-radius:8px; margin-bottom:25px; font-size:14px;">
        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
        <p><strong>Member since:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
    </div>

    <!-- UPDATE PROFILE FORM -->
    <h3 style="margin-bottom:15px;">Update Profile</h3>
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

    <!-- CHANGE PASSWORD FORM -->
    <h3 style="margin-top:35px; margin-bottom:15px;">Change Password</h3>
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

        <button type="submit" class="btn" style="width:100%; background:#8b6a00;">Change Password</button>
    </form>

    <p style="margin-top:25px; text-align:center;">
        <a href="dashboard.php" class="helper">← Back to Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>