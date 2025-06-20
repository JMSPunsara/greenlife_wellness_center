<?php
// filepath: c:\xampp\htdocs\icbt\02\greenlife-wellness-center\error.php

$error_code = $_GET['code'] ?? '404';
$error_messages = [
    '400' => 'Bad Request',
    '401' => 'Unauthorized',
    '403' => 'Forbidden',
    '404' => 'Page Not Found',
    '500' => 'Internal Server Error'
];

$title = $error_messages[$error_code] ?? 'Error';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - GreenLife Wellness Center</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f7fa; }
        .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; font-size: 4rem; margin: 0; }
        h2 { color: #2c3e50; margin: 20px 0; }
        p { color: #7f8c8d; margin-bottom: 30px; }
        .btn { background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1><?php echo $error_code; ?></h1>
        <h2><?php echo $title; ?></h2>
        <p>The page you're looking for could not be found.</p>
        <a href="/" class="btn">Go Home</a>
    </div>
</body>
</html>