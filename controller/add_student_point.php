<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if required data is provided
if (!isset($_POST['idno']) || empty($_POST['idno'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

require_once '../config/db_connect.php';

// Get student ID
$idno = $conn->real_escape_string($_POST['idno']);

// Start transaction
$conn->begin_transaction();

try {
    // Get current points of the student
    $query = "SELECT points, remaining_sessions FROM users WHERE idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $idno);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Student not found");
    }
    
    $student = $result->fetch_assoc();
    $currentPoints = $student['points'];
    $currentSessions = $student['remaining_sessions'];
    
    // Add 1 point
    $newPoints = $currentPoints + 1;
    $additionalSessions = 0;
    $message = "1 point has been added successfully.";
    
    // Check if the student has reached 3 points
    if ($newPoints >= 3) {
        // Calculate how many sessions to add
        $sessionsToAdd = floor($newPoints / 3);
        $newPoints = $newPoints % 3; // Remaining points after conversion
        $newSessions = $currentSessions + $sessionsToAdd;
        $additionalSessions = $sessionsToAdd;
        
        // Update message
        $message = "1 point has been added. " . 
                  $sessionsToAdd . " session" . ($sessionsToAdd > 1 ? "s" : "") . 
                  " added by converting " . ($sessionsToAdd * 3) . " points.";
        
        // Update the student's points and sessions
        $updateQuery = "UPDATE users SET points = ?, remaining_sessions = ? WHERE idno = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("iis", $newPoints, $newSessions, $idno);
    } else {
        // Just update points
        $updateQuery = "UPDATE users SET points = ? WHERE idno = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("is", $newPoints, $idno);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update student points");
    }
    
    // Record this action in a log if needed
    // ...
    
    // Commit the transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'newPoints' => $newPoints,
        'additionalSessions' => $additionalSessions
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

// Close connection
$conn->close();
?>
