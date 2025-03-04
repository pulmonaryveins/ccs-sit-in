<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['action'])) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action'];
    
    // Update reservation status
    $sql = "UPDATE reservations SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $stmt->bind_param("si", $status, $reservation_id);
    
    if ($stmt->execute()) {
        // Update current sit-in count in sessions table if approved
        if ($action === 'approve') {
            $date = date('Y-m-d');
            $sql = "INSERT INTO current_sessions (date, count) 
                   VALUES (?, 1) 
                   ON DUPLICATE KEY UPDATE count = count + 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $date);
            $stmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Reservation has been ' . $status
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error processing reservation'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

$conn->close();
?>
