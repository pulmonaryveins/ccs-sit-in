<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$response = ['success' => false, 'message' => 'No changes made'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'];
    
    // Get all form data
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $address = htmlspecialchars($_POST['address'] ?? '');
    $course = htmlspecialchars($_POST['course'] ?? '');
    $year = intval($_POST['year'] ?? 1);
    
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

    // Update database with course
    $sql = "UPDATE users SET 
            email = ?, 
            address = ?, 
            course = ?, 
            year = ?
            WHERE username = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $email, $address, $course, $year, $username);

    if ($stmt->execute()) {
        // Update session variables including course
        $_SESSION['email'] = $email;
        $_SESSION['address'] = $address;
        $_SESSION['course'] = $course;
        $_SESSION['year'] = $year;
        $_SESSION['year_level'] = $year_level;

        $response = [
            'success' => true, 
            'message' => 'Profile updated successfully',
            'data' => [
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
