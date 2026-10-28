<?php
/**
 * register.php
 * ------------------------------------------------------------
 * PURPOSE: Allows new users to create an account.
 *
 * FLOW:
 *   1. If already logged in → redirect to dashboard.
 *   2. If form submitted → validate inputs.
 *   3. Check email not already used.
 *   4. Hash password, insert user, redirect to login.
 *
 * SECURITY:
 *   - Passwords hashed with bcrypt (password_hash).
 *   - Prepared statements prevent SQL injection.
 *   - Output escaped with htmlspecialchars to prevent XSS.
 *   - ob_start / ob_end_clean used so headers can be sent
 *     after any output buffer has been cleared.
 * ------------------------------------------------------------
 */

// Start output buffering FIRST so header() calls work later,
// even if something was echoed earlier.
ob_start();

// Show all errors during development.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database.
require_once 'dbconnect.php';

// Start the session if not already started.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// If the user is already logged in, send them to the dashboard.
if (isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

// Array to collect validation / DB errors.
$errors = [];

// ------------------------------------------------------------
// HANDLE FORM SUBMISSION
// Only runs on POST (i.e. the user clicked "Register").
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read and trim the form values. The ?? '' avoids warnings
    // if a field is missing entirely.
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // --------------------------------------------------------
    // VALIDATION
    // --------------------------------------------------------
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // --------------------------------------------------------
    // CHECK EMAIL NOT ALREADY USED
    // --------------------------------------------------------
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "SELECT user_id FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered.";
        }
        $stmt->close();
    }

    // --------------------------------------------------------
    // INSERT THE NEW USER
    // --------------------------------------------------------
    if (empty($errors)) {

        // Hash the password with bcrypt (never store plain text).
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Insert the user as role 'user' by default.
        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, password, role)
             VALUES (?, ?, ?, 'user')"
        );
        $stmt->bind_param("sss", $full_name, $email, $hashed);

        if ($stmt->execute()) {

            // Set a one-time flash message for the login page.
            $_SESSION['flash_success'] = "Account created successfully! Please log in.";

            // Clear the output buffer before redirecting.
            ob_end_clean();
            header("Location: login.php");
            exit;

        } else {
            $errors[] = "Something went wrong. Try again.";
        }
        $stmt->close();
    }
}

// Now we can start outputting HTML — include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     REGISTRATION PAGE
     ============================================================ -->
<div class="container">
    <h2>Create an Account</h2>

    <!-- Show any validation errors collected above -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <div>• <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Registration form — posts back to this same file -->
    <form method="POST" action="register.php">

        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required
                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   minlength="6" required>
            <div class="helper">At least 6 characters.</div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password"
                   name="confirm_password" required>
        </div>

        <button type="submit" class="btn" style="width:100%;">Register</button>

        <p class="helper" style="text-align:center; margin-top:12px;">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </form>
</div>

<?php include 'includes/footer.php'; ?>