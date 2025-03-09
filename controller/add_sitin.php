<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate incoming data
if (!isset($_POST['idno']) || empty($_POST['idno']) ||
    !isset($_POST['purpose']) || empty($_POST['purpose']) ||
    !isset($_POST['laboratory']) || empty($_POST['laboratory']) ||
    !isset($_POST['pc_number']) || empty($_POST['pc_number']) ||
    !isset($_POST['time']) || empty($_POST['time']) ||
    !isset($_POST['date']) || empty($_POST['date'])) {
    
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Get form data
$idno = $_POST['idno'];
$purpose = $_POST['purpose'];
$laboratory = $_POST['laboratory'];
$pc_number = $_POST['pc_number'];
$time = $_POST['time'];
$date = $_POST['date'];

// Check if student exists
$studentQuery = "SELECT * FROM users WHERE idno = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param('s', $idno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

$student = $result->fetch_assoc();
$fullname = $student['lastname'] . ', ' . $student['firstname'];
if (!empty($student['middlename'])) {
    $fullname .= ' ' . $student['middlename'];
}

// Check if PC is available
$pcQuery = "SELECT status FROM computer_status WHERE laboratory = ? AND pc_number = ?";
$stmt = $conn->prepare($pcQuery);
$stmt->bind_param('si', $laboratory, $pc_number);
$stmt->execute();
$pcResult = $stmt->get_result();

if ($pcResult->num_rows === 0) {
    // PC doesn't exist in the database yet, add it first
    $insertPcQuery = "INSERT INTO computer_status (laboratory, pc_number, status) VALUES (?, ?, 'available')";
    $stmt = $conn->prepare($insertPcQuery);
    $stmt->bind_param('si', $laboratory, $pc_number);
    $stmt->execute();
} else {
    $pc = $pcResult->fetch_assoc();
    if ($pc['status'] === 'in-use') {
        echo json_encode(['success' => false, 'message' => 'Selected PC is already in use']);
        exit;
    }
}

// Begin transaction
$conn->begin_transaction();

try {
    // Insert into sit_ins table instead of reservations
    $insertQuery = "INSERT INTO sit_ins (idno, fullname, purpose, laboratory, pc_number, time_in, date, created_at, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'active')";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('ssssiss', $idno, $fullname, $purpose, $laboratory, $pc_number, $time, $date);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to add sit-in record: " . $stmt->error);
    }
    $sit_in_id = $conn->insert_id;
    
    // Update PC status to in-use
    $updatePcQuery = "UPDATE computer_status SET status = 'in-use' WHERE laboratory = ? AND pc_number = ?";
    $stmt = $conn->prepare($updatePcQuery);
    $stmt->bind_param('si', $laboratory, $pc_number);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update PC status: " . $stmt->error);
    }
    
    // Update current sessions count for today's date
    $today = date('Y-m-d');
    $checkSessionsQuery = "SELECT * FROM current_sessions WHERE date = ?";
    $stmt = $conn->prepare($checkSessionsQuery);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $sessionResult = $stmt->get_result();
    
    if ($sessionResult->num_rows === 0) {
        // No record for today, create one
        $insertSessionQuery = "INSERT INTO current_sessions (date, count) VALUES (?, 1)";
        $stmt = $conn->prepare($insertSessionQuery);
        $stmt->bind_param('s', $today);
        $stmt->execute();
    } else {
        // Update existing record
        $updateSessionQuery = "UPDATE current_sessions SET count = count + 1 WHERE date = ?";
        $stmt = $conn->prepare($updateSessionQuery);
        $stmt->bind_param('s', $today);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Student sit-in added successfully',
        'sit_in_id' => $sit_in_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
