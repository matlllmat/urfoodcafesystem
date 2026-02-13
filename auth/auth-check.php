<?php
/**
 * Authentication Check
 * Include this file at the top of protected pages
 * Redirects to login if user is not authenticated
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Redirect to login page
    header('Location: ../auth/login.php');
    exit();
}

// Make session variables available
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$is_super_admin = isset($_SESSION['is_super_admin']) ? $_SESSION['is_super_admin'] : false;
?>