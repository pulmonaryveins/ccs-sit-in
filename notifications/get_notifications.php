<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get the user ID from the session
$username = $_SESSION['username'];

// Get user ID from username
$query = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not found']);
    exit;
}

$user_id = $result->fetch_assoc()['id'];

// Get unread notifications count
$count_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result();
$unread_count = $count_result->fetch_assoc()['count'];

// Get notifications with announcement details
$query = "SELECT n.id, n.is_read, n.created_at, a.title, a.content, a.id as announcement_id
          FROM notifications n 
          JOIN announcements a ON n.announcement_id = a.id
          WHERE n.user_id = ?
          ORDER BY n.created_at DESC
          LIMIT 10";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'content' => mb_strimwidth($row['content'], 0, 100, '...'),
        'is_read' => (bool)$row['is_read'],
        'created_at' => date('M d, h:i A', strtotime($row['created_at'])),
        'announcement_id' => $row['announcement_id']
    ];
}

// Return the notifications
header('Content-Type: application/json');
echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);

$conn->close();
?>
