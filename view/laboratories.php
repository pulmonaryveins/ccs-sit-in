<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db_connect.php';

// Get statistics
$stats = [
    'total_students' => 0,
    'current_sitin' => 0,
    'total_sitin' => 0
];

// Get total registered students (modified query)
$query = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($query);
if ($result) {
    $stats['total_students'] = $result->fetch_assoc()['count'];
}

// Get current sit-in count (today)
$query = "SELECT COUNT(*) as count FROM reservations WHERE DATE(date) = CURDATE()";
$result = $conn->query($query);
if ($result) {
    $stats['current_sitin'] = $result->fetch_assoc()['count'];
}

// Get total sit-in count
$query = "SELECT COUNT(*) as count FROM reservations";
$result = $conn->query($query);
if ($result) {
    $stats['total_sitin'] = $result->fetch_assoc()['count'];
}

// Fetch laboratory schedules
$selected_lab = isset($_GET['lab']) ? $_GET['lab'] : 'Laboratory 517';
$selected_day = isset($_GET['day']) ? $_GET['day'] : 'Monday';

$lab_schedules = [];
$sql = "SELECT * FROM lab_schedules WHERE laboratory = ? AND day = ? ORDER BY time_start";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $selected_lab, $selected_day);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lab_schedules[] = $row;
    }
}

// Handle schedule CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new schedule
        if ($_POST['action'] === 'add_schedule') {
            $day = $_POST['day'];
            $laboratory = $_POST['laboratory'];
            $time_start = $_POST['time_start'];
            $time_end = $_POST['time_end'];
            $subject = $_POST['subject'];
            $professor = $_POST['professor'];
            
            $sql = "INSERT INTO lab_schedules (day, laboratory, time_start, time_end, subject, professor) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $day, $laboratory, $time_start, $time_end, $subject, $professor);
            
            if ($stmt->execute()) {
                header("Location: laboratories.php?lab=$laboratory&day=$day&success=Schedule added successfully");
                exit();
            } else {
                $error_message = "Error adding schedule: " . $conn->error;
            }
        }
        
        // Update schedule
        if ($_POST['action'] === 'update_schedule') {
            $id = $_POST['schedule_id'];
            $day = $_POST['day'];
            $laboratory = $_POST['laboratory'];
            $time_start = $_POST['time_start'];
            $time_end = $_POST['time_end'];
            $subject = $_POST['subject'];
            $professor = $_POST['professor'];
            
            $sql = "UPDATE lab_schedules SET day = ?, laboratory = ?, time_start = ?, 
                    time_end = ?, subject = ?, professor = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $day, $laboratory, $time_start, $time_end, $subject, $professor, $id);
            
            if ($stmt->execute()) {
                header("Location: laboratories.php?lab=$laboratory&day=$day&success=Schedule updated successfully");
                exit();
            } else {
                $error_message = "Error updating schedule: " . $conn->error;
            }
        }
        
        // Delete schedule
        if ($_POST['action'] === 'delete_schedule') {
            $id = $_POST['schedule_id'];
            $day = $_POST['day'];
            $laboratory = $_POST['laboratory'];
            
            $sql = "DELETE FROM lab_schedules WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                header("Location: laboratories.php?lab=$laboratory&day=$day&success=Schedule deleted successfully");
                exit();
            } else {
                $error_message = "Error deleting schedule: " . $conn->error;
            }
        }
    }
}

// Fetch announcements
$announcements = [];
$query = "SELECT * FROM announcements ORDER BY created_at DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Get pending reservations
$sql = "SELECT *, TIME_FORMAT(time_in, '%h:%i %p') as formatted_time FROM reservations WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($sql);
$pending_reservations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_reservations[] = $row;
    }
}

// Get approved reservations
$sql = "SELECT *, TIME_FORMAT(time_in, '%h:%i %p') as formatted_time FROM reservations WHERE status = 'approved' ORDER BY created_at DESC LIMIT 20";
$result = $conn->query($sql);
$approved_reservations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $approved_reservations[] = $row;
    }
}

// Get rejected reservations
$sql = "SELECT *, TIME_FORMAT(time_in, '%h:%i %p') as formatted_time FROM reservations WHERE status = 'rejected' ORDER BY created_at DESC LIMIT 20";
$result = $conn->query($sql);
$rejected_reservations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rejected_reservations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Management</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            opacity: 0;
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .content-wrapper {
            animation: fadeIn 0.5s ease-out forwards;
            padding-top: 80px;
            padding-bottom: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            padding-bottom: 1rem;
            text-align: center;
        }
        
        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: #7556cc !important;
            margin-bottom: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .table-wrapper {
            animation: fadeIn 0.6s ease-out forwards;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .table-wrapper:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .filter-tabs {
            animation: fadeIn 0.7s ease-out forwards;
            display: flex;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            border-bottom: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }
        
        .filter-tabs::-webkit-scrollbar {
            display: none;
        }
        
        .filter-tab {
            padding: 16px 24px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
            position: relative;
        }
        
        .filter-tab:hover {
            color: #7556cc;
            background-color: rgba(117, 86, 204, 0.05);
        }
        
        .filter-tab.active {
            color: #7556cc;
            border-bottom: 3px solid #7556cc;
            background-color: rgba(117, 86, 204, 0.1);
        }
        
        .filter-tab.active::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #7556cc, #9556cc);
            border-radius: 3px 3px 0 0;
        }
        
        .table-header {
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(to right, #f8fafc, #ffffff);
        }
        
        .table-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-header h2 i {
            color: #7556cc;
            font-size: 1.75rem;
        }
        
        .table-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .search-container {
            position: relative;
            width: 280px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .search-input:focus {
            outline: none;
            border-color: #7556cc;
            box-shadow: 0 0 0 3px rgba(117, 86, 204, 0.15);
            background-color: white;
        }
        
        .search-icon {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: #a0aec0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        
        .bulk-action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        
        .bulk-action-btn.primary {
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            color: white;
        }
        
        .bulk-action-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(117, 86, 204, 0.3);
        }
        
        .bulk-action-btn i {
            font-size: 1.1rem;
        }
        
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .modern-table thead tr {
            background-color: #f8fafc;
        }
        
        .modern-table th {
            padding: 16px 24px;
            font-weight: 600;
            color: #475569;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            position: relative;
        }
        
        .modern-table th:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, rgba(117, 86, 204, 0.2), rgba(149, 86, 204, 0));
        }
        
        .modern-table td {
            padding: 16px 24px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        
        .modern-table tbody tr {
            transition: all 0.2s ease;
        }
        
        .modern-table tbody tr:hover td {
            background-color: transparent;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
        }
        
        .action-button {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .action-button.edit {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
        }
        
        .action-button.edit:hover {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }
        
        .action-button.delete {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
        }
        
        .action-button.delete:hover {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
        }
        
        .action-button i {
            font-size: 1.1rem;
        }

        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .modal-backdrop.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background-color: white;
            border-radius: 16px;
            width: 500px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: translateY(30px) scale(0.95);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
        }
        
        .modal-backdrop.active .modal {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(to right, #f8fafc, #ffffff);
        }
        
        .modal-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-header h3 i {
            color: #7556cc;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #64748b;
            cursor: pointer;
            transition: color 0.2s ease, transform 0.2s ease;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-close:hover {
            color: #ef4444;
            background-color: #fee2e2;
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 16px;
            padding: 20px 24px;
            border-top: 1px solid #e2e8f0;
            background: linear-gradient(to right, #ffffff, #f8fafc);
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(117, 86, 204, 0.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(203, 213, 225, 0.5);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #475569;
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            background-color: #f8fafc;
            color: #334155;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #7556cc;
            box-shadow: 0 0 0 3px rgba(117, 86, 204, 0.2);
            outline: none;
            background-color: white;
        }
        
        .form-group input:hover,
        .form-group select:hover {
            border-color: #94a3b8;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.025em;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .status-badge.active {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border: 1px solid rgba(22, 101, 52, 0.1);
        }
        
        .status-badge.completed {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid rgba(30, 64, 175, 0.1);
        }
        
        .status-badge.approved {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid rgba(22, 101, 52, 0.1);
        }
        
        .status-badge.pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid rgba(146, 64, 14, 0.1);
        }
        
        .time-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .time-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .time-badge i {
            margin-right: 8px;
            color: #7556cc;
            font-size: 1rem;
        }
        
        /* Laboratory-specific styles */
        .day-selection {
            padding: 16px 24px;
            background: linear-gradient(to right, #f8fafc, #ffffff);
            border-bottom: 1px solid #e2e8f0;
        }
        
        .day-selection .day-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .day-btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: white;
            color: #475569;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .day-btn:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }
        
        .day-btn.active {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            color: #7556cc;
            border-color: #c4b5fd;
            box-shadow: 0 2px 5px rgba(117, 86, 204, 0.15);
        }
        
        .empty-state {
            padding: 48px 24px;
            text-align: center;
        }
        
        .empty-state-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.6s ease-out forwards;
            margin-top: 50px;
            margin-bottom: 50px;
        }
        
        .empty-state-content i {
            font-size: 3rem;
            color: #a0aec0;
            margin-bottom: 16px;
        }
        
        .empty-state-content p {
            font-size: 1.1rem;
            color: #a0aec0;
            font-weight: 400;
        }
        
        /* Animation utilities */
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .table-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            
            .search-container {
                width: 100%;
            }
            
            .search-container input {
                width: 100%;
            }
            
            .filter-tab {
                padding: 12px 16px;
                font-size: 0.9rem;
            }
            
            .day-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 12px 16px;
            }
            
            .modal {
                width: 95%;
            }
        }

        /* Container header styles to match leaderboard.php */
        .container-header {
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header-left {
            flex: 1;
            min-width: 250px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-left h2 {
            color: #1e293b;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .header-left h2 i {
            color: #7556cc;
        }
        
        .header-left p {
            color: #64748b;
            font-size: 0.9rem;
            margin: 0;
        }

        /* Notification system styles - Consistent across all pages */
        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            width: 350px;
            max-width: 90vw;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .notification {
            display: flex;
            align-items: flex-start;
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 10px;
            transform: translateX(120%);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            border-left: 4px solid #7556cc;
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification.info {
            border-left-color: #3b82f6;
        }
        
        .notification.success {
            border-left-color: #10b981;
        }
        
        .notification.warning {
            border-left-color: #f59e0b;
        }
        
        .notification.error {
            border-left-color: #ef4444;
        }
        
        .notification-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        
        .notification-icon i {
            font-size: 24px;
        }
        
        .notification.info .notification-icon i {
            color: #3b82f6;
        }
        
        .notification.success .notification-icon i {
            color: #10b981;
        }
        
        .notification.warning .notification-icon i {
            color: #f59e0b;
        }
        
        .notification.error .notification-icon i {
            color: #ef4444;
        }
        
        .notification-content {
            flex-grow: 1;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #111827;
        }
        
        .notification-message {
            font-size: 0.875rem;
            color: #4b5563;
        }
        
        .notification-close {
            background: transparent;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #9ca3af;
            margin-left: 12px;
            padding: 0;
            line-height: 1;
        }
        
        .notification-close:hover {
            color: #4b5563;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="nav-container">
        <div class="nav-wrapper">
            <!-- Left side - Profile -->
            <div class="nav-profile">
                <div class="profile-trigger" id="profile-trigger">
                    <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : '../assets/images/logo/AVATAR.png'; ?>" 
                         alt="Profile">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>

            <!-- Center - Navigation -->
            <nav class="nav-links">
                <a href="admin_dashboard.php" class="nav-link">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="leaderboard.php" class="nav-link">
                    <i class="ri-trophy-line"></i>
                    <span>Leaderboard</span>
                </a>
                <a href="laboratories.php" class="nav-link active">
                    <i class="ri-computer-line active"></i>
                    <span>Laboratory</span>
                </a>
                <a href="request.php" class="nav-link">
                    <i class="ri-mail-check-line"></i>
                    <span>Request</span>
                </a>
                <a href="sit-in.php" class="nav-link">
                    <i class="ri-map-pin-user-line"></i>
                    <span>Sit-in</span>
                </a>
                <a href="records.php" class="nav-link">
                    <i class="ri-bar-chart-line"></i>
                    <span>Records</span>
                </a>
                <a href="reports.php" class="nav-link">
                    <i class="ri-file-text-line"></i>
                    <span>Reports</span>
                </a>
            </nav>

            <!-- Right side - Actions -->
            <div class="nav-actions">
                <a href="#" class="action-link">
                    <i class="fas fa-bell"></i>
                </a>
                <a href="../auth/logout.php" class="action-link">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Notification System -->
    <div id="notification-container"></div>

    <div class="content-wrapper">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="ri-computer-line"></i>
                <span>Laboratory Management</span>
            </div>
        </div>
        
        <div class="table-wrapper">
            <div class="container-header">
                <div class="header-content">
                    <div class="header-left">
                        <h2><i class="ri-building-4-line"></i> Laboratory Schedules</h2>
                        <p>Manage laboratory schedules and availability for students</p>
                    </div>
                    <div class="header-right">
                        <button id="addScheduleBtn" class="bulk-action-btn primary">
                            <i class="ri-add-line"></i> Add Schedule
                        </button>
                        <div class="search-container">
                            <input type="text" id="searchInput" class="search-input" placeholder="Search schedules...">
                            <span class="search-icon">
                                <i class="ri-search-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab active" data-target="lab-517">Laboratory 517</div>
                <div class="filter-tab" data-target="lab-524">Laboratory 524</div>
                <div class="filter-tab" data-target="lab-526">Laboratory 526</div>
                <div class="filter-tab" data-target="lab-528">Laboratory 528</div>
                <div class="filter-tab" data-target="lab-530">Laboratory 530</div>
                <div class="filter-tab" data-target="lab-542">Laboratory 542</div>
            </div>

            <!-- Day Selection -->
            <div class="day-selection">
                <div class="day-buttons">
                    <button class="day-btn active" data-day="Monday">Monday</button>
                    <button class="day-btn" data-day="Tuesday">Tuesday</button>
                    <button class="day-btn" data-day="Wednesday">Wednesday</button>
                    <button class="day-btn" data-day="Thursday">Thursday</button>
                    <button class="day-btn" data-day="Friday">Friday</button>
                    <button class="day-btn" data-day="Saturday">Saturday</button>
                </div>
            </div>

            <!-- Schedule Table -->
            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th class="w-1/4">Time</th>
                            <th class="w-1/4">Subject</th>
                            <th class="w-1/4">Professor</th>
                            <th class="w-1/4">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="schedule-table-body">
                        <?php if (empty($lab_schedules)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                <div class="empty-state-content">
                                    <i class="ri-calendar-todo-line"></i>
                                    <p>No schedules found for <?php echo htmlspecialchars($selected_lab); ?> on <?php echo htmlspecialchars($selected_day); ?></p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($lab_schedules as $schedule): ?>
                            <tr>
                                <td>
                                    <span class="time-badge">
                                        <i class="ri-time-line"></i>
                                        <?php 
                                            $time_start = new DateTime($schedule['time_start']);
                                            $time_end = new DateTime($schedule['time_end']);
                                            echo $time_start->format('g:i A') . ' - ' . $time_end->format('g:i A'); 
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['professor']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit edit-btn" 
                                                data-id="<?php echo $schedule['id']; ?>"
                                                data-day="<?php echo htmlspecialchars($schedule['day']); ?>"
                                                data-lab="<?php echo htmlspecialchars($schedule['laboratory']); ?>"
                                                data-timestart="<?php echo htmlspecialchars($schedule['time_start']); ?>"
                                                data-timeend="<?php echo htmlspecialchars($schedule['time_end']); ?>"
                                                data-subject="<?php echo htmlspecialchars($schedule['subject']); ?>"
                                                data-professor="<?php echo htmlspecialchars($schedule['professor']); ?>">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button class="action-button delete delete-btn" 
                                                data-id="<?php echo $schedule['id']; ?>"
                                                data-day="<?php echo htmlspecialchars($schedule['day']); ?>"
                                                data-lab="<?php echo htmlspecialchars($schedule['laboratory']); ?>">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div id="addScheduleModal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="ri-add-circle-line"></i> Add Laboratory Schedule</h3>
                <button id="closeAddModal" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addScheduleForm" action="" method="POST">
                    <input type="hidden" name="action" value="add_schedule">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="day">Day</label>
                            <select name="day" id="day" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="laboratory">Laboratory</label>
                            <select name="laboratory" id="laboratory" required>
                                <option value="Laboratory 517">Laboratory 517</option>
                                <option value="Laboratory 524">Laboratory 524</option>
                                <option value="Laboratory 526">Laboratory 526</option>
                                <option value="Laboratory 528">Laboratory 528</option>
                                <option value="Laboratory 530">Laboratory 530</option>
                                <option value="Laboratory 542">Laboratory 542</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="time_start">Time Start</label>
                            <input type="time" name="time_start" id="time_start" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="time_end">Time End</label>
                            <input type="time" name="time_end" id="time_end" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" name="subject" id="subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="professor">Professor</label>
                        <input type="text" name="professor" id="professor" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelAddBtn" class="btn btn-secondary">Cancel</button>
                <button type="button" id="submitAddBtn" class="btn btn-primary">Add Schedule</button>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div id="editScheduleModal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="ri-edit-box-line"></i> Edit Laboratory Schedule</h3>
                <button id="closeEditModal" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editScheduleForm" action="" method="POST">
                    <input type="hidden" name="action" value="update_schedule">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="edit_day">Day</label>
                            <select name="day" id="edit_day" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_laboratory">Laboratory</label>
                            <select name="laboratory" id="edit_laboratory" required>
                                <option value="Laboratory 517">Laboratory 517</option>
                                <option value="Laboratory 524">Laboratory 524</option>
                                <option value="Laboratory 526">Laboratory 526</option>
                                <option value="Laboratory 528">Laboratory 528</option>
                                <option value="Laboratory 530">Laboratory 530</option>
                                <option value="Laboratory 542">Laboratory 542</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="edit_time_start">Time Start</label>
                            <input type="time" name="time_start" id="edit_time_start" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_time_end">Time End</label>
                            <input type="time" name="time_end" id="edit_time_end" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_subject">Subject</label>
                        <input type="text" name="subject" id="edit_subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_professor">Professor</label>
                        <input type="text" name="professor" id="edit_professor" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelEditBtn" class="btn btn-secondary">Cancel</button>
                <button type="button" id="submitEditBtn" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="ri-delete-bin-line"></i> Delete Schedule</h3>
                <button id="closeDeleteModal" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-4">
                        <i class="ri-error-warning-line text-3xl text-red-500"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-900 mb-2">Are you sure you want to delete this schedule?</p>
                    <p class="text-gray-500">This action cannot be undone.</p>
                </div>
                <form id="deleteScheduleForm" action="" method="POST">
                    <input type="hidden" name="action" value="delete_schedule">
                    <input type="hidden" name="schedule_id" id="delete_schedule_id">
                    <input type="hidden" name="day" id="delete_day">
                    <input type="hidden" name="laboratory" id="delete_laboratory">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelDeleteBtn" class="btn btn-secondary">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>

    <script>
        // Enhanced Notification System
        function showNotification(title, message, type = 'info', duration = 5000) {
            const notificationContainer = document.getElementById('notification-container');
            
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            let icon = 'information-line';
            if (type === 'success') icon = 'check-line';
            if (type === 'error') icon = 'error-warning-line';
            if (type === 'warning') icon = 'alert-line';
            
            notification.innerHTML = `
                <div class="notification-icon">
                    <i class="ri-${icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close" onclick="closeNotification(this)">&times;</button>
            `;
            
            notificationContainer.appendChild(notification);
            
            // Force reflow to enable animation
            notification.getBoundingClientRect();
            notification.classList.add('show');
            
            if (duration > 0) {
                setTimeout(() => closeNotification(notification), duration);
            }
            
            return notification;
        }
        
        function closeNotification(notification) {
            if (!notification) return;
            
            if (notification.tagName === 'BUTTON') {
                notification = notification.closest('.notification');
            }
            
            notification.classList.remove('show');
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.parentElement.removeChild(notification);
                }
            }, 400);
        }

        // Enhanced initial setup
        document.addEventListener('DOMContentLoaded', function() {
            // Success message handling
            <?php if (isset($_GET['success'])): ?>
            showNotification('Success', '<?php echo htmlspecialchars($_GET['success']); ?>', 'success');
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            showNotification('Error', '<?php echo htmlspecialchars($error_message); ?>', 'error');
            <?php endif; ?>
            
            // Set active tab based on URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const lab = urlParams.get('lab');
            if (lab) {
                const labTarget = lab.replace('Laboratory ', 'lab-');
                document.querySelectorAll('.filter-tab').forEach(tab => {
                    if (tab.getAttribute('data-target') === labTarget) {
                        tab.classList.add('active');
                    } else {
                        tab.classList.remove('active');
                    }
                });
            }
            
            // Set active day button based on URL parameter
            const day = urlParams.get('day');
            if (day) {
                document.querySelectorAll('.day-btn').forEach(btn => {
                    if (btn.getAttribute('data-day') === day) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }
            
            // Filter tab click handler with animation
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    // Update active class with animation
                    document.querySelectorAll('.filter-tab').forEach(t => {
                        t.classList.remove('active');
                        t.style.transition = 'all 0.3s ease';
                    });
                    
                    this.classList.add('active');
                    
                    // Get lab from data-target
                    const labTarget = this.getAttribute('data-target');
                    const lab = 'Laboratory ' + labTarget.replace('lab-', '');
                    
                    // Get current day
                    const activeDay = document.querySelector('.day-btn.active').getAttribute('data-day');
                    
                    // Show loading state
                    const tableBody = document.getElementById('schedule-table-body');
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="empty-state">
                                <div class="empty-state-content animate-pulse">
                                    <i class="ri-loader-4-line"></i>
                                    <p>Loading schedules...</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    
                    // Redirect to update the view
                    setTimeout(() => {
                        window.location.href = `laboratories.php?lab=${lab}&day=${activeDay}`;
                    }, 300);
                });
            });
            
            // Day button click handler with animation
            document.querySelectorAll('.day-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Update active class with animation
                    document.querySelectorAll('.day-btn').forEach(b => {
                        b.classList.remove('active');
                        b.style.transition = 'all 0.3s ease';
                    });
                    
                    this.classList.add('active');
                    
                    // Get day from data attribute
                    const day = this.getAttribute('data-day');
                    
                    // Get current lab
                    const labTarget = document.querySelector('.filter-tab.active').getAttribute('data-target');
                    const lab = 'Laboratory ' + labTarget.replace('lab-', '');
                    
                    // Show loading state
                    const tableBody = document.getElementById('schedule-table-body');
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="empty-state">
                                <div class="empty-state-content animate-pulse">
                                    <i class="ri-loader-4-line"></i>
                                    <p>Loading schedules...</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    
                    // Redirect to update the view
                    setTimeout(() => {
                        window.location.href = `laboratories.php?lab=${lab}&day=${day}`;
                    }, 300);
                });
            });
            
            // Enhanced search functionality for the new search input
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchText = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#schedule-table-body tr:not(.empty-state)');
                    
                    let hasResults = false;
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchText)) {
                            row.style.display = '';
                            hasResults = true;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Check if empty state exists
                    let emptyRow = document.querySelector('.empty-state');
                    
                    // If no results and empty state doesn't exist, add it
                    if (!hasResults && rows.length > 0) {
                        if (!emptyRow) {
                            const tableBody = document.getElementById('schedule-table-body');
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td colspan="4" class="empty-state">
                                    <div class="empty-state-content">
                                        <i class="ri-search-line"></i>
                                        <p>No schedules found matching "${searchText}"</p>
                                    </div>
                                </td>
                            `;
                            tableBody.appendChild(tr);
                        } else {
                            const emptyText = emptyRow.querySelector('p');
                            if (emptyText) {
                                emptyText.textContent = `No schedules found matching "${searchText}"`;
                            }
                            emptyRow.style.display = '';
                        }
                    } else if (emptyRow && hasResults) {
                        // Hide empty state if we have results
                        emptyRow.style.display = 'none';
                    }
                });
            }
            
            // Quick add button from empty state
            const quickAddBtn = document.getElementById('quickAddBtn');
            if (quickAddBtn) {
                quickAddBtn.addEventListener('click', function() {
                    document.getElementById('addScheduleBtn').click();
                });
            }

            // Set up modal event handlers
            setupModals();
        });

        // Enhanced modal handling
        function setupModals() {
            // Add Schedule Modal
            const addScheduleBtn = document.getElementById('addScheduleBtn');
            const addScheduleModal = document.getElementById('addScheduleModal');
            const closeAddModal = document.getElementById('closeAddModal');
            const cancelAddBtn = document.getElementById('cancelAddBtn');
            const submitAddBtn = document.getElementById('submitAddBtn');

            addScheduleBtn.addEventListener('click', () => {
                addScheduleModal.classList.add('active');
                
                // Pre-populate with current filter selections
                const activeTab = document.querySelector('.filter-tab.active');
                const activeDay = document.querySelector('.day-btn.active');
                
                if (activeTab && activeDay) {
                    const labTarget = activeTab.getAttribute('data-target');
                    const lab = 'Laboratory ' + labTarget.replace('lab-', '');
                    const day = activeDay.getAttribute('data-day');
                    
                    document.getElementById('laboratory').value = lab;
                    document.getElementById('day').value = day;
                    
                    // Focus on time_start after a short delay
                    setTimeout(() => {
                        document.getElementById('time_start').focus();
                    }, 300);
                }
            });

            closeAddModal.addEventListener('click', () => {
                addScheduleModal.classList.remove('active');
            });

            cancelAddBtn.addEventListener('click', () => {
                addScheduleModal.classList.remove('active');
            });

            submitAddBtn.addEventListener('click', () => {
                // Validate form
                const form = document.getElementById('addScheduleForm');
                
                // Custom validation
                const timeStart = document.getElementById('time_start').value;
                const timeEnd = document.getElementById('time_end').value;
                
                if (timeStart >= timeEnd) {
                    showNotification('Validation Error', 'End time must be after start time', 'error');
                    return;
                }
                
                if (form.checkValidity()) {
                    // Show processing notification
                    const notification = showNotification('Processing', 'Adding schedule...', 'info', 0);
                    
                    // Submit form with animation
                    submitAddBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Processing...';
                    submitAddBtn.disabled = true;
                    cancelAddBtn.disabled = true;
                    
                    setTimeout(() => {
                        form.submit();
                    }, 500);
                } else {
                    form.reportValidity();
                }
            });

            // Edit Schedule Modal
            const editScheduleModal = document.getElementById('editScheduleModal');
            const closeEditModal = document.getElementById('closeEditModal');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const submitEditBtn = document.getElementById('submitEditBtn');
            
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const id = button.getAttribute('data-id');
                    const day = button.getAttribute('data-day');
                    const lab = button.getAttribute('data-lab');
                    const timeStart = button.getAttribute('data-timestart');
                    const timeEnd = button.getAttribute('data-timeend');
                    const subject = button.getAttribute('data-subject');
                    const professor = button.getAttribute('data-professor');
                    
                    document.getElementById('edit_schedule_id').value = id;
                    document.getElementById('edit_day').value = day;
                    document.getElementById('edit_laboratory').value = lab;
                    document.getElementById('edit_time_start').value = timeStart.substr(0, 5);
                    document.getElementById('edit_time_end').value = timeEnd.substr(0, 5);
                    document.getElementById('edit_subject').value = subject;
                    document.getElementById('edit_professor').value = professor;
                    
                    editScheduleModal.classList.add('active');
                });
            });

            closeEditModal.addEventListener('click', () => {
                editScheduleModal.classList.remove('active');
            });

            cancelEditBtn.addEventListener('click', () => {
                editScheduleModal.classList.remove('active');
            });

            submitEditBtn.addEventListener('click', () => {
                // Validate form
                const form = document.getElementById('editScheduleForm');
                
                // Custom validation
                const timeStart = document.getElementById('edit_time_start').value;
                const timeEnd = document.getElementById('edit_time_end').value;
                
                if (timeStart >= timeEnd) {
                    showNotification('Validation Error', 'End time must be after start time', 'error');
                    return;
                }
                
                if (form.checkValidity()) {
                    // Show processing notification
                    const notification = showNotification('Processing', 'Updating schedule...', 'info', 0);
                    
                    // Submit form with animation
                    submitEditBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Processing...';
                    submitEditBtn.disabled = true;
                    cancelEditBtn.disabled = true;
                    
                    setTimeout(() => {
                        form.submit();
                    }, 500);
                } else {
                    form.reportValidity();
                }
            });

            // Delete Confirmation Modal
            const deleteModal = document.getElementById('deleteModal');
            const closeDeleteModal = document.getElementById('closeDeleteModal');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const id = button.getAttribute('data-id');
                    const day = button.getAttribute('data-day');
                    const lab = button.getAttribute('data-lab');
                    
                    document.getElementById('delete_schedule_id').value = id;
                    document.getElementById('delete_day').value = day;
                    document.getElementById('delete_laboratory').value = lab;
                    
                    deleteModal.classList.add('active');
                });
            });

            closeDeleteModal.addEventListener('click', () => {
                deleteModal.classList.remove('active');
            });

            cancelDeleteBtn.addEventListener('click', () => {
                deleteModal.classList.remove('active');
            });

            confirmDeleteBtn.addEventListener('click', () => {
                // Show processing notification
                const notification = showNotification('Processing', 'Deleting schedule...', 'info', 0);
                
                // Submit form with animation
                confirmDeleteBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Processing...';
                confirmDeleteBtn.disabled = true;
                cancelDeleteBtn.disabled = true;
                
                setTimeout(() => {
                    document.getElementById('deleteScheduleForm').submit();
                }, 500);
            });
            
            // Close modals when clicking outside
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.addEventListener('click', (e) => {
                    if (e.target === backdrop) {
                        backdrop.classList.remove('active');
                    }
                });
            });
            
            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Escape key to close modals
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-backdrop.active').forEach(modal => {
                        modal.classList.remove('active');
                    });
                }
                
                // Enter key in modals to submit
                if (e.key === 'Enter' && !e.shiftKey) {
                    // Check if a modal is active and focus is not in a textarea
                    if (document.querySelector('.modal-backdrop.active') && document.activeElement.tagName !== 'TEXTAREA') {
                        // Prevent default to stop form submission
                        e.preventDefault();
                        
                        // Determine which modal is active and click its submit button
                        if (document.getElementById('addScheduleModal').classList.contains('active')) {
                            document.getElementById('submitAddBtn').click();
                        } else if (document.getElementById('editScheduleModal').classList.contains('active')) {
                            document.getElementById('submitEditBtn').click();
                        } else if (document.getElementById('deleteModal').classList.contains('active')) {
                            document.getElementById('confirmDeleteBtn').click();
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>