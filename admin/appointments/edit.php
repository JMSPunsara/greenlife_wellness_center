<?php
$page_title = "Edit Appointment - GreenLife Wellness Center";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../includes/functions.php');

// Check if user is logged in as admin
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize variables
$error_message = '';
$success_message = '';
$appointment = null;

// Get appointment ID from URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $appointment_id = $_GET['id'];

    // Fetch appointment details from the database
    try {
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            $error_message = "Appointment not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error. Please try again.";
        error_log("Fetch appointment error: " . $e->getMessage());
    }
} else {
    $error_message = "Invalid appointment ID.";
}

// Handle form submission for updating appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = trim($_POST['date']);
    $time = trim($_POST['time']);
    $client_name = trim($_POST['client_name']);
    $service_id = trim($_POST['service_id']);

    // Validation
    if (empty($date) || empty($time) || empty($client_name) || empty($service_id)) {
        $error_message = "Please fill in all fields.";
    } else {
        try {
            // Update appointment in the database
            $stmt = $pdo->prepare("UPDATE appointments SET date = ?, time = ?, client_name = ?, service_id = ? WHERE id = ?");
            $stmt->execute([$date, $time, $client_name, $service_id, $appointment_id]);
            $success_message = "Appointment updated successfully.";
        } catch (PDOException $e) {
            $error_message = "Database error. Please try again.";
            error_log("Update appointment error: " . $e->getMessage());
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
        <h1>Edit Appointment</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($appointment): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" name="date" id="date" required value="<?php echo htmlspecialchars($appointment['date']); ?>">
                </div>

                <div class="form-group">
                    <label for="time">Time:</label>
                    <input type="time" name="time" id="time" required value="<?php echo htmlspecialchars($appointment['time']); ?>">
                </div>

                <div class="form-group">
                    <label for="client_name">Client Name:</label>
                    <input type="text" name="client_name" id="client_name" required value="<?php echo htmlspecialchars($appointment['client_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="service_id">Service:</label>
                    <select name="service_id" id="service_id" required>
                        <?php
                        // Fetch services for the dropdown
                        $services_stmt = $pdo->query("SELECT id, name FROM services");
                        while ($service = $services_stmt->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($service['id'] == $appointment['service_id']) ? 'selected' : '';
                            echo "<option value=\"{$service['id']}\" $selected>{$service['name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn">Update Appointment</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>