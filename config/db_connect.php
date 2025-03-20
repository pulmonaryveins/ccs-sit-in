<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'monitor';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone for PHP
date_default_timezone_set('Asia/Manila');

// Set timezone for MySQL session
$conn->query("SET time_zone = '+08:00'");
