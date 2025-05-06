<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db_connect.php';

// Get user details for the navigation
$username = $_SESSION['username'];
$sql = "SELECT idno, firstname, lastname, middlename, course, year, profile_image, remaining_sessions FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Store in session for easy access
    $_SESSION['idno'] = $row['idno'];
    $_SESSION['profile_image'] = $row['profile_image'] ?? '../assets/images/logo/AVATAR.png';
    $_SESSION['remaining_sessions'] = $row['remaining_sessions'] ?? 30;
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

// Get announcements for sidebar
$announcements = [];
$query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Schedules</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/student_nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="../assets/javascript/nav.js" defer></script>
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

        .search-box {
            display: flex;
            align-items: center;
            background-color: #f1f5f9;
            border-radius: 10px;
            padding: 8px 16px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .search-box:focus-within {
            background-color: white;
            box-shadow: 0 0 0 2px rgba(117, 86, 204, 0.2);
            border-color: rgba(117, 86, 204, 0.3);
        }
        
        .search-box i {
            color: #64748b;
            margin-right: 12px;
            font-size: 1.1rem;
        }
        
        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            color: #334155;
            width: 200px;
            font-size: 0.95rem;
        }
        
        .search-box input::placeholder {
            color: #94a3b8;
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
            background-color: rgba(117, 86, 204, 0.05);
        }
        
        /* Day Selection */
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
        
        /* Time badge */
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
        
        .status-badge.open {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border: 1px solid rgba(22, 101, 52, 0.1);
        }
        
        .status-badge.occupied {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #b91c1c;
            border: 1px solid rgba(185, 28, 28, 0.1);
        }
        
        /* Notification system */
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
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            margin-bottom: 10px;
            transform: translateX(120%);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
            border-left: 4px solid #7556cc;
            overflow: hidden;
            position: relative;
        }
        
        .notification::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #7556cc, #9556cc);
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification.info {
            border-left-color: #3b82f6;
        }
        
        .notification.info::before {
            background: linear-gradient(to bottom, #3b82f6, #60a5fa);
        }
        
        .notification.success {
            border-left-color: #10b981;
        }
        
        .notification.success::before {
            background: linear-gradient(to bottom, #10b981, #34d399);
        }
        
        .notification.warning {
            border-left-color: #f59e0b;
        }
        
        .notification.warning::before {
            background: linear-gradient(to bottom, #f59e0b, #fbbf24);
        }
        
        .notification.error {
            border-left-color: #ef4444;
        }
        
        .notification.error::before {
            background: linear-gradient(to bottom, #ef4444, #f87171);
        }
        
        .notification-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            margin-top: 2px;
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
            font-weight: 700;
            margin-bottom: 4px;
            color: #1e293b;
        }
        
        .notification-message {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.5;
        }
        
        .notification-close {
            background: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #94a3b8;
            margin-left: 16px;
            padding: 0;
            line-height: 1;
            transition: all 0.2s ease;
            height: 24px;
            width: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .notification-close:hover {
            color: #ef4444;
            background-color: #fee2e2;
            transform: rotate(90deg);
        }
        
        /* Navigation Styles from Dashboard */
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
        
        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.2s ease;
            opacity: 0;
            transform: scale(0);
        }
        
        .notification-badge.active {
            opacity: 1;
            transform: scale(1);
        }
        
        .notification-icon {
            position: relative;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        /* Sessions count styling from dashboard */
        .sessions-count {
            font-weight: 600;
            color: #7556cc;
        }
        
        .sessions-count.low {
            color: #dc2626;
        }
        
        .sessions-count.medium {
            color: #ea580c;
        }
        
        /* Responsive design */
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
            
            .search-box {
                width: 100%;
            }
            
            .search-box input {
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
        }

        /* Container header styles to match laboratories.php */
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
    </style>
</head>
<body>
<div id="notification-container"></div>
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
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="ri-dashboard-line"></i>
                <span>Dashboard</span>
            </a>
            
            <div class="nav-dropdown">
                <div class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['student_laboratories.php', 'student_resources.php']) ? 'active' : ''; ?>">
                    <i class="ri-computer-line"></i>
                    <span>Laboratory</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="student_laboratories.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'student_laboratories.php' ? 'active' : ''; ?>">
                        <i class="ri-calendar-line"></i>
                        <span>Schedules</span>
                    </a>
                    <a href="student-resources.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'student-resources.php' ? 'active' : ''; ?>">
                        <i class="ri-links-line"></i>
                        <span>Resources</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-dropdown">
                <div class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['sit-in.php', 'reservation.php', 'history.php']) ? 'active' : ''; ?>">
                    <i class="ri-map-pin-user-line"></i>
                    <span>Sit-In</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="reservation.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'reservation.php' ? 'active' : ''; ?>">
                        <i class="ri-calendar-check-line"></i>
                        <span>Reservations</span>
                    </a>
                    <a href="history.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'history.php' ? 'active' : ''; ?>">
                        <i class="ri-history-line"></i>
                        <span>History</span>
                    </a>
                </div>
            </div>
            
            <a href="student_leaderboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'student_leaderboard.php' ? 'active' : ''; ?>">
                <i class="ri-trophy-line"></i>
                <span>Leaderboard</span>
            </a>
            <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
            <i class="ri-user-3-line"></i>
                <span>Profile</span>
            </a>
        </nav>

        <!-- Right side - Actions -->
        <div class="nav-actions">
            <div class="notification-icon" id="notification-toggle">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notification-badge"></span>
                
                <!-- Notification Dropdown -->
                <div class="notification-dropdown" id="notification-dropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <button id="mark-all-read">Mark all as read</button>
                    </div>
                    <div class="notification-list" id="notification-list">
                        <!-- Notifications will be loaded here -->
                        <div class="notification-empty">
                            Loading notifications...
                        </div>
                    </div>
                </div>
            </div>
            <a href="../auth/logout.php" class="action-link">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</div> 

    <!-- Backdrop -->
    <div class="backdrop" id="backdrop"></div>

    <!-- Profile Panel (from dashboard.php) -->
    <div class="profile-panel" id="profile-panel">
        <div class="profile-content">
            <div class="profile-header">
                <h3>STUDENT INFORMATION</h3>
            </div>
            <div class="profile-body">
                <div class="profile-image-container">
                    <div class="profile-image">
                        <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : '../assets/images/logo/AVATAR.png'; ?>" 
                             alt="Profile Picture" 
                             id="profile-preview">
                    </div>
                    <div class="profile-name">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?></h3>
                        </div>  
                    </div>
                </div>

                <div class="student-info-grid">
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-profile-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Student ID</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['idno'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-timer-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Remaining Sessions</div>
                            <div class="detail-value sessions-count <?php 
                                $remaining = isset($_SESSION['remaining_sessions']) ? (int)$_SESSION['remaining_sessions'] : 30;
                                echo $remaining <= 5 ? 'low' : ($remaining <= 10 ? 'medium' : ''); 
                            ?>">
                                <?php echo $remaining; ?>
                            </div>
                        </div>
                    </div>
                    <div class="edit-controls">
                        <a href="profile.php" class="edit-btn">
                            <span>View Full Profile</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="ri-computer-line"></i>
                <span>Laboratory Schedules</span>
            </div>
        </div>
        
        <div class="table-wrapper">
            <div class="container-header">
                <div class="header-content">
                    <div class="header-left">
                        <h2><i class="ri-building-4-line"></i> Laboratory Schedules</h2>
                        <p>View laboratory schedules and availability for sit-in sessions</p>
                    </div>
                    <div class="header-right">
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
                <div class="filter-tab <?php echo $selected_lab == 'Laboratory 517' ? 'active' : ''; ?>" data-target="lab-517">Laboratory 517</div>
                <div class="filter-tab <?php echo $selected_lab == 'Laboratory 524' ? 'active' : ''; ?>" data-target="lab-524">Laboratory 524</div>
                <div class="filter-tab <?php echo $selected_lab == 'Laboratory 526' ? 'active' : ''; ?>" data-target="lab-526">Laboratory 526</div>
                <div class="filter-tab <?php echo $selected_lab == 'Laboratory 528' ? 'active' : ''; ?>" data-target="lab-528">Laboratory 528</div>
                <div class="filter-tab <?php echo $selected_lab == 'Laboratory 530' ? 'active' : ''; ?>" data-target="lab-530">Laboratory 530</div>
                <div class="filter-tab <?php echo $selected_lab == 'Laboratory 542' ? 'active' : ''; ?>" data-target="lab-542">Laboratory 542</div>
            </div>

            <!-- Day Selection -->
            <div class="day-selection">
                <div class="day-buttons">
                    <button class="day-btn <?php echo $selected_day == 'Monday' ? 'active' : ''; ?>" data-day="Monday">Monday</button>
                    <button class="day-btn <?php echo $selected_day == 'Tuesday' ? 'active' : ''; ?>" data-day="Tuesday">Tuesday</button>
                    <button class="day-btn <?php echo $selected_day == 'Wednesday' ? 'active' : ''; ?>" data-day="Wednesday">Wednesday</button>
                    <button class="day-btn <?php echo $selected_day == 'Thursday' ? 'active' : ''; ?>" data-day="Thursday">Thursday</button>
                    <button class="day-btn <?php echo $selected_day == 'Friday' ? 'active' : ''; ?>" data-day="Friday">Friday</button>
                    <button class="day-btn <?php echo $selected_day == 'Saturday' ? 'active' : ''; ?>" data-day="Saturday">Saturday</button>
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
                            <th class="w-1/4">Status</th>
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
                                    <?php
                                    // Check if the current time is within the class schedule
                                    $now = new DateTime();
                                    $is_current = ($now >= $time_start && $now <= $time_end && date('l') == $selected_day);
                                    
                                    if ($is_current): 
                                    ?>
                                        <span class="status-badge occupied">
                                            <i class="ri-user-line mr-1"></i> Occupied
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge open">
                                            <i class="ri-checkbox-circle-line mr-1"></i> Available
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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

        // Document ready
        document.addEventListener('DOMContentLoaded', function() {
            // Setup for profile panel
            const profileTrigger = document.getElementById('profile-trigger');
            const profilePanel = document.getElementById('profile-panel');
            const backdrop = document.getElementById('backdrop');
            
            // Toggle profile panel
            profileTrigger.addEventListener('click', function() {
                profilePanel.classList.toggle('active');
                backdrop.classList.toggle('active');
            });
            
            // Close profile panel when clicking backdrop
            backdrop.addEventListener('click', function() {
                profilePanel.classList.remove('active');
                backdrop.classList.remove('active');
            });
            
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
                        window.location.href = `student_laboratories.php?lab=${lab}&day=${activeDay}`;
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
                    const lab = '<?php echo $selected_lab; ?>';
                    
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
                        window.location.href = `student_laboratories.php?lab=${lab}&day=${day}`;
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
                    
                    // Show empty state if no results
                    let emptyRow = document.querySelector('.empty-state');
                    
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
                        emptyRow.style.display = 'none';
                    }
                });
            }
            
            // Notification badge check
            function checkNotifications() {
                fetch('../notifications/count_unread.php')
                    .then(response => response.json())
                    .then(data => {
                        const badge = document.getElementById('notification-badge');
                        if (data.count > 0) {
                            badge.textContent = data.count > 99 ? '99+' : data.count;
                            badge.classList.add('active');
                        } else {
                            badge.classList.remove('active');
                        }
                    })
                    .catch(error => console.error('Error checking notifications:', error));
            }
            
            // Check notifications on load
            checkNotifications();
            
            // Check notifications periodically
            setInterval(checkNotifications, 30000);
        });
    </script>
</body>
</html>
