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

// Check if ID is provided
if (!isset($_POST['idno']) || empty($_POST['idno'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

$idno = $_POST['idno'];

// Get current student information
$query = "SELECT firstname, lastname, remaining_sessions FROM users WHERE idno = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit();
}

$student = $result->fetch_assoc();

// Check if student already has 30 sessions
if ($student['remaining_sessions'] >= 30) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Student already has the maximum of 30 sessions',
        'student_name' => $student['firstname'] . ' ' . $student['lastname']
    ]);
    exit();
}

// Update the student's sessions to 30
$query = "UPDATE users SET remaining_sessions = 30 WHERE idno = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $idno);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Sessions reset successfully',
        'student_name' => $student['firstname'] . ' ' . $student['lastname']
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
