<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/db_connect.php';

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// Check if necessary data is provided
if (!isset($input['id']) || !isset($input['type']) || !isset($input['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

// Sanitize input
$id = intval($input['id']);
$type = $input['type'] === 'reservation' ? 'reservation' : 'sit_in';
$rating = intval($input['rating']);
$message = isset($input['message']) ? trim($input['message']) : '';

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit();
}

// Check if feedback table exists, create if not
$check_table = "SHOW TABLES LIKE 'feedback'";
$result = $conn->query($check_table);
if ($result->num_rows == 0) {
    // Table doesn't exist, create it
    $create_table = "CREATE TABLE feedback (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        reservation_id INT(11) NULL,
        sit_in_id INT(11) NULL,
        rating INT(1) NOT NULL,
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY reservation_id (reservation_id),
        KEY sit_in_id (sit_in_id)
    )";
    
    if (!$conn->query($create_table)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create feedback table: ' . $conn->error]);
        exit();
    }
}

// Check if feedback already exists
$check_query = "";
if ($type === 'reservation') {
    $check_query = "SELECT id FROM feedback WHERE reservation_id = ?";
} else {
    $check_query = "SELECT id FROM feedback WHERE sit_in_id = ?";
}

$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Feedback already submitted']);
    exit();
}

// Verify the record exists and belongs to the current user
$verify_query = "";
if ($type === 'reservation') {
    $verify_query = "SELECT id FROM reservations WHERE id = ? AND idno = ?";
} else {
    $verify_query = "SELECT id FROM sit_ins WHERE id = ? AND idno = ?";
}

$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("is", $id, $_SESSION['idno']);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Record not found or unauthorized']);
    exit();
}

// Submit feedback
$sql = "";
if ($type === 'reservation') {
    $sql = "INSERT INTO feedback (reservation_id, rating, message) VALUES (?, ?, ?)";
} else {
    $sql = "INSERT INTO feedback (sit_in_id, rating, message) VALUES (?, ?, ?)";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $id, $rating, $message);
$success = $stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit feedback: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
