<?php
// Prevent any output before JSON
error_reporting(0); // Disable error reporting temporarily for this script
ini_set('display_errors', 0); // Don't output errors

header('Content-Type: application/json'); // Ensure proper content type

session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if reservation ID and action are provided
if (!isset($_POST['reservation_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$reservation_id = intval($_POST['reservation_id']);
$action = $_POST['action'];

// Validate action
if ($action !== 'approve' && $action !== 'reject') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

try {
    // First check if updated_at column exists
    $column_check_query = "SHOW COLUMNS FROM reservations LIKE 'updated_at'";
    $column_result = $conn->query($column_check_query);
    $updated_at_exists = ($column_result && $column_result->num_rows > 0);
    
    // Get the reservation details before updating
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
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        exit();
    }
    
    // Update reservation status
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    // Prepare SQL based on whether updated_at column exists
    if ($updated_at_exists) {
        $sql = "UPDATE reservations SET status = ?, updated_at = NOW() WHERE id = ?";
    } else {
        $sql = "UPDATE reservations SET status = ? WHERE id = ?";
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
    
    // If approved, update computer status
    if ($action === 'approve') {
        $lab = $reservation['laboratory'];
        $pc_number = $reservation['pc_number'];
        
        // Update PC status to in-use
        $update_pc_sql = "INSERT INTO computer_status (laboratory, pc_number, status) 
                          VALUES (?, ?, 'in-use') 
                          ON DUPLICATE KEY UPDATE status = 'in-use'";
        $pc_stmt = $conn->prepare($update_pc_sql);
        
        if ($pc_stmt) {
            $pc_stmt->bind_param("si", $lab, $pc_number);
            $pc_stmt->execute();
            $pc_stmt->close();
        }

        // Get the username based on idno
        $get_username_sql = "SELECT username FROM users WHERE idno = ?";
        $username_stmt = $conn->prepare($get_username_sql);
        $username_stmt->bind_param("s", $reservation['idno']);
        $username_stmt->execute();
        $username_result = $username_stmt->get_result();
        $username_row = $username_result->fetch_assoc();
        $username = $username_row['username'];
        $username_stmt->close();
        
        // Format date and time in the requested format
        $formatted_datetime = date('F j, Y \a\t g:i A', strtotime($reservation['date'] . ' ' . $reservation['time_in']));
        
        // Create notification for student
        $notification_title = "Reservation Approved";
        $notification_content = "Your reservation for Laboratory {$lab}, PC {$pc_number} on {$formatted_datetime} has been approved.";
        
        // Modify notification SQL to include created_at field explicitly
        $notification_sql = "INSERT INTO notifications (username, title, content, created_at) VALUES (?, ?, ?, NOW())";
        $notif_stmt = $conn->prepare($notification_sql);
        
        if ($notif_stmt) {
            $notif_stmt->bind_param("sss", $username, $notification_title, $notification_content);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
    } elseif ($action === 'reject') {
        // Get the username based on idno
        $get_username_sql = "SELECT username FROM users WHERE idno = ?";
        $username_stmt = $conn->prepare($get_username_sql);
        $username_stmt->bind_param("s", $reservation['idno']);
        $username_stmt->execute();
        $username_result = $username_stmt->get_result();
        $username_row = $username_result->fetch_assoc();
        $username = $username_row['username'];
        $username_stmt->close();
        
        // Format date and time in the requested format
        $formatted_datetime = date('F j, Y \a\t g:i A', strtotime($reservation['date'] . ' ' . $reservation['time_in']));
        
        // Create rejection notification
        $notification_title = "Reservation Rejected";
        $notification_content = "Your reservation for Laboratory {$reservation['laboratory']}, PC {$reservation['pc_number']} on {$formatted_datetime} has been rejected.";
        
        // Modify notification SQL to include created_at field explicitly
        $notification_sql = "INSERT INTO notifications (username, title, content, created_at) VALUES (?, ?, ?, NOW())";
        $notif_stmt = $conn->prepare($notification_sql);
        
        if ($notif_stmt) {
            $notif_stmt->bind_param("sss", $username, $notification_title, $notification_content);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
    }
    
    // Get the updated timestamp
    if (!isset($reservation['updated_at']) && $updated_at_exists) {
        $query = "SELECT updated_at FROM reservations WHERE id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $reservation['updated_at'] = $row['updated_at'];
            }
            $stmt->close();
        }
    } else if (!isset($reservation['updated_at'])) {
        $reservation['updated_at'] = date('Y-m-d H:i:s');
    }
    
    // To ensure the sit-in.php page can display this reservation,
    // we need to ensure it has a valid date value
    if (empty($reservation['date'])) {
        // Update the reservation to have today's date if no date is set
        $today = date('Y-m-d');
        $update_date_sql = "UPDATE reservations SET date = ? WHERE id = ?";
        $date_stmt = $conn->prepare($update_date_sql);
        
        if ($date_stmt) {
            $date_stmt->bind_param("si", $today, $reservation_id);
            $date_stmt->execute();
            $date_stmt->close();
            
            // Update our reservation data for the response
            $reservation['date'] = $today;
        }
    }
    
    // Return success response with the reservation data
    echo json_encode([
        'success' => true, 
        'message' => 'Reservation ' . $action . 'd successfully',
        'reservation' => $reservation
    ]);
    
} catch (Exception $e) {
    // Log the error to a file instead of displaying it
    error_log("Error in process_approval.php: " . $e->getMessage(), 0);
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing the request: ' . $e->getMessage()
    ]);
}

$conn->close();
exit();
?>