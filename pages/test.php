<?php
// filepath: c:\xampp\htdocs\icbt\02\greenlife-wellness-center\pages\test.php

$page_title = "Test Page - GreenLife Wellness Center";
include '../includes/header.php';
?>

<main style="min-height: 60vh; padding: 2rem;">
    <div class="container">
        <h1>Test Page</h1>
        <p>This is a test page to check if header and footer are working correctly.</p>
        
        <div style="background: #f0f0f0; padding: 2rem; margin: 2rem 0; border-radius: 10px;">
            <h2>Page Structure Check:</h2>
            <ul>
                <li>✅ Header should appear at the top</li>
                <li>✅ This content should be in the middle</li>
                <li>✅ Footer should appear at the bottom</li>
            </ul>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>