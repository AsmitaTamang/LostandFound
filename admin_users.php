<?php
/**
 * admin_users.php
 * ------------------------------------------------------------
 * PURPOSE: Admin views the list of all registered users.
 *
 * FLOW:
 *   1. Require admin role.
 *   2. Fetch all users from the users table.
 *   3. Show a table with ID, name, email, role, joined date,
 *      and a small summary of how many items each has posted.
 *
 * ACCESS: Admin only.
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
// Admin only.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

// ------------------------------------------------------------
// FETCH ALL USERS + THEIR ITEM COUNTS
// Subqueries count each user's lost and found reports.
// ------------------------------------------------------------
$users = $conn->query(
    "SELECT u.user_id, u.full_name, u.email, u.role, u.created_at,
            (SELECT COUNT(*) FROM lost_items  WHERE user_id = u.user_id) AS lost_count,
            (SELECT COUNT(*) FROM found_items WHERE user_id = u.user_id) AS found_count
     FROM users u
     ORDER BY u.created_at DESC"
);

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     MANAGE USERS PAGE
     ============================================================ -->
<div class="container" style="max-width:1100px;">

    <h2>Registered Users</h2>

    <p class="helper" style="margin-bottom:20px;">
        Total users: <strong><?php echo $users->num_rows; ?></strong>
    </p>

    <table style="width:100%; border-collapse:collapse; font-size:14px;">
        <thead>
            <tr style="background:linear-gradient(120deg,#b39ddb,#f4b6c5); color:white;">
                <th style="padding:10px; text-align:left;">ID</th>
                <th style="padding:10px; text-align:left;">Name</th>
                <th style="padding:10px; text-align:left;">Email</th>
                <th style="padding:10px; text-align:left;">Role</th>
                <th style="padding:10px; text-align:center;">Lost</th>
                <th style="padding:10px; text-align:center;">Found</th>
                <th style="padding:10px; text-align:left;">Joined</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($u = $users->fetch_assoc()): ?>
            <tr style="border-bottom:1px solid #f0e8f4;">

                <!-- User ID -->
                <td style="padding:8px;"><?php echo (int)$u['user_id']; ?></td>

                <!-- Full name -->
                <td style="padding:8px;"><?php echo htmlspecialchars($u['full_name']); ?></td>

                <!-- Email -->
                <td style="padding:8px;"><?php echo htmlspecialchars($u['email']); ?></td>

                <!-- Role badge -->
                <td style="padding:8px;">
                    <?php if ($u['role'] === 'admin'): ?>
                        <span style="background:linear-gradient(120deg,#d67b8b,#c85a6e);
                                     color:white; padding:3px 10px; border-radius:12px;
                                     font-size:12px;">
                            ADMIN
                        </span>
                    <?php else: ?>
                        <span style="background:#f5ebf5; color:#6b5b82;
                                     padding:3px 10px; border-radius:12px; font-size:12px;">
                            USER
                        </span>
                    <?php endif; ?>
                </td>

                <!-- Lost item count -->
                <td style="padding:8px; text-align:center;">
                    <?php echo (int)$u['lost_count']; ?>
                </td>

                <!-- Found item count -->
                <td style="padding:8px; text-align:center;">
                    <?php echo (int)$u['found_count']; ?>
                </td>

                <!-- Joined date -->
                <td style="padding:8px;">
                    <?php echo date('M j, Y', strtotime($u['created_at'])); ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <p style="margin-top:25px;">
        <a href="admin_dashboard.php" class="btn">← Back to Admin Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>