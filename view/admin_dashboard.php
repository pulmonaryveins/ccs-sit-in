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
    'total_sitin' => 0
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

// Add this after the existing stats queries
// Get year level distribution
$year_level_stats = [
    '1st Year' => 0,
    '2nd Year' => 0,
    '3rd Year' => 0,
    '4th Year' => 0
];

$query = "SELECT year, COUNT(*) as count FROM users GROUP BY year ORDER BY year";
$result = $conn->query($query);
if ($result) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    </style>
</head>
<body>
    <!-- Add notification container after body tag -->
    <div id="notification-container"></div>
    
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
                <a href="admin_dashboard.php" class="nav-link active">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
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

    <div class="admin-dashboard">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                Administrator Dashboard
            </div>
        </div>
        
        <!-- Stats Grid - Modernized -->
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
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-item" data-id="<?php echo $announcement['id']; ?>">
                            <div class="announcement-title">
                                <i class="ri-notification-3-fill"></i>
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
                            <div class="announcement-details">
                                <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                                <span class="timestamp"><?php echo date('F d, Y', strtotime($announcement['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Overall Stats Chart -->
        <div class="chart-card" style="margin-bottom: 1.5rem;">
            <div class="chart-header">
                <i class="ri-bar-chart-box-line"></i>
                <span>Overall Statistics</span>
            </div>
            <div class="chart-container">
                <canvas id="statsChart"></canvas>
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
                    <button type="submit" class="edit-btn2">
                        <i class="ri-save-line"></i>
                        <span>Update Announcement</span>
                    </button>
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
        
        // Overall Statistics Chart
        const statsCtx = document.getElementById('statsChart');
        if (statsCtx) {
            new Chart(statsCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Total Students', 'Current Students', 'Total Sit-Ins'],
                    datasets: [{
                        data: [
                            <?php echo $stats['total_students']; ?>,
                            <?php echo $stats['current_sitin']; ?>,
                            <?php echo $stats['total_sitin']; ?>
                        ],
                        backgroundColor: [
                            'rgba(117,86,204,0.8)',
                            'rgba(213,105,167,0.8)',
                            'rgba(155,95,185,0.8)'
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
            // Debug output to verify data
            console.log('Purpose Stats:', <?php echo json_encode($purpose_stats); ?>);
            
            // Check if we have any data
            const purposeData = <?php echo json_encode(array_column($purpose_stats, 'count')); ?>;
            const hasPurposeData = purposeData && purposeData.length > 0 && purposeData.some(count => count > 0);
            
            if (!hasPurposeData) {
                // If no data, show a message
                const container = purposeCtx.closest('.chart-container');
                container.innerHTML = '<div style="display:flex; height:100%; align-items:center; justify-content:center; color:#666; text-align:center;">No sit-in purpose data available.<br>Students need to specify purposes when sitting in.</div>';
            } else {
                // Create the chart with existing data
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

        // Add this after the existing charts initialization
        
        // Year Level Distribution Chart - Changed to pie chart
        const yearLevelCtx = document.getElementById('yearLevelChart');
        if (yearLevelCtx) {
            new Chart(yearLevelCtx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['1st Year', '2nd Year', '3rd Year', '4th Year'],
                    datasets: [{
                        data: [
                            <?php echo $year_level_stats['1st Year']; ?>,
                            <?php echo $year_level_stats['2nd Year']; ?>,
                            <?php echo $year_level_stats['3rd Year']; ?>,
                            <?php echo $year_level_stats['4th Year']; ?>
                        ],
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
        
        // ...existing code...
    });

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
    </script>
</body>
</html>
