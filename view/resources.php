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

// Fetch announcements
$announcements = [];
$query = "SELECT * FROM announcements ORDER BY created_at DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Handle resource CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new resource
        if ($_POST['action'] === 'add_resource') {
            $name = $_POST['name'];
            $description = $_POST['description'];
            $link = $_POST['link'];
            
            // Handle image upload
            $image_path = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_dir = '../assets/images/resources/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $image_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $image_name;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path = 'assets/images/resources/' . $image_name;
                }
            }
            
            $sql = "INSERT INTO resources (name, description, image, link, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $description, $image_path, $link);
            
            if ($stmt->execute()) {
                header("Location: resources.php?success=Resource added successfully");
                exit();
            } else {
                $error_message = "Error adding resource: " . $conn->error;
            }
        }
        
        // Update resource
        if ($_POST['action'] === 'update_resource') {
            $id = $_POST['resource_id'];
            $name = $_POST['name'];
            $description = $_POST['description'];
            $link = $_POST['link'];
            
            // Get current resource data
            $sql = "SELECT image FROM resources WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $resource = $result->fetch_assoc();
            
            $image_path = $resource['image'];
            
            // Handle image upload if new image is selected
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_dir = '../assets/images/resources/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $image_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $image_name;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // Delete old image if exists
                    if (!empty($resource['image']) && file_exists('../' . $resource['image'])) {
                        unlink('../' . $resource['image']);
                    }
                    $image_path = 'assets/images/resources/' . $image_name;
                }
            }
            
            $sql = "UPDATE resources SET name = ?, description = ?, image = ?, link = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $name, $description, $image_path, $link, $id);
            
            if ($stmt->execute()) {
                header("Location: resources.php?success=Resource updated successfully");
                exit();
            } else {
                $error_message = "Error updating resource: " . $conn->error;
            }
        }
        
        // Delete resource
        if ($_POST['action'] === 'delete_resource') {
            $id = $_POST['resource_id'];
            
            // Get resource data to delete image
            $sql = "SELECT image FROM resources WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $resource = $result->fetch_assoc();
            
            $sql = "DELETE FROM resources WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Delete image file if exists
                if (!empty($resource['image']) && file_exists('../' . $resource['image'])) {
                    unlink('../' . $resource['image']);
                }
                
                header("Location: resources.php?success=Resource deleted successfully");
                exit();
            } else {
                $error_message = "Error deleting resource: " . $conn->error;
            }
        }
    }
}

// Fetch resources
$resources = [];
$query = "SELECT * FROM resources ORDER BY created_at DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
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
    <title>Resources Management</title>
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            background-color: #f8fafc;
            color: #334155;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #7556cc;
            box-shadow: 0 0 0 3px rgba(117, 86, 204, 0.2);
            outline: none;
            background-color: white;
        }
        
        .form-group input:hover,
        .form-group select:hover,
        .form-group textarea:hover {
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
        
        .image-preview {
            width: 100%;
            height: 140px;
            border-radius: 10px;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 12px;
            border: 1px dashed #cbd5e1;
            transition: all 0.3s ease;
        }
        
        .image-preview:hover {
            border-color: #7556cc;
            box-shadow: 0 0 0 2px rgba(117, 86, 204, 0.1);
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .image-preview-placeholder {
            color: #94a3b8;
            font-size: 0.9rem;
            text-align: center;
            padding: 20px;
        }
        
        .image-preview-placeholder i {
            font-size: 2rem;
            margin-bottom: 8px;
            display: block;
        }
        
        /* Resource card styles */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            padding: 24px;
        }
        
        .resource-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            height: 100%;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }
        
        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }
        
        .resource-image {
            width: 100%;
            height: 160px;
            overflow: hidden;
            position: relative;
            background-color: #f8fafc;
        }
        
        .resource-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .resource-card:hover .resource-image img {
            transform: scale(1.05);
        }
        
        .resource-content {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .resource-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .resource-description {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 16px;
            flex: 1;
        }
        
        .resource-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }
        
        .resource-link {
            display: inline-flex;
            align-items: center;
            color: #7556cc;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .resource-link:hover {
            color: #9556cc;
            text-decoration: underline;
        }
        
        .resource-link i {
            margin-left: 4px;
            font-size: 1rem;
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
        
        /* Container header styles */
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header-right {
                display: flex;
                flex-direction: column;
                width: 100%;
                gap: 12px;
            }
            
            .search-container {
                width: 100%;
            }
            
            .bulk-action-btn {
                width: 100%;
                justify-content: center;
            }
            
            .resources-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                padding: 16px;
                gap: 16px;
            }
        }

        .nav-container {
            margin: 0 auto;
            width: 100%;
            position: fixed;
            top: 0;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            z-index: 1000;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1),
                        0 8px 30px -5px rgba(0, 0, 0, 0.1);
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
                <a href="laboratories.php" class="nav-link">
                    <i class="ri-computer-line"></i>
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
                <a href="resources.php" class="nav-link active">
                    <i class="ri-links-line"></i>
                    <span>Resources</span>
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
                <i class="ri-links-line"></i>
                <span>Resources Management</span>
            </div>
        </div>
        
        <div class="table-wrapper">
            <div class="container-header">
                <div class="header-content">
                    <div class="header-left">
                        <h2><i class="ri-book-read-line"></i> Learning Resources</h2>
                        <p>Manage educational resources for students</p>
                    </div>
                    <div class="header-right">
                        <button id="addResourceBtn" class="bulk-action-btn primary">
                            <i class="ri-add-line"></i> Add Resource
                        </button>
                        <div class="search-container">
                            <input type="text" id="searchInput" class="search-input" placeholder="Search resources...">
                            <span class="search-icon">
                                <i class="ri-search-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resource Grid -->
            <div class="resources-grid" id="resources-container">
                <?php if (empty($resources)): ?>
                <div class="empty-state-content col-span-full">
                    <i class="ri-book-read-line"></i>
                    <p>No resources found. Click "Add Resource" to create your first resource.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($resources as $resource): ?>
                    <div class="resource-card" onclick="window.open('<?php echo htmlspecialchars($resource['link']); ?>', '_blank')">
                        <div class="resource-image">
                            <?php if (!empty($resource['image'])): ?>
                            <img src="<?php echo '../' . htmlspecialchars($resource['image']); ?>" alt="<?php echo htmlspecialchars($resource['name']); ?>">
                            <?php else: ?>
                            <div class="image-preview-placeholder">
                                <i class="ri-image-line"></i>
                                <span>No image available</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="resource-content">
                            <h3 class="resource-title"><?php echo htmlspecialchars($resource['name']); ?></h3>
                            <p class="resource-description"><?php echo htmlspecialchars($resource['description']); ?></p>
                            <div class="resource-actions">
                                <a href="<?php echo htmlspecialchars($resource['link']); ?>" class="resource-link" target="_blank">
                                    Visit Resource <i class="ri-external-link-line"></i>
                                </a>
                                <div class="action-buttons">
                                    <button class="action-button edit edit-btn" 
                                            data-id="<?php echo $resource['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($resource['name']); ?>"
                                            data-description="<?php echo htmlspecialchars($resource['description']); ?>"
                                            data-image="<?php echo htmlspecialchars($resource['image']); ?>"
                                            data-link="<?php echo htmlspecialchars($resource['link']); ?>">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="action-button delete delete-btn" 
                                            data-id="<?php echo $resource['id']; ?>">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Resource Modal -->
    <div id="addResourceModal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="ri-add-circle-line"></i> Add Resource</h3>
                <button id="closeAddModal" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addResourceForm" action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_resource">
                    
                    <div class="form-group">
                        <label for="name">Resource Name</label>
                        <input type="text" name="name" id="name" required placeholder="Enter resource name">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" required placeholder="Enter resource description"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="link">Resource Link</label>
                        <input type="url" name="link" id="link" required placeholder="https://example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Resource Image</label>
                        <div class="image-preview" id="add-image-preview">
                            <div class="image-preview-placeholder">
                                <i class="ri-image-add-line"></i>
                                <span>Click to select an image</span>
                            </div>
                        </div>
                        <input type="file" name="image" id="image" accept="image/*" style="display: none;">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelAddBtn" class="btn btn-secondary">Cancel</button>
                <button type="button" id="submitAddBtn" class="btn btn-primary">Add Resource</button>
            </div>
        </div>
    </div>

    <!-- Edit Resource Modal -->
    <div id="editResourceModal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="ri-edit-box-line"></i> Edit Resource</h3>
                <button id="closeEditModal" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editResourceForm" action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_resource">
                    <input type="hidden" name="resource_id" id="edit_resource_id">
                    
                    <div class="form-group">
                        <label for="edit_name">Resource Name</label>
                        <input type="text" name="name" id="edit_name" required placeholder="Enter resource name">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea name="description" id="edit_description" required placeholder="Enter resource description"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_link">Resource Link</label>
                        <input type="url" name="link" id="edit_link" required placeholder="https://example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_image">Resource Image</label>
                        <div class="image-preview" id="edit-image-preview">
                            <div class="image-preview-placeholder">
                                <i class="ri-image-edit-line"></i>
                                <span>Click to change image</span>
                            </div>
                        </div>
                        <input type="file" name="image" id="edit_image" accept="image/*" style="display: none;">
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
                <h3><i class="ri-delete-bin-line"></i> Delete Resource</h3>
                <button id="closeDeleteModal" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-4">
                        <i class="ri-error-warning-line text-3xl text-red-500"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-900 mb-2">Are you sure you want to delete this resource?</p>
                    <p class="text-gray-500">This action cannot be undone.</p>
                </div>
                <form id="deleteResourceForm" action="" method="POST">
                    <input type="hidden" name="action" value="delete_resource">
                    <input type="hidden" name="resource_id" id="delete_resource_id">
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

        // Initial setup
        document.addEventListener('DOMContentLoaded', function() {
            // Success message handling
            <?php if (isset($_GET['success'])): ?>
            showNotification('Success', '<?php echo htmlspecialchars($_GET['success']); ?>', 'success');
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            showNotification('Error', '<?php echo htmlspecialchars($error_message); ?>', 'error');
            <?php endif; ?>
            
            // Enhanced search functionality
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchText = this.value.toLowerCase();
                    const cards = document.querySelectorAll('.resource-card');
                    
                    let hasResults = false;
                    
                    cards.forEach(card => {
                        const text = card.textContent.toLowerCase();
                        if (text.includes(searchText)) {
                            card.style.display = '';
                            hasResults = true;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // Check if empty state exists
                    let emptyState = document.querySelector('.empty-state-content');
                    
                    // If no results and empty state doesn't exist, add it
                    if (!hasResults && cards.length > 0) {
                        if (!emptyState) {
                            const container = document.getElementById('resources-container');
                            const div = document.createElement('div');
                            div.className = 'empty-state-content col-span-full';
                            div.innerHTML = `
                                <i class="ri-search-line"></i>
                                <p>No resources found matching "${searchText}"</p>
                            `;
                            container.appendChild(div);
                        } else if (!emptyState.classList.contains('initial-empty')) {
                            const emptyText = emptyState.querySelector('p');
                            if (emptyText) {
                                emptyText.textContent = `No resources found matching "${searchText}"`;
                            }
                            emptyState.style.display = '';
                        }
                    } else if (emptyState && hasResults && !emptyState.classList.contains('initial-empty')) {
                        // Hide empty state if we have results
                        emptyState.style.display = 'none';
                    }
                });
            }

            // Set up modal event handlers
            setupModals();
        });

        // Enhanced modal handling
        function setupModals() {
            // Add Resource Modal
            const addResourceBtn = document.getElementById('addResourceBtn');
            const addResourceModal = document.getElementById('addResourceModal');
            const closeAddModal = document.getElementById('closeAddModal');
            const cancelAddBtn = document.getElementById('cancelAddBtn');
            const submitAddBtn = document.getElementById('submitAddBtn');
            const imagePreview = document.getElementById('add-image-preview');
            const imageInput = document.getElementById('image');

            addResourceBtn.addEventListener('click', () => {
                addResourceModal.classList.add('active');
                // Focus on name field after a short delay
                setTimeout(() => {
                    document.getElementById('name').focus();
                }, 300);
            });

            closeAddModal.addEventListener('click', () => {
                addResourceModal.classList.remove('active');
            });

            cancelAddBtn.addEventListener('click', () => {
                addResourceModal.classList.remove('active');
            });

            imagePreview.addEventListener('click', () => {
                imageInput.click();
            });

            imageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.innerHTML = `
                            <img src="${e.target.result}" alt="Image Preview">
                        `;
                    }
                    reader.readAsDataURL(file);
                }
            });

            submitAddBtn.addEventListener('click', () => {
                // Validate form
                const form = document.getElementById('addResourceForm');
                
                if (form.checkValidity()) {
                    // Show processing notification
                    const notification = showNotification('Processing', 'Adding resource...', 'info', 0);
                    
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

            // Edit Resource Modal
            const editResourceModal = document.getElementById('editResourceModal');
            const closeEditModal = document.getElementById('closeEditModal');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const submitEditBtn = document.getElementById('submitEditBtn');
            const editImagePreview = document.getElementById('edit-image-preview');
            const editImageInput = document.getElementById('edit_image');
            
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent triggering parent's onclick
                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');
                    const description = button.getAttribute('data-description');
                    const image = button.getAttribute('data-image');
                    const link = button.getAttribute('data-link');
                    
                    document.getElementById('edit_resource_id').value = id;
                    document.getElementById('edit_name').value = name;
                    document.getElementById('edit_description').value = description;
                    document.getElementById('edit_link').value = link;
                    
                    // Set image preview
                    if (image && image !== 'null') {
                        editImagePreview.innerHTML = `
                            <img src="../${image}" alt="Image Preview">
                        `;
                    } else {
                        editImagePreview.innerHTML = `
                            <div class="image-preview-placeholder">
                                <i class="ri-image-edit-line"></i>
                                <span>Click to add an image</span>
                            </div>
                        `;
                    }
                    
                    editResourceModal.classList.add('active');
                });
            });

            closeEditModal.addEventListener('click', () => {
                editResourceModal.classList.remove('active');
            });

            cancelEditBtn.addEventListener('click', () => {
                editResourceModal.classList.remove('active');
            });

            editImagePreview.addEventListener('click', () => {
                editImageInput.click();
            });

            editImageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        editImagePreview.innerHTML = `
                            <img src="${e.target.result}" alt="Image Preview">
                        `;
                    }
                    reader.readAsDataURL(file);
                }
            });

            submitEditBtn.addEventListener('click', () => {
                // Validate form
                const form = document.getElementById('editResourceForm');
                
                if (form.checkValidity()) {
                    // Show processing notification
                    const notification = showNotification('Processing', 'Updating resource...', 'info', 0);
                    
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
                button.addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent triggering parent's onclick
                    const id = button.getAttribute('data-id');
                    
                    document.getElementById('delete_resource_id').value = id;
                    
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
                const notification = showNotification('Processing', 'Deleting resource...', 'info', 0);
                
                // Submit form with animation
                confirmDeleteBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Processing...';
                confirmDeleteBtn.disabled = true;
                cancelDeleteBtn.disabled = true;
                
                setTimeout(() => {
                    document.getElementById('deleteResourceForm').submit();
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
                        if (document.getElementById('addResourceModal').classList.contains('active')) {
                            document.getElementById('submitAddBtn').click();
                        } else if (document.getElementById('editResourceModal').classList.contains('active')) {
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