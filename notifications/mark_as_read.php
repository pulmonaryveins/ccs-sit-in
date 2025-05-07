<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get the notification ID from POST data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;

if (!$notification_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing notification ID']);
    exit;
}

// Make sure the notification belongs to this user
$username = $_SESSION['username'];
$query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $notification_id, $username);
$result = $stmt->execute();

if ($result) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to update notification']);
}
$stmt->close();
$conn->close();
?>