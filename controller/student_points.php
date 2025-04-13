<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once '../config/db_connect.php';

// Check if ID is provided
if (!isset($_POST['idno']) || empty($_POST['idno'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

$idno = $_POST['idno'];

// Set content type to JSON
header('Content-Type: application/json');

// Get current student information
$query = "SELECT firstname, lastname, points, remaining_sessions FROM users WHERE idno = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit();
}

$student = $result->fetch_assoc();
$current_points = $student['points'];
$current_sessions = $student['remaining_sessions'];
$new_points = $current_points + 1;

// Check if we should convert 3 points to a session
$new_sessions = $current_sessions;
$message = "";

if ($new_points >= 3) {
    // Convert points to sessions
    $sessions_to_add = floor($new_points / 3);
    $new_sessions = $current_sessions + $sessions_to_add;
    $new_points = $new_points % 3; // Remaining points after conversion
    
    $message = "Added 1 point to " . $student['firstname'] . " " . $student['lastname'] . ". ";
    
    if ($sessions_to_add > 0) {
        $message .= "Converted " . ($sessions_to_add * 3) . " points to " . $sessions_to_add . " session(s). ";
    }
    
    $message .= "New totals: " . $new_points . " points and " . $new_sessions . " sessions.";
} else {
    $message = "Added 1 point to " . $student['firstname'] . " " . $student['lastname'] . ". ";
    $message .= "New total: " . $new_points . " points.";
}

// Update the student's points and sessions
$query = "UPDATE users SET points = ?, remaining_sessions = ? WHERE idno = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $new_points, $new_sessions, $idno);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'points' => $new_points,
        'sessions' => $new_sessions
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update points: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>