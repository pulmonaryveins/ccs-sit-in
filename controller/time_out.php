<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../config/db_connect.php';

// Set timezone to Manila (GMT+8)
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sit_in_id'])) {
    $sit_in_id = intval($_POST['sit_in_id']);
    
    // Get current date and time in GMT+8
    $current_time = date('H:i:s'); // Current time in 24-hour format
    
    // Update the sit-in record with time_out and status
    $stmt = $conn->prepare("UPDATE sit_ins SET time_out = ?, status = 'completed' WHERE id = ?");
    $stmt->bind_param("si", $current_time, $sit_in_id);
    
    if ($stmt->execute()) {
        // Get PC details to update availability
        $stmt2 = $conn->prepare("SELECT laboratory, pc_number FROM sit_ins WHERE id = ?");
        $stmt2->bind_param("i", $sit_in_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Update PC status to available
            $stmt3 = $conn->prepare("UPDATE computer_status SET status = 'available' WHERE laboratory = ? AND pc_number = ?");
            $stmt3->bind_param("si", $row['laboratory'], $row['pc_number']);
            $stmt3->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Time out recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating record: ' . $stmt->error]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
