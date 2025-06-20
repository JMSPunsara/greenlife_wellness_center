<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions for any cleanup if needed
require_once(__DIR__ . '/../includes/functions.php');

// Store user name for goodbye message (optional)
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$first_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';

// Destroy all session data
session_unset();
session_destroy();

// Start a new session for the logout message
session_start();
$_SESSION['logout_message'] = "You have been successfully logged out. Thank you for visiting GreenLife Wellness Center!";

// Set page title
$page_title = "Logout - GreenLife Wellness Center";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title><?php echo $page_title; ?></title>
    <style>
        /* Logout Page Specific Styles */
        .logout-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 1rem;
        }

        .logout-card {
            background: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logout-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .logout-card h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .logout-message {
            color: #7f8c8d;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .logout-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: #27ae60;
            border: 2px solid #27ae60;
        }

        .btn-outline:hover {
            background: #27ae60;
            color: white;
            transform: translateY(-2px);
        }

        .wellness-tips {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            border-left: 4px solid #27ae60;
        }

        .wellness-tips h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .wellness-tips ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .wellness-tips li {
            padding: 0.5rem 0;
            color: #555;
            font-size: 0.9rem;
        }

        .wellness-tips li:before {
            content: "🌿 ";
            margin-right: 0.5rem;
        }

        .footer-info {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .footer-info p {
            margin: 0.5rem 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .logout-container {
                padding: 1rem;
            }
            
            .logout-card {
                padding: 2rem 1.5rem;
            }
            
            .logout-card h1 {
                font-size: 1.5rem;
            }
            
            .logout-message {
                font-size: 1rem;
            }
        }

        /* Auto-redirect countdown */
        .countdown {
            background: #e8f5e8;
            border-radius: 10px;
            padding: 1rem;
            margin: 1.5rem 0;
            border: 1px solid #c3e6cb;
        }

        .countdown-text {
            color: #155724;
            font-weight: 600;
            margin: 0;
        }

        .countdown-number {
            font-size: 1.5rem;
            color: #27ae60;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-card">
            <div class="logout-icon">👋</div>
            <h1>Goodbye<?php echo $first_name ? ', ' . htmlspecialchars($first_name) : ''; ?>!</h1>
            <p class="logout-message">
                You have been successfully logged out from GreenLife Wellness Center. 
                Thank you for choosing us for your wellness journey. We hope to see you again soon!
            </p>

            <!-- Auto-redirect countdown -->
            <div class="countdown">
                <p class="countdown-text">
                    Redirecting to home page in <span class="countdown-number" id="countdown">10</span> seconds...
                </p>
            </div>

            <div class="logout-actions">
                <a href="../index.php" class="btn btn-primary">
                    <span>🏠</span> Go to Home Page
                </a>
                <a href="login.php" class="btn btn-secondary">
                    <span>🔐</span> Login Again
                </a>
                <a href="services.php" class="btn btn-outline">
                    <span>🌿</span> Browse Our Services
                </a>
            </div>

            <!-- Wellness Tips Section -->
            <div class="wellness-tips">
                <h3>💡 Wellness Tips for Your Day</h3>
                <ul>
                    <li>Take a few deep breaths and practice mindfulness</li>
                    <li>Stay hydrated with plenty of water</li>
                    <li>Take short breaks to stretch and move</li>
                    <li>Practice gratitude for positive mental health</li>
                    <li>Remember to schedule your next wellness session</li>
                </ul>
            </div>

            <!-- Contact Information -->
            <div class="footer-info">
                <p><strong>📞 Need Help?</strong></p>
                <p>Call us at +94 11 123 4567</p>
                <p>Email: info@greenlifewellness.com</p>
                <p><strong>🕒 Opening Hours:</strong> Mon-Fri 8AM-8PM, Sat 9AM-6PM, Sun 10AM-4PM</p>
            </div>
        </div>
    </div>

    <script>
        // Auto-redirect countdown
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '../index.php';
            }
        }, 1000);

        // Allow user to cancel auto-redirect by clicking anywhere
        document.addEventListener('click', () => {
            if (countdown > 0) {
                clearInterval(timer);
                document.querySelector('.countdown').style.display = 'none';
            }
        });

        // Prevent back button to logged-in pages
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function() {
            window.history.go(1);
        };

        // Clear any cached data
        if ('caches' in window) {
            caches.keys().then(names => {
                names.forEach(name => {
                    caches.delete(name);
                });
            });
        }

        // Show welcome message animation
        window.addEventListener('load', () => {
            const card = document.querySelector('.logout-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(-30px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>