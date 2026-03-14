<?php
// /var/www/html/gold-tracker/db_config.php
$host = 'localhost';
$db   = 'db_gold';
$user = 'root';
$pass = 'root'; // Sesuaikan

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
?>
