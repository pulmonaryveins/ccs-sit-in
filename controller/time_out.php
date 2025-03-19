<?php
session_start();
require_once '../config/db_connect.php';

// Set timezone for PHP to Asia/Manila (GMT+8)
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$response = array('success' => false, 'message' => '', 'remaining_sessions' => 0);

// Validate inputs
if (empty($_POST['sit_in_id'])) {
$response['message'] = 'Missing sit-in ID';
echo json_encode($response);
exit;
}

$sit_in_id = $_POST['sit_in_id'];

// Use the client-provided time if available, otherwise generate server time
if (!empty($_POST['time_out'])) {
$time_out = $_POST['time_out'];
} else {
// Get current time in Asia/Manila timezone
$time_out = date('H:i:s'); // 24-hour format
}

// Start transaction to ensure data consistency
$conn->begin_transaction();

try {
// 1. Get the student's ID number to update their remaining_sessions later
$query = "SELECT idno FROM sit_ins WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sit_in_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
throw new Exception('Sit-in record not found');
}

$row = $result->fetch_assoc();
$idno = $row['idno'];

// 2. Update the sit_in record with the time_out and set status to 'completed'
$query = "UPDATE sit_ins SET time_out = ?, status = 'completed' WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $time_out, $sit_in_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
throw new Exception('Failed to update sit-in record');
}

// 3. Decrement the remaining_sessions for the student
$query = "UPDATE users SET remaining_sessions = remaining_sessions - 1 WHERE idno = ? AND remaining_sessions > 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $idno);
$stmt->execute();

// 4. Get the updated remaining_sessions for returning in the response
$query = "SELECT remaining_sessions FROM users WHERE idno = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$remaining_sessions = $row['remaining_sessions'];

// Commit the transaction
$conn->commit();

// Return success response with remaining sessions
$response['success'] = true;
$response['message'] = 'Student timed out successfully';
$response['remaining_sessions'] = $remaining_sessions;

} catch (Exception $e) {
// Roll back the transaction if any operation fails
$conn->rollback();
$response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
} else {
header("HTTP/1.1 405 Method Not Allowed");
echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
}
?>