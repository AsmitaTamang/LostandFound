<?php
/**
 * login.php
 * ------------------------------------------------------------
 * PURPOSE: Allows existing users to log in.
 *
 * ACCESS: Public.
 *
 * FLOW:
 *   1. If already logged in, go straight to dashboard.
 *   2. On POST, look up the user by email.
 *   3. Verify the password (supports both hashed and legacy
 *      plain-text passwords; the latter are auto-upgraded).
 *   4. On success, set session variables and redirect.
 *   5. On failure, show an error message.
 * ------------------------------------------------------------
 */

// Show all errors while developing.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buffer output so headers can be sent later if needed.
ob_start();

// Connect to the database.
require_once 'dbconnect.php';

// Start the session (suppressed warning if already started).
@session_start();

// ------------------------------------------------------------
// ALREADY LOGGED IN?
// Skip the form and go straight to the dashboard.
// ------------------------------------------------------------
if (isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

// Holds validation/login errors.
$errors = [];

// ------------------------------------------------------------
// HANDLE FORM SUBMISSION
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and clean input.
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both email and password.";
    } else {

        // Look up the user by email.
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, email, password, role
             FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // ------------------------------------------------
            // CASE 1: Password is properly hashed
            // ------------------------------------------------
            if (password_verify($password, $user['password'])) {

                // Set session variables for the logged-in user.
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                $stmt->close();
                ob_end_clean();
                header("Location: dashboard.php");
                exit;
            }
            // ------------------------------------------------
            // CASE 2: Password stored as plain text (legacy)
            //         Match it, then upgrade to a hashed version.
            // ------------------------------------------------
            elseif ($user['password'] === $password) {

                // Upgrade to a hashed password.
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upgrade = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $upgrade->bind_param("si", $new_hash, $user['user_id']);
                $upgrade->execute();
                $upgrade->close();

                // Log the user in.
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                $stmt->close();
                ob_end_clean();
                header("Location: dashboard.php");
                exit;
            }
            // ------------------------------------------------
            // CASE 3: Wrong password
            // ------------------------------------------------
            else {
                $errors[] = "Incorrect email or password.";
            }

        } else {
            $errors[] = "Incorrect email or password.";
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
    <h2 style="margin-bottom:20px;">Log In</h2>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <!-- One-time success message from register.php -->
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <!-- Login errors -->
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <div>• <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <!-- Email field (pre-filled on failed submission) -->
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <!-- Password field -->
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn" style="width:100%;">Log In</button>

        <p class="helper" style="text-align:center;">
            Don't have an account? <a href="register.php">Register</a>
        </p>
    </form>
</div>

<?php include 'includes/footer.php'; ?>