<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $idno = $_POST['idno'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $laboratory = $_POST['laboratory'] ?? '';
    $date = $_POST['date'] ?? '';
    $time_in = $_POST['time_in'] ?? '';
    $pc_number = $_POST['pc_number'] ?? null;
    
    // Validate required fields
    if (empty($idno) || empty($fullname) || empty($purpose) || 
        empty($laboratory) || empty($date) || empty($time_in)) {
        $_SESSION['error_message'] = "All fields are required.";
        header('Location: ../view/reservation.php');
        exit;
    }
    
    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO reservations 
                          (idno, fullname, purpose, laboratory, date, time_in, pc_number, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    $stmt->bind_param("ssssssi", $idno, $fullname, $purpose, $laboratory, $date, $time_in, $pc_number);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Get the ID of the inserted reservation
        $reservation_id = $conn->insert_id;
        
        // Create a notification for admins
        $title = "New Reservation Request";
        $content = "$fullname requested to use PC #$pc_number in Laboratory $laboratory on " . 
                  date('F j, Y', strtotime($date)) . " at " . date('g:i A', strtotime($time_in));
        
        $admin_notification_sql = "INSERT INTO admin_notifications 
                                  (title, content, related_id, related_type, is_read) 
                                  VALUES (?, ?, ?, 'reservation', 0)";
        
        $notify = $conn->prepare($admin_notification_sql);
        $notify->bind_param("ssi", $title, $content, $reservation_id);
        $notify->execute();
        $notify->close();
        
        // Set success message
        $_SESSION['success_message'] = "Reservation submitted successfully!";
    } else {
        // Set error message
        $_SESSION['error_message'] = "Error: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    // Redirect back to the reservation page
    header('Location: ../view/reservation.php');
    exit;
} else {
    // Redirect if accessed directly without POST data
    header('Location: ../view/reservation.php');
    exit;
}
?>
