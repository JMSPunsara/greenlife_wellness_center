<?php
// filepath: c:\xampp\htdocs\icbt\02\greenlife-wellness-center\admin\services\delete.php
$page_title = "Delete Service - GreenLife Wellness Center";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration and functions
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../includes/functions.php');

// Check if user is logged in and has admin role
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle service deletion
if (isset($_GET['id'])) {
    $service_id = $_GET['id'];

    try {
        // Prepare and execute delete statement
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$service_id]);

        // Redirect to services index with success message
        $_SESSION['success_message'] = "Service deleted successfully.";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        // Log error and redirect with error message
        error_log("Service deletion error: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to delete service. Please try again.";
        header('Location: index.php');
        exit();
    }
} else {
    // Redirect if no service ID is provided
    $_SESSION['error_message'] = "No service ID provided.";
    header('Location: index.php');
    exit();
}
?>