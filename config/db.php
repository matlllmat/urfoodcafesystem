<?php
// Database credentials - change these for your environment
// Local (XAMPP):  host=localhost, user=root, pass='', db=urfoodhubcafe_db
// Hosting:        update with your hosting provider's credentials
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'urfoodhubcafe_db');
// For InfinityFree hosting, use:
// define('DB_USER', 'if0_41136192');
// define('DB_PASS', 'your_hosting_password');
// define('DB_NAME', 'if0_41136192_urfoodhubcafe_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>