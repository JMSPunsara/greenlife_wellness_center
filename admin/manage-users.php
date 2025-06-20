<?php
$page_title = "Manage Users - GreenLife Wellness Center";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/functions.php');

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';
$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? null;

// Handle different actions
switch ($action) {
    case 'add':
        // Handle user creation
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // Validation
            if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
                $error_message = "Please fill in all required fields.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Please enter a valid email address.";
            } elseif ($password !== $confirm_password) {
                $error_message = "Passwords do not match.";
            } elseif (strlen($password) < 6) {
                $error_message = "Password must be at least 6 characters long.";
            } else {
                try {
                    // Check if email already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if ($stmt->fetch()) {
                        $error_message = "Email address is already registered.";
                    } else {
                        // Create user account
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                        
                        if ($stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password])) {
                            $success_message = "User created successfully!";
                            $action = 'list'; // Redirect to list view
                        } else {
                            $error_message = "Error creating user. Please try again.";
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error. Please try again.";
                    error_log("User creation error: " . $e->getMessage());
                }
            }
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);

            // Validation
            if (empty($first_name) || empty($last_name) || empty($email)) {
                $error_message = "Please fill in all required fields.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Please enter a valid email address.";
            } else {
                try {
                    // Check if email already exists for other users
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user_id]);
                    
                    if ($stmt->fetch()) {
                        $error_message = "Email address is already registered by another user.";
                    } else {
                        // Update user
                        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                        
                        if ($stmt->execute([$first_name, $last_name, $email, $phone, $user_id])) {
                            $success_message = "User updated successfully!";
                        } else {
                            $error_message = "Error updating user. Please try again.";
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error. Please try again.";
                    error_log("User update error: " . $e->getMessage());
                }
            }
        }

        // Fetch user data for editing
        if ($user_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user_data) {
                    $error_message = "User not found.";
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $error_message = "Error fetching user data.";
                $action = 'list';
            }
        }
        break;

    case 'delete':
        if ($user_id && $_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Check if user has appointments
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $appointment_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($appointment_count > 0) {
                    $error_message = "Cannot delete user. User has existing appointments.";
                } else {
                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$user_id])) {
                        $success_message = "User deleted successfully!";
                    } else {
                        $error_message = "Error deleting user.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error. Please try again.";
                error_log("User deletion error: " . $e->getMessage());
            }
        }
        $action = 'list';
        break;

    case 'view':
        // Fetch user data and appointments for viewing
        if ($user_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user_data) {
                    $error_message = "User not found.";
                    $action = 'list';
                } else {
                    // Fetch user's appointments
                    $stmt = $pdo->prepare("
                        SELECT a.*, s.name as service_name, s.price 
                        FROM appointments a 
                        LEFT JOIN services s ON a.service_id = s.id 
                        WHERE a.user_id = ? 
                        ORDER BY a.appointment_date DESC, a.appointment_time DESC
                    ");
                    $stmt->execute([$user_id]);
                    $user_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                $error_message = "Error fetching user data.";
                $action = 'list';
            }
        }
        break;
}

// Fetch users for listing (with search and pagination)
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

try {
    // Count total users
    $count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_users / $per_page);

    // Fetch users for current page
    $users_sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($users_sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error fetching users.";
    $users = [];
    $total_users = 0;
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        /* Header Styles */
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .logo i {
            font-size: 2rem;
            color: #4ecdc4;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-welcome {
            text-align: right;
        }

        .admin-welcome h3 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .admin-welcome p {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        /* Navigation */
        .main-nav {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .nav-menu {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-link {
            color: #2c3e50;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            background: #667eea;
            color: white;
            transform: translateY(-1px);
        }

        .nav-link.active {
            background: #4ecdc4;
            color: white;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .page-title {
            font-size: 2rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .breadcrumb {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Buttons */
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
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Search and Filter */
        .search-filter {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-group {
            flex: 1;
            min-width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .table-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .table-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f1f3f4;
        }

        .users-table th {
            background: #fafbfc;
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .users-table tbody tr:hover {
            background: #f8f9fa;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .user-details h4 {
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .user-details p {
            color: #7f8c8d;
            font-size: 0.85rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Forms */
        .form-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* User Details View */
        .user-profile {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }

        .profile-details {
            padding: 2rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .detail-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            color: #2c3e50;
            font-size: 1rem;
        }

        /* Appointments Section */
        .appointments-section {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .appointments-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #dee2e6;
        }

        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce7ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
            padding: 0;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover {
            background: #f8f9fa;
            color: #495057;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }

            .main-content {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .search-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .search-group {
                min-width: auto;
            }

            .action-buttons {
                flex-direction: column;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .users-table {
                font-size: 0.85rem;
            }

            .users-table th,
            .users-table td {
                padding: 0.5rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-spa"></i>
                <h1>GreenLife Admin</h1>
            </div>
            <div class="admin-info">
                <div class="admin-welcome">
                    <h3>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_first_name'] ?? 'Admin'); ?>!</h3>
                    <p><i class="fas fa-clock"></i> <?php echo date('l, F j, Y'); ?></p>
                </div>
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_first_name'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="main-nav">
        <div class="nav-container">
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage-users.php" class="nav-link active"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage-services.php" class="nav-link"><i class="fas fa-spa"></i> Manage Services</a></li>
                <li><a href="manage-appointments.php" class="nav-link"><i class="fas fa-calendar"></i> Manage Appointments</a></li>
                <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-users"></i>
                    <?php 
                    switch($action) {
                        case 'add': echo 'Add New User'; break;
                        case 'edit': echo 'Edit User'; break;
                        case 'view': echo 'User Details'; break;
                        default: echo 'Manage Users'; break;
                    }
                    ?>
                </h1>
                <div class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a> / 
                    <a href="manage-users.php">Users</a> / 
                    <?php 
                    switch($action) {
                        case 'add': echo 'Add New'; break;
                        case 'edit': echo 'Edit'; break;
                        case 'view': echo 'View Details'; break;
                        default: echo 'All Users'; break;
                    }
                    ?>
                </div>
            </div>
            <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            <?php else: ?>
                <a href="manage-users.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            <?php endif; ?>
        </div>

        <!-- Alert Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Search and Filter -->
            <div class="search-filter">
                <div class="search-group">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                        <input type="text" 
                               name="search" 
                               class="search-input" 
                               placeholder="Search users by name or email..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if ($search): ?>
                            <a href="manage-users.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-users"></i>
                        All Users (<?php echo number_format($total_users); ?> total)
                    </div>
                </div>

                <?php if (!empty($users)): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details">
                                                <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                                <p>ID: #<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                                            <?php if ($user['phone']): ?>
                                                <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($user['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')" 
                                                    class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No Users Found</h3>
                        <p>
                            <?php if ($search): ?>
                                No users match your search criteria.
                            <?php else: ?>
                                No users have been registered yet.
                            <?php endif; ?>
                        </p>
                        <?php if (!$search): ?>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add First User
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- User Form -->
            <div class="form-container">
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name: <span class="required">*</span></label>
                            <input type="text" 
                                   name="first_name" 
                                   id="first_name" 
                                   required 
                                   placeholder="Enter first name"
                                   value="<?php echo isset($user_data) ? htmlspecialchars($user_data['first_name']) : (isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name: <span class="required">*</span></label>
                            <input type="text" 
                                   name="last_name" 
                                   id="last_name" 
                                   required 
                                   placeholder="Enter last name"
                                   value="<?php echo isset($user_data) ? htmlspecialchars($user_data['last_name']) : (isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address: <span class="required">*</span></label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               required 
                               placeholder="Enter email address"
                               value="<?php echo isset($user_data) ? htmlspecialchars($user_data['email']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" 
                               name="phone" 
                               id="phone" 
                               placeholder="Enter phone number"
                               value="<?php echo isset($user_data) ? htmlspecialchars($user_data['phone']) : (isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''); ?>">
                    </div>

                    <?php if ($action === 'add'): ?>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="password">Password: <span class="required">*</span></label>
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       required 
                                       placeholder="Create password (min 6 characters)" 
                                       minlength="6">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm Password: <span class="required">*</span></label>
                                <input type="password" 
                                       name="confirm_password" 
                                       id="confirm_password" 
                                       required 
                                       placeholder="Confirm password" 
                                       minlength="6">
                            </div>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            <?php echo $action === 'add' ? 'Create User' : 'Update User'; ?>
                        </button>
                        <a href="manage-users.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'view' && isset($user_data)): ?>
            <!-- User Profile View -->
            <div class="user-profile">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user_data['first_name'], 0, 1)); ?>
                    </div>
                    <h2><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h2>
                    <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                    <p>User ID: #<?php echo str_pad($user_data['id'], 4, '0', STR_PAD_LEFT); ?></p>
                </div>

                <div class="profile-details">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Full Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Email Address</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user_data['email']); ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Phone Number</div>
                            <div class="detail-value"><?php echo $user_data['phone'] ? htmlspecialchars($user_data['phone']) : 'Not provided'; ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Registration Date</div>
                            <div class="detail-value"><?php echo date('F j, Y g:i A', strtotime($user_data['created_at'])); ?></div>
                        </div>

                        <?php if (isset($user_data['updated_at']) && $user_data['updated_at']): ?>
                            <div class="detail-item">
                                <div class="detail-label">Last Updated</div>
                                <div class="detail-value"><?php echo date('F j, Y g:i A', strtotime($user_data['updated_at'])); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="detail-item">
                            <div class="detail-label">Total Appointments</div>
                            <div class="detail-value"><?php echo count($user_appointments); ?></div>
                        </div>
                    </div>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <a href="?action=edit&id=<?php echo $user_data['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit User
                        </a>
                        <button onclick="deleteUser(<?php echo $user_data['id']; ?>, '<?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>')" 
                                class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete User
                        </button>
                    </div>
                </div>
            </div>

            <!-- User Appointments -->
            <div class="appointments-section">
                <div class="appointments-header">
                    <h3 class="table-title">
                        <i class="fas fa-calendar"></i>
                        User Appointments (<?php echo count($user_appointments); ?>)
                    </h3>
                </div>

                <?php if (!empty($user_appointments)): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Booked On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment['service_name'] ?: 'N/A'); ?></td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></div>
                                        <small><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="status status-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($appointment['price'] ?? 0, 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($appointment['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar"></i>
                        <h3>No Appointments</h3>
                        <p>This user hasn't booked any appointments yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <button type="button" class="close-modal" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user?</p>
                <p><strong id="deleteUserName"></strong></p>
                <p class="text-muted">This action cannot be undone. The user will be permanently removed from the system.</p>
            </div>
            <div class="modal-footer" style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Delete user function
        function deleteUser(userId, userName) {
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteForm').action = '?action=delete&id=' + userId;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }

        // Password confirmation validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            if (password && confirmPassword) {
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    return;
                }

                if (password.value.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long.');
                    return;
                }
            }
        });

        // Search form auto-submit on Enter
        document.querySelector('.search-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });

        // Auto-focus on search field if it has value
        document.addEventListener('DOMContentLoaded', function() {
            const searchField = document.querySelector('.search-input');
            if (searchField && searchField.value) {
                searchField.focus();
                searchField.setSelectionRange(searchField.value.length, searchField.value.length);
            }
        });
    </script>
</body>
</html>