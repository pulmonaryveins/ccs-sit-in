<?php
session_start();
// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../config/db_connect.php';

// First, count total records to be deleted
$count_query = "SELECT 
                (SELECT COUNT(*) FROM reservations) + 
                (SELECT COUNT(*) FROM sit_ins) AS total_records";
$count_result = $conn->query($count_query);
$count = 0;

if ($count_result && $row = $count_result->fetch_assoc()) {
    $count = $row['total_records'];
}

// Begin transaction to ensure both tables are cleared successfully
$conn->begin_transaction();

try {
    // Delete all records from reservations table
    $query1 = "DELETE FROM reservations";
    $result1 = $conn->query($query1);
    
    // Delete all records from sit_ins table
    $query2 = "DELETE FROM sit_ins";
    $result2 = $conn->query($query2);
    
    if ($result1 && $result2) {
        // Commit the transaction
        $conn->commit();
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'count' => $count]);
    } else {
        // Roll back the transaction if either query fails
        $conn->rollback();
        
        // Return error response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} catch (Exception $e) {
    // Roll back the transaction if an exception occurs
    $conn->rollback();
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
}

$conn->close();
?>
