<?php
session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;

if (!$notification_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
    exit;
}

// Update notification status
$query = "UPDATE admin_notifications SET is_read = 1 WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $notification_id);
$success = $stmt->execute();
$stmt->close();

// Return result
header('Content-Type: application/json');
echo json_encode(['success' => $success]);
$conn->close();
?>
