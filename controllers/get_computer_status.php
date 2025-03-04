<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['lab'])) {
    echo json_encode(['error' => 'Laboratory not specified']);
    exit;
}

$laboratory = $_GET['lab'];

$sql = "SELECT pc_number, status FROM computer_status WHERE laboratory = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $laboratory);
$stmt->execute();
$result = $stmt->get_result();

$status = [];
while ($row = $result->fetch_assoc()) {
    $status[$row['pc_number']] = $row['status'];
}

echo json_encode($status);

$stmt->close();
$conn->close();
