<?php
// filepath: c:\xampp\htdocs\icbt\02\greenlife-wellness-center\includes\functions.php

function authenticateUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    
    return false;
}

function registerUser($username, $email, $password, $full_name, $phone) {
    global $pdo;
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'client')");
    return $stmt->execute([$username, $email, $hashedPassword, $full_name, $phone]);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserById($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getServices() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM services ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function bookAppointment($user_id, $service_id, $therapist_id, $appointment_date, $appointment_time) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, service_id, therapist_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    return $stmt->execute([$user_id, $service_id, $therapist_id, $appointment_date, $appointment_time]);
}
?>