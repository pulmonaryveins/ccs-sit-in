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
    $query = "SELECT u.id, u.idno, u.firstname, u.lastname, u.course, u.year, u.profile_image, u.remaining_sessions,
              CASE 
                WHEN u.year = 1 THEN '1st Year'
                WHEN u.year = 2 THEN '2nd Year'
                WHEN u.year = 3 THEN '3rd Year'
                WHEN u.year = 4 THEN '4th Year'
                ELSE CONCAT(u.year, 'th Year')
              END as year_level_display
              FROM users u 
              WHERE u.idno LIKE ?";
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    $search_param = $idno . '%'; // Allow partial search from beginning of ID
    $stmt->bind_param('s', $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if we found a student
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $student = [
            'id' => $row['id'],
            'idno' => $row['idno'],
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'course' => $row['course'],
            'year' => $row['year'],
            'year_level_display' => $row['year_level_display'],
            'profile_image' => $row['profile_image'] ?? '../assets/images/logo/AVATAR.png',
            'remaining_sessions' => $row['remaining_sessions'] ?? 30,
        ];
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
