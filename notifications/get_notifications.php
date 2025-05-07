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

// Get unread count
$unread_count = 0;
$unread_query = "SELECT COUNT(*) as count FROM notifications WHERE username = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("s", $username);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
if ($unread_result && $row = $unread_result->fetch_assoc()) {
    $unread_count = $row['count'];
}
$unread_stmt->close();

// Get notifications - limited to 15 most recent
$notifications = [];
$query = "SELECT id, title, content, is_read, DATE_FORMAT(created_at, '%m/%d/%Y %h:%i %p') as created_at 
          FROM notifications 
          WHERE username = ? 
          ORDER BY created_at DESC LIMIT 15";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
$stmt->close();

// Send data
header('Content-Type: application/json');
echo json_encode([
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);

$conn->close();
?>