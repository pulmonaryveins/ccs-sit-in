<?php
session_start();
require_once '../config/db_connect.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Get announcement data from POST
$title = $_POST['title'] ?? 'CCS ADMIN';
$content = $_POST['content'] ?? '';
$created_by = 'admin';

// Validate inputs
if (empty($content)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Content is required']);
    exit();
}

// Insert announcement
$query = "INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('sss', $title, $content, $created_by);
$result = $stmt->execute();

if ($result) {
    // Get the announcement ID
    $announcement_id = $conn->insert_id;
    
    // Create notifications for all users
    try {
        // Begin a transaction for multiple inserts
        $conn->begin_transaction();
        
        // Get all user IDs
        $user_query = "SELECT id FROM users";
        $user_result = $conn->query($user_query);
        
        if ($user_result && $user_result->num_rows > 0) {
            // Prepare notification insert statement
            $notification_query = "INSERT INTO notifications (user_id, announcement_id) VALUES (?, ?)";
            $notification_stmt = $conn->prepare($notification_query);
            
            // Insert a notification for each user
            while ($user = $user_result->fetch_assoc()) {
                $notification_stmt->bind_param('ii', $user['id'], $announcement_id);
                $notification_stmt->execute();
            }
            
            $notification_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Announcement created successfully with notifications']);
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Announcement created but notifications failed: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to create announcement']);
}

$stmt->close();
$conn->close();
?>
