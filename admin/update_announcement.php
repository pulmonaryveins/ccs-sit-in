<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../config/db_connect.php';

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['content'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

// Sanitize inputs
$id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
$title = mysqli_real_escape_string($conn, $data['title']);
$content = mysqli_real_escape_string($conn, $data['content']);

// Update the announcement
$query = "UPDATE announcements SET title = '$title', content = '$content' WHERE id = $id";

if ($conn->query($query)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>
