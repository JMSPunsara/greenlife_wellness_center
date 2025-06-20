-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
-- Host: 127.0.0.1
-- Generation Time: Jun 20, 2025 at 05:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Creating Database `greenlife_wellness_center`
CREATE DATABASE IF NOT EXISTS greenlife_wellness_center;
USE greenlife_wellness_center;

-- Table structure for table `admins`
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserting sample data for table `admins`
INSERT INTO `admins` (`first_name`, `last_name`, `email`, `password`, `created_at`, `updated_at`) VALUES
('Shashin', 'Punsara', 'admin@greenlife.com', '$2y$10$SWsVPwo08WCIiXtha9MUb.yYUhL/AkmSJF5hYBqsPPnnEV9Kxyx5K', '2025-06-20 14:23:51', '2025-06-20 14:25:56');

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin','therapist') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserting sample data for table `users`
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `phone`, `role`, `created_at`, `updated_at`) VALUES
('admin', 'admin@greenlife.com', '123456', 'Administrator', '', '+1755852558565', 'admin', '2025-06-20 12:03:16', '2025-06-20 12:07:58'),
('shahan', 'shahaniresh@outlook.com', '123456', 'Shahan', 'Iresh', '0789954632', 'customer', '2025-06-20 12:06:22', '2025-06-20 14:40:45'),
('esara', 'e154631@esoft.academy', '$2y$10$4tGofaQwWrWwscNTvTQi.O57KSdaRjYbIqaGdgtlyf/q4SI5fq5de', 'Esara', 'Prageeth', '0789926314', 'customer', '2025-06-20 12:17:29', '2025-06-20 12:17:29'),
('shashin', 'shashinpunsara@gmail.com', '$2y$10$.WqVy.LsWn8jT/8RU54v6.rCUCJKdbLh4L7mlgf31bzZM9vsvFhcy', 'J M Shashin', 'Punsara', '0779101841', 'customer', '2025-06-20 12:18:59', '2025-06-20 12:18:59'),
('admin2', 'admin2@greenlife.com', '$2y$10$.WqVy.LsWn8jT/8RU54v6.rCUCJKdbLh4L7mlgf31bzZM9vsvFhcy', 'Rohan', 'Ranajeewa', '07855236541', 'admin', '2025-06-19 18:30:00', '2025-06-20 12:24:03');

-- Table structure for table `services`
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserting sample data for table `services`
INSERT INTO services (name, description, duration, price) VALUES 
('Relaxing Massage', 'A soothing full-body massage to relieve stress and tension', 60, 80.00),
('Deep Tissue Massage', 'Intensive massage targeting deep muscle layers and knots', 90, 120.00),
('Facial Treatment', 'Rejuvenating facial treatment for healthy, glowing skin', 45, 65.00),
('Aromatherapy Session', 'Therapeutic treatment using essential oils for relaxation', 75, 95.00),
('Hot Stone Therapy', 'Relaxing massage using heated stones to ease muscle tension', 90, 110.00);

-- Table structure for table `appointments`
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    notes TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- Inserting sample data for table `appointments`
INSERT INTO appointments (user_id, service_id, appointment_date, appointment_time, notes, status) VALUES 
(1, 1, '2024-12-25', '10:00:00', 'First-time client, prefers relaxing massage', 'confirmed'),
(2, 2, '2024-12-26', '14:30:00', 'Regular client, focus on back pain', 'pending'),
(1, 3, '2024-12-27', '11:00:00', 'Special occasion facial treatment', 'confirmed');

COMMIT;
