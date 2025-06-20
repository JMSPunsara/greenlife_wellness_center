<?php
$page_title = "Edit Service - GreenLife Wellness Center";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../includes/functions.php');

// Check if admin is logged in
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$error_message = '';
$success_message = '';

// Get service ID from URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $service_id = $_GET['id'];

    // Fetch service details
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            $error_message = "Service not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error. Please try again.";
        error_log("Fetch service error: " . $e->getMessage());
    }
} else {
    $error_message = "Invalid service ID.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_name = trim($_POST['service_name']);
    $service_description = trim($_POST['service_description']);
    $service_price = trim($_POST['service_price']);

    // Validation
    if (empty($service_name) || empty($service_description) || empty($service_price)) {
        $error_message = "Please fill in all fields.";
    } elseif (!is_numeric($service_price)) {
        $error_message = "Price must be a valid number.";
    } else {
        try {
            // Update service details
            $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, price = ? WHERE id = ?");
            $stmt->execute([$service_name, $service_description, $service_price, $service_id]);
            $success_message = "Service updated successfully.";
        } catch (PDOException $e) {
            $error_message = "Database error. Please try again.";
            error_log("Update service error: " . $e->getMessage());
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
        <h1>Edit Service</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="service_name">Service Name:</label>
                <input type="text" name="service_name" id="service_name" required value="<?php echo htmlspecialchars($service['name']); ?>">
            </div>

            <div class="form-group">
                <label for="service_description">Description:</label>
                <textarea name="service_description" id="service_description" required><?php echo htmlspecialchars($service['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="service_price">Price:</label>
                <input type="text" name="service_price" id="service_price" required value="<?php echo htmlspecialchars($service['price']); ?>">
            </div>

            <button type="submit" class="btn">Update Service</button>
        </form>

        <a href="index.php">← Back to Services</a>
    </div>
</body>
</html>