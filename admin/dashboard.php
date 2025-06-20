<?php
$page_title = "Admin Dashboard - GreenLife Wellness Center";

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

// Fetch statistics for the dashboard
try {
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Appointments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments");
    $total_appointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Services
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM services");
    $total_services = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Admins
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM admins");
    $total_admins = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pending Appointments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'");
    $pending_appointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Today's Appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = CURDATE()");
    $stmt->execute();
    $today_appointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Revenue (completed appointments)
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(s.price), 0) as total 
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        WHERE a.status = 'completed'
    ");
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // This Week's Revenue
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(s.price), 0) as total 
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        WHERE a.status = 'completed' 
        AND WEEK(a.appointment_date) = WEEK(CURDATE())
        AND YEAR(a.appointment_date) = YEAR(CURDATE())
    ");
    $week_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // This Month's Appointments
    $stmt = $pdo->query("
        SELECT COUNT(*) as total FROM appointments 
        WHERE MONTH(appointment_date) = MONTH(CURDATE()) 
        AND YEAR(appointment_date) = YEAR(CURDATE())
    ");
    $month_appointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent Appointments
    $stmt = $pdo->prepare("
        SELECT a.*, u.first_name, u.last_name, u.email, s.name as service_name, s.price
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN services s ON a.service_id = s.id
        ORDER BY a.created_at DESC
        LIMIT 8
    ");
    $stmt->execute();
    $recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Users
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, created_at
        FROM users 
        ORDER BY created_at DESC
        LIMIT 8
    ");
    $stmt->execute();
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Admin List
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, created_at
        FROM admins 
        ORDER BY created_at ASC
    ");
    $stmt->execute();
    $admin_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Services by Bookings
    $stmt = $pdo->prepare("
        SELECT s.name, COUNT(a.id) as booking_count, s.price
        FROM services s
        LEFT JOIN appointments a ON s.id = a.service_id
        GROUP BY s.id, s.name, s.price
        ORDER BY booking_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $top_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Weekly appointment statistics
    $stmt = $pdo->prepare("
        SELECT 
            DATE(appointment_date) as date,
            COUNT(*) as count
        FROM appointments 
        WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(appointment_date)
        ORDER BY appointment_date ASC
    ");
    $stmt->execute();
    $weekly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error fetching dashboard data.";
    error_log("Dashboard error: " . $e->getMessage());
    
    // Set default values
    $total_users = 0;
    $total_appointments = 0;
    $total_services = 0;
    $total_admins = 0;
    $pending_appointments = 0;
    $today_appointments = 0;
    $total_revenue = 0;
    $week_revenue = 0;
    $month_appointments = 0;
    $recent_appointments = [];
    $recent_users = [];
    $admin_list = [];
    $top_services = [];
    $weekly_stats = [];
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

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-title {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .dashboard-subtitle {
            color: #7f8c8d;
            font-size: 1.1rem;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color);
        }

        .stat-card.users { --card-color: #3498db; }
        .stat-card.appointments { --card-color: #2ecc71; }
        .stat-card.services { --card-color: #f39c12; }
        .stat-card.admins { --card-color: #9b59b6; }
        .stat-card.pending { --card-color: #e74c3c; }
        .stat-card.today { --card-color: #1abc9c; }
        .stat-card.revenue { --card-color: #34495e; }
        .stat-card.week-revenue { --card-color: #e67e22; }
        .stat-card.month-appointments { --card-color: #16a085; }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            background: var(--card-color);
        }

        .stat-title {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .stat-description {
            color: #95a5a6;
            font-size: 0.85rem;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .section-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .action-card:hover {
            background: white;
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        }

        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #667eea;
        }

        .action-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .action-description {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        /* Data Tables */
        .dashboard-tables {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .table-section {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .table-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #dee2e6;
        }

        .table-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f1f3f4;
        }

        .data-table th {
            background: #fafbfc;
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        .user-info,
        .appointment-info {
            display: flex;
            flex-direction: column;
        }

        .user-name,
        .appointment-client {
            font-weight: 600;
            color: #2c3e50;
        }

        .user-email,
        .appointment-service {
            font-size: 0.85rem;
            color: #7f8c8d;
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

        .table-footer {
            padding: 1rem 2rem;
            background: #fafbfc;
            text-align: center;
        }

        .view-all-btn {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .view-all-btn:hover {
            background: #667eea;
            color: white;
        }

        /* Admin List Section */
        .admin-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .admin-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .admin-card:hover {
            background: white;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .admin-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .admin-email {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .admin-joined {
            color: #95a5a6;
            font-size: 0.75rem;
        }

        /* Top Services Chart */
        .services-chart {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .service-item:last-child {
            border-bottom: none;
        }

        .service-info {
            flex: 1;
        }

        .service-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .service-price {
            color: #27ae60;
            font-size: 0.9rem;
        }

        .service-count {
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-tables {
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .admin-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-title {
                font-size: 2rem;
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

        /* Utility Classes */
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 1rem; }
        .mb-3 { margin-bottom: 1.5rem; }
        .text-muted { color: #7f8c8d; }
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
                <li><a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage-users.php" class="nav-link"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage-services.php" class="nav-link"><i class="fas fa-spa"></i> Manage Services</a></li>
                <li><a href="manage-appointments.php" class="nav-link"><i class="fas fa-calendar"></i> Manage Appointments</a></li>
                <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title"><i class="fas fa-chart-line"></i> Dashboard Overview</h1>
            <p class="dashboard-subtitle">Monitor your wellness center's performance and activities</p>
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

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card users">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-title">Total Users</div>
                <div class="stat-number"><?php echo number_format($total_users); ?></div>
                <div class="stat-description">Registered members</div>
            </div>

            <div class="stat-card appointments">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                </div>
                <div class="stat-title">Total Appointments</div>
                <div class="stat-number"><?php echo number_format($total_appointments); ?></div>
                <div class="stat-description">All time bookings</div>
            </div>

            <div class="stat-card services">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-spa"></i></div>
                </div>
                <div class="stat-title">Active Services</div>
                <div class="stat-number"><?php echo number_format($total_services); ?></div>
                <div class="stat-description">Available treatments</div>
            </div>

            <div class="stat-card admins">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                </div>
                <div class="stat-title">Administrators</div>
                <div class="stat-number"><?php echo number_format($total_admins); ?>/4</div>
                <div class="stat-description">System administrators</div>
            </div>

            <div class="stat-card pending">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-title">Pending Appointments</div>
                <div class="stat-number"><?php echo number_format($pending_appointments); ?></div>
                <div class="stat-description">Awaiting confirmation</div>
            </div>

            <div class="stat-card today">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                </div>
                <div class="stat-title">Today's Appointments</div>
                <div class="stat-number"><?php echo number_format($today_appointments); ?></div>
                <div class="stat-description"><?php echo date('M j, Y'); ?></div>
            </div>

            <div class="stat-card revenue">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                </div>
                <div class="stat-title">Total Revenue</div>
                <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                <div class="stat-description">Completed services</div>
            </div>

            <div class="stat-card week-revenue">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                </div>
                <div class="stat-title">This Week's Revenue</div>
                <div class="stat-number">$<?php echo number_format($week_revenue, 2); ?></div>
                <div class="stat-description">Weekly earnings</div>
            </div>

            <div class="stat-card month-appointments">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                </div>
                <div class="stat-title">This Month</div>
                <div class="stat-number"><?php echo number_format($month_appointments); ?></div>
                <div class="stat-description">Monthly appointments</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="actions-grid">
                <a href="manage-users.php?action=add" class="action-card">
                    <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                    <div class="action-title">Add New User</div>
                    <div class="action-description">Register a new member</div>
                </a>
                <a href="manage-services.php?action=add" class="action-card">
                    <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="action-title">Add New Service</div>
                    <div class="action-description">Create wellness service</div>
                </a>
                <a href="manage-appointments.php" class="action-card">
                    <div class="action-icon"><i class="fas fa-calendar-plus"></i></div>
                    <div class="action-title">Manage Appointments</div>
                    <div class="action-description">View and update bookings</div>
                </a>
                <a href="#reports" class="action-card">
                    <div class="action-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="action-title">View Reports</div>
                    <div class="action-description">Analytics and insights</div>
                </a>
            </div>
        </div>

        <!-- Top Services Chart -->
        <?php if (!empty($top_services)): ?>
        <div class="services-chart">
            <h2 class="section-title"><i class="fas fa-trophy"></i> Top Services</h2>
            <?php foreach ($top_services as $service): ?>
                <div class="service-item">
                    <div class="service-info">
                        <div class="service-name"><?php echo htmlspecialchars($service['name']); ?></div>
                        <div class="service-price">$<?php echo number_format($service['price'], 2); ?></div>
                    </div>
                    <div class="service-count"><?php echo $service['booking_count']; ?> bookings</div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Data Tables -->
        <div class="dashboard-tables">
            <!-- Recent Appointments -->
            <div class="table-section">
                <div class="table-header">
                    <h3 class="table-title"><i class="fas fa-calendar"></i> Recent Appointments</h3>
                </div>
                <?php if (!empty($recent_appointments)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_appointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <div class="appointment-info">
                                            <div class="appointment-client"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></div>
                                            <div class="appointment-service"><?php echo htmlspecialchars($appointment['email']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['service_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <a href="manage-appointments.php" class="view-all-btn">View All Appointments</a>
                    </div>
                <?php else: ?>
                    <div class="text-center mb-3">
                        <p class="text-muted">No recent appointments found.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Users -->
            <div class="table-section">
                <div class="table-header">
                    <h3 class="table-title"><i class="fas fa-users"></i> Recent Users</h3>
                </div>
                <?php if (!empty($recent_users)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <a href="manage-users.php" class="view-all-btn">View All Users</a>
                    </div>
                <?php else: ?>
                    <div class="text-center mb-3">
                        <p class="text-muted">No users found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Administrator Section -->
        <div class="admin-section">
            <h2 class="section-title"><i class="fas fa-user-shield"></i> System Administrators (<?php echo count($admin_list); ?>/4)</h2>
            <?php if (!empty($admin_list)): ?>
                <div class="admin-grid">
                    <?php foreach ($admin_list as $admin): ?>
                        <div class="admin-card">
                            <div class="admin-avatar">
                                <?php echo strtoupper(substr($admin['first_name'], 0, 1)); ?>
                            </div>
                            <div class="admin-name"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></div>
                            <div class="admin-email"><?php echo htmlspecialchars($admin['email']); ?></div>
                            <div class="admin-joined">Joined: <?php echo date('M j, Y', strtotime($admin['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-muted">No administrators found.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);

        // Real-time clock update
        function updateClock() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.querySelector('.admin-welcome p').innerHTML = 
                '<i class="fas fa-clock"></i> ' + now.toLocaleDateString('en-US', options);
        }

        // Update clock every minute
        setInterval(updateClock, 60000);

        // Animate stat cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add hover effects to action cards
            document.querySelectorAll('.action-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });

        // Notification system (placeholder)
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
            
            document.querySelector('.main-content').insertBefore(
                notification, 
                document.querySelector('.dashboard-header')
            );
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        // Check for pending appointments and show notification
        <?php if ($pending_appointments > 0): ?>
            setTimeout(() => {
                showNotification(
                    `You have <?php echo $pending_appointments; ?> pending appointment(s) that need attention.`,
                    'warning'
                );
            }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>