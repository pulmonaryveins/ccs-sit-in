<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    $sql = "UPDATE reservations SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $reservation_id);
    
    if ($stmt->execute()) {
        if ($status === 'approved') {
            // Update computer status to 'reserved'
            $sql = "UPDATE computer_status 
                   SET status = 'reserved' 
                   WHERE laboratory = (SELECT laboratory FROM reservations WHERE id = ?) 
                   AND pc_number = (SELECT pc_number FROM reservations WHERE id = ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $reservation_id, $reservation_id);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true, 'message' => "Reservation $status successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error processing request']);
    }

    $stmt->close();
    $conn->close();
}
