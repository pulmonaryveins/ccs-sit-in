<?php
session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate that required fields exist
if (!isset($_POST['id']) || !isset($_POST['firstname']) || !isset($_POST['lastname']) || 
    !isset($_POST['year_level']) || !isset($_POST['course_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Get the form data
$id = $_POST['id'];
$firstname = trim($_POST['firstname']);
$lastname = trim($_POST['lastname']);
$year_level = $_POST['year_level'];
$course_id = $_POST['course_id'];

// Basic validation
if (empty($firstname) || empty($lastname)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Name fields cannot be empty']);
    exit;
}

// Update student information
$query = "UPDATE users SET firstname = ?, lastname = ?, year_level = ?, course_id = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssisi", $firstname, $lastname, $year_level, $course_id, $id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Student information updated successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
