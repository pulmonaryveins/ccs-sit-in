<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $created_by = $_SESSION['username'];

    $stmt = $conn->prepare("INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $content, $created_by);
    
    $success = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $success]);
}
