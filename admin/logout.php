<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store admin name for goodbye message (optional)
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
$first_name = isset($_SESSION['admin_first_name']) ? $_SESSION['admin_first_name'] : '';

// Destroy all admin session data
session_unset();
session_destroy();

// Start a new session for the logout message
session_start();
$_SESSION['logout_message'] = "You have been successfully logged out from the admin panel.";

// Set page title
$page_title = "Admin Logout - GreenLife Wellness Center";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .logout-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            text-align: center;
            width: 100%;
            max-width: 400px;
        }

        h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        p {
            margin-bottom: 1.5rem;
        }

        .admin-name {
            font-weight: bold;
            color: #333;
        }

        .logout-message {
            font-size: 1rem;
            color: #666;
        }

        .login-link {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .login-link:hover {
            background: #5a6dbf;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <h1>Goodbye, <span class="admin-name"><?php echo htmlspecialchars($first_name); ?></span>!</h1>
        <p class="logout-message"><?php echo $_SESSION['logout_message']; ?></p>
        <a href="login.php" class="login-link">Login Again</a>
    </div>
</body>
</html>