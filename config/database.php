<?php
$db_host = 'localhost';
$db_name = 'artsphere';
$db_user = 'root'; // Default XAMPP username
$db_pass = '';     // Default XAMPP password

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the actual error for debugging but don't expose it to the user
    error_log("Database Connection Error: " . $e->getMessage());
    die("A database connection error occurred. Please try again later.");
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/ArtSphere/');
}
?>
