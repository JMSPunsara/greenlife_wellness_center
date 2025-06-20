<?php
$page_title = "View Appointment - GreenLife Wellness Center";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../includes/functions.php');

// Check if user is logged in as admin
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get appointment ID from URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $appointment_id = $_GET['id'];

    try {
        // Fetch appointment details
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            header('Location: index.php?error=Appointment not found');
            exit();
        }
    } catch (PDOException $e) {
        header('Location: index.php?error=Database error');
        exit();
    }
} else {
    header('Location: index.php?error=Invalid appointment ID');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include_once(__DIR__ . '/../../includes/header.php'); ?>

    <div class="container">
        <h1>Appointment Details</h1>
        <div class="appointment-details">
            <p><strong>Appointment ID:</strong> <?php echo htmlspecialchars($appointment['id']); ?></p>
            <p><strong>User ID:</strong> <?php echo htmlspecialchars($appointment['user_id']); ?></p>
            <p><strong>Service ID:</strong> <?php echo htmlspecialchars($appointment['service_id']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment['date']); ?></p>
            <p><strong>Time:</strong> <?php echo htmlspecialchars($appointment['time']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($appointment['status']); ?></p>
        </div>
        <a href="index.php" class="btn">Back to Appointments</a>
    </div>

    <?php include_once(__DIR__ . '/../../includes/footer.php'); ?>
</body>
</html>