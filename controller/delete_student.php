<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/db_connect.php';

// Check if student ID was provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

$student_id = intval($_POST['id']);

// Begin transaction
$conn->begin_transaction();

try {
    // First, delete any reservations associated with this user
    $query = "DELETE FROM reservations WHERE idno IN (SELECT idno FROM users WHERE id = ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $stmt->close();
    
    // Then, delete the user record
    $query = "DELETE FROM users WHERE id = ? AND role = 'student'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    
    // If no rows were affected, the student might not exist
    if ($stmt->affected_rows === 0) {
        throw new Exception("Student not found or not deleted");
    }
    
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
