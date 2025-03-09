<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/db_connect.php';

// Check if required fields are provided
if (!isset($_POST['id']) || empty($_POST['id']) ||
    !isset($_POST['firstname']) || empty($_POST['firstname']) ||
    !isset($_POST['lastname']) || empty($_POST['lastname']) ||
    !isset($_POST['year_level']) || !isset($_POST['course_id'])) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit();
}

$id = intval($_POST['id']);
$firstname = trim($_POST['firstname']);
$lastname = trim($_POST['lastname']);
$year_level = intval($_POST['year_level']);
$course_id = intval($_POST['course_id']);

// Validate year level
if ($year_level < 1 || $year_level > 4) {
    echo json_encode(['success' => false, 'message' => 'Invalid year level']);
    exit();
}

// Update the student record
$query = "UPDATE users SET 
          firstname = ?, 
          lastname = ?, 
          year_level = ?, 
          course_id = ? 
          WHERE id = ? AND role = 'student'";

$stmt = $conn->prepare($query);
$stmt->bind_param('ssiii', $firstname, $lastname, $year_level, $course_id, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
