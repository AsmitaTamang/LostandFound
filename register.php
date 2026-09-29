<?php
/**
 * register.php
 * ------------------------------------------------------------
 * PURPOSE: Allows new users to create an account.
 *
 * ACCESS: Public.
 *
 * FLOW:
 *   1. If already logged in, go to dashboard.
 *   2. Validate name, email, password, and confirm password.
 *   3. Check the email isn't already registered.
 *   4. Hash the password and insert the new user.
 *   5. Redirect to login with a flash success message.
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
    @session_start();
}

// ------------------------------------------------------------
// ALREADY LOGGED IN?
// Redirect to dashboard.
// ------------------------------------------------------------
if (isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

// Holds validation errors.
$errors = [];

// ------------------------------------------------------------
// HANDLE FORM SUBMISSION
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Temporary debug line — can be removed later.
    echo "DEBUG: Form submitted<br>";

    // Get and clean inputs.
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Basic validation.
    if (empty($full_name))  $errors[] = "Full name is required.";
    if (empty($email))      $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (strlen($password) < 6)  $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    // --------------------------------------------------------
    // CHECK IF EMAIL IS ALREADY REGISTERED
    // --------------------------------------------------------
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered.";
        }
        $stmt->close();
    }

    // --------------------------------------------------------
    // CREATE THE ACCOUNT
    // --------------------------------------------------------
    if (empty($errors)) {
        // Hash the password before storing.
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, password, role)
             VALUES (?, ?, ?, 'user')"
        );
        $stmt->bind_param("sss", $full_name, $email, $hashed);

        if ($stmt->execute()) {
            // Flash a success message for the login page.
            $_SESSION['flash_success'] = "Account created successfully! Please log in.";
            ob_end_clean();
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Something went wrong. Try again.";
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
<div class="container">
    <h2>Create an Account</h2>

    <?php if (!empty($errors)): ?>
        <!-- Validation errors -->
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <div>• <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <!-- Full name -->
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <!-- Password -->
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <!-- Confirm password -->
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn" style="width:100%;">Register</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>