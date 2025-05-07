<?php
session_start();

// Check if user is logged in as a student
if (!isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db_connect.php';

// Get top 3 most active students based on sit-in count
$top_students = [];
$query = "SELECT u.idno, u.firstname, u.lastname, u.profile_image, u.course, u.year,
          u.points, u.remaining_sessions,
          COUNT(DISTINCT CASE WHEN s.id IS NOT NULL THEN s.id END) +
          COUNT(DISTINCT CASE WHEN r.id IS NOT NULL THEN r.id END) as total_sitins
          FROM users u
          LEFT JOIN sit_ins s ON u.idno = s.idno
          LEFT JOIN reservations r ON u.idno = r.idno
          GROUP BY u.idno
          ORDER BY total_sitins DESC, u.points DESC
          LIMIT 3";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $top_students[] = $row;
    }
}

// Get all students with their sit-in counts and points
$all_students = [];
$query = "SELECT u.idno, u.firstname, u.lastname, u.course, u.year, u.points, u.remaining_sessions,
          COUNT(DISTINCT CASE WHEN s.id IS NOT NULL THEN s.id END) +
          COUNT(DISTINCT CASE WHEN r.id IS NOT NULL THEN r.id END) as total_sitins
          FROM users u
          LEFT JOIN sit_ins s ON u.idno = s.idno
          LEFT JOIN reservations r ON u.idno = r.idno
          GROUP BY u.idno
          ORDER BY total_sitins DESC, u.points DESC";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_students[] = $row;
    }
}

// Format year level for display
function formatYearLevel($year) {
    $year = intval($year);
    return $year . (
        $year == 1 ? 'st' : 
        ($year == 2 ? 'nd' : 
        ($year == 3 ? 'rd' : 'th'))
    ) . ' Year';
}

// Find the current student's rank
$current_student_idno = $_SESSION['idno'];
$current_student_rank = 0;
foreach ($all_students as $rank => $student) {
    if ($student['idno'] == $current_student_idno) {
        $current_student_rank = $rank + 1;
        break;
    }
}

// Helper function to get ordinal suffix
function ordinalSuffix($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
        return 'th';
    } else {
        return $ends[$number % 10];
    }
}

// Get current student's data
$current_student = null;
foreach ($all_students as $student) {
    if ($student['idno'] == $current_student_idno) {
        $current_student = $student;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - CCS Sit-In System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/board.css">
    <link rel="stylesheet" href="../assets/css/student_nav.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="../assets/javascript/nav.js" defer></script>
    <script src="../assets/javascript/notification.js" defer></script>
    <script src="../assets/javascript/student_notifications.js" defer></script>
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
            padding: 1.5rem;
            max-width: 1400px;
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
        
        .my-ranking {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
            text-align: center;
            animation: fadeUp 0.7s ease-out 0.3s forwards;
            opacity: 0;
        }
        
        .my-ranking-title {
            font-size: 1.1rem;
            color: #4b5563;
            margin-bottom: 0.75rem;
        }
        
        .my-ranking-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #7556cc;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .my-stats {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .my-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }
        
        .my-stat-label {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .my-stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        /* My Points view styling */
        .my-points-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
            text-align: center;
            animation: fadeUp 0.7s ease-out 0.3s forwards;
            opacity: 0;
        }
        
        .points-value {
            font-size: 5rem;
            font-weight: 700;
            color: #7556cc;
            margin: 1.5rem 0;
            text-shadow: 0 2px 10px rgba(117, 86, 204, 0.2);
        }
        
        .points-info {
            max-width: 600px;
            margin: 0 auto 2rem auto;
            color: #475569;
            line-height: 1.6;
        }
        
        .points-card {
            background: rgba(117, 86, 204, 0.03);
            border: 1px solid rgba(117, 86, 204, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .points-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .points-card h3 {
            color: #7556cc;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .points-card ul {
            text-align: left;
            color: #4b5563;
            padding-left: 1.5rem;
        }
        
        .points-card li {
            margin-bottom: 0.75rem;
        }
        
        .sessions-info {
            background: rgba(45, 206, 137, 0.05);
            border: 1px solid rgba(45, 206, 137, 0.1);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .sessions-stat {
            text-align: center;
        }
        
        .sessions-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2dce89;
            margin-bottom: 0.5rem;
        }
        
        .sessions-label {
            font-size: 0.9rem;
            color: #4b5563;
            font-weight: 500;
        }
        
        .sessions-warning {
            text-align: center;
            color: #ef4444;
            font-size: 0.9rem;
            margin-top: 1rem;
            padding: 0.75rem;
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.1);
            border-radius: 8px;
            display: inline-block;
        }

        /* My Points cards styling improvements */
        .points-info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
            text-align: center;
            animation: fadeUp 0.7s ease-out 0.3s forwards;
            opacity: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .points-info-card h2 {
            font-size: 1.3rem;
            color: #7556cc;
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .points-info-card .points-value {
            font-size: 4rem;
            font-weight: 700;
            color: #7556cc;
            margin: 1rem 0;
            text-shadow: 0 2px 10px rgba(117, 86, 204, 0.2);
        }

        .points-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            width: 100%;
            margin-top: 1rem;
        }

        .points-info-card h3 {
            color: #7556cc;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .points-info-card ul {
            text-align: left;
            color: #4b5563;
            padding-left: 1.5rem;
        }

        .points-info-card li {
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }

        /* Updated My Points view styling */
        .points-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.75rem;
            margin-bottom: 2rem;
        }
        
        .points-summary-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            text-align: center;
            animation: fadeUp 0.7s ease-out 0.3s forwards;
            opacity: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .points-summary-card h2 {
            font-size: 1.3rem;
            color: #7556cc;
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .points-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(117, 86, 204, 0.1) 0%, rgba(149, 86, 204, 0.2) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem auto 1.5rem;
            position: relative;
            border: 4px solid rgba(117, 86, 204, 0.2);
        }
        
        .points-circle::before {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            border-radius: 50%;
            border: 2px solid rgba(117, 86, 204, 0.1);
            animation: pulseCircle 2s infinite;
        }
        
        @keyframes pulseCircle {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.3;
            }
            100% {
                transform: scale(1);
                opacity: 0.8;
            }
        }
        
        .points-count {
            font-size: 3.5rem;
            font-weight: 700;
            color: #7556cc;
            line-height: 1;
            text-shadow: 0 2px 10px rgba(117, 86, 204, 0.2);
        }
        
        .points-label {
            font-size: 1rem;
            color: #64748b;
            margin-top: 0.5rem;
            font-weight: 500;
        }
        
        .points-description {
            color: #64748b;
            font-size: 1.05rem;
            margin-top: 1.75rem;
            line-height: 1.6;
            max-width: 80%;
        }
        
        /* Points earning and usage sections */
        .earn-points-section {
            animation: fadeUp 0.7s ease-out 0.4s forwards;
            opacity: 0;
        }
        
        .use-points-section {
            animation: fadeUp 0.7s ease-out 0.5s forwards;
            opacity: 0;
        }
        
        .earn-points-section h3,
        .use-points-section h3 {
            font-size: 1.35rem;
            color: #7556cc;
            margin-bottom: 1.75rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .earn-points-section h3::after,
        .use-points-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .earn-points-section:hover h3::after,
        .use-points-section:hover h3::after {
            width: 100px;
        }
        
        .earn-points-section ul,
        .use-points-section ul {
            margin: 0;
            padding-left: 1.5rem;
            color: #4b5563;
        }
        
        .earn-points-section li,
        .use-points-section li {
            margin-bottom: 1.15rem;
            line-height: 1.7;
            position: relative;
            padding-left: 0.5rem;
            font-size: 1.05rem;
        }
        
        .earn-points-section li::marker,
        .use-points-section li::marker {
            color: #7556cc;
            font-weight: bold;
        }
        
        /* Responsive improvements */
        @media (max-width: 1024px) {
            .points-dashboard {
                grid-template-columns: 1fr;
            }
            
            .points-summary-card {
                margin-bottom: 1rem;
            }
            
            .points-circle {
                width: 160px;
                height: 160px;
            }
            
            .points-count {
                font-size: 3.75rem;
            }
            
            .points-description {
                max-width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .points-summary-card,
            .earn-points-section,
            .use-points-section {
                padding: 1.75rem;
            }
            
            .points-circle {
                width: 140px;
                height: 140px;
                margin: 0.5rem auto 1.5rem;
            }
            
            .points-count {
                font-size: 3.25rem;
            }
            
            .points-description {
                font-size: 1rem;
                margin-top: 1.25rem;
            }
        }

        /* Fix for notification icon alignment in navbar */
        .nav-actions .notification-icon {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            height: 24px;
            width: 24px;
        }

        
        .nav-actions .notification-icon i {
            font-size: 18px;
            padding-bottom: 12px;

        }

        .notification-dropdown {
            position: absolute;
            top: 120%;
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
        
        .nav-actions .notification-badge {
            position: absolute;
            top: 0px;
            right: -5px;
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

    <div class="content-wrapper">
        <!-- Leaderboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="ri-trophy-line"></i>
                <span>Student Leaderboard</span>
            </div>  
        </div>

        <!-- Tabs Section (New) -->
        <div class="filter-tabs">
            <div class="filter-tab active" data-target="all-students">All Students</div>
            <div class="filter-tab" data-target="my-points">My Points</div>
        </div>
        
        <!-- View Sections Container -->
        <div id="view-sections-container">
            <!-- All Students View Section -->
            <div id="all-students" class="view-container active">
                <!-- Top 3 Students Section -->
                <div class="top-students-section view-section active">
                    <h2>Top Active Students</h2>
                    <div class="top-students-grid">
                        <?php 
                        $positions = ['second', 'first', 'third'];
                        $ranks = ['2', '1', '3'];
                        
                        // Ensure we have exactly 3 positions
                        while (count($top_students) < 3) {
                            $top_students[] = [
                                'idno' => 'N/A',
                                'firstname' => 'No',
                                'lastname' => 'Student',
                                'profile_image' => '../assets/images/logo/AVATAR.png',
                                'course' => 'N/A',
                                'year' => '1',
                                'total_sitins' => '0',
                                'points' => '0',
                                'remaining_sessions' => '0'
                            ];
                        }
                        
                        // Reorder for display (1st in middle, 2nd on left, 3rd on right)
                        $display_order = [$top_students[1], $top_students[0], $top_students[2]];
                        
                        foreach ($display_order as $index => $student): ?>
                            <div class="top-student <?php echo $positions[$index]; ?> <?php echo ($student['idno'] === $current_student_idno) ? 'current-user' : ''; ?>">
                                <div class="position-icon"><?php echo $ranks[$index]; ?></div>
                                <div class="student-avatar">
                                    <img src="<?php echo isset($student['profile_image']) && $student['profile_image'] 
                                        ? htmlspecialchars($student['profile_image']) 
                                        : '../assets/images/logo/AVATAR.png'; ?>" 
                                        alt="Student Profile" onerror="this.src='../assets/images/logo/AVATAR.png'">
                                </div>
                                <div class="student-name">
                                    <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>
                                    <?php if ($student['idno'] === $current_student_idno): ?>
                                    <small>(You)</small>
                                    <?php endif; ?>
                                </div>
                                <div class="student-id">
                                    <?php echo htmlspecialchars($student['idno']); ?>
                                </div>
                                <div class="student-course">
                                    <?php echo htmlspecialchars($student['course']); ?> | 
                                    <?php echo formatYearLevel($student['year']); ?>
                                </div>
                                <div class="student-stats">
                                    <div class="stat">
                                        <i class="ri-computer-line"></i>
                                        <span><?php echo $student['total_sitins']; ?> Sit-ins</span>
                                    </div>
                                    <div class="stat">
                                        <i class="ri-star-line"></i>
                                        <span><?php echo $student['points']; ?> Points</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- All Students Container -->   
                <div class="table-container">
                    <div class="container-header">
                        <div class="header-content">
                            <div class="header-left">
                                <h2><i class="ri-user-search-line"></i> All Students Leaderboard</h2>
                                <p>Ranking of all students based on total sit-in sessions and points</p>
                            </div>
                            <div class="header-right">
                                <div class="search-container">
                                    <input type="text" id="studentSearchAll" class="search-input" placeholder="Search students...">
                                    <span class="search-icon">
                                        <i class="ri-search-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th class="text-center">Rank</th>
                                <th>ID Number</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th class="text-center">Total Sit-ins</th>
                                <th class="text-center">Points</th>
                                <th class="text-center">Sessions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_students)): ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <div class="empty-state-content">
                                            <i class="ri-user-search-line"></i>
                                            <p>No student records found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_students as $rank => $student): ?>
                                    <tr <?php echo ($student['idno'] === $current_student_idno) ? 'class="highlight-row"' : ''; ?>>
                                        <td class="text-center"><?php echo $rank + 1; ?></td>
                                        <td class="font-mono"><?php echo htmlspecialchars($student['idno']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>
                                            <?php if ($student['idno'] === $current_student_idno): ?>
                                            <span class="current-user-badge">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                                        <td><?php echo formatYearLevel($student['year']); ?></td>
                                        <td class="text-center">
                                            <span class="sitin-badge"><?php echo $student['total_sitins']; ?> sit-ins</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="points-badge"><?php echo $student['points']; ?> points</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="sessions-badge <?php echo $student['remaining_sessions'] <= 5 ? 'sessions-low' : ($student['remaining_sessions'] <= 10 ? 'sessions-medium' : ''); ?>">
                                                <?php echo $student['remaining_sessions']; ?> sessions
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination for All Students -->
                    <div class="pagination-container">
                        <div class="entries-selector">
                            <label for="students-per-page">Show</label>
                            <select id="all-students-entries" class="entries-select">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <label for="students-per-page">entries</label>
                        </div>
                        <div class="pagination-info">
                            Showing <span id="all-students-start">1</span>-<span id="all-students-end">10</span> of <span id="all-students-total"><?php echo count($all_students); ?></span> students
                        </div>
                        <div class="pagination-controls">
                            <button class="pagination-btn" id="all-students-prev" disabled>
                                <i class="ri-arrow-left-s-line"></i> Previous
                            </button>
                            <div class="pagination-pages" id="all-students-pages"></div>
                            <button class="pagination-btn" id="all-students-next">
                                Next <i class="ri-arrow-right-s-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- My Points View Section - Redesigned -->
            <div id="my-points" class="view-container">
                <?php if ($current_student_rank > 0): ?>
                <!-- My Ranking Section -->
                <div class="my-ranking">
                    <h2><i class="ri-award-fill"></i> Your Current Ranking</h2>
                    <div class="my-ranking-value">
                        <div class="rank-number">
                            #<?php echo $current_student_rank; ?><sup><?php echo ordinalSuffix($current_student_rank); ?></sup>
                        </div>
                    </div>
                    <?php if ($current_student): ?>
                    <div class="my-stats">
                        <div class="my-stat">
                            <div class="my-stat-icon"><i class="ri-computer-line"></i></div>
                            <div class="my-stat-value"><?php echo $current_student['total_sitins']; ?></div>
                            <div class="my-stat-label">Total Sit-ins</div>
                        </div>
                        <div class="my-stat">
                            <div class="my-stat-icon"><i class="ri-star-line"></i></div>
                            <div class="my-stat-value"><?php echo $current_student['points']; ?></div>
                            <div class="my-stat-label">Points</div>
                        </div>
                        <div class="my-stat">
                            <div class="my-stat-icon"><i class="ri-time-line"></i></div>
                            <div class="my-stat-value"><?php echo $current_student['remaining_sessions']; ?></div>
                            <div class="my-stat-label">Sessions</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($current_student): ?>
                <!-- Points & Sessions Dashboard -->
                <div class="points-dashboard">
                    <!-- Points Summary -->
                    <div class="points-summary-card">
                        <h2><i class="ri-coin-line"></i> Your Points</h2>
                        <div class="points-circle">
                            <div>
                                <div class="points-count"><?php echo $current_student['points']; ?></div>
                                <div class="points-label">Total Points</div>
                            </div>
                        </div>
                        <p class="points-description">
                            Points are rewards for active participation in the CCS Sit-In system.
                        </p>
                    </div>
                    
                    <!-- How to Earn Points -->
                    <div class="earn-points-section">
                        <h3><i class="ri-award-line"></i> How to Earn Points</h3>
                        <ul>
                            <li>Completing sit-in sessions (1 point per session)</li>
                            <li>Being recognized by administrators for exemplary behavior</li>
                            <li>Participating in special CCS events and activities</li>
                            <li>Helping other students with their coursework during sit-in sessions</li>
                        </ul>
                    </div>
                    
                    <!-- Using Your Points -->
                    <div class="use-points-section">
                        <h3><i class="ri-exchange-line"></i> Using Your Points</h3>
                        <ul>
                            <li>Exchange points for additional sit-in sessions when you've used your standard allocation</li>
                            <li>Gain special privileges within the CCS Sit-In System</li>
                            <li>Earn recognition on the student leaderboard</li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notification-container"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add CSS for styling student's own row
            document.head.insertAdjacentHTML('beforeend', `
                <style>
                    .highlight-row {
                        background-color: rgba(117, 86, 204, 0.05) !important;
                    }
                    
                    .highlight-row:hover {
                        background-color: rgba(117, 86, 204, 0.1) !important;
                    }
                    
                    .current-user-badge {
                        display: inline-block;
                        font-size: 0.7rem;
                        background: rgba(117, 86, 204, 0.1);
                        color: #7556cc;
                        padding: 0.1rem 0.4rem;
                        border-radius: 10px;
                        font-weight: 500;
                        margin-left: 0.5rem;
                        vertical-align: middle;
                    }
                    
                    .current-user {
                        position: relative;
                        box-shadow: 0 0 15px rgba(117, 86, 204, 0.2);
                        border: 1px solid rgba(117, 86, 204, 0.3);
                    }
                    
                    .current-user::after {
                        content: 'You';
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        font-size: 0.7rem;
                        background: rgba(117, 86, 204, 0.1);
                        color: #7556cc;
                        padding: 0.1rem 0.4rem;
                        border-radius: 10px;
                        font-weight: 500;
                    }
                    
                    /* Improved My Ranking section */
                    .my-ranking {
                        background: white;
                        border-radius: 12px;
                        padding: 1.5rem;
                        margin-bottom: 2rem;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                        border: 1px solid #e2e8f0;
                        text-align: center;
                        animation: fadeUp 0.7s ease-out 0.3s forwards;
                        opacity: 0;
                    }
                    
                    .my-ranking h2 {
                        font-size: 1.3rem;
                        color: #7556cc;
                        margin-bottom: 1rem;
                        font-weight: 600;
                        text-align: center;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 0.5rem;
                    }
                    
                    .my-ranking-value {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        margin-bottom: 1.5rem;
                    }
                    
                    .rank-number {
                        font-size: 3.5rem;
                        font-weight: 700;
                        color: #7556cc;
                        text-shadow: 0 2px 10px rgba(117, 86, 204, 0.2);
                    }
                    
                    .rank-number sup {
                        font-size: 1.5rem;
                        font-weight: 600;
                        position: relative;
                        top: -1.5rem;
                        margin-left: 0.1rem;
                    }
                    
                    .my-stats {
                        display: flex;
                        justify-content: center;
                        gap: 2.5rem;
                        margin-top: 1.5rem;
                    }
                    
                    .my-stat {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 0.5rem;
                        transition: all 0.3s ease;
                    }
                    
                    .my-stat:hover {
                        transform: translateY(-5px);
                    }
                    
                    .my-stat-icon {
                        width: 48px;
                        height: 48px;
                        background: rgba(117, 86, 204, 0.1);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: #7556cc;
                        font-size: 1.3rem;
                        margin-bottom: 0.5rem;
                    }
                    
                    .my-stat-value {
                        font-size: 1.8rem;
                        font-weight: 700;
                        color: #1e293b;
                    }
                    
                    .my-stat-label {
                        font-size: 0.9rem;
                        color: #64748b;
                        font-weight: 500;
                    }
                    
                    /* Responsive adjustments for My Ranking */
                    @media (max-width: 640px) {
                        .my-stats {
                            flex-direction: column;
                            gap: 1.5rem;
                        }
                        
                        .my-stat {
                            flex-direction: row;
                            width: 100%;
                            justify-content: flex-start;
                            gap: 1rem;
                        }
                        
                        .my-stat-icon {
                            margin-bottom: 0;
                        }
                        
                        .my-stat-details {
                            display: flex;
                            flex-direction: column;
                            align-items: flex-start;
                        }
                    }
                </style>
            `);
            
            // Tab switching functionality
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    document.querySelectorAll('.filter-tab').forEach(t => {
                        t.classList.remove('active');
                    });
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Hide all view containers
                    document.querySelectorAll('.view-container').forEach(container => {
                        container.classList.remove('active');
                    });
                    
                    // Show the target container
                    const targetId = this.getAttribute('data-target');
                    document.getElementById(targetId).classList.add('active');
                });
            });
            
            // Add search functionality
            const studentSearchAll = document.getElementById('studentSearchAll');
            
            // Search function
            function setupSearchForTable(searchInput, tableSelector, colIndexes) {
                if (!searchInput) return;
                
                searchInput.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase().trim();
                    const tableBody = document.querySelector(tableSelector);
                    
                    if (!tableBody) return;
                    
                    const rows = tableBody.querySelectorAll('tr');
                    let hasVisibleRows = false;
                    
                    rows.forEach(row => {
                        if (!row.querySelector('.empty-state') && !row.classList.contains('empty-search')) {
                            let match = false;
                            
                            // Check each column we want to search in
                            colIndexes.forEach(idx => {
                                const cell = row.querySelector(`td:nth-child(${idx})`);
                                if (cell && cell.textContent.toLowerCase().includes(searchValue)) {
                                    match = true;
                                }
                            });
                            
                            if (match || searchValue === '') {
                                row.style.display = '';
                                hasVisibleRows = true;
                            } else {
                                row.style.display = 'none';
                            }
                        }
                    });
                    
                    // Handle empty search results
                    const emptySearch = tableBody.querySelector('.empty-search');
                    
                    if (!hasVisibleRows && searchValue !== '') {
                        if (!emptySearch) {
                            const emptyRow = document.createElement('tr');
                            emptyRow.classList.add('empty-search');
                            emptyRow.innerHTML = `
                                <td colspan="8" class="empty-state">
                                    <div class="empty-state-content">
                                        <i class="ri-search-line"></i>
                                        <p>No students match your search</p>
                                    </div>
                                </td>
                            `;
                            tableBody.appendChild(emptyRow);
                        }
                    } else if (emptySearch) {
                        tableBody.removeChild(emptySearch);
                    }
                });
            }
            
            // Setup search
            setupSearchForTable(studentSearchAll, '#all-students .modern-table tbody', [2, 3, 4]);
            
            // Add CSS for the search container with improved styling
            document.head.insertAdjacentHTML('beforeend', `
                <style>
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
                    }
                    
                    .search-container {
                        position: relative;
                        width: 280px;
                        margin-top: 0.5rem;
                    }
                    
                    .search-input {
                        width: 80%;
                        padding: 10px 15px 10px 40px;
                        border-radius: 8px;
                        border: 1px solid #e2e8f0;
                        background-color: white;
                        font-size: 0.9rem;
                        transition: all 0.2s ease;
                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                    }
                    
                    .search-input:focus {
                        outline: none;
                        border-color: #7556cc;
                        box-shadow: 0 0 0 3px rgba(117, 86, 204, 0.15);
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
                    
                    /* Pagination styles */
                    .pagination-container {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-top: 1.5rem;
                        padding: 1rem;
                        background: white;
                        border-radius: 8px;
                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                    }
                    
                    .pagination-info {
                        color: #64748b;
                        font-size: 0.9rem;
                    }
                    
                    .pagination-controls {
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                    }
                    
                    .pagination-btn {
                        display: flex;
                        align-items: center;
                        padding: 0.5rem 1rem;
                        background: white;
                        border: 1px solid #e2e8f0;
                        border-radius: 6px;
                        color: #4b5563;
                        font-size: 0.9rem;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    }
                    
                    .pagination-btn:hover:not(:disabled) {
                        background: #f8fafc;
                        border-color: #cbd5e1;
                    }
                    
                    .pagination-btn:disabled {
                        opacity: 0.5;
                        cursor: not-allowed;
                    }
                    
                    .pagination-btn i {
                        font-size: 1.1rem;
                    }
                    
                    .pagination-pages {
                        display: flex;
                        gap: 0.25rem;
                    }
                    
                    .page-number {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 32px;
                        height: 32px;
                        border-radius: 6px;
                        font-size: 0.9rem;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        color: #4b5563;
                        border: 1px solid transparent;
                    }
                    
                    .page-number:hover:not(.active) {
                        background: #f1f5f9;
                    }
                    
                    .page-number.active {
                        background: #7556cc;
                        color: white;
                        font-weight: 600;
                    }

                    label {
                        font-size: 0.9rem;
                        color: #4b5563;
                    }
                    
                    /* Responsive pagination */
                    @media (max-width: 640px) {
                        .pagination-container {
                            flex-direction: column;
                            gap: 1rem;
                            align-items: flex-start;
                        }
                        
                        .pagination-controls {
                            width: 100%;
                            justify-content: space-between;
                        }
                    }
                    
                    /* Responsive adjustments */
                    @media (max-width: 768px) {
                        .header-content {
                            flex-direction: column;
                            align-items: flex-start;
                        }
                        
                        .search-container {
                            width: 100%;
                        }
                    }
                    
                    /* Table row animations */
                    .modern-table tbody tr {
                        opacity: 0;
                        transform: translateY(10px);
                        animation: fadeInRow 0.5s ease forwards;
                    }
                    
                    @keyframes fadeInRow {
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                    
                    /* Apply staggered delay to rows */
                    .modern-table tbody tr:nth-child(1) { animation-delay: 0.05s; }
                    .modern-table tbody tr:nth-child(2) { animation-delay: 0.1s; }
                    .modern-table tbody tr:nth-child(3) { animation-delay: 0.15s; }
                    .modern-table tbody tr:nth-child(4) { animation-delay: 0.2s; }
                    .modern-table tbody tr:nth-child(5) { animation-delay: 0.25s; }
                    .modern-table tbody tr:nth-child(6) { animation-delay: 0.3s; }
                    .modern-table tbody tr:nth-child(7) { animation-delay: 0.35s; }
                    .modern-table tbody tr:nth-child(8) { animation-delay: 0.4s; }
                    .modern-table tbody tr:nth-child(9) { animation-delay: 0.45s; }
                    .modern-table tbody tr:nth-child(10) { animation-delay: 0.5s; }
                    
                    /* Special styling for empty state rows */
                    .modern-table tbody tr.empty-state,
                    .modern-table tbody tr.empty-search {
                        animation-delay: 0.1s;
                    }
                    
                    /* Animate rows when changing pages */
                    .modern-table tbody tr.animate-new {
                        opacity: 0;
                        animation: fadeInRow 0.4s ease forwards;
                    }
                </style>
            `);

            // Setup pagination with entries per page
            function setupPagination(tableSelector, paginationIds, defaultItemsPerPage = 10) {
                const tableBody = document.querySelector(`${tableSelector} tbody`);
                const rows = Array.from(tableBody.querySelectorAll('tr')).filter(row => !row.classList.contains('empty-state') && !row.classList.contains('empty-search'));
                
                // Get pagination elements
                const prevButton = document.getElementById(paginationIds.prev);
                const nextButton = document.getElementById(paginationIds.next);
                const pagesContainer = document.getElementById(paginationIds.pages);
                const startElement = document.getElementById(paginationIds.start);
                const endElement = document.getElementById(paginationIds.end);
                const totalElement = document.getElementById(paginationIds.total);
                const entriesSelect = document.getElementById(paginationIds.entries);
                
                if (!tableBody || !prevButton || !nextButton || !pagesContainer) return;
                
                let currentPage = 1;
                let itemsPerPage = defaultItemsPerPage;
                let totalPages = Math.ceil(rows.length / itemsPerPage);
                
                // Set total count
                if (totalElement) totalElement.textContent = rows.length;
                
                // Function to render page numbers
                function renderPageNumbers() {
                    pagesContainer.innerHTML = '';
                    
                    // Recalculate total pages
                    totalPages = Math.ceil(rows.length / itemsPerPage);
                    
                    // If there are no pages, exit
                    if (totalPages === 0) return;
                    
                    // Ensure current page is not out of bounds
                    if (currentPage > totalPages) {
                        currentPage = totalPages;
                    }
                    
                    // Calculate visible page numbers
                    let startPage = Math.max(1, currentPage - 2);
                    let endPage = Math.min(totalPages, startPage + 4);
                    
                    if (endPage - startPage < 4) {
                        startPage = Math.max(1, endPage - 4);
                    }
                    
                    // Add first page if not visible
                    if (startPage > 1) {
                        const firstPage = document.createElement('div');
                        firstPage.className = 'page-number';
                        firstPage.textContent = '1';
                        firstPage.addEventListener('click', () => goToPage(1));
                        pagesContainer.appendChild(firstPage);
                        
                        if (startPage > 2) {
                            const dots = document.createElement('div');
                            dots.className = 'page-number dots';
                            dots.textContent = '...';
                            dots.style.cursor = 'default';
                            pagesContainer.appendChild(dots);
                        }
                    }
                    
                    // Add page numbers
                    for (let i = startPage; i <= endPage; i++) {
                        const pageNumber = document.createElement('div');
                        pageNumber.className = `page-number ${i === currentPage ? 'active' : ''}`;
                        pageNumber.textContent = i;
                        pageNumber.addEventListener('click', () => goToPage(i));
                        pagesContainer.appendChild(pageNumber);
                    }
                    
                    // Add last page if not visible
                    if (endPage < totalPages) {
                        if (endPage < totalPages - 1) {
                            const dots = document.createElement('div');
                            dots.className = 'page-number dots';
                            dots.textContent = '...';
                            dots.style.cursor = 'default';
                            pagesContainer.appendChild(dots);
                        }
                        
                        const lastPage = document.createElement('div');
                        lastPage.className = 'page-number';
                        lastPage.textContent = totalPages;
                        lastPage.addEventListener('click', () => goToPage(totalPages));
                        pagesContainer.appendChild(lastPage);
                    }
                }
                
                // Function to go to a specific page
                function goToPage(page) {
                    if (page < 1 || page > totalPages) return;
                    
                    currentPage = page;
                    showCurrentPage();
                    
                    // Update pagination UI
                    prevButton.disabled = currentPage === 1;
                    nextButton.disabled = currentPage === totalPages || totalPages === 0;
                    renderPageNumbers();
                    
                    // Update info text
                    if (startElement && endElement) {
                        if (rows.length === 0) {
                            startElement.textContent = '0';
                            endElement.textContent = '0';
                        } else {
                            const start = (currentPage - 1) * itemsPerPage + 1;
                            const end = Math.min(start + itemsPerPage - 1, rows.length);
                            startElement.textContent = start;
                            endElement.textContent = end;
                        }
                    }
                }
                
                // Function to show current page rows with animation
                function showCurrentPage() {
                    const start = (currentPage - 1) * itemsPerPage;
                    const end = start + itemsPerPage;
                    
                    // First hide all rows
                    rows.forEach(row => {
                        row.style.display = 'none';
                        row.classList.remove('animate-new');
                    });
                    
                    // Then show and animate the current page rows
                    rows.forEach((row, index) => {
                        if (index >= start && index < end) {
                            // Set display to empty string (show the row)
                            row.style.display = '';
                            
                            // Add animation class with a small delay based on position
                            const delay = 0.05 * (index - start);
                            row.style.animationDelay = delay + 's';
                            row.classList.add('animate-new');
                        }
                    });
                }
                
                // Function to change items per page
                function changeItemsPerPage(newItemsPerPage) {
                    itemsPerPage = parseInt(newItemsPerPage);
                    goToPage(1); // Reset to first page when changing items per page
                }
                
                // Setup pagination event listeners
                prevButton.addEventListener('click', () => goToPage(currentPage - 1));
                nextButton.addEventListener('click', () => goToPage(currentPage + 1));
                
                // Setup entries select
                if (entriesSelect) {
                    entriesSelect.addEventListener('change', function() {
                        changeItemsPerPage(this.value);
                    });
                }
                
                // Initial setup
                renderPageNumbers();
                showCurrentPage();
                
                // Return the reset function
                return function resetPagination() {
                    goToPage(1);
                };
            }
            
            // Initialize pagination for tables
            setupPagination(
                '#all-students .modern-table', 
                {
                    prev: 'all-students-prev',
                    next: 'all-students-next',
                    pages: 'all-students-pages',
                    start: 'all-students-start',
                    end: 'all-students-end',
                    total: 'all-students-total',
                    entries: 'all-students-entries'
                }
            );

            // Add improved styles for the My Points section
            document.head.insertAdjacentHTML('beforeend', `
                <style>
                    /* Enhanced My Points styling */
                    #my-points {
                        padding: 0.5rem;
                    }
                    
                    .my-ranking {
                        background: white;
                        border-radius: 12px;
                        padding: 2rem;
                        margin-bottom: 2rem;
                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
                        border: 1px solid #e2e8f0;
                        text-align: center;
                        animation: fadeUp 0.7s ease-out 0.3s forwards;
                        opacity: 0;
                        transition: transform 0.3s ease;
                    }
                    
                    .my-ranking:hover {
                        transform: translateY(-5px);
                    }
                    
                    .my-ranking h2 {
                        font-size: 1.4rem;
                        color: #7556cc;
                        margin-bottom: 1.5rem;
                        font-weight: 600;
                        text-align: center;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 0.5rem;
                    }
                    
                    .my-ranking-value {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        margin-bottom: 2rem;
                    }
                    
                    .rank-number {
                        font-size: 4rem;
                        font-weight: 700;
                        color: #7556cc;
                        text-shadow: 0 2px 10px rgba(117, 86, 204, 0.2);
                        background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
                        -webkit-background-clip: text;
                        background-clip: text;
                        -webkit-text-fill-color: transparent;
                    }
                    
                    .rank-number sup {
                        font-size: 1.6rem;
                        font-weight: 600;
                        position: relative;
                        top: -2rem;
                        margin-left: 0.1rem;
                    }
                    
                    .my-stats {
                        display: flex;
                        justify-content: center;
                        gap: 3rem;
                        margin-top: 2rem;
                    }
                    
                    .my-stat {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 0.75rem;
                        transition: all 0.3s ease;
                        position: relative;
                        padding: 1.25rem;
                        border-radius: 12px;
                    }
                    
                    .my-stat:hover {
                        transform: translateY(-5px);
                        background: rgba(117, 86, 204, 0.03);
                    }
                    
                    .my-stat:not(:last-child)::after {
                        content: '';
                        position: absolute;
                        right: -1.5rem;
                        top: 50%;
                        transform: translateY(-50%);
                        height: 50px;
                        width: 1px;
                        background: linear-gradient(to bottom, transparent, rgba(117, 86, 204, 0.2), transparent);
                    }
                    
                    .my-stat-icon {
                        width: 52px;
                        height: 52px;
                        background: rgba(117, 86, 204, 0.1);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: #7556cc;
                        font-size: 1.4rem;
                        margin-bottom: 0.75rem;
                        transition: all 0.3s ease;
                        box-shadow: 0 4px 12px rgba(117, 86, 204, 0.15);
                    }
                    
                    .my-stat:hover .my-stat-icon {
                        transform: scale(1.1);
                        background: rgba(117, 86, 204, 0.15);
                    }
                    
                    .my-stat-value {
                        font-size: 2rem;
                        font-weight: 700;
                        color: #1e293b;
                        transition: all 0.3s ease;
                    }
                    
                    .my-stat:hover .my-stat-value {
                        color: #7556cc;
                    }
                    
                    .my-stat-label {
                        font-size: 0.95rem;
                        color: #64748b;
                        font-weight: 500;
                    }
                    
                    /* Points Dashboard Styling */
                    .points-dashboard {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                        gap: 1.75rem;
                        margin-bottom: 2rem;
                    }
                    
                    .points-summary-card,
                    .earn-points-section, 
                    .use-points-section {
                        background: white;
                        border-radius: 12px;
                        padding: 2rem;
                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
                        border: 1px solid #e2e8f0;
                        transition: transform 0.3s ease, box-shadow 0.3s ease;
                        animation: fadeUp 0.7s ease-out forwards;
                        opacity: 0;
                        height: 100%;
                    }
                    
                    .points-summary-card:hover,
                    .earn-points-section:hover, 
                    .use-points-section:hover {
                        transform: translateY(-7px);
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
                    }
                    
                    .points-summary-card {
                        text-align: center;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        animation-delay: 0.3s;
                    }
                    
                    .earn-points-section {
                        animation-delay: 0.4s;
                    }
                    
                    .use-points-section {
                        animation-delay: 0.5s;
                    }
                    
                    .points-summary-card h2,
                    .earn-points-section h3, 
                    .use-points-section h3 {
                        font-size: 1.4rem;
                        color: #7556cc;
                        margin-bottom: 1.5rem;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        gap: 0.75rem;
                    }
                    
                    .points-summary-card h2 {
                        justify-content: center;
                        margin-bottom: 2rem;
                    }
                    
                    .points-circle {
                        width: 180px;
                        height: 180px;
                        border-radius: 50%;
                        background: linear-gradient(135deg, rgba(117, 86, 204, 0.08) 0%, rgba(149, 86, 204, 0.15) 100%);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 1rem auto 2rem;
                        position: relative;
                        border: 4px solid rgba(117, 86, 204, 0.15);
                        box-shadow: 0 5px 15px rgba(117, 86, 204, 0.15);
                        transition: all 0.5s ease;
                    }
                    
                    .points-summary-card:hover .points-circle {
                        transform: scale(1.05);
                        border-color: rgba(117, 86, 204, 0.25);
                    }
                    
                    .points-circle::before {
                        content: '';
                        position: absolute;
                        top: -8px;
                        left: -8px;
                        right: -8px;
                        bottom: -8px;
                        border-radius: 50%;
                        border: 2px solid rgba(117, 86, 204, 0.1);
                        animation: pulseCircle 3s infinite;
                    }
                    
                    .points-count {
                        font-size: 4rem;
                        font-weight: 700;
                        color: #7556cc;
                        line-height: 1;
                        text-shadow: 0 2px 10px rgba(117, 86, 204, 0.2);
                        background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
                        -webkit-background-clip: text;
                        background-clip: text;
                        -webkit-text-fill-color: transparent;
                    }
                    
                    .points-label {
                        font-size: 1.1rem;
                        color: #64748b;
                        margin-top: 0.75rem;
                        font-weight: 500;
                    }
                    
                    .points-description {
                        color: #64748b;
                        font-size: 1rem;
                        margin-top: 1.5rem;
                        line-height: 1.6;
                    }
                    
                    .earn-points-section h3,
                    .use-points-section h3 {
                        position: relative;
                        padding-bottom: 1rem;
                    }
                    
                    .earn-points-section h3::after,
                    .use-points-section h3::after {
                        content: '';
                        position: absolute;
                        bottom: 0;
                        left: 0;
                        width: 60px;
                        height: 3px;
                        background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
                        border-radius: 3px;
                        transition: width 0.3s ease;
                    }
                    
                    .earn-points-section:hover h3::after,
                    .use-points-section:hover h3::after {
                        width: 80px;
                    }
                    
                    .earn-points-section ul,
                    .use-points-section ul {
                        margin: 0;
                        padding-left: 1.5rem;
                        color: #4b5563;
                    }
                    
                    .earn-points-section li,
                    .use-points-section li {
                        margin-bottom: 1rem;
                        line-height: 1.7;
                        position: relative;
                        padding-left: 0.5rem;
                    }
                    
                    .earn-points-section li::marker,
                    .use-points-section li::marker {
                        color: #7556cc;
                    }
                    
                    @keyframes fadeUp {
                        from {
                            opacity: 0;
                            transform: translateY(20px);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                    
                    @keyframes pulseCircle {
                        0% {
                            transform: scale(1);
                            opacity: 0.7;
                        }
                        50% {
                            transform: scale(1.05);
                            opacity: 0.3;
                        }
                        100% {
                            transform: scale(1);
                            opacity: 0.7;
                        }
                    }
                    
                    /* Responsive adjustments */
                    @media (max-width: 768px) {
                        .my-stats {
                            flex-direction: column;
                            gap: 2rem;
                        }
                        
                        .my-stat {
                            width: 100%;
                            padding: 1.5rem;
                            border-radius: 12px;
                            background: rgba(117, 86, 204, 0.03);
                        }
                        
                        .my-stat:not(:last-child)::after {
                            display: none;
                        }
                        
                        .points-dashboard {
                            grid-template-columns: 1fr;
                        }
                        
                        .points-circle {
                            width: 150px;
                            height: 150px;
                        }
                        
                        .points-count {
                            font-size: 3rem;
                        }
                    }
                </style>
            `);
        });

        // Helper notification display function
        function showNotification(title, message, type = 'info') {
            const container = document.getElementById('notification-container');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            notification.innerHTML = `
                <div class="notification-icon">
                    <i class="ri-${type === 'success' ? 'check-line' : type === 'error' ? 'error-warning-line' : 'information-line'}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close">&times;</button>
            `;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            });
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
            
            return notification;
        }

        // Notification functionality
        const notificationToggle = document.getElementById('notification-toggle');
        const notificationDropdown = document.getElementById('notification-dropdown');
        const notificationBadge = document.getElementById('notification-badge');
        const notificationList = document.getElementById('notification-list');
        const markAllReadBtn = document.getElementById('mark-all-read');
        
        // Load notifications
        async function loadNotifications() {
            try {
                const response = await fetch('../notifications/get_notifications.php');
                const data = await response.json();
                
                // Update notification badge
                if (data.unread_count > 0) {
                    notificationBadge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    notificationBadge.classList.add('active');
                } else {
                    notificationBadge.classList.remove('active');
                }
                
                // Update notification list
                notificationList.innerHTML = '';
                
                if (data.notifications.length === 0) {
                    notificationList.innerHTML = `
                        <div class="notification-empty">
                            You have no notifications
                        </div>
                    `;
                    return;
                }
                
                data.notifications.forEach(notification => {
                    const item = document.createElement('div');
                    item.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
                    item.dataset.id = notification.id;
                    
                    item.innerHTML = `
                        <div class="notification-indicator"></div>
                        <div class="notification-content">
                            <h4>${notification.title}</h4>
                            <p>${notification.content}</p>
                            <span class="notification-time">${notification.created_at}</span>
                        </div>
                    `;
                    
                    item.addEventListener('click', () => markAsRead(notification.id));
                    
                    notificationList.appendChild(item);
                });
            } catch (error) {
                console.error('Error loading notifications:', error);
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        Error loading notifications
                    </div>
                `;
            }
        }
        
        // Toggle notification dropdown
        notificationToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isOpen = notificationDropdown.classList.contains('active');
            
            if (!isOpen) {
                loadNotifications();
                notificationDropdown.classList.add('active');
            } else {
                notificationDropdown.classList.remove('active');
            }
        });
        
        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationDropdown.contains(e.target) && 
                !notificationToggle.contains(e.target)) {
                notificationDropdown.classList.remove('active');
            }
        });
        
        // Mark notification as read
        async function markAsRead(id) {
            try {
                const response = await fetch('../notifications/mark_as_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ notification_id: id }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update UI
                    const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                    if (item) {
                        item.classList.remove('unread');
                    }
                    
                    // Reload notifications to update badge count
                    loadNotifications();
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }
        
        // Mark all notifications as read
        markAllReadBtn.addEventListener('click', async function() {
            try {
                const response = await fetch('../notifications/mark_all_read.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update UI - remove unread class from all notifications
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    
                    // Update badge
                    notificationBadge.classList.remove('active');
                    
                    // Show confirmation
                    showNotification('Success', 'All notifications marked as read', 'success');
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        });
        
        // Load notifications on page load
        loadNotifications();
        
        // Set interval to refresh notifications (every 30 seconds)
        setInterval(loadNotifications, 30000);

    </script>
</body>
</html>
         
        <style>
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
                    
                    .header-right {
                        display: flex;
                        align-items: center;
                    }
                    
                    .entries-selector {
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        margin-left: 0;
                    }
                    
                    .entries-select {
                        padding: 0.5rem;
                        border: 1px solid #e2e8f0;
                        border-radius: 6px;
                        background-color: white;
                        color: #4b5563;
                        font-size: 0.9rem;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    }
                    
                    .entries-select:focus {
                        outline: none;
                        border-color: #7556cc;
                        box-shadow: 0 0 0 2px rgba(117, 86, 204, 0.1);
                    }
                    
                    /* Container headers consistent styling */
                    .container-header {
                        margin-bottom: 1.5rem;
                        padding: 1.25rem;
                        background: white;
                        border-radius: 12px;
                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                    }
                    
                    /* Responsive pagination for entries selector */
                    @media (max-width: 768px) {
                        .pagination-controls {
                            flex-wrap: wrap;
                            gap: 0.75rem;
                        }
                        
                        .entries-selector {
                            margin-left: 0;
                            margin-top: 0.5rem;
                            width: 100%;
                        }
                        
                        .entries-select {
                            width: 100%;
                        }
                        
                        .header-content {
                            flex-direction: column;
                            align-items: flex-start;
                        }
                    }

        </style>
</body>
</html>
