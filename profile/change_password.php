<?php
session_start();
header('Content-Type: application/json');

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if all required fields are provided
if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Check if new passwords match
if ($_POST['new_password'] !== $_POST['confirm_password']) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit();
}

require_once '../config/db_connect.php';

$username = $_SESSION['username'];
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];

// Verify current password
$sql = "SELECT password FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    $stmt->close();
    $conn->close();
    exit();
}

$user = $result->fetch_assoc();

// Verify the current password matches the stored password
if (!password_verify($current_password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    $stmt->close();
    $conn->close();
    exit();
}

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the password
$update_sql = "UPDATE users SET password = ? WHERE username = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ss", $hashed_password, $username);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password: ' . $conn->error]);
}

$stmt->close();
$update_stmt->close();
$conn->close();
?>
