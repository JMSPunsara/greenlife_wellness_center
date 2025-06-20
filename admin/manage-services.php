<?php
$page_title = "Manage Services - GreenLife Wellness Center";

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
$service_id = $_GET['id'] ?? null;

// Handle different actions
switch ($action) {
    case 'add':
        // Handle service creation
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $duration = intval($_POST['duration']);

            // Validation
            if (empty($name) || empty($description) || $price <= 0 || $duration <= 0) {
                $error_message = "Please fill in all required fields with valid values.";
            } else {
                try {
                    // Check if service name already exists
                    $stmt = $pdo->prepare("SELECT id FROM services WHERE name = ?");
                    $stmt->execute([$name]);
                    
                    if ($stmt->fetch()) {
                        $error_message = "A service with this name already exists.";
                    } else {
                        // Create service
                        $stmt = $pdo->prepare("INSERT INTO services (name, description, price, duration, created_at) VALUES (?, ?, ?, ?, NOW())");
                        
                        if ($stmt->execute([$name, $description, $price, $duration])) {
                            $success_message = "Service created successfully!";
                            $action = 'list'; // Redirect to list view
                        } else {
                            $error_message = "Error creating service. Please try again.";
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error. Please try again.";
                    error_log("Service creation error: " . $e->getMessage());
                }
            }
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $duration = intval($_POST['duration']);

            // Validation
            if (empty($name) || empty($description) || $price <= 0 || $duration <= 0) {
                $error_message = "Please fill in all required fields with valid values.";
            } else {
                try {
                    // Check if service name already exists for other services
                    $stmt = $pdo->prepare("SELECT id FROM services WHERE name = ? AND id != ?");
                    $stmt->execute([$name, $service_id]);
                    
                    if ($stmt->fetch()) {
                        $error_message = "A service with this name already exists.";
                    } else {
                        // Update service
                        $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration = ?, updated_at = NOW() WHERE id = ?");
                        
                        if ($stmt->execute([$name, $description, $price, $duration, $service_id])) {
                            $success_message = "Service updated successfully!";
                        } else {
                            $error_message = "Error updating service. Please try again.";
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error. Please try again.";
                    error_log("Service update error: " . $e->getMessage());
                }
            }
        }

        // Fetch service data for editing
        if ($service_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
                $stmt->execute([$service_id]);
                $service_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$service_data) {
                    $error_message = "Service not found.";
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $error_message = "Error fetching service data.";
                $action = 'list';
            }
        }
        break;

    case 'delete':
        if ($service_id && $_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Check if service has appointments (if appointments table exists)
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE service_id = ?");
                    $stmt->execute([$service_id]);
                    $appointment_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($appointment_count > 0) {
                        $error_message = "Cannot delete service. Service has existing appointments.";
                    } else {
                        // Delete service
                        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
                        if ($stmt->execute([$service_id])) {
                            $success_message = "Service deleted successfully!";
                        } else {
                            $error_message = "Error deleting service.";
                        }
                    }
                } catch (PDOException $e) {
                    // If appointments table doesn't exist, just delete the service
                    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
                    if ($stmt->execute([$service_id])) {
                        $success_message = "Service deleted successfully!";
                    } else {
                        $error_message = "Error deleting service.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error. Please try again.";
                error_log("Service deletion error: " . $e->getMessage());
            }
        }
        $action = 'list';
        break;

    case 'view':
        // Fetch service data and appointments for viewing
        if ($service_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
                $stmt->execute([$service_id]);
                $service_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$service_data) {
                    $error_message = "Service not found.";
                    $action = 'list';
                } else {
                    // Try to fetch service appointments (if appointments table exists)
                    $service_appointments = [];
                    $service_stats = [
                        'total_bookings' => 0,
                        'pending_bookings' => 0,
                        'confirmed_bookings' => 0,
                        'completed_bookings' => 0,
                        'cancelled_bookings' => 0
                    ];
                    
                    try {
                        $stmt = $pdo->prepare("
                            SELECT a.*, u.first_name, u.last_name, u.email 
                            FROM appointments a 
                            JOIN users u ON a.user_id = u.id 
                            WHERE a.service_id = ? 
                            ORDER BY a.appointment_date DESC, a.appointment_time DESC
                            LIMIT 10
                        ");
                        $stmt->execute([$service_id]);
                        $service_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Get appointment statistics
                        $stmt = $pdo->prepare("
                            SELECT 
                                COUNT(*) as total_bookings,
                                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
                                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
                                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
                                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings
                            FROM appointments 
                            WHERE service_id = ?
                        ");
                        $stmt->execute([$service_id]);
                        $service_stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        // Appointments table doesn't exist yet
                        error_log("Appointments table not found: " . $e->getMessage());
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Error fetching service data.";
                $action = 'list';
            }
        }
        break;
}

// Fetch services for listing (with search and pagination)
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    // Count total services
    $count_sql = "SELECT COUNT(*) as total FROM services $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_services = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_services / $per_page);

    // Fetch services for current page
    $services_sql = "SELECT * FROM services $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($services_sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error fetching services.";
    $services = [];
    $total_services = 0;
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
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .filter-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Service Cards Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .service-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .service-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 1px solid #dee2e6;
        }

        .service-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .service-body {
            padding: 1.5rem;
        }

        .service-description {
            color: #7f8c8d;
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .service-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            text-align: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .detail-label {
            font-size: 0.75rem;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .price-value {
            color: #27ae60;
            font-size: 1.1rem;
        }

        .service-footer {
            padding: 1rem 1.5rem;
            background: #fafbfc;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .service-actions {
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

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Service Details View */
        .service-profile {
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

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 4px solid var(--stat-color);
        }

        .stat-card.total { --stat-color: #3498db; }
        .stat-card.pending { --stat-color: #f39c12; }
        .stat-card.confirmed { --stat-color: #2ecc71; }
        .stat-card.completed { --stat-color: #9b59b6; }
        .stat-card.cancelled { --stat-color: #e74c3c; }

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

        /* Appointments Table */
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

        .section-title {
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
            flex-direction: column;
        }

        .client-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .client-email {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .appointment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .appointment-status.pending { background: #fff3cd; color: #856404; }
        .appointment-status.confirmed { background: #d4edda; color: #155724; }
        .appointment-status.completed { background: #cce7ff; color: #004085; }
        .appointment-status.cancelled { background: #f8d7da; color: #721c24; }

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
            .form-grid {
                grid-template-columns: 1fr;
            }

            .services-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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

            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                min-width: auto;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .service-details {
                grid-template-columns: 1fr;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .service-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .service-actions {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .service-details {
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
                <li><a href="manage-services.php" class="nav-link active"><i class="fas fa-spa"></i> Manage Services</a></li>
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
                    <i class="fas fa-spa"></i>
                    <?php 
                    switch($action) {
                        case 'add': echo 'Add New Service'; break;
                        case 'edit': echo 'Edit Service'; break;
                        case 'view': echo 'Service Details'; break;
                        default: echo 'Manage Services'; break;
                    }
                    ?>
                </h1>
                <div class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a> / 
                    <a href="manage-services.php">Services</a> / 
                    <?php 
                    switch($action) {
                        case 'add': echo 'Add New'; break;
                        case 'edit': echo 'Edit'; break;
                        case 'view': echo 'View Details'; break;
                        default: echo 'All Services'; break;
                    }
                    ?>
                </div>
            </div>
            <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Service
                </a>
            <?php else: ?>
                <a href="manage-services.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Services
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
                <form method="GET">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search Services</label>
                            <input type="text" 
                                   id="search"
                                   name="search" 
                                   class="filter-input" 
                                   placeholder="Search by name or description..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if ($search): ?>
                                    <a href="manage-services.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Services Grid -->
            <?php if (!empty($services)): ?>
                <div class="services-grid">
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <div class="service-header">
                                <div class="service-title">
                                    <i class="fas fa-spa"></i>
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </div>
                            </div>

                            <div class="service-body">
                                <p class="service-description">
                                    <?php echo htmlspecialchars($service['description']); ?>
                                </p>

                                <div class="service-details">
                                    <div class="detail-item">
                                        <div class="detail-label">Price</div>
                                        <div class="detail-value price-value">
                                            $<?php echo number_format($service['price'], 2); ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Duration</div>
                                        <div class="detail-value">
                                            <?php echo $service['duration']; ?> min
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="service-footer">
                                <div class="service-actions">
                                    <a href="?action=view&id=<?php echo $service['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?action=edit&id=<?php echo $service['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Edit Service">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="deleteService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['name']); ?>')" 
                                            class="btn btn-sm btn-danger" title="Delete Service">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

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
                    <i class="fas fa-spa"></i>
                    <h3>No Services Found</h3>
                    <p>
                        <?php if ($search): ?>
                            No services match your search criteria.
                        <?php else: ?>
                            No services have been created yet.
                        <?php endif; ?>
                    </p>
                    <?php if (!$search): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Service
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- Service Form -->
            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Service Name: <span class="required">*</span></label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               required 
                               placeholder="Enter service name"
                               value="<?php echo isset($service_data) ? htmlspecialchars($service_data['name']) : (isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''); ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description: <span class="required">*</span></label>
                        <textarea name="description" 
                                  id="description" 
                                  required 
                                  placeholder="Describe the service, its benefits, and what clients can expect..."><?php echo isset($service_data) ? htmlspecialchars($service_data['description']) : (isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''); ?></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="price">Price ($): <span class="required">*</span></label>
                            <input type="number" 
                                   name="price" 
                                   id="price" 
                                   required 
                                   min="0" 
                                   step="0.01" 
                                   placeholder="0.00"
                                   value="<?php echo isset($service_data) ? $service_data['price'] : (isset($_POST['price']) ? $_POST['price'] : ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="duration">Duration (minutes): <span class="required">*</span></label>
                            <input type="number" 
                                   name="duration" 
                                   id="duration" 
                                   required 
                                   min="1" 
                                   placeholder="60"
                                   value="<?php echo isset($service_data) ? $service_data['duration'] : (isset($_POST['duration']) ? $_POST['duration'] : ''); ?>">
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            <?php echo $action === 'add' ? 'Create Service' : 'Update Service'; ?>
                        </button>
                        <a href="manage-services.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'view' && isset($service_data)): ?>
            <!-- Service Profile View -->
            <div class="service-profile">
                <div class="profile-header">
                    <div class="profile-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <h2><?php echo htmlspecialchars($service_data['name']); ?></h2>
                    <p>Service ID: #<?php echo str_pad($service_data['id'], 4, '0', STR_PAD_LEFT); ?></p>
                </div>

                <div class="profile-details">
                    <div class="detail-grid">
                        <div class="detail-card">
                            <div class="detail-card-label">Service Name</div>
                            <div class="detail-card-value"><?php echo htmlspecialchars($service_data['name']); ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-label">Price</div>
                            <div class="detail-card-value price-value">$<?php echo number_format($service_data['price'], 2); ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-label">Duration</div>
                            <div class="detail-card-value"><?php echo $service_data['duration']; ?> minutes</div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-label">Created</div>
                            <div class="detail-card-value"><?php echo date('F j, Y g:i A', strtotime($service_data['created_at'])); ?></div>
                        </div>

                        <?php if (isset($service_data['updated_at']) && $service_data['updated_at']): ?>
                            <div class="detail-card">
                                <div class="detail-card-label">Last Updated</div>
                                <div class="detail-card-value"><?php echo date('F j, Y g:i A', strtotime($service_data['updated_at'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="detail-card">
                        <div class="detail-card-label">Description</div>
                        <div class="detail-card-value"><?php echo nl2br(htmlspecialchars($service_data['description'])); ?></div>
                    </div>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <a href="?action=edit&id=<?php echo $service_data['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit Service
                        </a>
                        <button onclick="deleteService(<?php echo $service_data['id']; ?>, '<?php echo htmlspecialchars($service_data['name']); ?>')" 
                                class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Service
                        </button>
                    </div>
                </div>
            </div>

            <!-- Service Statistics -->
            <?php if (isset($service_stats)): ?>
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-number"><?php echo $service_stats['total_bookings']; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-number"><?php echo $service_stats['pending_bookings']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card confirmed">
                        <div class="stat-number"><?php echo $service_stats['confirmed_bookings']; ?></div>
                        <div class="stat-label">Confirmed</div>
                    </div>
                    <div class="stat-card completed">
                        <div class="stat-number"><?php echo $service_stats['completed_bookings']; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card cancelled">
                        <div class="stat-number"><?php echo $service_stats['cancelled_bookings']; ?></div>
                        <div class="stat-label">Cancelled</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Service Appointments -->
            <?php if (!empty($service_appointments)): ?>
                <div class="appointments-section">
                    <div class="appointments-header">
                        <h3 class="section-title">
                            <i class="fas fa-calendar"></i>
                            Recent Appointments (<?php echo count($service_appointments); ?>)
                        </h3>
                    </div>

                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Booked On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service_appointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <div class="client-info">
                                            <div class="client-name"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></div>
                                            <div class="client-email"><?php echo htmlspecialchars($appointment['email']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></div>
                                        <small><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="appointment-status <?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($appointment['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
                <p>Are you sure you want to delete this service?</p>
                <p><strong id="deleteServiceName"></strong></p>
                <p style="color: #7f8c8d; margin-top: 1rem;">This action cannot be undone. The service will be permanently removed from the system.</p>
            </div>
            <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" style="display: inline;" id="deleteForm">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Service
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Delete service function
        function deleteService(serviceId, serviceName) {
            document.getElementById('deleteServiceName').textContent = serviceName;
            document.getElementById('deleteForm').action = '?action=delete&id=' + serviceId;
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

        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const price = document.getElementById('price');
            const duration = document.getElementById('duration');

            if (price && parseFloat(price.value) <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0.');
                price.focus();
                return;
            }

            if (duration && parseInt(duration.value) <= 0) {
                e.preventDefault();
                alert('Duration must be greater than 0.');
                duration.focus();
                return;
            }
        });

        // Auto-focus on search field if it has value
        document.addEventListener('DOMContentLoaded', function() {
            const searchField = document.getElementById('search');
            if (searchField && searchField.value) {
                searchField.focus();
                searchField.setSelectionRange(searchField.value.length, searchField.value.length);
            }

            // Animate service cards on load
            const serviceCards = document.querySelectorAll('.service-card');
            serviceCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Price formatting
        document.getElementById('price')?.addEventListener('input', function() {
            let value = parseFloat(this.value);
            if (!isNaN(value) && value >= 0) {
                this.style.color = '#27ae60';
            } else {
                this.style.color = '#e74c3c';
            }
        });

        // Duration formatting
        document.getElementById('duration')?.addEventListener('input', function() {
            let value = parseInt(this.value);
            if (!isNaN(value) && value > 0) {
                this.style.color = '#27ae60';
            } else {
                this.style.color = '#e74c3c';
            }
        });
    </script>
</body>
</html>