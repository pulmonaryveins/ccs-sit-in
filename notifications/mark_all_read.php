<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$username = $_SESSION['username'];

// Update all notifications to read for this user
$query = "UPDATE notifications SET is_read = 1 WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$success = $stmt->execute();
$stmt->close();

// Return result
header('Content-Type: application/json');
echo json_encode(['success' => $success]);
$conn->close();
?>