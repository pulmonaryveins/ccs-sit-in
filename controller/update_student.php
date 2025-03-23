<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../config/db_connect.php';

// For debugging - log the POST data
error_log('Update Student POST data: ' . print_r($_POST, true));

// Set content type to JSON
header('Content-Type: application/json');

// Validate form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$student_id = $_POST['id'] ?? '';
$firstname = $_POST['firstname'] ?? '';
$lastname = $_POST['lastname'] ?? '';
$year_level = $_POST['year_level'] ?? '';
$course = $_POST['course_id'] ?? ''; // Get the course value from the form's course_id field

// Validate required fields
if (empty($student_id) || empty($firstname) || empty($lastname) || empty($year_level) || empty($course)) {
    echo json_encode([
        'success' => false, 
        'message' => 'All fields are required',
        'received_data' => [
            'id' => $student_id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'year_level' => $year_level,
            'course' => $course
        ]
    ]);
    exit();
}

try {
    // Sanitize inputs
    $student_id = $conn->real_escape_string($student_id);
    $firstname = $conn->real_escape_string($firstname);
    $lastname = $conn->real_escape_string($lastname);
    $year_level = $conn->real_escape_string($year_level);
    $course = $conn->real_escape_string($course);
    
    // Simplified query that only uses fields that exist in the database
    $query = "UPDATE users SET 
            firstname = '$firstname', 
            lastname = '$lastname', 
            year = '$year_level', 
            course = '$course'
            WHERE id = '$student_id'";
    
    // Execute query directly for simplicity
    $result = $conn->query($query);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Student updated successfully',
            'student_name' => $firstname . ' ' . $lastname
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update student: ' . $conn->error,
            'query' => $query
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Exception: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
