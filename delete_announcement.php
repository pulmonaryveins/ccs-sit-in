<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (isset($data['id'])) {
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    
    $success = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $success]);
}
