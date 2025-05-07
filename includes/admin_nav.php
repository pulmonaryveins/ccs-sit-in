<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Get the current page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="nav-container">
    <div class="nav-wrapper">
        <!-- Left side - Logo -->
        <div class="nav-logo">
            <a href="admin_dashboard.php">
                <img src="../assets/images/logo/whiteLogo.png" alt="Logo">
                <span>CCS Admin</span>
            </a>
        </div>
        
        <!-- Center - Navigation -->
        <nav class="nav-links">
            <a href="admin_dashboard.php" class="nav-link <?php echo $current_page === 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="ri-dashboard-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_reservations.php" class="nav-link <?php echo $current_page === 'admin_reservations.php' ? 'active' : ''; ?>">
                <i class="ri-calendar-check-line"></i>
                <span>Reservations</span>
            </a>
            <a href="admin_students.php" class="nav-link <?php echo $current_page === 'admin_students.php' ? 'active' : ''; ?>">
                <i class="ri-user-line"></i>
                <span>Students</span>
            </a>
            <a href="admin_labs.php" class="nav-link <?php echo $current_page === 'admin_labs.php' ? 'active' : ''; ?>">
                <i class="ri-computer-line"></i>
                <span>Laboratories</span>
            </a>
            <a href="admin_settings.php" class="nav-link <?php echo $current_page === 'admin_settings.php' ? 'active' : ''; ?>">
                <i class="ri-settings-line"></i>
                <span>Settings</span>
            </a>
        </nav>
        
        <!-- Right side - Actions -->
        <div class="nav-actions">
            <!-- Add notification icon with dropdown -->
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
            
            <div class="admin-dropdown">
                <div class="admin-trigger">
                    <img src="../assets/images/logo/admin-avatar.png" alt="Admin">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="admin-menu">
                    <a href="admin_profile.php">
                        <i class="ri-user-line"></i>
                        <span>Profile</span>
                    </a>
                    <a href="../auth/logout.php">
                        <i class="ri-logout-box-line"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
