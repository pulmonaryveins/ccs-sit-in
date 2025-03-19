<?php
// Turn off output buffering
ob_end_clean();

// Set proper content type for JSON
header('Content-Type: application/json');

session_start();
require_once '../config/db_connect.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Function to validate required fields
function validateFields($requiredFields) {
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            return false;
        }
    }
    return true;
}

// Required fields (removed pc_number from the required fields)
$requiredFields = ['idno', 'purpose', 'laboratory', 'date', 'time'];

// Validate required fields
if (!validateFields($requiredFields)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Get form data
$studentId = isset($_POST['student_id']) ? $_POST['student_id'] : null;
$idno = $_POST['idno'];
$purpose = $_POST['purpose'];
$laboratory = $_POST['laboratory'];
$date = $_POST['date'];
$time_in = $_POST['time'];
// Even if pc_number is provided, we're not going to validate it anymore
$pc_number = isset($_POST['pc_number']) ? $_POST['pc_number'] : '1'; // Default to '1' if not provided

// Set status to active
$status = 'active';

try {
    // Insert into sit_ins table with current time
    $stmt = $conn->prepare("INSERT INTO sit_ins (idno, purpose, laboratory, pc_number, date, time_in, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Fix parameter binding count - we have 7 placeholders
    $stmt->bind_param("sssssss", $idno, $purpose, $laboratory, $pc_number, $date, $time_in, $status);
    $success = $stmt->execute();

    if ($success) {
        // Update the remaining_sessions for the user if the student exists
        if ($studentId) {
            // Get the current remaining_sessions
            $stmt = $conn->prepare("SELECT remaining_sessions FROM users WHERE id = ?");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user) {
                // Calculate new remaining sessions (don't go below 0)
                $remainingSessions = max(0, ($user['remaining_sessions'] ?? 30) - 1);

            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Sit-in added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add sit-in: ' . $stmt->error]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
