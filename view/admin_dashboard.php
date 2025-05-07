<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if this is the first time loading the dashboard after login
$show_welcome_notification = false;
if (!isset($_SESSION['dashboard_welcome_shown'])) {
    $show_welcome_notification = true;
    $_SESSION['dashboard_welcome_shown'] = true;
}

require_once '../config/db_connect.php';

// Get statistics
$stats = [
    'total_students' => 0,
    'current_sitin' => 0,
    'total_sitin' => 0,
    'avg_daily_usage' => 0,    // New stat
    'peak_hour' => 'N/A',      // New stat
    'most_active_lab' => 'N/A' // New stat
];

// Get total registered students (modified query)
$query = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($query);
if ($result) {
    $stats['total_students'] = $result->fetch_assoc()['count'];
}

// Get current sit-in count (combined from both reservations and sit_ins tables)
// First, count current students from reservations
$query = "SELECT COUNT(*) as count FROM reservations 
          WHERE DATE(date) = CURDATE() 
          AND time_in IS NOT NULL 
          AND time_out IS NULL
          AND status = 'approved'";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['current_sitin'] = $row['count'];
} else {
    $stats['current_sitin'] = 0;
}

// Next, add current students from sit_ins
$query = "SELECT COUNT(*) as count FROM sit_ins 
          WHERE time_out IS NULL 
          AND status = 'active'";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['current_sitin'] += $row['count'];
}

// Get total sit-in count from both tables
// First from reservations
$query = "SELECT COUNT(*) as count FROM reservations";
$result = $conn->query($query);
if ($result) {
    $stats['total_sitin'] = $result->fetch_assoc()['count'];
}

// Add count from sit_ins
$query = "SELECT COUNT(*) as count FROM sit_ins";
$result = $conn->query($query);
if ($result) {
    $stats['total_sitin'] += $result->fetch_assoc()['count'];
}

// NEW: Calculate average daily usage (past 30 days)
$query = "SELECT AVG(daily_count) as avg_count FROM (
    SELECT DATE(time_in) as check_date, COUNT(*) as daily_count
    FROM reservations
    WHERE time_in IS NOT NULL AND time_in >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(time_in)
    UNION ALL
    SELECT DATE(time_in) as check_date, COUNT(*) as daily_count
    FROM sit_ins
    WHERE time_in IS NOT NULL AND time_in >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(time_in)
  ) as daily_stats";
$result = $conn->query($query);
if ($result && ($row = $result->fetch_assoc()) && !empty($row['avg_count'])) {
    $stats['avg_daily_usage'] = round($row['avg_count'], 1);
} else {
    $stats['avg_daily_usage'] = 0;
}

// NEW: Find peak hour (time with most students)
$query = "SELECT 
            HOUR(time_in) as check_hour, 
            COUNT(*) as hourly_count
          FROM (
            SELECT time_in FROM reservations 
            WHERE time_in IS NOT NULL
            UNION ALL
            SELECT time_in FROM sit_ins 
            WHERE time_in IS NOT NULL
          ) as combined_times
          GROUP BY HOUR(time_in)
          ORDER BY hourly_count DESC
          LIMIT 1";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $hour = $row['check_hour'];
    $ampm = $hour >= 12 ? 'PM' : 'AM';
    $hour12 = $hour % 12;
    if ($hour12 == 0) $hour12 = 12;
    $stats['peak_hour'] = $hour12 . ' ' . $ampm;
}

// Check column names for both tables - Update to check for "laboratory" first
$reservations_lab_column = 'laboratory'; // Use the actual column name in database
$sit_ins_lab_column = 'laboratory';      // Use the actual column name in database

// NEW: Find most active laboratory (using the correct column names)
$query = "SELECT lab, COUNT(*) as usage_count
          FROM (
            SELECT $reservations_lab_column as lab FROM reservations 
            WHERE $reservations_lab_column IS NOT NULL AND $reservations_lab_column != ''
            UNION ALL
            SELECT $sit_ins_lab_column as lab FROM sit_ins 
            WHERE $sit_ins_lab_column IS NOT NULL AND $sit_ins_lab_column != ''
          ) as combined_labs
          GROUP BY lab
          ORDER BY usage_count DESC
          LIMIT 1";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['most_active_lab'] = $row['lab'];
}

// Add this after the existing stats queries
// Get year level distribution
$year_level_stats = [
    '1st Year' => 0,
    '2nd Year' => 0,
    '3rd Year' => 0,
    '4th Year' => 0
];

$query = "SELECT year, COUNT(*) as count FROM users 
          WHERE year BETWEEN 1 AND 4 
          GROUP BY year 
          ORDER BY year";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $year = (int)$row['year'];
        switch ($year) {
            case 1:
                $year_level_stats['1st Year'] = $row['count'];
                break;
            case 2:
                $year_level_stats['2nd Year'] = $row['count'];
                break;
            case 3:
                $year_level_stats['3rd Year'] = $row['count'];
                break;
            case 4:
                $year_level_stats['4th Year'] = $row['count'];
                break;
        }
    }
}

// NEW: Get lab usage statistics
$lab_usage_stats = [];

// Construct the query using the correct column names for both tables
$query = "SELECT lab, COUNT(*) as count 
          FROM (
            SELECT $reservations_lab_column as lab FROM reservations 
            WHERE $reservations_lab_column IS NOT NULL AND $reservations_lab_column != ''
            UNION ALL
            SELECT $sit_ins_lab_column as lab FROM sit_ins 
            WHERE $sit_ins_lab_column IS NOT NULL AND $sit_ins_lab_column != ''
          ) as combined_labs
          GROUP BY lab
          ORDER BY count DESC";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lab_usage_stats[] = [
            'label' => $row['lab'],
            'count' => $row['count']
        ];
    }
}

// NEW: Get daily usage for the past 14 days
$daily_usage = [];
$dates = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M d', strtotime($date));
    $daily_usage[$date] = 0;
}

$query = "SELECT 
            DATE(time_in) as check_date, 
            COUNT(*) as daily_count
          FROM (
            SELECT time_in FROM reservations 
            WHERE time_in IS NOT NULL AND time_in >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            UNION ALL
            SELECT time_in FROM sit_ins 
            WHERE time_in IS NOT NULL AND time_in >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
          ) as combined_times
          GROUP BY DATE(time_in)";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (isset($daily_usage[$row['check_date']])) {
            $daily_usage[$row['check_date']] = $row['daily_count'];
        }
    }
}
$daily_usage_values = array_values($daily_usage);

// NEW: Get hourly distribution for a heat map
$hourly_distribution = [];
for ($i = 0; $i < 24; $i++) {
    $hourly_distribution[$i] = 0;
}

$query = "SELECT 
            HOUR(time_in) as check_hour, 
            COUNT(*) as hourly_count
          FROM (
            SELECT time_in FROM reservations 
            WHERE time_in IS NOT NULL AND time_in >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            UNION ALL
            SELECT time_in FROM sit_ins 
            WHERE time_in IS NOT NULL AND time_in >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          ) as combined_times
          GROUP BY HOUR(time_in)";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hourly_distribution[$row['check_hour']] = $row['hourly_count'];
    }
}

// Get student purposes from sit-ins table - IMPROVED QUERY
$purpose_stats = [];
$query = "SELECT purpose, COUNT(*) as count 
          FROM sit_ins 
          WHERE purpose IS NOT NULL 
          GROUP BY purpose 
          ORDER BY count DESC 
          LIMIT 5";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $purpose_stats[] = [
            'label' => $row['purpose'],
            'count' => $row['count']
        ];
    }
} else {
    // Fallback to reservations table if no data in sit_ins
    $query = "SELECT purpose, COUNT(*) as count 
              FROM reservations 
              WHERE purpose IS NOT NULL 
              GROUP BY purpose 
              ORDER BY count DESC 
              LIMIT 5";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $purpose_stats[] = [
                'label' => $row['purpose'],
                'count' => $row['count']
            ];
        }
    }
}

// Fetch announcements - fixed query
$announcements = [];
$query = "SELECT * FROM announcements ORDER BY created_at DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Fetch feedback data
$feedback_data = [
    'total_count' => 0,
    'average_rating' => 0,
    'distribution' => [
        1 => 0, // 1 star
        2 => 0, // 2 stars
        3 => 0, // 3 stars
        4 => 0, // 4 stars
        5 => 0  // 5 stars
    ],
    'recent_feedback' => []
];

// Get overall ratings count and average
$query = "SELECT COUNT(*) as total, AVG(rating) as average FROM feedback";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $feedback_data['total_count'] = (int)$row['total'];
    $feedback_data['average_rating'] = round($row['average'], 1);
}

// Get rating distribution
$query = "SELECT rating, COUNT(*) as count FROM feedback GROUP BY rating ORDER BY rating";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rating = (int)$row['rating'];
        $feedback_data['distribution'][$rating] = (int)$row['count'];
    }
}

// Calculate distribution percentages
if ($feedback_data['total_count'] > 0) {
    foreach ($feedback_data['distribution'] as $rating => $count) {
        $feedback_data['percentage'][$rating] = round(($count / $feedback_data['total_count']) * 100);
    }
} else {
    $feedback_data['percentage'] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
}

// Get recent feedback with user details
$query = "SELECT f.*, 
          CASE 
            WHEN r.idno IS NOT NULL THEN r.idno
            WHEN s.idno IS NOT NULL THEN s.idno
            ELSE NULL
          END as idno,
          CASE 
            WHEN r.fullname IS NOT NULL AND r.fullname != '' THEN r.fullname
            WHEN s.fullname IS NOT NULL AND s.fullname != '' THEN s.fullname
            ELSE NULL
          END as fullname,
          f.created_at,
          u.firstname, u.lastname
          FROM feedback f
          LEFT JOIN reservations r ON f.reservation_id = r.id
          LEFT JOIN sit_ins s ON f.sit_in_id = s.id
          LEFT JOIN users u ON r.idno = u.idno OR s.idno = u.idno
          ORDER BY f.created_at DESC
          LIMIT 3";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // If we have user details from users table, use them
        if (!empty($row['firstname']) && !empty($row['lastname'])) {
            $name = $row['firstname'] . ' ' . $row['lastname'];
            $initials = strtoupper(substr($row['firstname'], 0, 1) . substr($row['lastname'], 0, 1));
        } 
        // Otherwise use the fullname from sit_ins or reservations
        elseif (!empty($row['fullname'])) {
            $name = $row['fullname'];
            $name_parts = explode(' ', trim($row['fullname']));
            if (count($name_parts) >= 2) {
                $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts)-1], 0, 1));
            } else {
                $initials = strtoupper(substr($row['fullname'], 0, 2));
            }
        } 
        // Fallback
        else {
            $name = "Anonymous User";
            $initials = "AU";
        }
        
        // Format date if available
        $created_date = "N/A";
        if (!empty($row['created_at']) && $row['created_at'] != '0000-00-00 00:00:00') {
            $created_date = date('F j, Y', strtotime($row['created_at']));
        }
        
        $feedback_data['recent_feedback'][] = [
            'name' => $name,
            'initials' => $initials,
            'rating' => (int)$row['rating'],
            'message' => $row['message'],
            'date' => $created_date
        ];
    }
}

// If no recent feedback is found, provide sample data
if (empty($feedback_data['recent_feedback'])) {
    $feedback_data['recent_feedback'] = [
        [
            'name' => 'No feedback available',
            'initials' => 'NF',
            'rating' => 0,
            'message' => 'No student feedback has been submitted yet. Feedback will appear here once students begin providing ratings.',
            'date' => date('F j, Y')
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/nav.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/javascript/nav.js" defer></script>
    <script src="../assets/javascript/notification.js" defer></script>
    <script src="../assets/javascript/admin_notifications.js" defer></script>

    <style>
        /* Page animation styles */
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
        
        .admin-dashboard {
            opacity: 0;
            transform: translateY(20px);
            animation: slideUpFade 0.8s ease-out 0.2s forwards;
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
        
        .nav-container {
            opacity: 0;
            transform: translateY(-10px);
            animation: navSlideDown 0.5s ease-out forwards;
        }
        
        @keyframes navSlideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stats-grid .stat-card {
            opacity: 0;
            transform: translateY(15px);
            animation: cardFadeIn 0.5s ease-out forwards;
        }
        
        .stats-grid .stat-card:nth-child(1) {
            animation-delay: 0.3s;
        }
        
        .stats-grid .stat-card:nth-child(2) {
            animation-delay: 0.45s;
        }
        
        .stats-grid .stat-card:nth-child(3) {
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

        /* Critical CSS for announcement items - ensures styles are applied */
        .announcement-list {
            max-height: 380px !important;
            overflow-y: auto !important;
            scrollbar-width: thin !important;
            scrollbar-color: rgba(117, 86, 204, 0.5) transparent !important;
            padding: 1rem 1.5rem !important;
        }
        
        .announcement-item {
            display: flex !important;
            background: white !important;
            border-radius: 10px !important;
            margin-bottom: 15px !important;
            padding: 0 !important;
            border: none !important;
            overflow: hidden !important;
            border-left: 4px solid #7556cc !important;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.3s ease !important;
        }
        
        .announcement-item:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1) !important;
        }
        
        .announcement-date {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 70px !important;
            padding: 15px 0 !important;
            background: rgba(117, 86, 204, 0.08) !important;
            border-right: 1px solid rgba(117, 86, 204, 0.15) !important;
        }
        
        .date-day {
            font-size: 1.8rem !important;
            font-weight: 700 !important;
            color: #7556cc !important;
            line-height: 1 !important;
        }
        
        .date-month {
            font-size: 0.9rem !important;
            color: #7556cc !important;
            font-weight: 500 !important;
            text-transform: uppercase !important;
        }
        
        .announcement-content {
            flex: 1 !important;
            padding: 15px 20px !important;
        }
        
        .announcement-header {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            margin-bottom: 8px !important;
        }
        
        .announcement-content h3 {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            margin: 0 !important;
        }
        
        .announcement-content p {
            font-size: 0.95rem !important;
            color: #475569 !important;
            margin: 0 0 12px 0 !important;
            line-height: 1.5 !important;
        }
        
        .announcement-meta {
            display: flex !important;
            font-size: 0.8rem !important;
            color: #94a3b8 !important;
        }
        
        .announcement-meta span {
            display: flex !important;
            align-items: center !important;
        }
        
        .announcement-meta i {
            margin-right: 4px !important;
            font-size: 0.9rem !important;
        }

        /* Enhanced scrollbar styles for announcement lists */
        .announcement-list {
            max-height: 400px !important;
            overflow-y: auto !important;
            scrollbar-width: thin !important;
            scrollbar-color: rgba(117, 86, 204, 0.5) transparent !important;
        }
        
        /* WebKit browser scrollbar styles (Chrome, Safari, etc.) */
        .announcement-list::-webkit-scrollbar {
            width: 6px !important;
        }
        
        .announcement-list::-webkit-scrollbar-track {
            background: transparent !important;
        }
        
        .announcement-list::-webkit-scrollbar-thumb {
            background-color: rgba(117, 86, 204, 0.5) !important;
            border-radius: 6px !important;
        }
        
        .announcement-list::-webkit-scrollbar-thumb:hover {
            background-color: rgba(117, 86, 204, 0.8) !important;
        }
        
        /* For Firefox browsers */
        .announcement-list {
            scrollbar-width: thin !important;
            scrollbar-color: rgba(117, 86, 204, 0.5) transparent !important;
        }
        
        /* Add padding to ensure content doesn't touch scrollbar */
        .announcement-list {
            padding-right: 10px !important;
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

    <div id="notification-container"></div>
    
    <?php include '../view/nav.php'; ?>
    
    <div class="admin-dashboard">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                Administrator Dashboard
            </div>
        </div>
        
        <!-- Enhanced Stats Grid - Now with more metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Students</div>
                <div class="stat-value"><?php echo $stats['total_students']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Current Students in Lab</div>
                <div class="stat-value"><?php echo $stats['current_sitin']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Sit-In Records</div>
                <div class="stat-value"><?php echo $stats['total_sitin']; ?></div>
            </div>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Avg. Daily Usage</div>
                <div class="stat-value"><?php echo $stats['avg_daily_usage']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Peak Usage Hour</div>
                <div class="stat-value"><?php echo $stats['peak_hour']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Most Active Lab</div>
                <div class="stat-value"><?php echo $stats['most_active_lab']; ?></div>
            </div>
        </div>

        <!-- Announcements Section -->
        <div class="announcements-grid">
            <!-- Left Column - Create Announcement -->
            <div class="announcements-card">
                <div class="announcements-header">
                    <i class="ri-add-line"></i>
                    <span>Create Announcement</span>
                </div>
                <form id="announcementForm" class="announcement-form">
                    <input type="hidden" id="title" name="title" value="CCS ADMIN">
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="4" placeholder="Type your announcement here..." required></textarea>
                    </div>
                    <button type="submit" class="edit-btn2">
                        <i class="ri-send-plane-fill"></i>
                        <span>Post Announcement</span>
                    </button>
                </form>
            </div>

            <!-- Right Column - Announcement List -->
            <div class="announcements-card">
                <div class="announcements-header">
                    <i class="ri-notification-3-line"></i>
                    <span>Recent Announcements</span>
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
                            <div class="announcement-item" data-id="<?php echo $announcement['id']; ?>">
                                <div class="announcement-date">
                                    <div class="date-day"><?php echo date('d', strtotime($announcement['created_at'])); ?></div>
                                    <div class="date-month"><?php echo date('M', strtotime($announcement['created_at'])); ?></div>
                                </div>
                                <div class="announcement-content">
                                    <div class="announcement-header">
                                        <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                        <div class="announcement-actions">
                                            <button class="edit-announcement" onclick="editAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo addslashes(htmlspecialchars($announcement['title'])); ?>', '<?php echo addslashes(htmlspecialchars($announcement['content'])); ?>')">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button class="delete-announcement" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Redesigned Overall Statistics Section -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                Overall Statistics
            </div>
        </div>
        
        <!-- Daily Usage Trend Chart -->
        <div class="chart-card" style="margin-bottom: 1.5rem;">
            <div class="chart-header">
                <i class="ri-line-chart-line"></i>
                <span>Daily Student Usage (Past 14 Days)</span>
            </div>
            <div class="chart-container">
                <canvas id="dailyUsageChart"></canvas>
            </div>
        </div>

        <!-- Advanced Statistics Grid -->
        <div class="charts-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 2rem;">
            <div class="chart-card" style="min-height: 400px;">
                <div class="chart-header">
                    <i class="ri-building-line"></i>
                    <span>Laboratory Usage Distribution</span>
                </div>
                <div class="chart-container">
                    <canvas id="labUsageChart"></canvas>
                </div>
            </div>
            <div class="chart-card" style="min-height: 400px;">
                <div class="chart-header">
                    <i class="ri-time-line"></i>
                    <span>Hourly Traffic Distribution</span>
                </div>
                <div class="chart-container">
                    <canvas id="hourlyTrafficChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Charts Grid - Two columns for pie charts -->
        <div class="charts-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 2rem;">
            <div class="chart-card" style="min-height: 400px;">
                <div class="chart-header">
                    <i class="ri-bar-chart-box-line"></i>
                    <span>Year Level Distribution</span>
                </div>
                <div class="chart-container">
                    <canvas id="yearLevelChart"></canvas>
                </div>
            </div>
            <div class="chart-card" style="min-height: 400px;">
                <div class="chart-header">
                    <i class="ri-questionnaire-line"></i>
                    <span>Student Sit-in Purposes</span>
                </div>
                <div class="chart-container">
                    <canvas id="purposeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Student Feedback and Ratings Section -->
        <div class="feedback-section">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    Feedback and Ratings
                </div>
            </div>
            
            <div class="feedback-grid">
                <!-- Overall Rating Card -->
                <div class="feedback-card overall-rating">
                    <div class="card-header">
                        <h3>Overall Satisfaction Rating</h3>
                    </div>
                    <div class="rating-display">
                        <div class="rating-number"><?php echo $feedback_data['average_rating']; ?></div>
                        <div class="rating-stars">
                            <?php
                            // Display stars based on average rating
                            $avg = $feedback_data['average_rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= floor($avg)) {
                                    echo '<i class="ri-star-fill"></i>'; // Full star
                                } elseif ($i == ceil($avg) && $avg != floor($avg)) {
                                    echo '<i class="ri-star-half-line"></i>'; // Half star
                                } else {
                                    echo '<i class="ri-star-line"></i>'; // Empty star
                                }
                            }
                            ?>
                        </div>
                        <div class="rating-count">Based on <?php echo $feedback_data['total_count']; ?> student rating<?php echo $feedback_data['total_count'] != 1 ? 's' : ''; ?></div>
                    </div>
                </div>
                
                <!-- Rating Distribution Card -->
                <div class="feedback-card rating-distribution">
                    <div class="card-header">
                        <h3>Rating Distribution</h3>
                    </div>
                    <div class="distribution-bars">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="dist-item">
                            <div class="dist-label"><?php echo $i; ?> <i class="ri-star-fill"></i></div>
                            <div class="dist-bar-container">
                                <div class="dist-bar" style="width: <?php echo $feedback_data['percentage'][$i]; ?>%"></div>
                            </div>
                            <div class="dist-count"><?php echo $feedback_data['percentage'][$i]; ?>%</div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Recent Feedback Card -->
                <div class="feedback-card recent-feedback">
                    <div class="card-header">
                        <h3>Recent Feedback</h3>
                    </div>
                    <div class="feedback-list">
                        <?php foreach ($feedback_data['recent_feedback'] as $feedback): ?>
                        <div class="feedback-item">
                            <div class="feedback-user">
                                <div class="user-avatar"><?php echo $feedback['initials']; ?></div>
                                <div class="user-info">
                                    <div class="user-name"><?php echo htmlspecialchars($feedback['name']); ?></div>
                                    <div class="user-stars">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $feedback['rating']) {
                                                echo '<i class="ri-star-fill"></i>';
                                            } else {
                                                echo '<i class="ri-star-line"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="feedback-text">
                                "<?php echo htmlspecialchars($feedback['message']); ?>"
                            </div>
                            <div class="feedback-date"><?php echo $feedback['date']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>    
    
        
        <!-- Enhanced Edit Announcement Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Announcement</h3>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <form id="editAnnouncementForm">
                    <input type="hidden" id="edit_id" name="id">
                    <input type="hidden" id="edit_title" name="title" value="CCS ADMIN">
                    <div class="form-group">
                        <label for="edit_content">Content</label>
                        <textarea id="edit_content" name="content" rows="4" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="edit-btn2">
                            <i class="ri-save-line"></i>
                            <span>Update Announcement</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div><!-- End of admin-dashboard div -->

    <!-- Notification System -->
    <div id="notification-container"></div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Announcement</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this announcement?</p>
                <p>This action cannot be undone.</p>
                <input type="hidden" id="delete_id">
            </div>
            <div class="modal-footer">
                <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                <button class="delete-btn" onclick="confirmDeleteAnnouncement()">Delete</button>
            </div>
        </div>
    </div>

    <script>
    // Notification System Functions
    function showNotification(title, message, type = 'info', duration = 5000) {
        const notificationContainer = document.getElementById('notification-container');
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        // Set icon based on type
        let icon = 'information-line';
        if (type === 'success') icon = 'check-line';
        if (type === 'error') icon = 'error-warning-line';
        if (type === 'warning') icon = 'alert-line';
        
        // Create notification content
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
        
        // Force reflow before adding the 'show' class for proper animation
        notification.getBoundingClientRect();
        
        // Show notification with animation
        notification.classList.add('show');
        
        // Auto dismiss after duration (if specified)
        if (duration > 0) {
            setTimeout(() => closeNotification(notification), duration);
        }
        
        return notification;
    }
    
    function closeNotification(notification) {
        if (!notification) return;
        
        // If notification is a button, find its parent notification
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

    // Wait for DOM to be fully loaded before initializing charts
    document.addEventListener('DOMContentLoaded', function() {
        // Show welcome notification only on fresh login
        <?php if ($show_welcome_notification): ?>
        showNotification(
            "Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>", 
            "You're now logged in to the CCS Sit-In Administrator Dashboard.",
            "success"
        );
        <?php endif; ?>

        // Add notification CSS styles
        document.head.insertAdjacentHTML('beforeend', `
        <style>
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
                transform: translateX(0);
                opacity: 1;
                transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), 
                            opacity 0.3s ease;
                border-left: 4px solid #7556cc;
                animation: slideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            }
            
            @keyframes slideIn {
                0% {
                    transform: translateX(120%);
                    opacity: 0;
                }
                100% {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

             @keyframes slideOut {
                0% {
                    transform: translateX(0);
                    opacity: 1;
                }
                100% {
                    transform: translateX(120%);
                    opacity: 0;
                }
            }

            .notification.hide {
                animation: slideOut 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
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
                font-size: 18px;
                padding-top: 14px;
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
            
            /* Modal footer button styles */
            .modal-footer {
                display: flex;
                justify-content: flex-end;
                padding: 1rem;
                gap: 10px;
                border-top: 1px solid #e5e7eb;
            }
            
            .cancel-btn {
                background-color: #f3f4f6;
                color: #1f2937;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                font-weight: 500;
                cursor: pointer;
                transition: background-color 0.2s;
            }
            
            .cancel-btn:hover {
                background-color: #e5e7eb;
            }
            
            .delete-btn {
                background-color: #ef4444;
                color: white;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                font-weight: 500;
                cursor: pointer;
                transition: background-color 0.2s;
            }
            
            .delete-btn:hover {
                background-color: #dc2626;
            }
            
            /* Additional staggered animations for dashboard elements */
            .announcements-grid {
                opacity: 0;
                animation: fadeUp 0.7s ease-out 0.7s forwards;
            }
            
            .chart-card {
                opacity: 0;
                animation: fadeUp 0.7s ease-out 0.9s forwards;
            }
            
            .charts-grid {
                opacity: 0;
                animation: fadeUp 0.7s ease-out 1.1s forwards;
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
        </style>
        `);
        
        // Daily Usage Chart - New
        const dailyUsageCtx = document.getElementById('dailyUsageChart');
        if (dailyUsageCtx) {
            new Chart(dailyUsageCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Daily Student Count',
                        data: <?php echo json_encode($daily_usage_values); ?>,
                        backgroundColor: 'rgba(117, 86, 204, 0.2)',
                        borderColor: 'rgba(117, 86, 204, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: 'white',
                        pointBorderColor: 'rgba(117, 86, 204, 1)',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.raw} student${context.raw !== 1 ? 's' : ''}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        
        // Laboratory Usage Chart - New
        const labUsageCtx = document.getElementById('labUsageChart');
        if (labUsageCtx) {
            const labData = <?php echo json_encode(array_column($lab_usage_stats, 'count')); ?>;
            const hasLabData = labData && labData.length > 0 && labData.some(count => count > 0);
            
            if (!hasLabData) {
                const container = labUsageCtx.closest('.chart-container');
                container.innerHTML = '<div style="display:flex; height:100%; align-items:center; justify-content:center; color:#666; text-align:center;">No laboratory usage data available.<br>Data will appear once students start using laboratories.</div>';
            } else {
                new Chart(labUsageCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode(array_column($lab_usage_stats, 'label')); ?>,
                        datasets: [{
                            data: labData,
                            backgroundColor: [
                                'rgba(117, 86, 204, 0.8)',
                                'rgba(213, 105, 167, 0.8)',
                                'rgba(155, 95, 185, 0.8)',
                                'rgba(94, 114, 228, 0.8)',
                                'rgba(45, 206, 137, 0.8)',
                                'rgba(255, 184, 0, 0.8)',
                                'rgba(255, 97, 132, 0.8)'
                            ],
                            borderWidth: 2,
                            borderColor: 'white'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${context.label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
        
        // Hourly Traffic Chart - New
        const hourlyTrafficCtx = document.getElementById('hourlyTrafficChart');
        if (hourlyTrafficCtx) {
            const hourlyData = <?php echo json_encode(array_values($hourly_distribution)); ?>;
            const hasHourlyData = hourlyData && hourlyData.some(count => count > 0);
            
            if (!hasHourlyData) {
                const container = hourlyTrafficCtx.closest('.chart-container');
                container.innerHTML = '<div style="display:flex; height:100%; align-items:center; justify-content:center; color:#666; text-align:center;">No hourly traffic data available yet.<br>Data will appear as students use the facilities.</div>';
            } else {
                // Generate labels for 24-hour format
                const hourLabels = Array.from({length: 24}, (_, i) => {
                    const hour = i % 12 || 12;
                    const ampm = i < 12 ? 'AM' : 'PM';
                    return `${hour} ${ampm}`;
                });
                
                new Chart(hourlyTrafficCtx, {
                    type: 'bar',
                    data: {
                        labels: hourLabels,
                        datasets: [{
                            data: hourlyData,
                            backgroundColor: (context) => {
                                const value = context.dataset.data[context.dataIndex];
                                const max = Math.max(...context.dataset.data);
                                const alpha = 0.3 + (value / max) * 0.7; // Scale between 0.3 and 1.0
                                return `rgba(117, 86, 204, ${alpha})`;
                            },
                            borderRadius: 4,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        return `${value} student${value !== 1 ? 's' : ''}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    precision: 0
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        }
                    }
                });
            }
        }
        
        // Your existing charts
        // Overall Statistics Chart
        const statsCtx = document.getElementById('statsChart');
        if (statsCtx) {
            new Chart(statsCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Total Students', 'Current Students', 'Total Sit-Ins', 'Avg. Daily Usage'],
                    datasets: [{
                        data: [
                            <?php echo $stats['total_students']; ?>,
                            <?php echo $stats['current_sitin']; ?>,
                            <?php echo $stats['total_sitin']; ?>,
                            <?php echo $stats['avg_daily_usage']; ?>
                        ],
                        backgroundColor: [
                            'rgba(117,86,204,0.8)',
                            'rgba(213,105,167,0.8)',
                            'rgba(155,95,185,0.8)',
                            'rgba(94,114,228,0.8)'
                        ],
                        borderRadius: 6,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Purpose Distribution Chart - Enhanced with better tooltips and formatting
        const purposeCtx = document.getElementById('purposeChart');
        if (purposeCtx) {
            const purposeData = <?php echo json_encode(array_column($purpose_stats, 'count')); ?>;
            const hasPurposeData = purposeData && purposeData.length > 0 && purposeData.some(count => count > 0);
            
            if (!hasPurposeData) {
                const container = purposeCtx.closest('.chart-container');
                container.innerHTML = '<div style="display:flex; height:100%; align-items:center; justify-content:center; color:#666; text-align:center;">No sit-in purpose data available.<br>Students need to specify purposes when sitting in.</div>';
            } else {
                new Chart(purposeCtx, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($purpose_stats, 'label')); ?>,
                        datasets: [{
                            data: purposeData,
                            backgroundColor: [
                                'rgba(117,86,204,0.8)',
                                'rgba(213,105,167,0.8)',
                                'rgba(155,95,185,0.8)',
                                'rgba(94,114,228,0.8)',
                                'rgba(45,206,137,0.8)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.raw + ' time' + (context.raw != 1 ? 's' : '') + ' selected';
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        // Year Level Distribution Chart - Changed to pie chart
        const yearLevelCtx = document.getElementById('yearLevelChart');
        if (yearLevelCtx) {
            const yearLevelData = [
                <?php echo $year_level_stats['1st Year']; ?>,
                <?php echo $year_level_stats['2nd Year']; ?>,
                <?php echo $year_level_stats['3rd Year']; ?>,
                <?php echo $year_level_stats['4th Year']; ?>
            ];
            
            const hasYearLevelData = yearLevelData.some(count => count > 0);
            
            if (!hasYearLevelData) {
                const container = yearLevelCtx.closest('.chart-container');
                container.innerHTML = '<div style="display:flex; height:100%; align-items:center; justify-content:center; color:#666; text-align:center;">No year level data available.<br>Please ensure student year levels are properly set.</div>';
            } else {
                new Chart(yearLevelCtx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: ['1st Year', '2nd Year', '3rd Year', '4th Year'],
                        datasets: [{
                            data: yearLevelData,
                            backgroundColor: [
                                'rgba(117,86,204,0.8)',
                                'rgba(213,105,167,0.8)',
                                'rgba(155,95,185,0.8)',
                                'rgba(94,114,228,0.8)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.raw + ' student' + (context.raw !== 1 ? 's' : '');
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                    });
            }
        }

    // Announcement Form Handler
    document.getElementById('announcementForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('../admin/process_announcement.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                document.getElementById('content').value = ''; // Clear the form
                showNotification("Success", "Announcement posted successfully", "success");
                // Reload the page after a short delay to show the new announcement
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotification("Error", "Error posting announcement", "error");
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification("Error", "An error occurred while posting the announcement", "error");
        }
    });

    // Delete Announcement Handler - Updated to use modal
    function deleteAnnouncement(id) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteModal').style.display = 'block';
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    async function confirmDeleteAnnouncement() {
        const id = document.getElementById('delete_id').value;
        
        try {
            const response = await fetch('../admin/delete_announcement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            });
            
            const result = await response.json();
            if (result.success) {
                closeDeleteModal();
                document.querySelector(`[data-id="${id}"]`).remove();
                showNotification("Success", "Announcement deleted successfully", "success");
            } else {
                showNotification("Error", "Error deleting announcement", "error");
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification("Error", "An error occurred while deleting the announcement", "error");
        }
    }

    // Modal Functions - Edit Modal
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    function editAnnouncement(id, title, content) {
        // Populate the form
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_content').value = content;
        // Show the modal
        document.getElementById('editModal').style.display = 'block';
    }

    // Edit Announcement Form Handler - Updated to use notifications
    document.getElementById('editAnnouncementForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            id: formData.get('id'),
            title: formData.get('title'),
            content: formData.get('content')
        };
        
        try {
            const response = await fetch('../admin/update_announcement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (result.success) {
                closeModal();
                showNotification("Success", "Announcement updated successfully", "success");
                // Reload the page after a short delay to show updated content
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotification("Error", "Error updating announcement", "error");
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification("Error", "An error occurred while updating the announcement", "error");
        }
    });

    // Close modal when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target == editModal) {
                closeModal();
            }
            
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }
    });
    </script>
</body>
</html>