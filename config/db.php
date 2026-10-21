<?php
// config/db.php
// Database connection configuration using MySQLi

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'dochelp_db';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
