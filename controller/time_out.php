<?php
session_start();
require_once '../config/db_connect.php';

// Check if request is POST and sit_in_id is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sit_in_id'])) {
    $sit_in_id = $_POST['sit_in_id'];
    $response = ['success' => false, 'message' => ''];
    
    // Start transaction for data consistency
    $conn->begin_transaction();
    
    try {
        // Update sit_in record with time_out and status
        $current_time = date('H:i:s');
        $update_sitin = $conn->prepare("UPDATE sit_ins SET time_out = ?, status = 'completed' WHERE id = ? AND time_out IS NULL");
        $update_sitin->bind_param('si', $current_time, $sit_in_id);
        
        if ($update_sitin->execute()) {
            // Get the student ID number from the sit_in record
            $get_student = $conn->prepare("SELECT idno, laboratory, pc_number FROM sit_ins WHERE id = ?");
            $get_student->bind_param('i', $sit_in_id);
            $get_student->execute();
            $result = $get_student->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $idno = $row['idno'];
                $laboratory = $row['laboratory'];
                $pc_number = $row['pc_number'];
                
                // Update computer status to available
                $update_pc = $conn->prepare("UPDATE computer_status SET status = 'available' WHERE laboratory = ? AND pc_number = ?");
                $update_pc->bind_param('si', $laboratory, $pc_number);
                $update_pc->execute();
                
                // Deduct one session from the student's remaining_sessions
                $update_sessions = $conn->prepare("UPDATE users SET remaining_sessions = GREATEST(remaining_sessions - 1, 0) WHERE idno = ?");
                $update_sessions->bind_param('s', $idno);
                
                if ($update_sessions->execute()) {
                    // Get updated remaining sessions
                    $get_remaining = $conn->prepare("SELECT remaining_sessions FROM users WHERE idno = ?");
                    $get_remaining->bind_param('s', $idno);
                    $get_remaining->execute();
                    $sessions_result = $get_remaining->get_result();
                    $sessions_row = $sessions_result->fetch_assoc();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $remaining_sessions = $sessions_row ? $sessions_row['remaining_sessions'] : 0;
                    $response = [
                        'success' => true,
                        'message' => 'Student timed out successfully.',
                        'remaining_sessions' => $remaining_sessions
                    ];
                } else {
                    throw new Exception("Failed to update remaining sessions.");
                }
            } else {
                throw new Exception("Could not find student information.");
            }
        } else {
            throw new Exception("Failed to update sit-in record.");
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing sit_in_id']);
}
?>
