<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db_connect.php';

// Fetch user details
$username = $_SESSION['username'];
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Store remaining sessions in session
$_SESSION['remaining_sessions'] = $user['remaining_sessions'] ?? 30;

// Fetch laboratory schedules - similar to admin's laboratories.php
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

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Schedules - Student View</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
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
        
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
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
            font-size: 0.9rem;
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
            font-size: 0.95rem;
        }
        
        .modern-table tbody tr {
            transition: all 0.2s ease;
        }
        
        .modern-table tbody tr:hover {
            background-color: #f8fafc;
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
        
        .text-center {
            text-align: center;
        }

        /* Enhanced search box */
        .search-box {
            display: flex;
            align-items: center;
            background-color: #f1f5f9;
            border-radius: 10px;
            padding: 8px 16px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            margin-left: auto;
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
        
        @media (max-width: 768px) {
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
            
            .search-box {
                width: 100%;
                margin-top: 12px;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        /* Notification Container */
        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            width: 350px;
            max-width: 90vw;
        }

        /* Rest of your existing styles */
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
        
        .notification-icon {
            position: relative;
            width: 24px;
            height: 24px;
            display: flex; /* Ensure proper alignment */
            align-items: center; /* Vertically align with logout icon */
            justify-content: center; /* Horizontally center the icon */
            margin-right: 15px; /* Add spacing similar to logout icon */
        }
        
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            width: 360px;
            max-width: 90vw;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            z-index: 50;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
        }
        
        .notification-dropdown.active {
            max-height: 500px;
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            transition: max-height 0.3s ease-out, opacity 0.2s ease-out, transform 0.2s ease-out;
        }
        
        .notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .notification-header h3 {
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
            margin: 0;
        }
        
        .notification-header button {
            background: none;
            border: none;
            color: #7556cc;
            font-size: 0.875rem;
            cursor: pointer;
            font-weight: 500;
        }
        
        .notification-list {
            max-height: 350px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(117, 86, 204, 0.5) transparent;
        }
        
        .notification-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .notification-list::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .notification-list::-webkit-scrollbar-thumb {
            background-color: rgba(117, 86, 204, 0.5);
            border-radius: 10px;
        }
        
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: flex-start;
        }
        
        .notification-item:hover {
            background-color: #f8fafc;
        }
        
        .notification-item.unread {
            background-color: #f0f9ff;
        }
        
        .notification-item.unread:hover {
            background-color: #e0f2fe;
        }
        
        .notification-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #7556cc;
            margin-top: 6px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .notification-item.unread .notification-indicator {
            background-color: #3b82f6;
        }
        
        .notification-content {
            flex-grow: 1;
        }
        
        .notification-content h4 {
            font-weight: 600;
            font-size: 0.9rem;
            margin: 0 0 4px 0;
            color: #334155;
        }
        
        .notification-content p {
            font-size: 0.8rem;
            margin: 0 0 6px 0;
            color: #64748b;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        .notification-empty {
            padding: 24px 16px;
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            animation: fadeInUp 0.6s ease-out 0.2s backwards;
        }
    </style>
</head>
<body>
    <!-- Include notification system -->
    <?php include '../includes/notification.php'; ?>
    
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
                <a href="dashboard.php" class="nav-link">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="student_leaderboard.php" class="nav-link">
                    <i class="ri-trophy-line"></i>
                    <span>Leaderboard</span>
                </a>
                <a href="student_laboratories.php" class="nav-link active">
                <i class="ri-computer-line active"></i>
                <span>Laboratory</span>
                </a>
                <a href="reservation.php" class="nav-link">
                    <i class="ri-calendar-line"></i>
                    <span>Reservation</span>
                </a>
                <a href="history.php" class="nav-link">
                    <i class="ri-history-line"></i>
                    <span>History</span>
                </a>
                <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === '../profile/profile.php' ? 'active' : ''; ?>">
                    <i class="ri-user-3-line"></i>
                    <span>Profile</span>
                </a>
            </nav>

            <!-- Right side - Actions -->
            <div class="nav-actions">
                <!-- Notification Icon with Badge -->
                <div class="notification-icon">
                    <a href="#" class="action-link" id="notification-toggle">
                        <i class="fas fa-bell"></i>
                    </a>
                    <span class="notification-badge" id="notification-badge">0</span>
                    
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

    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="ri-computer-line"></i>
                <span>Laboratory Schedules</span>
            </div>
        </div>
        
        <div class="table-wrapper">
            <div class="table-header">
                <h2><i class="ri-building-4-line"></i> Laboratory Schedules</h2>
                <div class="search-box">
                    <i class="ri-search-line"></i>
                    <input type="text" id="searchInput" placeholder="Search schedules...">
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab <?php echo ($selected_lab == 'Laboratory 517') ? 'active' : ''; ?>" data-target="lab-517">Laboratory 517</div>
                <div class="filter-tab <?php echo ($selected_lab == 'Laboratory 524') ? 'active' : ''; ?>" data-target="lab-524">Laboratory 524</div>
                <div class="filter-tab <?php echo ($selected_lab == 'Laboratory 526') ? 'active' : ''; ?>" data-target="lab-526">Laboratory 526</div>
                <div class="filter-tab <?php echo ($selected_lab == 'Laboratory 528') ? 'active' : ''; ?>" data-target="lab-528">Laboratory 528</div>
                <div class="filter-tab <?php echo ($selected_lab == 'Laboratory 530') ? 'active' : ''; ?>" data-target="lab-530">Laboratory 530</div>
                <div class="filter-tab <?php echo ($selected_lab == 'Laboratory 542') ? 'active' : ''; ?>" data-target="lab-542">Laboratory 542</div>
            </div>

            <!-- Day Selection -->
            <div class="day-selection">
                <div class="day-buttons">
                    <button class="day-btn <?php echo ($selected_day == 'Monday') ? 'active' : ''; ?>" data-day="Monday">Monday</button>
                    <button class="day-btn <?php echo ($selected_day == 'Tuesday') ? 'active' : ''; ?>" data-day="Tuesday">Tuesday</button>
                    <button class="day-btn <?php echo ($selected_day == 'Wednesday') ? 'active' : ''; ?>" data-day="Wednesday">Wednesday</button>
                    <button class="day-btn <?php echo ($selected_day == 'Thursday') ? 'active' : ''; ?>" data-day="Thursday">Thursday</button>
                    <button class="day-btn <?php echo ($selected_day == 'Friday') ? 'active' : ''; ?>" data-day="Friday">Friday</button>
                    <button class="day-btn <?php echo ($selected_day == 'Saturday') ? 'active' : ''; ?>" data-day="Saturday">Saturday</button>
                </div>
            </div>

            <!-- Schedule Table -->
            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th class="w-1/4">Time</th>
                            <th class="w-1/4">Subject</th>
                            <th class="w-1/2">Professor</th>
                        </tr>
                    </thead>
                    <tbody id="schedule-table-body">
                        <?php if (empty($lab_schedules)): ?>
                        <tr>
                            <td colspan="3" class="empty-state">
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
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add notification container -->
    <div class="notification-container" id="notification-container"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter tab click handler
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    // Update active class
                    document.querySelectorAll('.filter-tab').forEach(t => {
                        t.classList.remove('active');
                    });
                    
                    this.classList.add('active');
                    
                    // Get lab from data-target
                    const labTarget = this.getAttribute('data-target');
                    const lab = 'Laboratory ' + labTarget.replace('lab-', '');
                    
                    // Get current day
                    const activeDay = document.querySelector('.day-btn.active').getAttribute('data-day');
                    
                    // Direct redirect without delay
                    window.location.href = `student_laboratories.php?lab=${lab}&day=${activeDay}`;
                });
            });
            
            // Day button click handler
            document.querySelectorAll('.day-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Update active class
                    document.querySelectorAll('.day-btn').forEach(b => {
                        b.classList.remove('active');
                    });
                    
                    this.classList.add('active');
                    
                    // Get day from data attribute
                    const day = this.getAttribute('data-day');
                    
                    // Get current lab
                    const labTarget = document.querySelector('.filter-tab.active').getAttribute('data-target');
                    const lab = 'Laboratory ' + labTarget.replace('lab-', '');
                    
                    // Direct redirect without delay
                    window.location.href = `student_laboratories.php?lab=${lab}&day=${day}`;
                });
            });
            
            // Search functionality
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
                    const emptyRow = document.querySelector('.empty-state');
                    if (emptyRow) {
                        if (!hasResults && rows.length > 0) {
                            emptyRow.style.display = 'table-cell';
                            const emptyStateContent = emptyRow.querySelector('.empty-state-content p');
                            if (emptyStateContent) {
                                emptyStateContent.textContent = `No schedules found matching "${searchText}"`;
                            }
                        } else if (rows.length > 0) {
                            emptyRow.style.display = 'none';
                        }
                    }
                });
            }
            
            // Your existing notification code
            // ...existing code...
        });
    </script>
</body>
</html>
