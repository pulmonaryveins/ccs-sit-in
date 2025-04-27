<?php
// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if reservation_id and action are provided
if (!isset($_POST['reservation_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Connect to the database
require_once '../config/db_connect.php';

// Get the reservation ID and action
$reservation_id = intval($_POST['reservation_id']);
$action = $_POST['action'];

// Validate action
if ($action !== 'cancel' && $action !== 'complete') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

try {
    // Get the reservation details
    $sql = "SELECT * FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();
    
    if (!$reservation) {
        throw new Exception("Reservation not found");
    }
    
    // Check if updated_at column exists
    $column_check_query = "SHOW COLUMNS FROM reservations LIKE 'updated_at'";
    $column_result = $conn->query($column_check_query);
    $updated_at_exists = ($column_result && $column_result->num_rows > 0);
    
    // Update reservation status based on action
    $status = ($action === 'cancel') ? 'cancelled' : 'completed';
    
    if ($updated_at_exists) {
        $sql = "UPDATE reservations SET status = ?, updated_at = NOW()";
        
        // If action is complete, also set time_out field
        if ($action === 'complete') {
            $sql .= ", time_out = NOW()";
        }
        
        $sql .= " WHERE id = ?";
    } else {
        $sql = "UPDATE reservations SET status = ?";
        
        // If action is complete, also set time_out field
        if ($action === 'complete') {
            $sql .= ", time_out = NOW()";
        }
        
        $sql .= " WHERE id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare update statement: " . $conn->error);
    }
    
    $stmt->bind_param("si", $status, $reservation_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update reservation: " . $stmt->error);
    }
    
    $stmt->close();
    
    // If action is cancel and computer is marked as in-use, update computer status back to available
    if ($action === 'cancel') {
        $lab = $reservation['laboratory'];
        $pc_number = $reservation['pc_number'];
        
        $update_pc_sql = "UPDATE computer_status SET status = 'available' 
                          WHERE laboratory = ? AND pc_number = ? AND status = 'in-use'";
        $pc_stmt = $conn->prepare($update_pc_sql);
        
        if ($pc_stmt) {
            $pc_stmt->bind_param("si", $lab, $pc_number);
            $pc_stmt->execute();
            $pc_stmt->close();
        }
        
        // Try to create a notification for the student
        try {
            $check_table_sql = "SHOW TABLES LIKE 'notifications'";
            $table_result = $conn->query($check_table_sql);
            
            if ($table_result && $table_result->num_rows > 0) {
                $username = $reservation['username'] ?? null;
                
                if ($username) {
                    $notification_title = "Reservation Cancelled";
                    $notification_content = "Your reservation for Laboratory {$reservation['laboratory']}, PC {$reservation['pc_number']} on {$reservation['date']} has been cancelled by an administrator.";
                    
                    $notification_sql = "INSERT INTO notifications (username, title, content) VALUES (?, ?, ?)";
                    $notif_stmt = $conn->prepare($notification_sql);
                    
                    if ($notif_stmt) {
                        $notif_stmt->bind_param("sss", $username, $notification_title, $notification_content);
                        $notif_stmt->execute();
                        $notif_stmt->close();
                    }
                }
            }
        } catch (Exception $e) {
            // Log notification error, but don't fail the operation
            error_log("Failed to create notification: " . $e->getMessage());
        }
    }
    
    // Send success response
    echo json_encode([
        'success' => true, 
        'message' => 'Reservation ' . ($action === 'cancel' ? 'cancelled' : 'completed') . ' successfully'
    ]);
    
} catch (Exception $e) {
    // Send error response
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

// Close the connection
$conn->close();
?>
