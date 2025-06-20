<?php
$page_title = "Add Service - GreenLife Wellness Center";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../includes/functions.php');

// Redirect if not logged in as admin
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle service addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_name = trim($_POST['service_name']);
    $service_description = trim($_POST['service_description']);
    $service_price = trim($_POST['service_price']);

    // Validation
    if (empty($service_name) || empty($service_description) || empty($service_price)) {
        $error_message = "Please fill in all fields.";
    } elseif (!is_numeric($service_price) || $service_price < 0) {
        $error_message = "Please enter a valid price.";
    } else {
        try {
            // Insert new service into the database
            $stmt = $pdo->prepare("INSERT INTO services (name, description, price) VALUES (?, ?, ?)");
            $stmt->execute([$service_name, $service_description, $service_price]);
            $success_message = "Service added successfully.";
        } catch (PDOException $e) {
            $error_message = "Database error. Please try again.";
            error_log("Add service error: " . $e->getMessage());
        }
    }
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
    <div class="container">
        <h1>Add New Service</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="service_name">Service Name:</label>
                <input type="text" name="service_name" id="service_name" required>
            </div>

            <div class="form-group">
                <label for="service_description">Service Description:</label>
                <textarea name="service_description" id="service_description" required></textarea>
            </div>

            <div class="form-group">
                <label for="service_price">Service Price:</label>
                <input type="number" name="service_price" id="service_price" required step="0.01">
            </div>

            <button type="submit" class="btn-submit">Add Service</button>
        </form>

        <a href="index.php">← Back to Services</a>
    </div>
</body>
</html>