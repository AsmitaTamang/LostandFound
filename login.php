<?php
/**
 * login.php
 * ------------------------------------------------------------
 * PURPOSE: Allows existing users to log in.
 *
 * FLOW:
 *   1. If already logged in → redirect to dashboard.
 *   2. If form submitted → look up user by email.
 *   3. Verify password against stored hash.
 *   4. Store user info in session, redirect to dashboard.
 *
 * FALLBACK BEHAVIOUR:
 *   If a user's password is stored as plain text (legacy seed
 *   data), the login succeeds and the password is upgraded to
 *   a hashed version automatically. This avoids breaking old
 *   accounts while still moving everything to bcrypt.
 *
 * SECURITY:
 *   - Prepared statements prevent SQL injection.
 *   - password_verify() protects against timing attacks.
 *   - Generic "Incorrect email or password" message avoids
 *     leaking whether an account exists.
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

// If already logged in, go straight to the dashboard.
if (isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

// Array to collect validation / login errors.
$errors = [];

// ------------------------------------------------------------
// HANDLE FORM SUBMISSION
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read and trim inputs.
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // --------------------------------------------------------
    // BASIC VALIDATION
    // --------------------------------------------------------
    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both email and password.";
    } else {

        // ----------------------------------------------------
        // LOOK UP USER BY EMAIL
        // ----------------------------------------------------
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
            // CASE 1: Password stored as a bcrypt hash.
            // Verify with password_verify().
            // ------------------------------------------------
            if (password_verify($password, $user['password'])) {

                // Store user info in session.
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
            // CASE 2: Legacy plain-text password.
            // If it matches exactly, upgrade it to a hash
            // and log the user in.
            // ------------------------------------------------
            elseif ($user['password'] === $password) {

                // Hash the plaintext password and store it.
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upgrade  = $conn->prepare(
                    "UPDATE users SET password = ? WHERE user_id = ?"
                );
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
            // CASE 3: Password mismatch.
            // ------------------------------------------------
            else {
                $errors[] = "Incorrect email or password.";
            }

        } else {
            // No user with that email.
            $errors[] = "Incorrect email or password.";
        }

        $stmt->close();
    }
}

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     LOGIN PAGE
     ============================================================ -->
<div class="container">
    <h2>Log In</h2>

    <!-- Success message from registration (one-time) -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?php
                echo htmlspecialchars($_SESSION['flash_success']);
                unset($_SESSION['flash_success']);   // only show once
            ?>
        </div>
    <?php endif; ?>

    <!-- Show any validation / login errors -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <div>• <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Login form — posts back to this same file -->
    <form method="POST" action="login.php">

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn" style="width:100%;">Log In</button>

        <p class="helper" style="text-align:center; margin-top:12px;">
            Don't have an account? <a href="register.php">Register</a>
        </p>
    </form>
</div>

<?php include 'includes/footer.php'; ?>