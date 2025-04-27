<?php
// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if reservation_id is provided
if (!isset($_POST['reservation_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing reservation ID']);
    exit();
}

// Connect to the database
require_once '../config/db_connect.php';

// Get the reservation ID
$reservation_id = intval($_POST['reservation_id']);

// Start a transaction
$conn->begin_transaction();

try {
    // Fetch the reservation details
    $sql = "SELECT * FROM reservations WHERE id = ? AND status = 'approved'";
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
        throw new Exception("Reservation not found or not in approved status");
    }
    
    // Insert the data into sit_ins table
    $sql = "INSERT INTO sit_ins (idno, fullname, purpose, laboratory, pc_number, time_in, date, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare insert statement: " . $conn->error);
    }
    
    $stmt->bind_param("sssssss", 
        $reservation['idno'],
        $reservation['fullname'],
        $reservation['purpose'],
        $reservation['laboratory'],
        $reservation['pc_number'],
        $reservation['time_in'],
        $reservation['date']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create sit-in: " . $stmt->error);
    }
    
    $sit_in_id = $conn->insert_id;
    $stmt->close();
    
    // Update the reservation status to 'completed'
    $sql = "UPDATE reservations SET status = 'completed' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare update statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $reservation_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update reservation status: " . $stmt->error);
    }
    
    $stmt->close();
    
    // Update the computer status to in-use
    $sql = "INSERT INTO computer_status (laboratory, pc_number, status) 
            VALUES (?, ?, 'in-use') 
            ON DUPLICATE KEY UPDATE status = 'in-use'";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare computer status statement: " . $conn->error);
    }
    
    $stmt->bind_param("si", 
        $reservation['laboratory'],
        $reservation['pc_number']
    );
    
    $stmt->execute();
    $stmt->close();
    
    // Commit the transaction
    $conn->commit();
    
    // Send success response
    echo json_encode([
        'success' => true, 
        'message' => 'Reservation converted to sit-in successfully',
        'sit_in_id' => $sit_in_id
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    
    // Send error response
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

// Close the connection
$conn->close();
?>
