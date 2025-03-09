<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Include the ensure_tables script to make sure sit_ins table exists
require_once '../config/ensure_tables.php';
require_once '../config/db_connect.php';

// Fetch current sit-in students from the sit_ins table
$current_students = [];
$query = "SELECT s.*, u.firstname, u.lastname 
          FROM sit_ins s
          LEFT JOIN users u ON s.idno = u.idno
          WHERE s.time_out IS NULL
          AND s.status = 'active'
          ORDER BY s.time_in DESC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $current_students[] = $row;
    }
}

// For debugging
echo "<!-- Found " . count($current_students) . " current students -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="admin_dashboard.php" class="nav-link ">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="request.php" class="nav-link">
                    <i class="ri-mail-check-line"></i>
                    <span>Request</span>
                </a>
                <a href="sit-in.php" class="nav-link active">
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

    <style>
        .content-wrapper {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - var(--nav-height));
            padding: 2rem;
            margin-top: var(--nav-height);
        }

        .table-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 1600px;
            padding: 0;
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-header h2 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
        }

        .search-box {
            position: relative;
            width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .search-box input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(117,86,204,0.1);
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table td {
            padding: 1rem;
            font-size: 0.875rem;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table tr:hover td {
            background-color: #f8fafc;
        }

        .empty-state {
            text-align: center;
            padding: 3rem !important;
        }

        .empty-state-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            color: #a0aec0;
        }

        .empty-state-content i {
            font-size: 2.5rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge.active {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.approved {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-badge.completed {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #b45309;
        }

        .action-button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-button.danger {
            background: #ef4444;
            color: white;
        }

        .action-button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        /* Add these styles for the modal form */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            overflow: hidden;
        }

        .modal-backdrop.active .modal {
            transform: translateY(0);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #4a5568;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #a0aec0;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: #4a5568;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: #4a5568;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(117,86,204,0.1);
            outline: none;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #64748b;
        }

        .btn-add {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-add:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .student-info {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 6px;
            margin-top: 1rem;
            display: none;
        }

        .student-info.active {
            display: block;
        }

        .student-info h4 {
            margin: 0 0 0.5rem;
            font-size: 1rem;
            color: #4a5568;
        }

        .student-info p {
            margin: 0;
            font-size: 0.875rem;
            color: #718096;
        }

        .pc-selector {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-top: 0.5rem;
            max-height: 300px;
            overflow-y: auto;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 6px;
        }

        /* Updated PC unit styling to match request page */
        .pc-unit {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .pc-unit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .computer-icon {
            font-size: 1.5rem;
            color: #7556cc;
            margin-bottom: 0.5rem;
        }

        .computer-info {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .pc-number {
            font-weight: 600;
            color: #2d3748;
        }

        .pc-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            margin-top: 0.25rem;
        }

        .pc-unit.available {
            border-color: #c6f6d5;
        }

        .pc-unit.in-use {
            border-color: #fed7d7;
            opacity: 0.8;
            cursor: not-allowed;
        }

        .pc-status.available {
            background: #c6f6d5;
            color: #2f855a;
        }

        .pc-status.in-use {
            background: #fed7d7;
            color: #c53030;
        }

        .pc-unit.selected {
            background: #e0f2fe;
            border-color: #7556cc;
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(117, 86, 204, 0.2);
        }
        
        .sessions-badge {
            background-color: #dcfce7;
            color: #16a34a;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-weight: bold;
        }

        #submitSitinBtn {
            opacity: 0.7;
            cursor: not-allowed;
        }

        #submitSitinBtn.active {
            opacity: 1;
            cursor: pointer;
        }
        
        /* Adjusted spacing between dashboard grid and filter tabs */
        .filter-tabs {
            display: flex;
            padding: 0 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 0.5rem; /* Reduced margin */
        }

        .filter-tab {
            padding: 1rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: #718096;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .filter-tab:hover {
            color: var(--primary-color);
        }

        .filter-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .view-container {
            display: none;
        }

        .view-container.active {
            display: block;
        }

        .dashboard-grid {
            display: flex;
            flex-direction: row;
            gap: 1.5rem;
            padding: 1.5rem;
            height: calc(100vh - 240px);
            margin-top: 0; /* Removed margin-top */
        }

        .dashboard-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .profile-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .profile-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: #2d3748;
        }

        .profile-content {
            padding: 1rem;
            overflow-y: auto;
            flex: 1;
        }

        /* Reservation form styles */        /* Add styles for the two-column layout */
        .reservation-form {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding-bottom: 2rem; /* Add padding to the bottom of the form */
        }
        
        /* Adjust student info grid for consistent spacing */
        .student-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            padding: 1.25rem;
            overflow-y: auto;
            margin-bottom: 4rem; /* Add bottom margin for button space */
        }
        
        .edit-controls {
            width: 100%;
            margin-top: 2rem; /* Increased top margin */
            margin-bottom: 1rem; /* Add bottom margin */
            display: flex;
            justify-content: center;
            grid-column: span 2;
            padding: 0 1rem;
        }
        
        .edit-btn {
            width: auto; /* Allow button to size to content */
            min-width: 200px; /* Set minimum width */
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, rgba(117,86,204,0.95) 0%, rgba(213,105,167,0.95) 100%);
            color: white;
            text-decoration: none;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(117,86,204,0.2);
            cursor: pointer;
            white-space: nowrap; /* Prevent text wrapping */
        }
        
        /* Dashboard grid and columns */
        .dashboard-grid {
            display: flex;
            flex-direction: row;
            gap: 1.5rem;
            padding: 1.5rem;
        }
        
        .dashboard-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        /* Lab selector styles */
        .lab-select {
            padding: 0.5rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-left: 1rem;
            background: white;
        }
        
        /* Computer unit styles to match reservation.php */
        .computer-unit {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .computer-unit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .computer-unit.in-use {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .computer-icon {
            font-size: 1.5rem;
            color: #7556cc;
            margin-bottom: 0.5rem;
        }
        
        .computer-info {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .pc-number {
            font-weight: 600;
            color: #2d3748;
        }
        
        .pc-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            margin-top: 0.25rem;
        }
        
        .computer-unit.selected {
            background: #e0f2fe;
            border-color: #7556cc;
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(117, 86, 204, 0.2);
        }
        
        /* Computer grid layout */
        .computer-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            padding: 1rem;
        }
        
        .initial-message {
            grid-column: 1 / -1;
            text-align: center;
            color: #718096;
            padding: 2rem;
        }
        
        /* Style for submit button */
        #submitSitinBtn {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        #submitSitinBtn.active {
            opacity: 1;
            cursor: pointer;
        }
        
        /* Dashboard Improvements */
        .dashboard-grid {
            display: flex;
            flex-direction: row;
            gap: 1.5rem;
            padding: 1.5rem;
            height: calc(100vh - 240px);
        }
        
        .dashboard-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }
        
        .profile-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }
        
        .profile-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: #2d3748;
        }
        
        .profile-content {
            padding: 1rem;
            overflow-y: auto;
            flex: 1;
        }
        
        /* Form Improvements */
        .reservation-form {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .search-container {
            padding: 1.25rem;
            border-bottom: 1px solid #f0f2f5;
        }
        
        .search-container label {
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .search-field {
            display: flex;
            gap: 0.75rem;
        }
        
        .search-field input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        .search-field input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(117, 86, 204, 0.1);
            outline: none;
        }
        
        .search-field button {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0 1.25rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .search-field button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        /* Student Info Grid Improvements */
        .student-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            padding: 1.25rem;
            overflow-y: auto;
        }
        
        .info-card {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            transition: all 0.2s ease;
            border: 1px solid #f0f2f5;
        }
        
        .info-card:hover {
            border-color: #e2e8f0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .info-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(117,86,204,0.1), rgba(213,105,167,0.1));
            color: var(--primary-color);
        }
        
        /* Computer Grid Improvements */
        .computer-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.75rem;
            padding: 1rem;
            overflow-y: auto;
            height: 100%;
        }
        
        .computer-unit {
            background: white;
            border: 1px solid #f0f2f5;
            border-radius: 8px;
            padding: 0.75rem 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .computer-unit.available:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-color: #c6f6d5;
        }
        
        .computer-unit.selected {
            background: #e0f2fe;
            border-color: var(--primary-color);
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(117, 86, 204, 0.2);
        }
        
        .initial-message {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #a0aec0;
            text-align: center;
            padding: 3rem 1rem;
            font-size: 0.95rem;
            gap: 1rem;
        }
        
        .initial-message i {
            font-size: 2.5rem;
            opacity: 0.5;
        }
        
        .lab-select {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #4a5568;
            font-size: 0.95rem;
        }
        
        /* Enhanced PC status styling */
        .computer-unit {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .computer-unit.available {
            border-color: #c6f6d5;
        }

        .computer-unit.in-use {
            border-color: #fed7d7;
            background-color: #fff5f5;
            cursor: not-allowed;
        }

        .computer-unit.in-use .pc-status {
            background-color: #fed7d7;
            color: #c53030;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            margin-top: 0.5rem;
        }

        .computer-icon {
            font-size: 1.5rem;
            color: #7556cc;
            margin-bottom: 0.5rem;
        }

        .computer-info {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .pc-number {
            font-weight: 600;
            color: #2d3748;
        }

        .pc-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            margin-top: 0.25rem;
        }

        .computer-unit.selected {
            background: #e0f2fe;
            border-color: #7556cc;
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(117, 86, 204, 0.2);
        }

        .initial-message {
            grid-column: 1 / -1;
            text-align: center;
            color: #718096;
            padding: 2rem;
        }
    </style>

    <div class="content-wrapper">
        <div class="table-wrapper">
            <div class="table-header">
                <h2>Laboratory Management</h2>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="ri-search-line"></i>
                        <input type="text" id="searchInput" placeholder="Search...">
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab active" data-target="current-students">Current Students in Laboratory</div>
                <div class="filter-tab" data-target="add-sitin">Add Sit-in</div>
            </div>
            
            <!-- Current Students Container -->
            <div id="current-students" class="view-container active">
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Full Name</th>
                                <th>Purpose</th>
                                <th>Laboratory</th>
                                <th>PC Number</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($current_students)): ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <div class="empty-state-content">
                                            <i class="ri-computer-line"></i>
                                            <p>No students currently sitting in</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($current_students as $student): ?>
                                    <tr>
                                        <td class="font-mono"><?php echo htmlspecialchars($student['idno']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($student['firstname']) && !empty($student['lastname'])) {
                                                echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']);
                                            } else {
                                                echo htmlspecialchars($student['fullname']);
                                            } 
                                            ?>
                                        </td>
                                        <td><span class="purpose-badge"><?php echo htmlspecialchars($student['purpose']); ?></span></td>
                                        <td>Laboratory <?php echo htmlspecialchars($student['laboratory']); ?></td>
                                        <td>PC <?php echo htmlspecialchars($student['pc_number']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($student['time_in'])); ?></td>
                                        <td><span class="status-badge active">Active</span></td>
                                        <td>
                                            <button class="action-button danger" onclick="markTimeOut('<?php echo $student['id']; ?>')">
                                                <i class="ri-logout-box-line"></i> Time Out
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Add Sit-in Container -->
            <div id="add-sitin" class="view-container">
                <div class="dashboard-grid">
                    <!-- Left Column - Student Search and Information -->
                    <div class="dashboard-column">
                        <div class="profile-card">
                            <div class="profile-header">
                                <h3>Student Information</h3>
                            </div>
                            <form id="addSitInForm" class="reservation-form">
                                <!-- Student ID Search Field -->
                                <div class="search-container">
                                    <label for="student_idno">Student ID Number</label>
                                    <div class="search-field">
                                        <input type="text" id="student_idno" name="idno" placeholder="Enter student ID">
                                        <button type="button" id="searchStudentBtn">
                                            <i class="ri-search-line"></i> Search
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Student info displayed in cards -->
                                <div id="studentInfo" class="student-info-grid" style="display: none;">
                                    <!-- ID Number -->
                                    <div class="info-card">
                                        <div class="info-icon"><i class="ri-profile-fill"></i></div>
                                        <div class="info-content">
                                            <div class="detail-label">Student ID</div>
                                            <div class="detail-value">
                                                <input type="text" id="display_idno" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Full Name -->
                                    <div class="info-card">
                                        <div class="info-icon"><i class="ri-user-3-fill"></i></div>
                                        <div class="info-content">
                                            <div class="detail-label">Full Name</div>
                                            <div class="detail-value">
                                                <input type="text" id="display_fullname" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Course and Year -->
                                    <div class="info-card">
                                        <div class="info-icon"><i class="ri-book-fill"></i></div>
                                        <div class="info-content">
                                            <div class="detail-label">Course/Depart. & Year</div>
                                            <div class="detail-value">
                                                <input type="text" id="display_course_year" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remaining Sessions -->
                                    <div class="info-card">
                                        <div class="info-icon"><i class="ri-timer-fill"></i></div>
                                        <div class="info-content">
                                            <div class="detail-label">Remaining Sessions</div>
                                            <div class="detail-value sessions-count" id="remainingSessions">30</div>
                                        </div>
                                    </div>

                                    <!-- Purpose -->
                                    <div class="info-card">
                                        <div class="info-icon"><i class="ri-code-box-fill"></i></div>
                                        <div class="info-content">
                                            <div class="detail-label">Purpose</div>
                                            <div class="detail-value">
                                                <select id="purpose" name="purpose" required>
                                                    <option value="">Select Purpose</option>
                                                    <option value="C Programming">C Programming</option>
                                                    <option value="Java Programming">Java Programming</option>
                                                    <option value="C#">C#</option>
                                                    <option value="PHP">PHP</option>
                                                    <option value="ASP.Net">ASP.Net</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Laboratory -->
                                    <div class="info-card">
                                        <div class="info-icon"><i class="ri-computer-fill"></i></div>
                                        <div class="info-content">
                                            <div class="detail-label">Laboratory</div>
                                            <div class="detail-value">
                                                <select id="laboratory" name="laboratory" required>
                                                    <option value="">Select Laboratory</option>
                                                    <option value="524">524</option>
                                                    <option value="526">526</option>
                                                    <option value="528">528</option>
                                                    <option value="530">530</option>
                                                    <option value="542">542</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Date Field -->
                                    <div class="info-card">
                                        <div class="info-icon"><i class="ri-calendar-fill"></i></div>
                                        <div class="info-content">
                                            <div class="detail-label">Date</div>
                                            <div class="detail-value">
                                                <input type="date" id="sit_in_date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Time Field -->
                                    <div class="info-card">
                                        <div class="info-icon"><i class="ri-time-fill"></i></div>
                                        <div class="info-content">
                                            <div class="detail-label">Time</div>
                                            <div class="detail-value">
                                                <input type="time" id="sit_in_time" name="time" required min="07:00" max="17:00">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Submit Button - Updated to be outside the grid flow -->
                                    <div class="edit-controls">
                                        <button type="button" class="edit-btn" id="submitSitinBtn" onclick="submitAddSitIn()">
                                            <i class="ri-check-line"></i>
                                            <span>Add Student Sit-in</span>
                                        </button>
                                    </div>
                                    
                                    <input type="hidden" id="selected_pc" name="pc_number" value="">
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Right Column - PC Selection -->
                    <div class="dashboard-column">
                        <div class="profile-card">
                            <div class="profile-header">
                                <h3>Select a PC</h3>
                                <select id="labSelectPc" class="lab-select">
                                    <option value="">Select Laboratory</option>
                                    <option value="524">Laboratory 524</option>
                                    <option value="526">Laboratory 526</option>
                                    <option value="528">Laboratory 528</option>
                                    <option value="530">Laboratory 530</option>
                                    <option value="542">Laboratory 542</option>
                                </select>
                            </div>
                            <div class="profile-content">
                                <div class="computer-grid" id="pcSelector">
                                    <div class="initial-message">
                                        <i class="ri-computer-line"></i>
                                        <div>Please select a laboratory to view available PCs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Tab switching functionality
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all view containers
            document.querySelectorAll('.view-container').forEach(container => {
                container.classList.remove('active');
            });

            // Show the target container
            const targetId = this.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');

            // Clear search input when switching tabs
            document.getElementById('searchInput').value = '';
        });
    });
    
    // Search functionality
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        let searchText = this.value.toLowerCase();
        let activeView = document.querySelector('.view-container.active');

        if (activeView.id === 'current-students') {
            let tableRows = activeView.querySelectorAll('.modern-table tbody tr');
            tableRows.forEach(row => {
                if (!row.querySelector('.empty-state')) {
                    let text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchText) ? '' : 'none';
                }
            });
        }
    });

    function markTimeOut(sitInId) {
        if (confirm("Are you sure you want to mark this student as timed out?")) {
            console.log("Timing out sit-in ID: " + sitInId); // Debug log
            
            // Create form data to send
            const formData = new FormData();
            formData.append('sit_in_id', sitInId);
            
            // Send AJAX request to process time out
            fetch('../controller/time_out.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log("Response status:", response.status);
                return response.json();
            })
            .then(data => {
                console.log("Response data:", data);
                if (data.success) {
                    alert('Student has been marked as timed out successfully.');
                    // Reload the page to reflect changes
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    }

    // Student search by ID - enhanced to fetch remaining sessions
    document.getElementById('student_idno')?.addEventListener('input', function() {
        let idno = this.value.trim();
        if (idno.length >= 5) {
            // Search for student with this ID
            fetch('../controller/search_student.php?idno=' + idno)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Display student information
                        document.getElementById('studentName').textContent = data.student.firstname + ' ' + data.student.lastname;
                        document.getElementById('studentCourse').textContent = data.student.course || 'No course information';
                        document.getElementById('student_id').value = data.student.id;
                        document.getElementById('remainingSessions').textContent = data.student.remaining_sessions || '30';
                        document.getElementById('studentInfo').classList.add('active');
                        validateForm();
                    } else {
                        document.getElementById('studentInfo').classList.remove('active');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('studentInfo').classList.remove('active');
                });
        } else {
            document.getElementById('studentInfo').classList.remove('active');
        }
    });

    // Load PC availability when laboratory is selected
    document.getElementById('laboratory')?.addEventListener('change', function() {
        const laboratory = this.value;
        if (laboratory) {
            loadPCAvailability(laboratory);
        } else {
            document.getElementById('pcSelector').innerHTML = '<p>Please select a laboratory first to view available PCs</p>';
            document.getElementById('selected_pc').value = '';
            validateForm();
        }
    });

    function loadPCAvailability(laboratory) {
        fetch('../controller/get_pc_availability.php?laboratory=' + laboratory)
            .then(response => response.json())
            .then(data => {
                const pcSelector = document.getElementById('pcSelector');
                pcSelector.innerHTML = '';
                
                // Check if data is available
                if (!data || data.length === 0) {
                    pcSelector.innerHTML = `
                        <div class="initial-message">
                            <i class="ri-information-line"></i>
                            <div>No PC data available for this laboratory</div>
                        </div>
                    `;
                    return;
                }
                
                // Process PC data
                data.forEach(pc => {
                    const isAvailable = pc.status === 'available';
                    const pcClass = isAvailable ? 'available' : 'in-use';
                    const pcStatus = isAvailable ? 'Available' : 'In Use';
                    const pcUnit = document.createElement('div');
                    pcUnit.className = `computer-unit ${pcClass}`;
                    pcUnit.dataset.pcNumber = pc.pc_number;

                    pcUnit.innerHTML = `
                        <div class="computer-icon">
                            <i class="ri-computer-${isAvailable ? 'line' : 'fill'}"></i>
                        </div>
                        <div class="computer-info">
                            <span class="pc-number">PC${pc.pc_number}</span>
                            <span class="pc-status ${pcClass}">${pcStatus}</span>
                        </div>
                    `;

                    if (isAvailable) {
                        pcUnit.addEventListener('click', function() {
                            document.querySelectorAll('.computer-unit.selected').forEach(unit => {
                                unit.classList.remove('selected');
                            });
                            this.classList.add('selected');
                            document.getElementById('selected_pc').value = this.dataset.pcNumber;
                            validateForm();
                        });
                    }

                    pcSelector.appendChild(pcUnit);
                });
                
                validateForm();
            })
            .catch(error => {
                console.error('Error loading PC availability:', error);
                document.getElementById('pcSelector').innerHTML = `
                    <div class="initial-message">
                        <i class="ri-error-warning-line"></i>
                        <div>Error loading PC availability. Please try again.</div>
                    </div>
                `;
            });
    }

    function validateForm() {
        const requiredFields = [
            { id: 'student_idno', check: () => document.getElementById('studentInfo').style.display === 'grid' },
            { id: 'purpose', check: () => document.getElementById('purpose').value !== '' },
            { id: 'laboratory', check: () => document.getElementById('laboratory').value !== '' },
            { id: 'sit_in_date', check: () => document.getElementById('sit_in_date').value !== '' },
            { id: 'sit_in_time', check: () => document.getElementById('sit_in_time').value !== '' },
            { id: 'selected_pc', check: () => document.getElementById('selected_pc').value !== '' },
        ];

        const isValid = requiredFields.every(field => field.check());
        const submitBtn = document.getElementById('submitSitinBtn');
        if (isValid) {
            submitBtn.classList.add('active');
            submitBtn.disabled = false;
        } else {
            submitBtn.classList.remove('active');
            submitBtn.disabled = true;
        };
        
        return isValid;
    }

    // Add event listeners for form fields    
    document.getElementById('purpose')?.addEventListener('change', validateForm);
    document.getElementById('sit_in_date')?.addEventListener('change', validateForm);
    document.getElementById('sit_in_time')?.addEventListener('change', validateForm);

    function submitAddSitIn() {
        if (!validateForm()) {
            alert('Please fill out all required fields and select a PC.');
            return;
        }
        const submitBtn = document.getElementById('submitSitinBtn');
        const formData = new FormData(document.getElementById('addSitInForm'));
        
        // For debugging - log form data
        console.log("Submitting form with data:");
        for (const [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        fetch('../controller/add_sitin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Student added to sit-in successfully');
                
                // Reset the form
                document.getElementById('addSitInForm').reset();
                document.getElementById('studentInfo').style.display = 'none';
                document.getElementById('selected_pc').value = '';
                document.getElementById('pcSelector').innerHTML = '<div class="initial-message">Please select a laboratory to view available PCs</div>';
                
                // Switch back to the current students tab and reload
                document.querySelector('.filter-tab[data-target="current-students"]').click();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the student: ' + error.message);
        });
    }

    // Add search button click handler
    document.getElementById('searchStudentBtn')?.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent form submission
        searchStudent();
    });
    
    // Add enter key support for search
    document.getElementById('student_idno')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent form submission
            searchStudent();
        }
    });
    
    // Separate search functionality into its own function
    function searchStudent() {
        let idno = document.getElementById('student_idno').value.trim();
        const studentInfo = document.getElementById('studentInfo');
        
        if (idno.length < 5) {
            alert('Please enter at least 5 characters of the student ID');
            return;
        }
        
        // Show loading indicator
        document.getElementById('searchStudentBtn').innerHTML = '<i class="ri-loader-4-line"></i> Searching...';
        document.getElementById('searchStudentBtn').disabled = true;
        
        // Clear the previous search result
        studentInfo.style.display = 'none';
        
        // Search for student with this ID
        fetch('../controller/search_student.php?idno=' + idno)
            .then(response => {
                // Check if the response is ok (status in the range 200-299)
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Search response:', data); // Debug log
                if (data.success) {
                    // Display complete student information
                    const student = data.student;
                    document.getElementById('display_idno').value = student.idno || '';
                    document.getElementById('display_fullname').value = 
                        `${student.lastname || ''}, ${student.firstname || ''}`;
                    document.getElementById('display_course_year').value = 
                        `${student.course || 'Not specified'} - ${student.year_level_display || 'Not specified'}`;
                    document.getElementById('remainingSessions').textContent = 
                        student.remaining_sessions || '30';
                    
                    // Store the student ID for form submission
                    const hiddenIdField = document.createElement('input');
                    hiddenIdField.type = 'hidden';
                    hiddenIdField.name = 'student_id';
                    hiddenIdField.value = student.id;
                    
                    // Remove any existing hidden field before adding a new one
                    const existingField = document.querySelector('input[name="student_id"]');
                    if (existingField) existingField.remove();
                    document.getElementById('addSitInForm').appendChild(hiddenIdField);
                    
                    // Show the student info section
                    studentInfo.style.display = 'grid';
                    validateForm();
                } else {
                    studentInfo.style.display = 'none';
                    alert('Student not found. Please check the ID number.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                studentInfo.style.display = 'none';
                
                // Provide more helpful error message
                if (error.message && error.message.includes('Network response was not ok')) {
                    alert('Server error: ' + error.message);
                } else {
                    alert('Error searching for student. Please try again. Details: ' + error.message);
                }
            })
            .finally(() => {
                // Reset button state
                document.getElementById('searchStudentBtn').innerHTML = '<i class="ri-search-line"></i> Search';
                document.getElementById('searchStudentBtn').disabled = false;
            });
    }
    
    // Connect lab select to laboratory field
    document.getElementById('labSelectPc')?.addEventListener('change', function() {
        const labValue = this.value;
        if (labValue) {
            // Update the laboratory select field to match
            const laboratoryField = document.getElementById('laboratory');
            if (laboratoryField) {
                laboratoryField.value = labValue;
                loadPCAvailability(labValue);
            }
        } else {
            document.getElementById('pcSelector').innerHTML = `
                <div class="initial-message">
                    <i class="ri-computer-line"></i>
                    <div>Please select a laboratory to view available PCs</div>
                </div>
            `;
        }
    });
    </script>
</body>
</html>