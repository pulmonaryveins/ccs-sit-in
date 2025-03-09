<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// For debugging
error_log('POST data: ' . print_r($_POST, true));

// Check if any ID parameter is provided
if (empty($_POST['reservation_id']) && empty($_POST['sit_in_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ID parameter']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    $current_time = date('H:i:s');
    $id = null;
    $laboratory = null;
    $pc_number = null;
    $table_type = null;

    // Handle reservation time out
    if (!empty($_POST['reservation_id'])) {
        $id = $_POST['reservation_id'];
        $table_type = 'reservation';
        
        // Get laboratory and PC number
        $query = "SELECT laboratory, pc_number FROM reservations WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Reservation not found");
        }
        
        $row = $result->fetch_assoc();
        $laboratory = $row['laboratory'];
        $pc_number = $row['pc_number'];
        
        // Update reservation status
        $updateQuery = "UPDATE reservations SET time_out = ?, status = 'completed' WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('si', $current_time, $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update reservation: " . $stmt->error);
        }
    }
    // Handle sit-in time out
    else if (!empty($_POST['sit_in_id'])) {
        $id = $_POST['sit_in_id'];
        $table_type = 'sit_in';
        
        // Get laboratory and PC number
        $query = "SELECT laboratory, pc_number FROM sit_ins WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Sit-in record not found");
        }
        
        $row = $result->fetch_assoc();
        $laboratory = $row['laboratory'];
        $pc_number = $row['pc_number'];
        
        // Update sit_in status
        $updateQuery = "UPDATE sit_ins SET time_out = ?, status = 'completed' WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('si', $current_time, $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update sit-in record: " . $stmt->error);
        }
    }
    else {
        throw new Exception("Invalid request: No valid ID provided");
    }

    // Update PC status to available
    $updatePcQuery = "UPDATE computer_status SET status = 'available' WHERE laboratory = ? AND pc_number = ?";
    $stmt = $conn->prepare($updatePcQuery);
    $stmt->bind_param('si', $laboratory, $pc_number);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update PC status: " . $stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => ucfirst($table_type) . ' completed successfully',
        'time_out' => $current_time
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
