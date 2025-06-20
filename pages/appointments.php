<?php
session_start();
include('../config/database.php');
include('../includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Page title
$page_title = "Appointments - GreenLife Wellness Center";
include '../includes/login-header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle appointment cancellation
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appointment_id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'cancel') {
        try {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$appointment_id, $user_id])) {
                $success_message = "Appointment cancelled successfully.";
            } else {
                $error_message = "Error cancelling appointment.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error. Please try again.";
            error_log("Cancel appointment error: " . $e->getMessage());
        }
    }
}

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $notes = trim($_POST['notes']);

    // Validation
    if (empty($service_id) || empty($appointment_date) || empty($appointment_time)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Validate date is not in the past
        $today = date('Y-m-d');
        if ($appointment_date < $today) {
            $error_message = "Please select a future date.";
        } else {
            // Check if the appointment slot is available
            try {
                $check_stmt = $pdo->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
                $check_stmt->execute([$appointment_date, $appointment_time]);
                
                if ($check_stmt->fetch()) {
                    $error_message = "This time slot is already booked. Please choose another time.";
                } else {
                    // Book the appointment
                    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, service_id, appointment_date, appointment_time, notes, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                    
                    if ($stmt->execute([$user_id, $service_id, $appointment_date, $appointment_time, $notes])) {
                        $success_message = "Appointment booked successfully!";
                        // Redirect to prevent form resubmission
                        header('Location: appointments.php?success=1');
                        exit();
                    } else {
                        $error_message = "Error booking appointment. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error. Please try again.";
                error_log("Appointment booking error: " . $e->getMessage());
            }
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Appointment booked successfully!";
}

// Fetch user's appointments with service details
try {
    $stmt = $pdo->prepare("
        SELECT a.*, s.name as service_name, s.duration, s.price 
        FROM appointments a 
        LEFT JOIN services s ON a.service_id = s.id 
        WHERE a.user_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $appointments = [];
    error_log("Fetch appointments error: " . $e->getMessage());
}

// Fetch services for the dropdown
try {
    $services_stmt = $pdo->query("SELECT * FROM services ORDER BY name");
    $services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $services = [];
    error_log("Fetch services error: " . $e->getMessage());
}
?>

<main>
    <div class="appointments-container">
        <div class="container">
            <h1>Your Appointments</h1>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <!-- Existing Appointments -->
            <div class="appointments-section">
                <h2>Your Current Appointments</h2>
                <?php if (!empty($appointments)): ?>
                    <div class="appointments-grid">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-header">
                                    <h3><?php echo htmlspecialchars($appointment['service_name'] ?: 'Service #' . $appointment['service_id']); ?></h3>
                                    <span class="status status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </div>
                                <div class="appointment-details">
                                    <p><strong>📅 Date:</strong> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></p>
                                    <p><strong>🕒 Time:</strong> <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></p>
                                    <?php if ($appointment['duration']): ?>
                                        <p><strong>⏱️ Duration:</strong> <?php echo $appointment['duration']; ?> minutes</p>
                                    <?php endif; ?>
                                    <?php if ($appointment['price']): ?>
                                        <p><strong>💰 Price:</strong> $<?php echo number_format($appointment['price'], 2); ?></p>
                                    <?php endif; ?>
                                    <?php if ($appointment['notes']): ?>
                                        <p><strong>📝 Notes:</strong> <?php echo htmlspecialchars($appointment['notes']); ?></p>
                                    <?php endif; ?>
                                    <p><strong>📋 Booking ID:</strong> #<?php echo str_pad($appointment['id'], 4, '0', STR_PAD_LEFT); ?></p>
                                    <p><strong>📅 Booked on:</strong> <?php echo date('M j, Y', strtotime($appointment['created_at'])); ?></p>
                                </div>
                                <?php if ($appointment['status'] == 'pending'): ?>
                                    <div class="appointment-actions">
                                        <a href="?action=cancel&id=<?php echo $appointment['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel Appointment</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-appointments">
                        <div class="no-appointments-icon">📅</div>
                        <h3>No Appointments Yet</h3>
                        <p>You don't have any appointments scheduled.</p>
                        <p>Book your first appointment below to start your wellness journey!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Book New Appointment -->
            <div class="booking-section">
                <h2>Book a New Appointment</h2>
                <div class="booking-info">
                    <p>📞 <strong>Contact us:</strong> +94 11 123 4567 | 📧 <strong>Email:</strong> info@greenlifewellness.com</p>
                    <p>🕒 <strong>Available Hours:</strong> Monday-Friday: 8:00 AM - 8:00 PM | Saturday: 9:00 AM - 6:00 PM | Sunday: 10:00 AM - 4:00 PM</p>
                </div>
                
                <form action="appointments.php" method="POST" class="appointment-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="service_id">Service: <span class="required">*</span></label>
                            <select name="service_id" id="service_id" required>
                                <option value="">Select a service</option>
                                <?php if (!empty($services)): ?>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" 
                                                data-price="<?php echo $service['price']; ?>"
                                                data-duration="<?php echo $service['duration']; ?>">
                                            <?php echo htmlspecialchars($service['name']); ?> - $<?php echo number_format($service['price'], 2); ?> (<?php echo $service['duration']; ?> min)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="1">Massage Therapy - $80.00 (60 min)</option>
                                    <option value="2">Yoga Session - $50.00 (45 min)</option>
                                    <option value="3">Nutrition Counseling - $40.00 (30 min)</option>
                                    <option value="4">Physiotherapy - $90.00 (60 min)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="appointment_date">Date: <span class="required">*</span></label>
                            <input type="date" name="appointment_date" id="appointment_date" 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                   max="<?php echo date('Y-m-d', strtotime('+3 months')); ?>"
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="appointment_time">Time: <span class="required">*</span></label>
                            <select name="appointment_time" id="appointment_time" required>
                                <option value="">Select time</option>
                                <optgroup label="Morning">
                                    <option value="09:00:00">9:00 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                </optgroup>
                                <optgroup label="Afternoon">
                                    <option value="14:00:00">2:00 PM</option>
                                    <option value="15:00:00">3:00 PM</option>
                                    <option value="16:00:00">4:00 PM</option>
                                </optgroup>
                                <optgroup label="Evening">
                                    <option value="17:00:00">5:00 PM</option>
                                    <option value="18:00:00">6:00 PM</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Additional Notes (Optional):</label>
                        <textarea name="notes" id="notes" rows="4" placeholder="Please share any specific requests, health concerns, or preferences that will help us provide you with the best service..."></textarea>
                    </div>
                    
                    <div class="form-summary" id="booking-summary" style="display: none;">
                        <h4>Booking Summary</h4>
                        <div class="summary-item">
                            <span>Service:</span>
                            <span id="summary-service">-</span>
                        </div>
                        <div class="summary-item">
                            <span>Duration:</span>
                            <span id="summary-duration">-</span>
                        </div>
                        <div class="summary-item">
                            <span>Price:</span>
                            <span id="summary-price">-</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">
                        <span>📅</span> Book Appointment
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<style>
/* Basic Appointments Styling */
.appointments-container {
    padding: 2rem 0;
    background: #f8f9fa;
    min-height: 80vh;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.container h1 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 2rem;
    font-size: 2.5rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    text-align: center;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.appointments-section, .booking-section {
    margin-bottom: 3rem;
}

.appointments-section h2, .booking-section h2 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
    text-align: center;
}

.appointments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.appointment-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-left: 4px solid #27ae60;
    transition: transform 0.3s ease;
}

.appointment-card:hover {
    transform: translateY(-2px);
}

.appointment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
}

.appointment-header h3 {
    color: #2c3e50;
    margin: 0;
    font-size: 1.2rem;
}

.status {
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
    text-transform: uppercase;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-confirmed {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.appointment-details p {
    margin: 0.5rem 0;
    color: #555;
    font-size: 0.9rem;
}

.appointment-actions {
    margin-top: 1rem;
    text-align: center;
}

.no-appointments {
    text-align: center;
    background: white;
    padding: 3rem 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.no-appointments-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.no-appointments h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.booking-info {
    background: #e8f5e8;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    text-align: center;
}

.booking-info p {
    margin: 0.5rem 0;
    color: #2c3e50;
    font-size: 0.9rem;
}

.appointment-form {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    max-width: 800px;
    margin: 0 auto;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #2c3e50;
    font-weight: 600;
}

.required {
    color: #e74c3c;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #27ae60;
}

.form-summary {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.form-summary h4 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin: 0.5rem 0;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-primary {
    background: #27ae60;
    color: white;
}

.btn-primary:hover {
    background: #2ecc71;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.btn-large {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .appointments-grid {
        grid-template-columns: 1fr;
    }
    
    .appointment-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<script>
// Show booking summary when service is selected
document.getElementById('service_id').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const summaryDiv = document.getElementById('booking-summary');
    
    if (this.value) {
        const serviceName = option.text.split(' - ')[0];
        const price = option.getAttribute('data-price');
        const duration = option.getAttribute('data-duration');
        
        document.getElementById('summary-service').textContent = serviceName;
        document.getElementById('summary-duration').textContent = duration + ' minutes';
        document.getElementById('summary-price').textContent = '$' + parseFloat(price).toFixed(2);
        
        summaryDiv.style.display = 'block';
    } else {
        summaryDiv.style.display = 'none';
    }
});

// Date validation
document.getElementById('appointment_date').addEventListener('change', function() {
    const date = new Date(this.value);
    const dayOfWeek = date.getDay();
    
    if (dayOfWeek === 0) {
        alert('Please note: We have limited availability on Sundays. Our team will contact you to confirm your appointment.');
    }
});
</script>

