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
$sql = "SELECT idno, firstname, lastname, middlename, course, year, profile_image, email, address, remaining_sessions FROM users WHERE username = ?";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
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
        
        /* Enhanced announcement styles */
        .announcement-list {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 10px;
            scrollbar-width: thin;
            scrollbar-color: rgba(117, 86, 204, 0.5) transparent;
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
        
        .announcement-item {
            background-color: white;
            padding: 15px;
            transition: all 0.2s ease;
            border-left: #7556cc solid 4px;
        }

        .announcement-item:hover {
            transform: translateY(-2px);
        }
        
        .announcement-title {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .announcement-title i {
            color: #7556cc;
            font-size: 1.25rem;
            margin-right: 8px;
        }
        
        .announcement-title h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #334155;
            margin: 0;
        }
        
        .announcement-details {
            padding-left: 32px;
        }
        
        .announcement-details p {
            margin-bottom: 10px;
            color: #475569;
            line-height: 1.5;
        }
        
        .timestamp {
            display: block;
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: right;
        }
        
        /* Notification styles */
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
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="reservation.php" class="nav-link">
                    <i class="ri-calendar-line"></i>
                    <span>Reservation</span>
                </a>
                <a href="history.php" class="nav-link">
                    <i class="ri-history-line"></i>
                    <span>History</span>
                </a>
                <a href="profile.php" class="nav-link">
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

    <!-- Dashboard Grid Container -->
    <div class="dashboard-grid" style="margin-top: 80px;">
        <!-- Left Column - Announcements -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header centered-header">
                    <h2>CCS Announcements</h2>
                </div>
                <div class="profile-content">
                    <div class="announcement-list">
                        <?php if (empty($announcements)): ?>
                            <div class="announcement-item">
                                <div class="announcement-title">
                                    <i class="ri-information-line"></i>
                                    <h3>No Announcements</h3>
                                </div>
                                <div class="announcement-details">
                                    <p>There are no announcements at this time.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-item">
                                    <div class="announcement-title">
                                        <i class="ri-notification-3-fill"></i>
                                        <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                    </div>
                                    <div class="announcement-details">
                                        <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                                        <span class="timestamp"><?php echo date('F d, Y', strtotime($announcement['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Rules -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header centered-header">
                    <h2>Laboratory Rules and Regulations</h2>
                </div>
                <div class="profile-content rules-container">
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
    </div>
    
    <style>
        /* Update card height */
        .dashboard-column .profile-card {
            height: 750px; /* Increased from 700px */
        }

        /* Ensure consistent scrolling behavior */
        .rules-container {
            height: 700px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(117, 86, 204, 0.5) transparent;
            padding-right: 10px;
        }
        
        .rules-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .rules-container::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .rules-container::-webkit-scrollbar-thumb {
            background-color: rgba(117, 86, 204, 0.5);
            border-radius: 10px;
        }
    </style>

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
        // Show welcome notification only on fresh login
        <?php if ($show_welcome_notification): ?>
        showNotification(
            "Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!", 
            "You're now logged in to CCS Sit-In System.",
            "success"
        );
        <?php endif; ?>

        // Add notification styles
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
                transform: translateX(120%);
                opacity: 0;
                transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), 
                            opacity 0.3s ease;
                border-left: 4px solid #7556cc;
                animation: slideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            }
            
            @keyframes slideIn {
                0% {
                    transform: translateX(100%);
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
                    transform: translateX(100%);
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
            
            .notification.info { border-left-color: #3b82f6; }
            .notification.success { border-left-color: #10b981; }
            .notification.warning { border-left-color: #f59e0b; }
            .notification.error { border-left-color: #ef4444; }
            
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
            }
            
            .notification.info .notification-icon i { color: #3b82f6; }
            .notification.success .notification-icon i { color: #10b981; }
            .notification.warning .notification-icon i { color: #f59e0b; }
            .notification.error .notification-icon i { color: #ef4444; }
            
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
        `);
        
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
                    showNotification(
                        "Success", 
                        "All notifications marked as read",
                        "success",
                        3000
                    );
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        });
        
        // Load notifications on page load
        loadNotifications();
        
        // Set interval to refresh notifications (every 30 seconds)
        setInterval(loadNotifications, 30000);
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
        } catch (error) {
            console.error('Error updating profile:', error);
        }
    }

    // Update profile when panel is opened
    profileTrigger.addEventListener('click', updateProfilePanel);
    </script>
</body>
</html>