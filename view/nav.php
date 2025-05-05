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
            <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="ri-dashboard-line"></i>
                <span>Dashboard</span>
            </a>
            
            <div class="nav-dropdown">
                <div class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['laboratories.php', 'resources.php']) ? 'active' : ''; ?>">
                    <i class="ri-computer-line"></i>
                    <span>Laboratory</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="laboratories.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'laboratories.php' ? 'active' : ''; ?>">
                        <i class="ri-calendar-line"></i>
                        <span>Schedules</span>
                    </a>
                    <a href="resources.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'resources.php' ? 'active' : ''; ?>">
                        <i class="ri-links-line"></i>
                        <span>Resources</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-dropdown">
                <div class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['sit-in.php', 'request.php']) ? 'active' : ''; ?>">
                    <i class="ri-map-pin-user-line"></i>
                    <span>Sit-In</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="sit-in.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'sit-in.php' ? 'active' : ''; ?>">
                        <i class="ri-user-location-line"></i>
                        <span>Student Sit-ins</span>
                    </a>
                    <a href="request.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'request.php' ? 'active' : ''; ?>">
                        <i class="ri-mail-check-line"></i>
                        <span>Requests</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-dropdown">
                <div class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['leaderboard.php', 'records.php', 'reports.php']) ? 'active' : ''; ?>">
                    <i class="ri-bar-chart-line"></i>
                    <span>Analytics</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="leaderboard.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'leaderboard.php' ? 'active' : ''; ?>">
                        <i class="ri-trophy-line"></i>
                        <span>Leaderboard</span>
                    </a>
                    <a href="records.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'records.php' ? 'active' : ''; ?>">
                        <i class="ri-database-2-line"></i>
                        <span>Records</span>
                    </a>
                    <a href="reports.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                        <i class="ri-file-text-line"></i>
                        <span>Overall Reports</span>
                    </a>
                </div>
            </div>
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

