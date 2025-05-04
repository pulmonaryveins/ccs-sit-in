<?php
session_start();
require_once '../config/db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Initialize response
$response = ['success' => false, 'message' => 'Invalid request'];

// Get record type (sit_in or reservation)
$record_type = isset($_POST['record_type']) ? $_POST['record_type'] : 'sit_in';

// Determine which ID to use based on record type
if ($record_type === 'sit_in') {
    // Handle sit_in timeout
    if (isset($_POST['sit_in_id'])) {
        $sit_in_id = intval($_POST['sit_in_id']);
        $time_out = isset($_POST['time_out']) ? $_POST['time_out'] : date('H:i:s');
        $admin_timeout = isset($_POST['admin_timeout']) && $_POST['admin_timeout'] === 'true';
        
        // First, get the student's ID number to update remaining sessions
        $query = "SELECT idno, laboratory, pc_number FROM sit_ins WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $sit_in_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $idno = $row['idno'];
            $laboratory = $row['laboratory'];
            $pc_number = $row['pc_number'];
            
            // Update the sit_in record
            $update_query = "UPDATE sit_ins SET time_out = ?, status = 'completed' WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('si', $time_out, $sit_in_id);
            
            if ($update_stmt->execute()) {
                // Decrement remaining sessions for the student
                $sessions_query = "UPDATE users SET remaining_sessions = GREATEST(0, remaining_sessions - 1) WHERE idno = ?";
                $sessions_stmt = $conn->prepare($sessions_query);
                $sessions_stmt->bind_param('s', $idno);
                $sessions_stmt->execute();
                
                // Get the updated number of remaining sessions
                $remaining_query = "SELECT remaining_sessions FROM users WHERE idno = ?";
                $remaining_stmt = $conn->prepare($remaining_query);
                $remaining_stmt->bind_param('s', $idno);
                $remaining_stmt->execute();
                $remaining_result = $remaining_stmt->get_result();
                $remaining_sessions = 0;
                
                if ($remaining_row = $remaining_result->fetch_assoc()) {
                    $remaining_sessions = (int)$remaining_row['remaining_sessions'];
                }
                
                // Update the computer status to 'available'
                if (!empty($laboratory) && !empty($pc_number)) {
                    $computer_update_query = "UPDATE computer_status SET status = 'available' WHERE laboratory = ? AND pc_number = ?";
                    $computer_update_stmt = $conn->prepare($computer_update_query);
                    $computer_update_stmt->bind_param('si', $laboratory, $pc_number);
                    $computer_update_stmt->execute();
                    
                    // Add information about updated computer to response
                    $response = [
                        'success' => true,
                        'message' => 'Walk-in sit-in timed out successfully',
                        'remaining_sessions' => $remaining_sessions,
                        'computer_updated' => true,
                        'laboratory' => $laboratory,
                        'pc_number' => $pc_number
                    ];
                } else {
                    $response = [
                        'success' => true,
                        'message' => 'Walk-in sit-in timed out successfully',
                        'remaining_sessions' => $remaining_sessions,
                        'computer_updated' => false
                    ];
                }
            } else {
                $response = ['success' => false, 'message' => 'Failed to update walk-in sit-in record: ' . $conn->error];
            }
        } else {
            $response = ['success' => false, 'message' => 'Sit-in record not found'];
        }
    }
} else if ($record_type === 'reservation') {
    // Handle reservation timeout
    if (isset($_POST['reservation_id'])) {
        $reservation_id = intval($_POST['reservation_id']);
        $time_out = isset($_POST['time_out']) ? $_POST['time_out'] : date('H:i:s');
        $admin_timeout = isset($_POST['admin_timeout']) && $_POST['admin_timeout'] === 'true';
        
        // Get laboratory and PC number from form data if provided
        $laboratory = isset($_POST['laboratory']) ? $_POST['laboratory'] : null;
        $pc_number = isset($_POST['pc_number']) ? intval($_POST['pc_number']) : null;
        
        // First, get the student's ID number and reservation details to update remaining sessions
        $query = "SELECT idno, laboratory, pc_number FROM reservations WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $idno = $row['idno'];
            // Use database values if not provided in the form
            $laboratory = $laboratory ?? $row['laboratory']; 
            $pc_number = $pc_number ?? $row['pc_number'];
            
            // Update the reservation record
            $update_query = "UPDATE reservations SET time_out = ?, status = 'completed' WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('si', $time_out, $reservation_id);
            
            if ($update_stmt->execute()) {
                // Decrement remaining sessions for the student
                $sessions_query = "UPDATE users SET remaining_sessions = GREATEST(0, remaining_sessions - 1) WHERE idno = ?";
                $sessions_stmt = $conn->prepare($sessions_query);
                $sessions_stmt->bind_param('s', $idno);
                $sessions_stmt->execute();
                
                // Get the updated number of remaining sessions
                $remaining_query = "SELECT remaining_sessions FROM users WHERE idno = ?";
                $remaining_stmt = $conn->prepare($remaining_query);
                $remaining_stmt->bind_param('s', $idno);
                $remaining_stmt->execute();
                $remaining_result = $remaining_stmt->get_result();
                $remaining_sessions = 0;
                
                if ($remaining_row = $remaining_result->fetch_assoc()) {
                    $remaining_sessions = (int)$remaining_row['remaining_sessions'];
                }
                
                // Update the computer status to 'available'
                if (!empty($laboratory) && !empty($pc_number)) {
                    $computer_update_query = "UPDATE computer_status SET status = 'available' WHERE laboratory = ? AND pc_number = ?";
                    $computer_update_stmt = $conn->prepare($computer_update_query);
                    $computer_update_stmt->bind_param('si', $laboratory, $pc_number);
                    $computer_update_result = $computer_update_stmt->execute();
                    
                    // Add information about updated computer to response
                    $response = [
                        'success' => true,
                        'message' => 'Reservation timed out successfully',
                        'remaining_sessions' => $remaining_sessions,
                        'computer_updated' => $computer_update_result,
                        'laboratory' => $laboratory,
                        'pc_number' => $pc_number
                    ];
                } else {
                    $response = [
                        'success' => true,
                        'message' => 'Reservation timed out successfully, but no computer information was available',
                        'remaining_sessions' => $remaining_sessions,
                        'computer_updated' => false
                    ];
                }
            } else {
                $response = ['success' => false, 'message' => 'Failed to update reservation record: ' . $conn->error];
            }
        } else {
            $response = ['success' => false, 'message' => 'Reservation record not found'];
        }
    }
}

// Return the response
echo json_encode($response);
?>