<?php
session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get unread count
$unread_count = 0;
$unread_query = "SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0";
$unread_result = $conn->query($unread_query);
if ($unread_result && $row = $unread_result->fetch_assoc()) {
    $unread_count = $row['count'];
}

// Get notifications - limited to 15 most recent
$notifications = [];
$query = "SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 15";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Format the date for display
        $date = new DateTime($row['created_at']);
        $now = new DateTime();
        $interval = $now->diff($date);
        
        if ($interval->days > 0) {
            if ($interval->days == 1) {
                $time_ago = "Yesterday";
            } else {
                $time_ago = $interval->days . " days ago";
            }
        } elseif ($interval->h > 0) {
            $time_ago = $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " ago";
        } elseif ($interval->i > 0) {
            $time_ago = $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " ago";
        } else {
            $time_ago = "Just now";
        }
        
        $notifications[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'content' => $row['content'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $time_ago,
            'related_id' => $row['related_id'],
            'related_type' => $row['related_type']
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);

$conn->close();
?>
