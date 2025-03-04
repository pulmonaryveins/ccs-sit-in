<?php
session_start();
require_once '../config/db_connect.php';

// Check if user has an active reservation
if (isset($_SESSION['idno'])) {
    $sql = "UPDATE reservations 
            SET status = 'completed', time_out = NOW() 
            WHERE idno = ? 
            AND status = 'approved' 
            AND DATE(date) = CURDATE()";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['idno']);
    $stmt->execute();
    
    // Update current sessions count
    if ($stmt->affected_rows > 0) {
        $sql = "UPDATE current_sessions 
                SET count = GREATEST(count - 1, 0) 
                WHERE date = CURDATE()";
        $conn->query($sql);
    }
    
    $stmt->close();
}

$conn->close();

// Clear all session variables
session_unset();
session_destroy();

// Redirect to login page
header("Location: ../auth/login.php");
exit();
?>