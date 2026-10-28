<?php
/**
 * admin_claims.php
 * ------------------------------------------------------------
 * PURPOSE: Admin reviews user claims and either approves or
 *          rejects them.
 *
 * FLOW:
 *   1. Require admin role.
 *   2. On POST (approve/reject):
 *        - Load the claim and its linked items.
 *        - Update claims.status.
 *        - If approved, mark BOTH the lost item and found item
 *          as status='claimed' so they leave the open pool.
 *   3. Show pending claims table with action buttons, plus a
 *      read-only table of recently resolved claims.
 *
 * SECURITY:
 *   - Admin-only access.
 *   - Prepared statements prevent SQL injection.
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

// Variable to hold a status message.
$message = '';

// ------------------------------------------------------------
// HANDLE APPROVE / REJECT
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $claim_id = (int)($_POST['claim_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    if ($claim_id > 0 && in_array($action, ['approve', 'reject'])) {

        // Load the claim row so we know which lost/found items
        // are involved (needed if we approve).
        $stmt = $conn->prepare(
            "SELECT * FROM claims WHERE claim_id = ?"
        );
        $stmt->bind_param("i", $claim_id);
        $stmt->execute();
        $claim = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($claim) {

            // New status value for the claim.
            $new_status = ($action === 'approve') ? 'approved' : 'rejected';

            // Update the claim's status.
            $stmt = $conn->prepare(
                "UPDATE claims SET status = ? WHERE claim_id = ?"
            );
            $stmt->bind_param("si", $new_status, $claim_id);
            $stmt->execute();
            $stmt->close();

            // If approved, mark BOTH items as claimed so they
            // no longer appear in the open pool for matching.
            if ($action === 'approve') {
                $conn->query(
                    "UPDATE lost_items
                     SET status='claimed'
                     WHERE lost_id=" . (int)$claim['lost_id']
                );
                $conn->query(
                    "UPDATE found_items
                     SET status='claimed'
                     WHERE found_id=" . (int)$claim['found_id']
                );
            }

            $message = "Claim " . htmlspecialchars($new_status) . " successfully.";
        }
    }
}

// ------------------------------------------------------------
// FETCH PENDING CLAIMS
// Includes item names and the claimant's name/email.
// ------------------------------------------------------------
$pending = $conn->query(
    "SELECT cl.*,
            li.item_name AS lost_name,
            fi.item_name AS found_name,
            u.full_name AS claimant_name,
            u.email AS claimant_email
     FROM claims cl
     LEFT JOIN lost_items  li ON cl.lost_id     = li.lost_id
     LEFT JOIN found_items fi ON cl.found_id    = fi.found_id
     LEFT JOIN users       u  ON cl.claimant_id = u.user_id
     WHERE cl.status = 'pending'
     ORDER BY cl.created_at ASC"
);

// ------------------------------------------------------------
// FETCH RECENTLY RESOLVED CLAIMS
// Last 20 resolved (approved or rejected), newest first.
// ------------------------------------------------------------
$resolved = $conn->query(
    "SELECT cl.*,
            li.item_name AS lost_name,
            fi.item_name AS found_name,
            u.full_name AS claimant_name
     FROM claims cl
     LEFT JOIN lost_items  li ON cl.lost_id     = li.lost_id
     LEFT JOIN found_items fi ON cl.found_id    = fi.found_id
     LEFT JOIN users       u  ON cl.claimant_id = u.user_id
     WHERE cl.status IN ('approved','rejected')
     ORDER BY cl.created_at DESC
     LIMIT 20"
);

// Include the shared header.
include 'includes/header.php';
?>

<!-- ============================================================
     MANAGE CLAIMS PAGE
     ============================================================ -->
<div class="container" style="max-width:1100px;">

    <h2>Manage Claims</h2>

    <!-- Status message after an approve/reject -->
    <?php if ($message): ?>
        <div class="alert alert-success">🌸 <?php echo $message; ?></div>
    <?php endif; ?>

    <!-- ============================================================
         PENDING CLAIMS TABLE
         Each row has Approve and Reject buttons.
         ============================================================ -->
    <h3 style="margin-top:20px; color:#6b5b82;">
        Pending Claims (<?php echo $pending->num_rows; ?>)
    </h3>

    <?php if ($pending->num_rows === 0): ?>

        <p class="helper">No pending claims.</p>

    <?php else: ?>

        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
            <thead>
                <tr style="background:linear-gradient(120deg,#b39ddb,#f4b6c5); color:white;">
                    <th style="padding:10px; text-align:left;">Lost Item</th>
                    <th style="padding:10px; text-align:left;">Found Item</th>
                    <th style="padding:10px; text-align:left;">Claimant</th>
                    <th style="padding:10px; text-align:left;">Date</th>
                    <th style="padding:10px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $pending->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #f0e8f4;">

                    <!-- Lost item name -->
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['lost_name'] ?? '—'); ?>
                    </td>

                    <!-- Found item name -->
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['found_name'] ?? '—'); ?>
                    </td>

                    <!-- Claimant name + email -->
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['claimant_name'] ?? '—'); ?><br>
                        <small style="color:#9b8ba8;">
                            <?php echo htmlspecialchars($row['claimant_email'] ?? ''); ?>
                        </small>
                    </td>

                    <!-- Claim submission date -->
                    <td style="padding:8px;">
                        <?php echo date('M j, Y', strtotime($row['created_at'])); ?>
                    </td>

                    <!-- Approve / Reject buttons -->
                    <td style="padding:8px; text-align:center;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="claim_id" value="<?php echo (int)$row['claim_id']; ?>">

                            <!-- Approve -->
                            <button type="submit" name="action" value="approve"
                                    style="background:linear-gradient(120deg,#a5d8b8,#7bc99a);
                                           color:white; border:none; padding:6px 14px;
                                           border-radius:14px; cursor:pointer; font-size:12px;">
                                ✓ Approve
                            </button>

                            <!-- Reject -->
                            <button type="submit" name="action" value="reject"
                                    style="background:linear-gradient(120deg,#d67b8b,#c85a6e);
                                           color:white; border:none; padding:6px 14px;
                                           border-radius:14px; cursor:pointer; font-size:12px; margin-left:5px;">
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
         RECENTLY RESOLVED CLAIMS TABLE
         Read-only history. Shows APPROVED / REJECTED badges.
         ============================================================ -->
    <h3 style="margin-top:40px; color:#6b5b82;">
        Recent Resolved Claims
    </h3>

    <?php if ($resolved->num_rows === 0): ?>

        <p class="helper">No resolved claims yet.</p>

    <?php else: ?>

        <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
            <thead>
                <tr style="background:#d8cfe8; color:#4a3b52;">
                    <th style="padding:10px; text-align:left;">Lost</th>
                    <th style="padding:10px; text-align:left;">Found</th>
                    <th style="padding:10px; text-align:left;">Claimant</th>
                    <th style="padding:10px; text-align:left;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $resolved->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #f0e8f4;">

                    <!-- Lost item -->
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['lost_name'] ?? '—'); ?>
                    </td>

                    <!-- Found item -->
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['found_name'] ?? '—'); ?>
                    </td>

                    <!-- Claimant -->
                    <td style="padding:8px;">
                        <?php echo htmlspecialchars($row['claimant_name'] ?? '—'); ?>
                    </td>

                    <!-- Status badge -->
                    <td style="padding:8px;">
                        <?php if ($row['status'] === 'approved'): ?>
                            <span style="background:#d4edda; color:#155724;
                                         padding:3px 10px; border-radius:12px; font-size:12px;">
                                APPROVED
                            </span>
                        <?php else: ?>
                            <span style="background:#f8d7da; color:#721c24;
                                         padding:3px 10px; border-radius:12px; font-size:12px;">
                                REJECTED
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <p style="margin-top:30px;">
        <a href="admin_dashboard.php" class="btn">← Back to Admin Dashboard</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>