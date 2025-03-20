<?php
// Start session and check if admin is logged in
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and validate the required fields
$idno = isset($_POST['idno']) ? trim($_POST['idno']) : '';
$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
$lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$year_level = isset($_POST['year_level']) ? trim($_POST['year_level']) : '';
$course_id = isset($_POST['course_id']) ? trim($_POST['course_id']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate all required fields
if (empty($idno) || empty($firstname) || empty($lastname) || empty($username) || 
    empty($year_level) || empty($course_id) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate ID number format (must be 8 digits)
if (!preg_match('/^\d{8}$/', $idno)) {
    echo json_encode(['success' => false, 'message' => 'ID Number must be exactly 8 digits']);
    exit();
}

// Check if the ID number already exists
$check_idno = $conn->prepare("SELECT idno FROM users WHERE idno = ?");
$check_idno->bind_param("s", $idno);
$check_idno->execute();
$result_idno = $check_idno->get_result();

if ($result_idno->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A student with this ID number already exists']);
    exit();
}

// Check if the username already exists
$check_username = $conn->prepare("SELECT username FROM users WHERE username = ?");
$check_username->bind_param("s", $username);
$check_username->execute();
$result_username = $check_username->get_result();

if ($result_username->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already taken']);
    exit();
}

// Hash the password for security
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

//Set default values
$role = 'student'; // Default user type
$remaining_sessions = 30; // Default number of sessions
$profile_image = '../assets/images/logo/AVATAR.png'; // Default profile image

// Insert the new student into the database
$insert_stmt = $conn->prepare("INSERT INTO users (idno, firstname, lastname, username, password, role, year, course, remaining_sessions, profile_image) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$insert_stmt->bind_param("ssssssssss", $idno, $firstname, $lastname, $username, $hashed_password, $role, $year_level, $course_id, $remaining_sessions, $profile_image);

// Execute the statement and check for success
if ($insert_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Student added successfully']);
} else {
    // If there's an error, return the error message
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

// Close the database connection
$insert_stmt->close();
$conn->close();
?>
