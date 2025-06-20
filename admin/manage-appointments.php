<?php
$page_title = "Manage Appointments - GreenLife Wellness Center";

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
$appointment_id = $_GET['id'] ?? null;

// Handle different actions
switch ($action) {
    case 'add':
        // Handle appointment creation
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user_id = intval($_POST['user_id']);
            $service_id = !empty($_POST['service_id']) ? intval($_POST['service_id']) : null;
            $appointment_date = $_POST['appointment_date'];
            $appointment_time = $_POST['appointment_time'];
            $notes = trim($_POST['notes']);
            $status = $_POST['status'] ?? 'pending';

            // Validation
            if (empty($user_id) || empty($appointment_date) || empty($appointment_time)) {
                $error_message = "Please fill in all required fields.";
            } elseif (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
                $error_message = "Appointment date cannot be in the past.";
            } else {
                try {
                    // Check if user exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    if (!$stmt->fetch()) {
                        $error_message = "Selected user does not exist.";
                    } else {
                        // Check if service exists (if service_id is provided)
                        if ($service_id) {
                            $stmt = $pdo->prepare("SELECT id FROM services WHERE id = ?");
                            $stmt->execute([$service_id]);
                            if (!$stmt->fetch()) {
                                $error_message = "Selected service does not exist.";
                            }
                        }

                        if (empty($error_message)) {
                            // Check for time conflicts
                            $stmt = $pdo->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
                            $stmt->execute([$appointment_date, $appointment_time]);
                            
                            if ($stmt->fetch()) {
                                $error_message = "This time slot is already booked.";
                            } else {
                                // Create appointment
                                $stmt = $pdo->prepare("INSERT INTO appointments (user_id, service_id, appointment_date, appointment_time, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                                
                                if ($stmt->execute([$user_id, $service_id, $appointment_date, $appointment_time, $notes, $status])) {
                                    $success_message = "Appointment created successfully!";
                                    $action = 'list'; // Redirect to list view
                                } else {
                                    $error_message = "Error creating appointment. Please try again.";
                                }
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error. Please try again.";
                    error_log("Appointment creation error: " . $e->getMessage());
                }
            }
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user_id = intval($_POST['user_id']);
            $service_id = !empty($_POST['service_id']) ? intval($_POST['service_id']) : null;
            $appointment_date = $_POST['appointment_date'];
            $appointment_time = $_POST['appointment_time'];
            $notes = trim($_POST['notes']);
            $status = $_POST['status'];

            // Validation
            if (empty($user_id) || empty($appointment_date) || empty($appointment_time)) {
                $error_message = "Please fill in all required fields.";
            } else {
                try {
                    // Check for time conflicts (excluding current appointment)
                    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled' AND id != ?");
                    $stmt->execute([$appointment_date, $appointment_time, $appointment_id]);
                    
                    if ($stmt->fetch()) {
                        $error_message = "This time slot is already booked.";
                    } else {
                        // Update appointment
                        $stmt = $pdo->prepare("UPDATE appointments SET user_id = ?, service_id = ?, appointment_date = ?, appointment_time = ?, notes = ?, status = ?, updated_at = NOW() WHERE id = ?");
                        
                        if ($stmt->execute([$user_id, $service_id, $appointment_date, $appointment_time, $notes, $status, $appointment_id])) {
                            $success_message = "Appointment updated successfully!";
                        } else {
                            $error_message = "Error updating appointment. Please try again.";
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error. Please try again.";
                    error_log("Appointment update error: " . $e->getMessage());
                }
            }
        }

        // Fetch appointment data for editing
        if ($appointment_id) {
            try {
                $stmt = $pdo->prepare("
                    SELECT a.*, u.first_name, u.last_name, u.email, s.name as service_name 
                    FROM appointments a 
                    JOIN users u ON a.user_id = u.id 
                    LEFT JOIN services s ON a.service_id = s.id 
                    WHERE a.id = ?
                ");
                $stmt->execute([$appointment_id]);
                $appointment_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$appointment_data) {
                    $error_message = "Appointment not found.";
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $error_message = "Error fetching appointment data.";
                $action = 'list';
            }
        }
        break;

    case 'delete':
        if ($appointment_id && $_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Delete appointment
                $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
                if ($stmt->execute([$appointment_id])) {
                    $success_message = "Appointment deleted successfully!";
                } else {
                    $error_message = "Error deleting appointment.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error. Please try again.";
                error_log("Appointment deletion error: " . $e->getMessage());
            }
        }
        $action = 'list';
        break;

    case 'update_status':
        if ($appointment_id && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $new_status = $_POST['new_status'];
            $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            
            if (in_array($new_status, $valid_statuses)) {
                try {
                    $stmt = $pdo->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$new_status, $appointment_id])) {
                        $success_message = "Appointment status updated successfully!";
                    } else {
                        $error_message = "Error updating appointment status.";
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error. Please try again.";
                    error_log("Status update error: " . $e->getMessage());
                }
            } else {
                $error_message = "Invalid status.";
            }
        }
        $action = 'list';
        break;

    case 'view':
        // Fetch appointment details for viewing
        if ($appointment_id) {
            try {
                $stmt = $pdo->prepare("
                    SELECT a.*, u.first_name, u.last_name, u.email, u.phone, s.name as service_name, s.price, s.duration 
                    FROM appointments a 
                    JOIN users u ON a.user_id = u.id 
                    LEFT JOIN services s ON a.service_id = s.id 
                    WHERE a.id = ?
                ");
                $stmt->execute([$appointment_id]);
                $appointment_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$appointment_data) {
                    $error_message = "Appointment not found.";
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $error_message = "Error fetching appointment data.";
                $action = 'list';
            }
        }
        break;
}

// Fetch data for forms
try {
    // Fetch all users for dropdowns
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users ORDER BY first_name, last_name");
    $stmt->execute();
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all services for dropdowns
    $stmt = $pdo->prepare("SELECT id, name, price, duration FROM services ORDER BY name");
    $stmt->execute();
    $all_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_users = [];
    $all_services = [];
}

// Fetch appointments for listing (with search and pagination)
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR s.name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "a.appointment_date = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    // Count total appointments
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM appointments a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN services s ON a.service_id = s.id 
        $where_clause
    ";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_appointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_appointments / $per_page);

    // Fetch appointments for current page
    $appointments_sql = "
        SELECT a.*, u.first_name, u.last_name, u.email, s.name as service_name, s.price 
        FROM appointments a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN services s ON a.service_id = s.id 
        $where_clause 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC 
        LIMIT $per_page OFFSET $offset
    ";
    $stmt = $pdo->prepare($appointments_sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get appointment statistics
    $stats_sql = "
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
            COUNT(CASE WHEN appointment_date = CURDATE() THEN 1 END) as today
        FROM appointments
    ";
    $stmt = $pdo->prepare($stats_sql);
    $stmt->execute();
    $appointment_stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error fetching appointments.";
    $appointments = [];
    $total_appointments = 0;
    $total_pages = 0;
    $appointment_stats = [
        'total' => 0, 'pending' => 0, 'confirmed' => 0, 
        'completed' => 0, 'cancelled' => 0, 'today' => 0
    ];
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

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 4px solid var(--stat-color);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card.total { --stat-color: #3498db; }
        .stat-card.pending { --stat-color: #f39c12; }
        .stat-card.confirmed { --stat-color: #2ecc71; }
        .stat-card.completed { --stat-color: #9b59b6; }
        .stat-card.cancelled { --stat-color: #e74c3c; }
        .stat-card.today { --stat-color: #1abc9c; }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--stat-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        }

        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .filter-input, .filter-select {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .filter-input:focus, .filter-select:focus {
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
        }

        .table-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .appointments-table th,
        .appointments-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f1f3f4;
        }

        .appointments-table th {
            background: #fafbfc;
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .appointments-table tbody tr:hover {
            background: #f8f9fa;
        }

        .client-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .client-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .client-details h4 {
            color: #2c3e50;
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
        }

        .client-details p {
            color: #7f8c8d;
            font-size: 0.8rem;
        }

        .appointment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .appointment-status.pending { background: #fff3cd; color: #856404; }
        .appointment-status.confirmed { background: #d4edda; color: #155724; }
        .appointment-status.completed { background: #cce7ff; color: #004085; }
        .appointment-status.cancelled { background: #f8d7da; color: #721c24; }

        .action-buttons {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }

        /* Status Update Dropdown */
        .status-update {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-select {
            padding: 0.25rem 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.8rem;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Appointment Details View */
        .appointment-profile {
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

        .profile-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
        }

        .profile-details {
            padding: 2rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-card {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .detail-card-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .detail-card-value {
            color: #2c3e50;
            font-size: 1rem;
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

        /* Empty State */
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

        /* Responsive Design */
        @media (max-width: 1200px) {
            .filter-row {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .appointments-table {
                font-size: 0.85rem;
            }

            .appointments-table th,
            .appointments-table td {
                padding: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
                <li><a href="manage-users.php" class="nav-link"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage-services.php" class="nav-link"><i class="fas fa-spa"></i> Manage Services</a></li>
                <li><a href="manage-appointments.php" class="nav-link active"><i class="fas fa-calendar"></i> Manage Appointments</a></li>
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
                    <i class="fas fa-calendar"></i>
                    <?php 
                    switch($action) {
                        case 'add': echo 'Add New Appointment'; break;
                        case 'edit': echo 'Edit Appointment'; break;
                        case 'view': echo 'Appointment Details'; break;
                        default: echo 'Manage Appointments'; break;
                    }
                    ?>
                </h1>
                <div class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a> / 
                    <a href="manage-appointments.php">Appointments</a> / 
                    <?php 
                    switch($action) {
                        case 'add': echo 'Add New'; break;
                        case 'edit': echo 'Edit'; break;
                        case 'view': echo 'View Details'; break;
                        default: echo 'All Appointments'; break;
                    }
                    ?>
                </div>
            </div>
            <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Appointment
                </a>
            <?php else: ?>
                <a href="manage-appointments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Appointments
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
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $appointment_stats['total']; ?></div>
                    <div class="stat-label">Total Appointments</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo $appointment_stats['pending']; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card confirmed">
                    <div class="stat-number"><?php echo $appointment_stats['confirmed']; ?></div>
                    <div class="stat-label">Confirmed</div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-number"><?php echo $appointment_stats['completed']; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card cancelled">
                    <div class="stat-number"><?php echo $appointment_stats['cancelled']; ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
                <div class="stat-card today">
                    <div class="stat-number"><?php echo $appointment_stats['today']; ?></div>
                    <div class="stat-label">Today</div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="search-filter">
                <form method="GET">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search Appointments</label>
                            <input type="text" 
                                   id="search"
                                   name="search" 
                                   class="filter-input" 
                                   placeholder="Search by client name, email, or service..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="filter-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="date">Date</label>
                            <input type="date" 
                                   id="date"
                                   name="date" 
                                   class="filter-input"
                                   value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>

                        <div class="filter-group">
                            <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <?php if ($search || $status_filter || $date_filter): ?>
                                    <a href="manage-appointments.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Appointments Table -->
            <div class="table-container">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-calendar"></i>
                        All Appointments (<?php echo number_format($total_appointments); ?> total)
                    </div>
                </div>

                <?php if (!empty($appointments)): ?>
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <div class="client-info">
                                            <div class="client-avatar">
                                                <?php echo strtoupper(substr($appointment['first_name'], 0, 1)); ?>
                                            </div>
                                            <div class="client-details">
                                                <h4><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($appointment['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($appointment['service_name'] ?: 'No Service'); ?></strong>
                                            <?php if ($appointment['price']): ?>
                                                <div><small>$<?php echo number_format($appointment['price'], 2); ?></small></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></div>
                                        <small><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="status-update">
                                            <span class="appointment-status <?php echo $appointment['status']; ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                            <form method="POST" action="?action=update_status&id=<?php echo $appointment['id']; ?>" style="display: inline; margin-left: 0.5rem;" onchange="this.submit()">
                                                <select name="new_status" class="status-select">
                                                    <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </form>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($appointment['created_at'])); ?></div>
                                        <small><?php echo date('g:i A', strtotime($appointment['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="deleteAppointment(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>')" 
                                                    class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar"></i>
                        <h3>No Appointments Found</h3>
                        <p>
                            <?php if ($search || $status_filter || $date_filter): ?>
                                No appointments match your filter criteria.
                            <?php else: ?>
                                No appointments have been scheduled yet.
                            <?php endif; ?>
                        </p>
                        <?php if (!$search && !$status_filter && !$date_filter): ?>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Schedule First Appointment
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- Appointment Form -->
            <div class="form-container">
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="user_id">Client: <span class="required">*</span></label>
                            <select name="user_id" id="user_id" required>
                                <option value="">Select a client</option>
                                <?php foreach ($all_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo (isset($appointment_data) && $appointment_data['user_id'] == $user['id']) || (isset($_POST['user_id']) && $_POST['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="service_id">Service:</label>
                            <select name="service_id" id="service_id">
                                <option value="">Select a service (optional)</option>
                                <?php foreach ($all_services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>" 
                                            <?php echo (isset($appointment_data) && $appointment_data['service_id'] == $service['id']) || (isset($_POST['service_id']) && $_POST['service_id'] == $service['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($service['name'] . ' - $' . number_format($service['price'], 2) . ' (' . $service['duration'] . 'min)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="appointment_date">Date: <span class="required">*</span></label>
                            <input type="date" 
                                   name="appointment_date" 
                                   id="appointment_date" 
                                   required 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo isset($appointment_data) ? $appointment_data['appointment_date'] : (isset($_POST['appointment_date']) ? $_POST['appointment_date'] : ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="appointment_time">Time: <span class="required">*</span></label>
                            <input type="time" 
                                   name="appointment_time" 
                                   id="appointment_time" 
                                   required
                                   value="<?php echo isset($appointment_data) ? substr($appointment_data['appointment_time'], 0, 5) : (isset($_POST['appointment_time']) ? $_POST['appointment_time'] : ''); ?>">
                        </div>
                    </div>

                    <?php if ($action === 'edit'): ?>
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select name="status" id="status">
                                <option value="pending" <?php echo (isset($appointment_data) && $appointment_data['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo (isset($appointment_data) && $appointment_data['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo (isset($appointment_data) && $appointment_data['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo (isset($appointment_data) && $appointment_data['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group full-width">
                        <label for="notes">Notes:</label>
                        <textarea name="notes" 
                                  id="notes" 
                                  placeholder="Any special requirements, allergies, or additional information..."><?php echo isset($appointment_data) ? htmlspecialchars($appointment_data['notes']) : (isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            <?php echo $action === 'add' ? 'Schedule Appointment' : 'Update Appointment'; ?>
                        </button>
                        <a href="manage-appointments.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'view' && isset($appointment_data)): ?>
            <!-- Appointment Details View -->
            <div class="appointment-profile">
                <div class="profile-header">
                    <div class="profile-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <h2>Appointment #<?php echo str_pad($appointment_data['id'], 4, '0', STR_PAD_LEFT); ?></h2>
                    <p><?php echo date('l, F j, Y \a\t g:i A', strtotime($appointment_data['appointment_date'] . ' ' . $appointment_data['appointment_time'])); ?></p>
                    <span class="appointment-status <?php echo $appointment_data['status']; ?>" style="display: inline-block; margin-top: 1rem;">
                        <?php echo ucfirst($appointment_data['status']); ?>
                    </span>
                </div>

                <div class="profile-details">
                    <div class="detail-grid">
                        <div class="detail-card">
                            <div class="detail-card-label">Client Name</div>
                            <div class="detail-card-value"><?php echo htmlspecialchars($appointment_data['first_name'] . ' ' . $appointment_data['last_name']); ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-label">Email</div>
                            <div class="detail-card-value"><?php echo htmlspecialchars($appointment_data['email']); ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-label">Phone</div>
                            <div class="detail-card-value"><?php echo $appointment_data['phone'] ? htmlspecialchars($appointment_data['phone']) : 'Not provided'; ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-label">Service</div>
                            <div class="detail-card-value"><?php echo htmlspecialchars($appointment_data['service_name'] ?: 'No specific service'); ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-label">Date</div>
                            <div class="detail-card-value"><?php echo date('F j, Y', strtotime($appointment_data['appointment_date'])); ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-label">Time</div>
                            <div class="detail-card-value"><?php echo date('g:i A', strtotime($appointment_data['appointment_time'])); ?></div>
                        </div>

                        <?php if ($appointment_data['duration']): ?>
                            <div class="detail-card">
                                <div class="detail-card-label">Duration</div>
                                <div class="detail-card-value"><?php echo $appointment_data['duration']; ?> minutes</div>
                            </div>
                        <?php endif; ?>

                        <?php if ($appointment_data['price']): ?>
                            <div class="detail-card">
                                <div class="detail-card-label">Price</div>
                                <div class="detail-card-value">$<?php echo number_format($appointment_data['price'], 2); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="detail-card">
                            <div class="detail-card-label">Status</div>
                            <div class="detail-card-value">
                                <span class="appointment-status <?php echo $appointment_data['status']; ?>">
                                    <?php echo ucfirst($appointment_data['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-label">Created</div>
                            <div class="detail-card-value"><?php echo date('F j, Y g:i A', strtotime($appointment_data['created_at'])); ?></div>
                        </div>

                        <?php if (isset($appointment_data['updated_at']) && $appointment_data['updated_at']): ?>
                            <div class="detail-card">
                                <div class="detail-card-label">Last Updated</div>
                                <div class="detail-card-value"><?php echo date('F j, Y g:i A', strtotime($appointment_data['updated_at'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($appointment_data['notes']): ?>
                        <div class="detail-card">
                            <div class="detail-card-label">Notes</div>
                            <div class="detail-card-value"><?php echo nl2br(htmlspecialchars($appointment_data['notes'])); ?></div>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="?action=edit&id=<?php echo $appointment_data['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit Appointment
                        </a>
                        <button onclick="deleteAppointment(<?php echo $appointment_data['id']; ?>, '<?php echo htmlspecialchars($appointment_data['first_name'] . ' ' . $appointment_data['last_name']); ?>')" 
                                class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Appointment
                        </button>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <!-- JavaScript -->
    <script>
        function deleteAppointment(id, name) {
            if (confirm("Are you sure you want to delete the appointment for " + name + "?")) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</body>
</html>