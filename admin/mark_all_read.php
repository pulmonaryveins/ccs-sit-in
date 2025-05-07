<?php
session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Update all notifications to read
$query = "UPDATE admin_notifications SET is_read = 1";
$success = $conn->query($query);

// Return result
header('Content-Type: application/json');
echo json_encode(['success' => $success]);
$conn->close();
?>
