<?php
session_start();
// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../config/db_connect.php';

// Reset all users' remaining_sessions back to 30
$query = "UPDATE users SET remaining_sessions = 30";
$result = $conn->query($query);

if ($result) {
    // Get count of affected rows
    $count = $conn->affected_rows;
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'count' => $count]);
} else {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>
