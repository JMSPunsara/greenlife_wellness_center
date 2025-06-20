<?php
echo "<h1>Debug Page</h1>";
echo "<p>Testing includes...</p>";

echo "<hr><h2>Header Content:</h2>";
include '../includes/header.php';

echo "<hr><h2>Main Content Area</h2>";
echo "<p>This should be between header and footer</p>";

echo "<hr><h2>Footer Content:</h2>";
include '../includes/footer.php';
?>