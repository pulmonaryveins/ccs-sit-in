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

// Fetch resources
$resources = [];
$query = "SELECT * FROM resources ORDER BY created_at DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educational Resources</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/student_nav.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
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
            
            .resources-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                padding: 16px;
                gap: 16px;
            }
        }
        
        /* Notification system styles */
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
        
        .image-preview-placeholder {
            color: #94a3b8;
            font-size: 0.9rem;
            text-align: center;
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .image-preview-placeholder i {
            font-size: 2rem;
            margin-bottom: 8px;
            display: block;
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

    <!-- Content -->
    <div class="content-wrapper">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="ri-links-line"></i>
                <span>Educational Resources</span>
            </div>
        </div>
        
        <div class="table-wrapper">
            <div class="container-header">
                <div class="header-content">
                    <div class="header-left">
                        <h2><i class="ri-book-read-line"></i> Learning Resources</h2>
                        <p>Access educational materials and helpful links for your studies</p>
                    </div>
                    <div class="header-right">
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
                    <p>No resources available at the moment. Check back later!</p>
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
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
            
            // Search functionality for resources
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
            
            // Load notifications on load
            loadNotifications();
            
            // Check notifications periodically
            setInterval(loadNotifications, 30000);

            
        });
    </script>
</body>
</html>
