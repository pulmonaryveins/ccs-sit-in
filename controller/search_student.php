<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the student ID from query string
if (!isset($_GET['idno']) || empty($_GET['idno'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

$idno = trim($_GET['idno']);

try {
    // Query to find the student with all necessary fields
    // Use a simpler query first to ensure it works
    $query = "SELECT * FROM users WHERE idno = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    $stmt->bind_param('s', $idno);
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Format year level display text
        $yearLevel = isset($student['year_level']) ? $student['year_level'] : 
                    (isset($student['year']) ? $student['year'] : 1);
        
        switch (intval($yearLevel)) {
            case 1: $yearLevelDisplay = '1st Year'; break;
            case 2: $yearLevelDisplay = '2nd Year'; break;
            case 3: $yearLevelDisplay = '3rd Year'; break;
            case 4: $yearLevelDisplay = '4th Year'; break;
            default: $yearLevelDisplay = 'Not specified';
        }
        
        // Get course name
        $courseName = 'Not specified';
        if (!empty($student['course'])) {
            $courseName = $student['course'];
        } else if (!empty($student['course_id'])) {
            $courseId = intval($student['course_id']);
            switch ($courseId) {
                case 1: $courseName = 'BS Computer Science'; break;
                case 2: $courseName = 'BS Information Technology'; break;
                case 3: $courseName = 'BS Information Systems'; break;
                case 4: $courseName = 'BS Computer Engineering'; break;
                default: $courseName = 'Unknown Course #' . $courseId;
            }
        }
        
        // Build response data
        $responseData = [
            'id' => $student['id'],
            'idno' => $student['idno'],
            'firstname' => $student['firstname'] ?? '',
            'lastname' => $student['lastname'] ?? '',
            'course' => $courseName,
            'year_level' => intval($yearLevel),
            'year_level_display' => $yearLevelDisplay,
            'remaining_sessions' => 30
        ];
        
        echo json_encode(['success' => true, 'student' => $responseData]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }

    $stmt->close();
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in search_student.php: " . $e->getMessage());
    
    // Return a friendly error message to the client
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while searching for the student',
        'debug' => $e->getMessage()
    ]);
}

$conn->close();
