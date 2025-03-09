<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/db_connect.php';

// Get laboratory parameter
if (!isset($_GET['laboratory']) || empty($_GET['laboratory'])) {
    echo json_encode([]);
    exit();
}

$laboratory = $_GET['laboratory'];

// Query to get PC status from the database
$query = "SELECT pc_number, status FROM computer_status WHERE laboratory = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $laboratory);
$stmt->execute();
$result = $stmt->get_result();

// Create an array of PCs with their status
$pcs = [];
while ($row = $result->fetch_assoc()) {
    $pcs[] = $row;
}

// Ensure we have entries for all PCs (1-40)
$allPcs = [];
for ($i = 1; $i <= 40; $i++) {
    $found = false;
    foreach ($pcs as $pc) {
        if ($pc['pc_number'] == $i) {
            $allPcs[] = $pc;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $allPcs[] = [
            'pc_number' => $i,
            'status' => 'available' // Default status if not in database
        ];
    }
}

// Return the PCs (this is the full array, not nested under in_use_pcs)
echo json_encode($allPcs);

$stmt->close();
$conn->close();
?>
