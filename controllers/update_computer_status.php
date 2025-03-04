<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['laboratory'], $data['pc_number'], $data['status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$sql = "INSERT INTO computer_status (laboratory, pc_number, status) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE status = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("siss", 
    $data['laboratory'],
    $data['pc_number'],
    $data['status'],
    $data['status']
);

$success = $stmt->execute();
echo json_encode(['success' => $success]);

$stmt->close();
$conn->close();
