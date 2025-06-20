<?php
session_start();
include 'config/database.php';
include 'includes/header.php';

// Redirect to home page
header('Location: pages/home.php');
exit();
?>