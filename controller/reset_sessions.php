<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    // Return error if not authenticated
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once '../config/db_connect.php';

// Check if the IDNO parameter is provided
if (!isset($_POST['idno']) || empty($_POST['idno'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

$idno = $_POST['idno'];
$default_sessions = 30; // Default number of sessions
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// Begin transaction to ensure data consistency
$conn->begin_transaction();

try {
    // 1. Update the remaining_sessions in the users table
    $query_users = "UPDATE users SET remaining_sessions = ? WHERE idno = ?";
    $stmt = $conn->prepare($query_users);
    $stmt->bind_param("is", $default_sessions, $idno);
    $result = $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("No user found with ID: $idno");
    }
    
    // 2. Optionally, log the reset action in sit_ins table (without the remaining_sessions column)
    $query = "INSERT INTO sit_ins 
              (idno, laboratory, pc_number, purpose, date, time_in, status) 
              VALUES (?, 0, 0, 'ADMIN_RESET', ?, ?, 'reset')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $idno, $current_date, $current_time);
    $stmt->execute();
    
    // 3. Also update any existing pending reservations
    $query_reservations = "UPDATE reservations 
                          SET status = 'reset' 
                          WHERE idno = ? AND status = 'pending'";
                          
    $stmt = $conn->prepare($query_reservations);
    $stmt->bind_param("s", $idno);
    $stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Sessions reset successfully']);
    
} catch (Exception $e) {
    // An error occurred, rollback the transaction
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
