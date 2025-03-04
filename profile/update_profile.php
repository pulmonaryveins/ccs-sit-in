<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$response = ['success' => false, 'message' => 'No changes made'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_username = $_SESSION['username'];
    $new_username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $course = $_POST['course'];
    $year = $_POST['year'];
    $address = trim($_POST['address']);

    // Check if new username is already taken (if username was changed)
    if ($new_username !== $current_username) {
        $check_sql = "SELECT username FROM users WHERE username = ? AND username != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $new_username, $current_username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already taken']);
            exit();
        }
        $check_stmt->close();
    }

    // Validate year
    if ($year < 1 || $year > 4) {
        $year = 1;
    }

    // Format year level
    $year_level = $year . (
        $year == 1 ? 'st' : 
        ($year == 2 ? 'nd' : 
        ($year == 3 ? 'rd' : 'th'))
    ) . ' Year';

    // Update user information
    $sql = "UPDATE users SET username = ?, email = ?, course = ?, year = ?, address = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $new_username, $email, $course, $year, $address, $current_username);

    if ($stmt->execute()) {
        // Update session variables including course
        $_SESSION['username'] = $new_username;
        $_SESSION['email'] = $email;
        $_SESSION['address'] = $address;
        $_SESSION['course'] = $course;
        $_SESSION['year'] = $year;
        $_SESSION['year_level'] = $year_level;

        $response = [
            'success' => true, 
            'message' => 'Profile updated successfully',
            'data' => [
                'username' => $new_username,
                'email' => $email,
                'address' => $address,
                'course' => $course,
                'year' => $year,
                'year_level' => $year_level
            ]
        ];
    } else {
        $response = ['success' => false, 'message' => 'Error updating profile: ' . $conn->error];
    }

    $stmt->close();
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>
