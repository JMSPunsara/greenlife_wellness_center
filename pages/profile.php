<?php

$page_title = "Profile - GreenLife Wellness Center";
include '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];

        // Validation
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error_message = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } else {
            // Check if email is already taken by another user
            try {
                $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check_stmt->execute([$email, $user_id]);
                
                if ($check_stmt->fetch()) {
                    $error_message = "Email address is already in use by another account.";
                } else {
                    // Update profile
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, date_of_birth = ?, gender = ?, updated_at = NOW() WHERE id = ?");
                    
                    if ($stmt->execute([$first_name, $last_name, $email, $phone, $address, $date_of_birth, $gender, $user_id])) {
                        $success_message = "Profile updated successfully!";
                        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    } else {
                        $error_message = "Error updating profile. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error. Please try again.";
                error_log("Profile update error: " . $e->getMessage());
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "Please fill in all password fields.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long.";
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($current_password, $user['password'])) {
                    $error_message = "Current password is incorrect.";
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    
                    if ($stmt->execute([$hashed_password, $user_id])) {
                        $success_message = "Password changed successfully!";
                    } else {
                        $error_message = "Error changing password. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error. Please try again.";
                error_log("Password change error: " . $e->getMessage());
            }
        }
    }
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: logout.php');
        exit();
    }
} catch (PDOException $e) {
    $error_message = "Error fetching profile data.";
    error_log("Fetch user error: " . $e->getMessage());
}

// Fetch user's recent appointments
try {
    $stmt = $pdo->prepare("
        SELECT a.*, s.name as service_name, s.duration, s.price 
        FROM appointments a 
        LEFT JOIN services s ON a.service_id = s.id 
        WHERE a.user_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_appointments = [];
    error_log("Fetch recent appointments error: " . $e->getMessage());
}

// Calculate user statistics
try {
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_appointments,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_appointments,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_appointments,
            SUM(CASE WHEN status = 'completed' AND s.price IS NOT NULL THEN s.price ELSE 0 END) as total_spent
        FROM appointments a 
        LEFT JOIN services s ON a.service_id = s.id 
        WHERE a.user_id = ?
    ");
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total_appointments' => 0, 'completed_appointments' => 0, 'pending_appointments' => 0, 'total_spent' => 0];
    error_log("Fetch stats error: " . $e->getMessage());
}
?>

<main>
    <div class="profile-container">
        <div class="container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                </div>
                <div class="profile-info">
                    <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h1>
                    <p class="member-since">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Profile Statistics -->
            <div class="stats-section">
                <h2>Your Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-number"><?php echo $stats['total_appointments']; ?></div>
                        <div class="stat-label">Total Appointments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?php echo $stats['completed_appointments']; ?></div>
                        <div class="stat-label">Completed Sessions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-number"><?php echo $stats['pending_appointments']; ?></div>
                        <div class="stat-label">Upcoming Appointments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-number">$<?php echo number_format($stats['total_spent'], 2); ?></div>
                        <div class="stat-label">Total Investment</div>
                    </div>
                </div>
            </div>

            <div class="profile-content">
                <!-- Personal Information Section -->
                <div class="profile-section">
                    <h2>Personal Information</h2>
                    <form method="POST" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name: <span class="required">*</span></label>
                                <input type="text" name="first_name" id="first_name" required 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name: <span class="required">*</span></label>
                                <input type="text" name="last_name" id="last_name" required 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address: <span class="required">*</span></label>
                                <input type="email" name="email" id="email" required 
                                       value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number:</label>
                                <input type="tel" name="phone" id="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth:</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" 
                                       value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender:</label>
                                <select name="gender" id="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    <option value="prefer_not_to_say" <?php echo ($user['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address:</label>
                            <textarea name="address" id="address" rows="3" 
                                      placeholder="Enter your full address..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <span>💾</span> Update Profile
                        </button>
                    </form>
                </div>

                <!-- Password Change Section -->
                <div class="profile-section">
                    <h2>Change Password</h2>
                    <form method="POST" class="password-form">
                        <div class="form-group">
                            <label for="current_password">Current Password: <span class="required">*</span></label>
                            <input type="password" name="current_password" id="current_password" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password: <span class="required">*</span></label>
                                <input type="password" name="new_password" id="new_password" required minlength="6">
                                <small>Minimum 6 characters</small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password: <span class="required">*</span></label>
                                <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-secondary">
                            <span>🔒</span> Change Password
                        </button>
                    </form>
                </div>

                <!-- Recent Appointments Section -->
                <div class="profile-section">
                    <h2>Recent Appointments</h2>
                    <?php if (!empty($recent_appointments)): ?>
                        <div class="appointments-list">
                            <?php foreach ($recent_appointments as $appointment): ?>
                                <div class="appointment-item">
                                    <div class="appointment-date">
                                        <div class="date-day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></div>
                                        <div class="date-month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></div>
                                    </div>
                                    <div class="appointment-details">
                                        <h4><?php echo htmlspecialchars($appointment['service_name'] ?: 'Service'); ?></h4>
                                        <p>🕒 <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></p>
                                        <?php if ($appointment['price']): ?>
                                            <p>💰 $<?php echo number_format($appointment['price'], 2); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="appointment-status">
                                        <span class="status status-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="view-all-link">
                            <a href="appointments.php" class="btn btn-outline">View All Appointments</a>
                        </div>
                    <?php else: ?>
                        <div class="no-appointments">
                            <p>You haven't booked any appointments yet.</p>
                            <a href="appointments.php" class="btn btn-primary">Book Your First Appointment</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions Section -->
                <div class="profile-section">
                    <h2>Quick Actions</h2>
                    <div class="quick-actions">
                        <a href="appointments.php" class="action-card">
                            <div class="action-icon">📅</div>
                            <h3>Book Appointment</h3>
                            <p>Schedule your next wellness session</p>
                        </a>
                        <a href="services.php" class="action-card">
                            <div class="action-icon">🌿</div>
                            <h3>Browse Services</h3>
                            <p>Explore our wellness offerings</p>
                        </a>
                        <a href="contact.php" class="action-card">
                            <div class="action-icon">📞</div>
                            <h3>Contact Us</h3>
                            <p>Get in touch with our team</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Profile Page Styles */
.profile-container {
    padding: 2rem 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.profile-header {
    display: flex;
    align-items: center;
    background: white;
    padding: 2rem;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.profile-avatar {
    margin-right: 2rem;
}

.avatar-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.profile-info h1 {
    color: #2c3e50;
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
}

.member-since {
    color: #7f8c8d;
    font-size: 1.1rem;
    margin: 0;
}

.stats-section {
    margin-bottom: 2rem;
}

.stats-section h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    font-size: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #27ae60;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-content {
    display: grid;
    gap: 2rem;
}

.profile-section {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.profile-section h2 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    border-bottom: 2px solid #f8f9fa;
    padding-bottom: 0.5rem;
}

.profile-form, .password-form {
    max-width: 800px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
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
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #27ae60;
    box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
}

.form-group small {
    color: #6c757d;
    font-size: 0.8rem;
    margin-top: 0.25rem;
    display: block;
}

.appointments-list {
    margin-bottom: 1rem;
}

.appointment-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    margin-bottom: 1rem;
    transition: box-shadow 0.3s ease;
}

.appointment-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.appointment-date {
    text-align: center;
    margin-right: 1rem;
    min-width: 60px;
}

.date-day {
    font-size: 1.5rem;
    font-weight: bold;
    color: #27ae60;
}

.date-month {
    font-size: 0.8rem;
    color: #7f8c8d;
    text-transform: uppercase;
}

.appointment-details {
    flex: 1;
}

.appointment-details h4 {
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
}

.appointment-details p {
    margin: 0.25rem 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.appointment-status {
    margin-left: 1rem;
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

.status-completed {
    background: #cce7ff;
    color: #004085;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.action-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.action-card:hover {
    background: white;
    border-color: #27ae60;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.action-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.action-card h3 {
    color: #2c3e50;
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.action-card p {
    color: #6c757d;
    margin: 0;
    font-size: 0.9rem;
}

.no-appointments {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
}

.view-all-link {
    text-align: center;
    margin-top: 1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #495057, #6c757d);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
}

.btn-outline {
    background: transparent;
    color: #27ae60;
    border: 2px solid #27ae60;
}

.btn-outline:hover {
    background: #27ae60;
    color: white;
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

/* Mobile Responsive */
@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-avatar {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .appointment-item {
        flex-direction: column;
        text-align: center;
    }
    
    .appointment-date {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .appointment-status {
        margin-left: 0;
        margin-top: 1rem;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Form submission confirmations
document.querySelector('.profile-form').addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to update your profile information?')) {
        e.preventDefault();
    }
});

document.querySelector('.password-form').addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to change your password?')) {
        e.preventDefault();
    }
});
</script>

<?php include '../includes/footer.php'; ?>