<?php
// filepath: /greenlife-wellness-admin/greenlife-wellness-admin/admin/users/delete.php
$page_title = "Delete User - GreenLife Wellness Center";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../includes/functions.php');

// Check if the user is logged in and has admin privileges
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Check if user ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];

    // Prepare and execute delete statement
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        // Redirect to users index with success message
        $_SESSION['success_message'] = "User deleted successfully.";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        // Log error and redirect with error message
        error_log("User deletion error: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred while deleting the user. Please try again.";
        header('Location: index.php');
        exit();
    }
} else {
    // Redirect to users index with error message if no ID is provided
    $_SESSION['error_message'] = "Invalid user ID.";
    header('Location: index.php');
    exit();
}
?>