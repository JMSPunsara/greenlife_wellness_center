<?php
// Check if session is already started before calling session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/functions.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style2.css">
    <title><?php echo isset($page_title) ? $page_title : 'GreenLife Wellness Center'; ?></title>
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="nav-container">
                <a href="../index.php" class="nav-logo">🌿 GreenLife Wellness</a>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="../index.php" class="nav-link">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="about.php" class="nav-link">About</a>
                    </li>
                    <li class="nav-item">
                        <a href="services.php" class="nav-link">Services</a>
                    </li>
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <li class="nav-item">
                            <a href="appointments.php" class="nav-link">Appointments</a>
                        </li>
                        <li class="nav-item">
                            <a href="profile.php" class="nav-link">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="login.php" class="nav-link">Login</a>
                        </li>
                        <li class="nav-item">
                            <a href="register.php" class="nav-link">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>🌿 GreenLife Wellness Center</h3>
                <p>Your journey to better health and wellness starts here. We provide holistic care for mind, body, and spirit.</p>
            </div>
            <div class="footer-section">
                <h3>📞 Contact Info</h3>
                <p>📍 123 Wellness Street, Colombo 03, Sri Lanka</p>
                <p>📞 +94 11 123 4567</p>
                <p>✉️ info@greenlifewellness.com</p>
                <p>🌐 www.greenlifewellness.com</p>
            </div>
            <div class="footer-section">
                <h3>🕒 Opening Hours</h3>
                <p><strong>Mon - Fri:</strong> 8:00 AM - 8:00 PM</p>
                <p><strong>Saturday:</strong> 9:00 AM - 6:00 PM</p>
                <p><strong>Sunday:</strong> 10:00 AM - 4:00 PM</p>
                <p><em>Appointments available 7 days a week</em></p>
            </div>
            <div class="footer-section">
                <h3>🔗 Quick Links</h3>
                <p><a href="../index.php">Home</a></p>
                <p><a href="about.php">About Us</a></p>
                <p><a href="services.php">Our Services</a></p>
                <p><a href="contact.php">Contact</a></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 GreenLife Wellness Center. All rights reserved. | Privacy Policy | Terms of Service</p>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
</body>
</html>