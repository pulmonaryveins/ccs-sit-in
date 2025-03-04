<?php
require_once '../config/db_connect.php';

$sql = "SELECT * FROM reservations WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($sql);

$reservations = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
}

return $reservations;
?>
