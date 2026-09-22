<?php
/**
 * dashboard.php
 * ------------------------------------------------------------
 * PURPOSE: A simple page shown after login.
 *          For now it just greets the user. We'll replace it
 *          with a real dashboard later.
 * ------------------------------------------------------------
 */

require_once 'dbconnect.php';
session_start();

// If user is not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'includes/header.php';
?>

<div class="container" style="max-width:700px;">
    <h2 style="margin-bottom:15px;">
        Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
    </h2>

    <div class="alert alert-success">
        You are logged in as <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>.
    </div>

    <p style="margin-bottom:20px;">What you can do here:</p>
    <ul style="margin-left:20px; line-height:2;">
        <li>Report a lost item (coming soon)</li>
        <li>Report a found item (coming soon)</li>
        <li>Search for matches (coming soon)</li>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <li><strong>Admin:</strong> Approve category suggestions (coming soon)</li>
            <li><strong>Admin:</strong> Manage claims (coming soon)</li>
        <?php endif; ?>
    </ul>

    <p class="helper" style="margin-top:20px;">
        Debug info: user_id = <?php echo (int)$_SESSION['user_id']; ?>,
        email = <?php echo htmlspecialchars($_SESSION['email']); ?>
    </p>
</div>

<?php include 'includes/footer.php'; ?>