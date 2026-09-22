<?php
/**
 * register.php
 * ------------------------------------------------------------
 * PURPOSE: Allows new users to create an account.
 *          Validates input, hashes password, inserts into DB.
 * ------------------------------------------------------------
 */

// Start output buffering FIRST (must be before ANY output)
ob_start();

require_once 'dbconnect.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];

// ------------------------------------------------------------
// Handle form submission
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($full_name))                      $errors[] = "Full name is required.";
    if (empty($email))                          $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (strlen($password) < 6)                  $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm)                 $errors[] = "Passwords do not match.";

    // Check if email already exists
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

    // Insert new user
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, password, role)
             VALUES (?, ?, ?, 'user')"
        );
        $stmt->bind_param("sss", $full_name, $email, $hashed);

        if ($stmt->execute()) {
            // Set flash message
            $_SESSION['flash_success'] = "Account created successfully! Please log in.";

            // Clear output buffer before redirect
            ob_end_clean();

            // Redirect to login
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Something went wrong. Try again.";
        }
        $stmt->close();
    }
}

// Now include the header (output can start here)
include 'includes/header.php';
?>