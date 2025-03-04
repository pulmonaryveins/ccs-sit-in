<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate PC selection
    if (!isset($_POST['pc_number']) || empty($_POST['pc_number'])) {
        $_SESSION['message'] = "Please select a PC.";
        $_SESSION['message_type'] = "error";
        header("Location: ../view/reservation.php");
        exit();
    }

    // Check if PC is still available
    $lab = $_POST['laboratory'];
    $pc = $_POST['pc_number'];
    $sql = "SELECT status FROM computer_status WHERE laboratory = ? AND pc_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $lab, $pc);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $status = $result->fetch_assoc()['status'];
        if ($status !== 'available') {
            $_SESSION['message'] = "Selected PC is no longer available. Please choose another PC.";
            $_SESSION['message_type'] = "error";
            header("Location: ../view/reservation.php");
            exit();
        }
    }

    $idno = $_POST['idno'];
    $fullname = $_POST['fullname'];
    $purpose = $_POST['purpose'];
    $laboratory = $_POST['laboratory'];
    $pc_number = $_POST['pc_number'];
    $date = $_POST['date'];
    $time_in = $_POST['time_in'];
    $status = 'pending'; // Default status

    $sql = "INSERT INTO reservations (idno, fullname, purpose, laboratory, pc_number, date, time_in, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $idno, $fullname, $purpose, $laboratory, $pc_number, $date, $time_in, $status);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Reservation request submitted successfully! Waiting for admin approval.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error submitting reservation request.";
        $_SESSION['message_type'] = "error";
    }

    $stmt->close();
    $conn->close();

    header("Location: ../view/history.php");
    exit();
}
