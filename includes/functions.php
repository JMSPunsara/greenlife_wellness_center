<?php
// functions.php

// Prevent multiple inclusions
if (!function_exists('sanitizeInput')) {

// Function to sanitize user input
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Function to check if a user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to redirect to a specific page
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to set a flash message
function setFlashMessage($message) {
    $_SESSION['flash_message'] = $message;
}

// Function to get a flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return '';
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to hash passwords
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify passwords
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to authenticate user
function authenticateUser($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

// Function to register a new user
function registerUser($username, $email, $password, $first_name, $last_name, $phone = '') {
    global $pdo;
    
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone, role, created_at) VALUES (?, ?, ?, ?, ?, ?, 'customer', NOW())");
        return $stmt->execute([$username, $email, $hashedPassword, $first_name, $last_name, $phone]);
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

// Function to get user by ID
function getUserById($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        return false;
    }
}

// Function to get all services
function getServices() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM services ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get services error: " . $e->getMessage());
        return [];
    }
}

// Function to get service by ID
function getServiceById($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get service error: " . $e->getMessage());
        return false;
    }
}

// Function to book an appointment
function bookAppointment($user_id, $service_id, $therapist_id, $appointment_date, $appointment_time, $notes = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO appointments (user_id, service_id, therapist_id, appointment_date, appointment_time, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
        return $stmt->execute([$user_id, $service_id, $therapist_id, $appointment_date, $appointment_time, $notes]);
    } catch (PDOException $e) {
        error_log("Book appointment error: " . $e->getMessage());
        return false;
    }
}

// Function to get user appointments
function getUserAppointments($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, s.name as service_name, s.duration, s.price 
            FROM appointments a 
            JOIN services s ON a.service_id = s.id 
            WHERE a.user_id = ? 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get appointments error: " . $e->getMessage());
        return [];
    }
}

// Function to check if username exists
function usernameExists($username) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Check username error: " . $e->getMessage());
        return true; // Return true to be safe
    }
}

// Function to check if email exists
function emailExists($email) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Check email error: " . $e->getMessage());
        return true; // Return true to be safe
    }
}

// Function to logout user
function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
    redirect('../index.php');
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Function to get current user
function getCurrentUser() {
    if (isLoggedIn()) {
        return getUserById($_SESSION['user_id']);
    }
    return false;
}

} // End of include guard
?>