<?php
require_once(__DIR__ . '/../../config/database.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Check if appointment ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $appointment_id = $_GET['id'];

    try {
        // Prepare delete statement
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->execute([$appointment_id]);

        // Redirect to appointments index with success message
        header('Location: index.php?message=Appointment deleted successfully.');
        exit();
    } catch (PDOException $e) {
        // Handle error
        header('Location: index.php?error=Could not delete appointment. Please try again.');
        exit();
    }
} else {
    // Redirect if no ID is provided
    header('Location: index.php?error=Invalid appointment ID.');
    exit();
}
?>