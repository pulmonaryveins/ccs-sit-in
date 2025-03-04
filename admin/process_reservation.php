<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../config/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['reservation_id']) || !isset($data['status'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
}

$reservation_id = intval($data['reservation_id']);
$status = $data['status'];
$processed_by = $_SESSION['username'];

try {
    // First, check if the reservation exists and hasn't been processed
    $check_stmt = $conn->prepare("SELECT status FROM reservations WHERE id = ?");
    $check_stmt->bind_param("i", $reservation_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $check_stmt->close();

    if ($result->num_rows === 0) {
        throw new Exception("Reservation not found");
    }

    $current_status = $result->fetch_assoc()['status'];
    if ($current_status !== 'pending') {
        throw new Exception("Reservation has already been processed");
    }

    // Update the reservation status
    $stmt = $conn->prepare("UPDATE reservations SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $status, $processed_by, $reservation_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'message' => "Reservation has been {$status}"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
