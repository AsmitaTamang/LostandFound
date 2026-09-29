<?php
/**
 * admin_claims.php
 * ------------------------------------------------------------
 * PURPOSE: Admin reviews user claims and approves or rejects.
 *
 * ACCESS: Admin only.
 *
 * FLOW:
 *   1. Start session and verify the user is an admin.
 *   2. Handle approve/reject form submissions.
 *   3. If approved, mark the claim as approved AND update
 *      both the lost and found items to status 'claimed'.
 *   4. If rejected, only update the claim status.
 *   5. Load pending claims with item and user details.
 *   6. Load recently resolved claims for reference.
 *   7. Render the management interface.
 * ------------------------------------------------------------
 */

// Show all errors while developing.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buffer output so headers can be sent later if needed.
ob_start();

// Connect to the database.
require_once 'dbconnect.php';

// Start the session.
@session_start();

// ------------------------------------------------------------
// AUTHORIZATION CHECK
// Only admins may access this page.
// If the user is not logged in, or is not an admin,
// redirect them to the normal dashboard.
// ------------------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

// Holds a success/status message for the admin.
$message = '';

// ------------------------------------------------------------
// HANDLE APPROVE / REJECT ACTIONS
// This runs when the admin submits one of the action buttons.
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get the claim ID and the chosen action.
    $claim_id = (int)($_POST['claim_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    // Only process if we have a valid ID and a valid action.
    if ($claim_id > 0 && in_array($action, ['approve', 'reject'])) {

        // ----------------------------------------------------
        // FETCH THE CLAIM
        // We need the lost_id and found_id so we can update
        // the related items if the claim is approved.
        // ----------------------------------------------------
        $stmt = $conn->prepare("SELECT * FROM claims WHERE claim_id = ?");
        $stmt->bind_param("i", $claim_id);
        $stmt->execute();
        $claim = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Only continue if the claim actually exists.
        if ($claim) {

            // Determine the new status based on the action.
            $new_status = ($action === 'approve') ? 'approved' : 'rejected';

            // ------------------------------------------------
            // UPDATE THE CLAIM STATUS
            // ------------------------------------------------
            $stmt = $conn->prepare("UPDATE claims SET status = ? WHERE claim_id = ?");
            $stmt->bind_param("si", $new_status, $claim_id);
            $stmt->execute();
            $stmt->close();

            // ------------------------------------------------
            // IF APPROVED: MARK BOTH ITEMS AS 'CLAIMED'
            // This prevents other users from claiming them.
            // We cast IDs to int for safety before injecting
            // them into the query.
            // ------------------------------------------------
            if ($action === 'approve') {
                $conn->query("UPDATE lost_items SET status='claimed' WHERE lost_id=" . (int)$claim['lost_id']);
                $conn->query("UPDATE found_items SET status='claimed' WHERE found_id=" . (int)$claim['found_id']);
            }

            // Build a success message.
            $message = "Claim " . htmlspecialchars($new_status) . " successfully.";
        }
    }
}

// ------------------------------------------------------------
// FETCH PENDING CLAIMS
// Joins lost items, found items, and users so the admin can
// see the full context of each claim request.
// Only shows claims with status = 'pending'.
// ------------------------------------------------------------
$pending = $conn->query(
    "SELECT cl.*,
            li.item_name AS lost_name,
            fi.item_name AS found_name,
            u.full_name AS claimant_name,
            u.email AS claimant_email
     FROM claims cl
     LEFT JOIN lost_items li ON cl.lost_id = li.lost_id
     LEFT JOIN found_items fi ON cl.found_id = fi.found_id
     LEFT JOIN users u ON cl.claimant_id = u.user_id
     WHERE cl.status = 'pending'
     ORDER BY cl.created_at ASC"
);

// ------------------------------------------------------------
// FETCH RESOLVED CLAIMS
// Shows the most recent 20 approved or rejected claims so the
// admin can review past decisions.
// ------------------------------------------------------------
$resolved = $conn->query(
    "SELECT cl.*,
            li.item_name AS lost_name,
            fi.item_name AS found_name,
            u.full_name AS claimant_name
     FROM claims cl
     LEFT JOIN lost_items li ON cl.lost_id = li.lost_id
     LEFT JOIN found_items fi ON cl.found_id = fi.found_id
     LEFT JOIN users u ON cl.claimant_id = u.user_id
     WHERE cl.status IN ('approved','rejected')
     ORDER BY cl.created_at DESC
     LIMIT 20"
);

// Include the shared header/navigation.
include 'includes/header.php';
?>

<!-- ============================================================
     MAIN PAGE CONTAINER
     ============================================================ -->
<div class="container" style="max-width:1100px;">
    <h2 style="margin-bottom:20px;">Manage Claims</h2>

    <!-- Display success/status message if one exists -->
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- ============================================================
         PENDING CLAIMS SECTION
         Shows claims that need admin review.
         ============================================================ -->
    <h3 style="margin-top:20px;">Pending Claims (<?php echo $pending->num_rows; ?>)</h3>

    <?php if ($pending->num_rows === 0): ?>
        <!-- Message shown when there are no pending claims -->
        <p class="helper">No pending claims.</p>
    <?php else: ?>

        <!-- Table of pending claims -->
        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
            <thead>
                <tr style="background:#041E60; color:white;">
                    <th style="padding:10px; text-align:left;">Lost Item</th>
                    <th style="padding:10px; text-align:left;">Found Item</th>
                    <th style="padding:10px; text-align:left;">Claimant</th>
                    <th style="padding:10px; text-align:left;">Date</th>
                    <th style="padding:10px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $pending->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <!-- Lost item name -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['lost_name']); ?></td>

                    <!-- Found item name -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['found_name']); ?></td>

                    <!-- Claimant name and email -->
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['claimant_name']); ?><br>
                        <small style="color:#888;"><?php echo htmlspecialchars($row['claimant_email']); ?></small>
                    </td>

                    <!-- Date the claim was created -->
                    <td style="padding:8px;"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>

                    <!-- Approve / Reject buttons -->
                    <td style="padding:8px; text-align:center;">
                        <form method="POST" style="display:inline;">
                            <!-- Hidden field identifies which claim is being reviewed -->
                            <input type="hidden" name="claim_id" value="<?php echo $row['claim_id']; ?>">

                            <!-- Approve button -->
                            <button type="submit" name="action" value="approve"
                                    style="background:#28a745; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer;">
                                ✓ Approve
                            </button>

                            <!-- Reject button -->
                            <button type="submit" name="action" value="reject"
                                    style="background:#dc3545; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; margin-left:5px;">
                                ✗ Reject
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- ============================================================
         RECENT RESOLVED CLAIMS SECTION
         Shows claims that have already been approved or rejected.
         ============================================================ -->
    <h3 style="margin-top:40px;">Recent Resolved Claims</h3>

    <?php if ($resolved->num_rows === 0): ?>
        <!-- Message shown when there are no resolved claims yet -->
        <p class="helper">No resolved claims yet.</p>
    <?php else: ?>

        <!-- Table of resolved claims -->
        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
            <thead>
                <tr style="background:#888; color:white;">
                    <th style="padding:10px; text-align:left;">Lost</th>
                    <th style="padding:10px; text-align:left;">Found</th>
                    <th style="padding:10px; text-align:left;">Claimant</th>
                    <th style="padding:10px; text-align:left;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $resolved->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <!-- Lost item name -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['lost_name']); ?></td>

                    <!-- Found item name -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['found_name']); ?></td>

                    <!-- Claimant name -->
                    <td style="padding:8px;"><?php echo htmlspecialchars($row['claimant_name']); ?></td>

                    <!-- Status badge (approved or rejected) -->
                    <td style="padding:8px;">
                        <?php if ($row['status'] === 'approved'): ?>
                            <span style="background:#d4edda; color:#155724; padding:3px 10px; border-radius:10px; font-size:12px;">APPROVED</span>
                        <?php else: ?>
                            <span style="background:#f8d7da; color:#721c24; padding:3px 10px; border-radius:10px; font-size:12px;">REJECTED</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Back link to admin dashboard -->
    <p style="margin-top:30px;">
        <a href="admin_dashboard.php" class="btn">← Back to Admin Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>