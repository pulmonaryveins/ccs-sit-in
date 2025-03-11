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

// Check if ID number was provided
if (isset($_GET['idno']) && !empty($_GET['idno'])) {
    $idno = $_GET['idno'];
    
    // Prepare the SQL query to search for students
    $query = "SELECT 
                id, idno, firstname, lastname, course, year, 
                CASE 
                    WHEN year = 1 THEN '1st Year'
                    WHEN year = 2 THEN '2nd Year'
                    WHEN year = 3 THEN '3rd Year'
                    WHEN year = 4 THEN '4th Year'
                    ELSE CONCAT(year, 'th Year')
                END as year_level_display,
                remaining_sessions
              FROM users 
              WHERE idno LIKE ?";
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    $search_param = $idno . '%'; // Allow partial search from beginning of ID
    $stmt->bind_param('s', $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if we found a student
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo json_encode([
            'success' => true, 
            'student' => $student
        ]);
    } else {
        // No student found
        echo json_encode([
            'success' => false, 
            'message' => 'No student found with that ID number'
        ]);
    }
    
    // Close statement
    $stmt->close();
} else {
    // No ID number provided
    echo json_encode([
        'success' => false, 
        'message' => 'Please provide an ID number'
    ]);
}

// Close connection
$conn->close();
?>
