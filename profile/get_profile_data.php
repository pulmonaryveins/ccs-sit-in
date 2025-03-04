<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$username = $_SESSION['username'];
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Format the response data
$year = $user['year'];
$year_level = $year . (
    $year == 1 ? 'st' : 
    ($year == 2 ? 'nd' : 
    ($year == 3 ? 'rd' : 'th'))
) . ' Year';

$response = [
    'profile_image' => $user['profile_image'] ?? '../assets/images/logo/AVATAR.png',
    'fullname' => $user['lastname'] . ', ' . $user['firstname'],
    'idno' => $user['idno'],
    'course' => $user['course'],
    'year_level' => $year_level
];

header('Content-Type: application/json');
echo json_encode($response);

$stmt->close();
$conn->close();
