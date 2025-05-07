<?php
session_start();

// Add session flag check for welcome notification
$show_welcome_notification = false;
if (!isset($_SESSION['dashboard_welcome_shown'])) {
    $show_welcome_notification = true;
    $_SESSION['dashboard_welcome_shown'] = true;
}

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get user details from database
require_once '../config/db_connect.php';
$username = $_SESSION['username'];
// Update the SQL query to include remaining_sessions
$sql = "SELECT idno, firstname, lastname, middlename, course, year, profile_image, email, address, remaining_sessions, points FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Format the full name
    $fullname = $row['lastname'] . ', ' . $row['firstname'];
    if (!empty($row['middlename'])) {
        $fullname .= ' ' . substr($row['middlename'], 0, 1) . '.';
    }
    
    // Store in session for easy access
    $_SESSION['idno'] = $row['idno'];
    $_SESSION['fullname'] = $fullname;
    $_SESSION['course'] = $row['course'];
    
    // Fix the year level formatting
    $year = intval($row['year']); // Ensure year is an integer
    $_SESSION['year'] = $year;
    $_SESSION['year_level'] = $year . (
        $year == 1 ? 'st' : 
        ($year == 2 ? 'nd' : 
        ($year == 3 ? 'rd' : 'th'))
    ) . ' Year';
    
    $_SESSION['profile_image'] = $row['profile_image'] ?? '../assets/images/logo/AVATAR.png';
    $_SESSION['email'] = $row['email'];
    $_SESSION['address'] = $row['address'];
    
    // Store remaining sessions in session
    $_SESSION['remaining_sessions'] = $row['remaining_sessions'] ?? 30;
    
    // Store points in session
    $_SESSION['points'] = $row['points'] ?? 0;
}

// Get student statistics for dashboard
$stats = [
    'total_sitins' => 0,
    'remaining_sessions' => $_SESSION['remaining_sessions'],
    'pending_reservations' => 0
];

// Get total completed sit-ins for this student
$id = $_SESSION['idno'];
$query = "SELECT COUNT(*) as count FROM sit_ins WHERE idno = ? AND status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_sitins'] = $row['count'];
}

// Get count of pending reservations
$query = "SELECT COUNT(*) as count FROM reservations WHERE idno = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $stats['pending_reservations'] = $row['count'];
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

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/student_nav.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="../assets/javascript/nav.js" defer></script>
    <script src="../assets/javascript/notification.js"></script>
    <script src="../assets/javascript/student_notifications.js"></script>

    <style>
        /* Add styles for sessions count */
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
        
        
        
        /* Add animations for new dashboard elements */
        .student-dashboard {
            display: none; /* Hide old container */
        }
        
        .admin-dashboard {
            opacity: 0;
            transform: translateY(20px);
            animation: slideUpFade 0.8s ease-out 0.2s forwards;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .admin-dashboard .stat-card {
            opacity: 0;
            transform: translateY(15px);
            animation: cardFadeIn 0.5s ease-out forwards;
        }
        
        .admin-dashboard .stat-card:nth-child(1) {
            animation-delay: 0.3s;
        }
        
        .admin-dashboard .stat-card:nth-child(2) {
            animation-delay: 0.45s;
        }
        
        .admin-dashboard .stat-card:nth-child(3) {
            animation-delay: 0.6s;
        }
        
        @keyframes cardFadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
        }
        
        .announcements-grid {
            opacity: 0;
            animation: fadeUp 0.7s ease-out 0.7s forwards;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
        }
        
        @media (max-width: 992px) {
            .announcements-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .announcements-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            height: auto;
            display: flex;
            flex-direction: column;
        }
        
        .announcements-header {
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem 1.5rem;
            border-bottom: 1px solid #eee;
            flex-shrink: 0;
        }
        
        /* Modern announcement list styles */
        .announcement-list {
            max-height: 400px !important; /* Increased from 300px */
            height: 400px !important; /* Increased from 300px */
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(117, 86, 204, 0.5) transparent;
            padding: 1rem 1.5rem;
            flex-grow: 1;
        }
        
        .announcement-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .announcement-list::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .announcement-list::-webkit-scrollbar-thumb {
            background-color: rgba(117, 86, 204, 0.5);
            border-radius: 10px;
        }
        
        /* Announcement Items */
        .announcement-list .announcement-item {
            display: flex;
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 0;
            border: none;
            transition: all 0.3s ease;
            overflow: hidden;
            border-left: 4px solid #7556cc;
        }
        
        .announcement-list .announcement-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        
        .announcement-date {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 70px;
            padding: 15px 0;
            background: rgba(117, 86, 204, 0.08);
            border-right: 1px solid rgba(117, 86, 204, 0.15);
        }
        
        .date-day {
            font-size: 1.8rem;
            font-weight: 700;
            color: #7556cc;
            line-height: 1;
        }
        
        .date-month {
            font-size: 0.9rem;
            color: #7556cc;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .announcement-content {
            flex: 1;
            padding: 15px 20px;
        }
        
        .announcement-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 8px 0;
        }
        
        .announcement-content p {
            font-size: 0.95rem;
            color: #475569;
            margin: 0 0 12px 0;
            line-height: 1.5;
        }
        
        .announcement-meta {
            display: flex;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        
        .announcement-meta span {
            display: flex;
            align-items: center;
        }
        
        .announcement-meta i {
            margin-right: 4px;
            font-size: 0.9rem;
        }
        
        /* Empty announcement state */
        .announcement-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            color: #94a3b8;
            text-align: center;
            width: 100%;
        }
        
        .announcement-empty i {
            font-size: 2.5rem;
            color: #cbd5e1;
            margin-bottom: 15px;
        }
        
        .announcement-empty p {
            font-size: 1rem;
            color: #64748b;
            margin: 0;
        }
        
        .rules-container {
            height: 400px !important; /* Reduced to match announcement-list */
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(117, 86, 204, 0.5) transparent;
            padding: 0 1.5rem 1.5rem;
        }
        
        .rules-header {
            padding-top: 1rem;
            text-align: center;
            margin-bottom: 1.25rem;
        }
        
        .rules-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #1e293b;
        }
        
        .rules-header h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #475569;
        }
        
        .rules-intro {
            margin-bottom: 1rem;
            line-height: 1.5;
            color: #475569;
        }
        
        .rules-list {
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .rules-list li {
            margin-bottom: 0.75rem;
            color: #334155;
            line-height: 1.5;
        }
        
        .rules-list ul {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
        }
        
        .rules-list ul li {
            margin-bottom: 0.35rem;
            color: #475569;
        }
        
        .disciplinary-section {
            background-color: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
            border-left: 4px solid #7556cc;
            margin-top: 1.5rem;
        }
        
        .disciplinary-section h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #1e293b;
        }
        
        .disciplinary-section ul {
            padding-left: 1.25rem;
            margin-bottom: 0;
        }
        
        .disciplinary-section li {
            margin-bottom: 0.5rem;
            color: #475569;
            line-height: 1.5;
        }
        
        /* System Selection Section */
        .system-selection {
            max-width: 1400px;
            margin: 0 auto 3rem;
            padding: 0 1.5rem;
        }
        
        .selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .selection-card {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }
        
        .selection-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            transform: translateX(5px);
            transition: transform 0.3s ease;
        }
        
        .selection-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        
        .selection-card:hover::after {
            transform: translateX(0);
        }
        
        .selection-icon {
            width: 50px;
            height: 50px;
            min-width: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(117, 86, 204, 0.1);
            border-radius: 12px;
            margin-right: 1rem;
            color: #7556cc;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .selection-card:hover .selection-icon {
            background: #7556cc;
            color: white;
        }
        
        .selection-details h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 5px 0;
            transition: color 0.3s ease;
        }
        
        .selection-details p {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
            line-height: 1.4;
        }
        
        .selection-card:hover .selection-details h3 {
            color: #7556cc;
        }

        .dashboard-header {
            margin-top: 5rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            text-align: center;  /* Added */
        }
        
        @media (max-width: 767px) {
            .selection-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Points value styling */
        .points-value {
            font-weight: 600;
            color: #7556cc;
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

    <!-- Profile Panel -->
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
                            <h3><?php echo htmlspecialchars($_SESSION['fullname']); ?></h3>
                        </div>  
                    </div>
                </div>

                <div class="student-info-grid">
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-profile-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Student ID</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['idno']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-user-3-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Full Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-graduation-cap-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Course</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['course']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-expand-up-down-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Year Level</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['year_level']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-mail-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['email']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-home-9-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Address</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['address']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-star-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Points</div>
                            <div class="detail-value points-value"><?php echo htmlspecialchars($_SESSION['points'] ?? 0); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-timer-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Session</div>
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
                            <span>Edit Profile</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Dashboard Header and Stats -->
    <div class="admin-dashboard">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                Student Dashboard
                
            </div>
        </div>
        
        <!-- Stats Grid - Modernized -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Sit-ins</div>
                <div class="stat-value"><?php echo $stats['total_sitins']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Remaining Sessions</div>
                <div class="stat-value <?php echo $stats['remaining_sessions'] <= 5 ? 'low' : ($stats['remaining_sessions'] <= 10 ? 'medium' : ''); ?>">
                    <?php echo $stats['remaining_sessions']; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Points</div>
                <div class="stat-value points-display"><?php echo $_SESSION['points'] ?? 0; ?></div>
            </div>
        </div>

        
        <div class="analytics-summary">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="ri-time-line"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-title">Average Session Duration</div>
                    <div class="summary-value">1.5 hours</div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="ri-calendar-check-line"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-title">Most Active Day</div>
                    <div class="summary-value">Wednesday</div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="ri-award-line"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-title">Sit-in Streak</div>
                    <div class="summary-value">3 days</div>
                </div>
            </div>
        </div>


        <!-- Announcements and Rules Grid -->
        <div class="announcements-grid">
            <!-- Left Column - Announcements -->
            <div class="announcements-card">
                <div class="announcements-header">
                    <i class="ri-notification-3-line"></i>
                    <span>CCS Announcements</span>
                </div>
                <div class="announcement-list">
                    <?php if (empty($announcements)): ?>
                        <div class="announcement-item">
                            <div class="announcement-empty">
                                <i class="ri-information-line"></i>
                                <p>No announcements available at this time.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item">
                                <div class="announcement-date">
                                    <div class="date-day"><?php echo date('d', strtotime($announcement['created_at'])); ?></div>
                                    <div class="date-month"><?php echo date('M', strtotime($announcement['created_at'])); ?></div>
                                </div>
                                <div class="announcement-content">
                                    <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Rules -->
            <div class="announcements-card">
                <div class="announcements-header">
                    <i class="ri-file-list-3-line"></i>
                    <span>Laboratory Rules and Regulations</span>
                </div>
                <div class="rules-container">
                    <div class="rules-header">
                        <h3>UNIVERSITY OF CEBU</h3>
                        <h4>COLLEGE OF INFORMATION & COMPUTER STUDIES</h4>
                    </div>

                    <div class="rules-content">
                        <p class="rules-intro">To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                        
                        <ol class="rules-list">
                            <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.</li>
                            <li>Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.</li>
                            <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</li>
                            <li>Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</li>
                            <li>Deleting computer files and changing the set-up of the computer is a major offense.</li>
                            <li>Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".</li>
                            <li>Observe proper decorum while inside the laboratory.
                                <ul>
                                    <li>Do not get inside the lab unless the instructor is present.</li>
                                    <li>All bags, knapsacks, and the likes must be deposited at the counter.</li>
                                    <li>Follow the seating arrangement of your instructor.</li>
                                    <li>At the end of class, all software programs must be closed.</li>
                                    <li>Return all chairs to their proper places after using.</li>
                                </ul>
                            </li>
                            <li>Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.</li>
                            <li>Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the members of the community, including public display of physical intimacy, are not tolerated.</li>
                            <li>Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.</li>
                            <li>For serious offense, the lab personnel may call the Civil Security Office (CSU) for assistance.</li>
                            <li>Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant or instructor immediately.</li>
                        </ol>

                        <div class="disciplinary-section">
                            <h4>DISCIPLINARY ACTION</h4>
                            <ul>
                                <li><strong>First Offense</strong> - The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.</li>
                                <li><strong>Second and Subsequent Offenses</strong> - A recommendation for a heavier sanction will be endorsed to the Guidance Center.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Selection Section -->
        <div class="dashboard-header">
                <div class="dashboard-title">
                    Quick Actions
                </div>
            </div>
        <div class="system-selection">
            <div class="selection-header">
            </div>
            <div class="selection-grid">
                <a href="reservation.php" class="selection-card">
                    <div class="selection-icon">
                        <i class="ri-calendar-check-line"></i>
                    </div>
                    <div class="selection-details">
                        <h3>Make Reservation</h3>
                        <p>Schedule a laboratory session</p>
                    </div>
                </a>
                <a href="history.php" class="selection-card">
                    <div class="selection-icon">
                        <i class="ri-history-line"></i>
                    </div>
                    <div class="selection-details">
                        <h3>View History</h3>
                        <p>See your past lab activities</p>
                    </div>
                </a>
                <a href="profile.php" class="selection-card">
                    <div class="selection-icon">
                        <i class="ri-user-settings-line"></i>
                    </div>
                    <div class="selection-details">
                        <h3>Profile Settings</h3>
                        <p>Update your account information</p>
                    </div>
                </a>
                <a href="#" class="selection-card" onclick="document.getElementById('notification-toggle').click(); return false;">
                    <div class="selection-icon">
                        <i class="ri-notification-4-line"></i>
                    </div>
                    <div class="selection-details">
                        <h3>Notifications</h3>
                        <p>Check your recent alerts</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Add Data Visualization Section after Quick Actions -->
    <div class="admin-dashboard">
        <div class="dashboard-header">
            <div class="dashboard-title">

            </div>
        </div>
    </div> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Add notification system functions at the start of scripts
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
        
        // Add hide class for out animation
        notification.classList.add('hide');
        notification.classList.remove('show');
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.parentElement.removeChild(notification);
            }
        }, 300); // Match animation duration
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize the Student Notifications system
        StudentNotifications.init({
            refreshInterval: 30000 // Check for new notifications every 30 seconds
        });
        
        // Show welcome notification only on fresh login
        <?php if ($show_welcome_notification): ?>
        showNotification(
            "Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!", 
            "You're now logged in to CCS Sit-In System.",
            "success"
        );
        <?php endif; ?>

        // Rest of your script
        // ...existing code...
    });
    
    // Profile panel functionality
    const profilePanel = document.getElementById('profile-panel');
    const backdrop = document.getElementById('backdrop');
    const profileTrigger = document.getElementById('profile-trigger');

    function toggleProfile(show) {
        profilePanel.classList.toggle('active', show);
        backdrop.classList.toggle('active', show);
        document.body.style.overflow = show ? 'hidden' : '';
    }

    profileTrigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleProfile(true);
    });

    // Close profile panel when clicking outside
    document.addEventListener('click', (e) => {
        if (profilePanel.classList.contains('active') && 
            !profilePanel.contains(e.target) && 
            !profileTrigger.contains(e.target)) {
            toggleProfile(false);
        }
    });

    // Prevent clicks inside panel from closing it
    profilePanel.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    // Close on backdrop click
    backdrop.addEventListener('click', () => toggleProfile(false));

    // Profile data update function
    async function updateProfilePanel() {
        try {
            const response = await fetch('../profile/get_profile_data.php');
            const data = await response.json();
            
            // Update profile image
            const profileImages = document.querySelectorAll('.profile-image img');
            profileImages.forEach(img => {
                img.src = data.profile_image + '?t=' + new Date().getTime();
            });

            // Update info
            document.querySelector('.user-info h3').textContent = data.fullname;
            
            // Update info cards
            const detailValues = document.querySelectorAll('.info-card .detail-value');
            detailValues[0].textContent = data.idno;
            detailValues[1].textContent = data.fullname;
            detailValues[2].textContent = data.course;
            detailValues[3].textContent = data.year_level;
            
            // Update points if available
            if (data.points !== undefined) {
                const pointsElement = document.querySelector('.points-value');
                if (pointsElement) {
                    pointsElement.textContent = data.points;
                }
            }
        } catch (error) {
            console.error('Error updating profile:', error);
        }
    }

    // Update profile when panel is opened
    profileTrigger.addEventListener('click', updateProfilePanel);

    // Chart initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize charts if they exist
        initializeCharts();
    });
    
    function initializeCharts() {
        // Sit-in History Chart
        const sitInCtx = document.getElementById('sitInChart');
        if (sitInCtx) {
            new Chart(sitInCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Sit-ins Completed',
                        data: [4, 7, 5, 8, 6, <?php echo $stats['total_sitins']; ?>],
                        borderColor: '#7556cc',
                        backgroundColor: 'rgba(117, 86, 204, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Monthly Sit-in Activity'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        // Session Usage Chart
        const sessionCtx = document.getElementById('sessionUsageChart');
        if (sessionCtx) {
            new Chart(sessionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Used', 'Remaining'],
                    datasets: [{
                        data: [30 - <?php echo $_SESSION['remaining_sessions']; ?>, <?php echo $_SESSION['remaining_sessions']; ?>],
                        backgroundColor: [
                            'rgba(117, 86, 204, 0.8)',
                            'rgba(117, 86, 204, 0.2)'
                        ],
                        borderColor: [
                            'rgba(117, 86, 204, 1)',
                            'rgba(117, 86, 204, 0.3)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Session Allocation'
                        }
                    }
                }
            });
        }
        
        // Points Chart
        const pointsCtx = document.getElementById('pointsChart');
        if (pointsCtx) {
            new Chart(pointsCtx, {
                type: 'bar',
                data: {
                    labels: ['Sit-ins', 'Bonuses', 'Other'],
                    datasets: [{
                        label: 'Points Source',
                        data: [<?php echo min($stats['total_sitins'], $_SESSION['points']); ?>, 
                               <?php echo max(0, $_SESSION['points'] - $stats['total_sitins'] - 2); ?>, 
                               2],
                        backgroundColor: [
                            'rgba(117, 86, 204, 0.7)',
                            'rgba(45, 206, 137, 0.7)',
                            'rgba(66, 153, 225, 0.7)'
                        ],
                        borderColor: [
                            'rgba(117, 86, 204, 1)',
                            'rgba(45, 206, 137, 1)',
                            'rgba(66, 153, 225, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        title: {
                            display: true,
                            text: 'Points Distribution'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    }
    </script>
    
    <style>
        /* Points display styling */
        .points-display {
            font-weight: 700;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Analytics section styling */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
        }
        
        .wide-card {
            grid-column: span 2;
        }
        
        .analytics-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            height: auto;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }
        
        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        
        .analytics-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: #7556cc;
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            gap: 0.5rem;
        }
        
        .analytics-body {
            padding: 1.5rem;
            flex-grow: 1;
            position: relative;
            height: 300px;
        }
        
        .analytics-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        
        .summary-icon {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 12px;
            background: rgba(117, 86, 204, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7556cc;
            font-size: 1.5rem;
        }
        
        .summary-content {
            flex-grow: 1;
        }
        
        .summary-title {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .summary-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        @media (max-width: 992px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .wide-card {
                grid-column: span 1;
            }
            
            .analytics-body {
                height: 250px;
            }
        }
    </style>
</body>
</html>