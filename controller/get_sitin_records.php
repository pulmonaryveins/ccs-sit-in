<?php
// Start session if needed
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    // Return error if not authenticated
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Include database connection
require_once '../config/db_connect.php';
// Ensure proper timezone
date_default_timezone_set('Asia/Manila');

// Fetch all sit-in records with remaining sessions
$records = [];

// Fetch reservation records with remaining_sessions
$query_reservations = "SELECT r.*, u.firstname, u.lastname, u.idno, u.remaining_sessions, 'reservation' as source 
                       FROM reservations r
                       JOIN users u ON r.idno = u.idno
                       ORDER BY r.date DESC, r.time_in DESC";

$result = $conn->query($query_reservations);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

// Fetch direct sit-in records with remaining_sessions
$query_sitins = "SELECT s.*, u.firstname, u.lastname, u.idno, u.remaining_sessions, 'sit_in' as source
                FROM sit_ins s
                JOIN users u ON s.idno = u.idno
                ORDER BY s.date DESC, s.time_in DESC";

$result = $conn->query($query_sitins);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

// Sort all records by date and time (newest first)
usort($records, function($a, $b) {
    $a_datetime = strtotime($a['date'] . ' ' . $a['time_in']);
    $b_datetime = strtotime($b['date'] . ' ' . $b['time_in']);
    return $b_datetime - $a_datetime; // Descending order
});

// Return the records as JSON
header('Content-Type: application/json');
echo json_encode($records);
?>
