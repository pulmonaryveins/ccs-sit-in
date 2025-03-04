<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idno = $_SESSION['idno'];
    $fullname = $_SESSION['fullname'];
    $purpose = $_POST['purpose'];
    $laboratory = $_POST['laboratory'];
    $time_in = $_POST['time_in'];
    $date = $_POST['date'];
    
    // Check remaining sessions
    $remaining_sessions = isset($_SESSION['remaining_sessions']) ? $_SESSION['remaining_sessions'] : 30;
    
    if ($remaining_sessions <= 0) {
        $_SESSION['error'] = "No remaining sessions available.";
        header("Location: ../view/reservation.php");
        exit();
    }

    // Insert reservation into database
    $sql = "INSERT INTO reservations (idno, fullname, purpose, laboratory, time_in, date) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $idno, $fullname, $purpose, $laboratory, $time_in, $date);
    
    if ($stmt->execute()) {
        // Decrease remaining sessions
        $_SESSION['remaining_sessions'] = $remaining_sessions - 1;
        $_SESSION['success'] = "Reservation successful!";
    } else {
        $_SESSION['error'] = "Error making reservation. Please try again.";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: ../view/reservation.php");
    exit();
}
?>
