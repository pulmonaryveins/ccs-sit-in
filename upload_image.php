<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_FILES['profile_image'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

// Create uploads directory if it doesn't exist
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['profile_image'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileError = $file['error'];

// Generate unique filename
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$uniqueFileName = uniqid('profile_') . '.' . $fileExtension;
$targetFilePath = $uploadDir . $uniqueFileName;

// Allowed file types
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($fileExtension, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit();
}

if ($fileError !== 0) {
    echo json_encode(['success' => false, 'message' => 'Error uploading file']);
    exit();
}

// Move uploaded file and update database
if (move_uploaded_file($fileTmpName, $targetFilePath)) {
    // Delete old profile image if it exists
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $oldImage = $row['profile_image'];
        if ($oldImage && $oldImage !== 'default-avatar.png' && file_exists($oldImage)) {
            unlink($oldImage);
        }
    }

    // Update database with new image path
    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE username = ?");
    $stmt->bind_param("ss", $targetFilePath, $_SESSION['username']);
    
    if ($stmt->execute()) {
        $_SESSION['profile_image'] = $targetFilePath;
        echo json_encode([
            'success' => true,
            'image_path' => $targetFilePath
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database update failed'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to move uploaded file'
    ]);
}

$conn->close();
