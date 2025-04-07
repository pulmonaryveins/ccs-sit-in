<?php
// Start session
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once '../config/db_connect.php';

// Count students who need sessions reset (less than 30)
$check_query = "SELECT COUNT(*) as count FROM users WHERE remaining_sessions < 30";
$check_result = $conn->query($check_query);
$count_data = $check_result->fetch_assoc();

if ($count_data['count'] == 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All students already have the maximum of 30 sessions']);
    exit();
}

// Update only students with less than 30 sessions
$query = "UPDATE users SET remaining_sessions = 30 WHERE remaining_sessions < 30";
$result = $conn->query($query);

if ($result) {
    $affected_rows = $conn->affected_rows;
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Sessions reset successfully',
        'affected_students' => $affected_rows
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>
