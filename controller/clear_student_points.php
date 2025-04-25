<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if the idno parameter is provided
if (!isset($_POST['idno']) || empty($_POST['idno'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

require_once '../config/db_connect.php';

$idno = $_POST['idno'];

// Validate the student exists
$check_sql = "SELECT * FROM users WHERE idno = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $idno);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Get current points before clearing
    $get_points_sql = "SELECT points, firstname, lastname FROM users WHERE idno = ?";
    $get_points_stmt = $conn->prepare($get_points_sql);
    $get_points_stmt->bind_param("s", $idno);
    $get_points_stmt->execute();
    $points_result = $get_points_stmt->get_result();
    $student_data = $points_result->fetch_assoc();
    $current_points = $student_data['points'];
    $student_name = $student_data['firstname'] . ' ' . $student_data['lastname'];
    
    // If student already has 0 points, no need to update
    if ($current_points == 0) {
        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Student already has 0 points']);
        exit();
    }
    
    // Update student points to 0
    $update_sql = "UPDATE users SET points = 0 WHERE idno = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("s", $idno);
    $update_stmt->execute();
    
    if ($update_stmt->affected_rows > 0) {
        // Log the action
        $admin_username = $_SESSION['username'];
        $action = "Cleared points for $student_name (ID: $idno, Points cleared: $current_points)";
        
        // Check if admin_logs table exists
        $tables_result = $conn->query("SHOW TABLES LIKE 'admin_logs'");
        
        // If admin_logs table exists, log the action
        if ($tables_result->num_rows > 0) {
            $log_sql = "INSERT INTO admin_logs (admin_username, action, timestamp) VALUES (?, ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("ss", $admin_username, $action);
            $log_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => "All points ($current_points) for this student have been cleared successfully"]);
    } else {
        // Rollback if no rows affected
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to clear points']);
    }
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

$conn->close();
?>
