<?php
/**
 * login.php
 * ------------------------------------------------------------
 * PURPOSE: Allows existing users to log in.
 * ------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
require_once 'dbconnect.php';

// DO NOT call session_start() here — header.php does it

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, email, password, role
             FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Start session (header.php will check and skip if already started)
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                ob_end_clean();
                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Incorrect email or password.";
            }
        } else {
            $errors[] = "Incorrect email or password.";
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <h2 style="margin-bottom:20px;">Log In</h2>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <div>• <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>

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