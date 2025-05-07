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

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Student Information</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/student_nav.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="../assets/javascript/nav.js" defer></script>
    <script src="../assets/javascript/notification.js"></script>
    <script src="../assets/javascript/student_notifications.js"></script>
    <!-- Removed SweetAlert2 script import as it's now redundant -->
    <style>
        .profile-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            animation: fadeInUp 0.6s ease-out 0.2s backwards;
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

    <!-- Add notification container -->
    <div class="backdrop" id="backdrop"></div>

    <div class="profile-panel" id="profile-panel">
        <div class="profile-content">
            <div class="profile-header">
                <h3>STUDENT INFORMATION</h3>
            </div>
            <div class="profile-body">
                <div class="profile-image-container">
                    <div class="profile-image">
                        <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : '../assets/images/logo/AVATAR.png'; ?>" 
                             alt="Profile Picture">
                    </div>
                    <div class="profile-name">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($user['lastname'] . ', ' . $user['firstname']); ?></h3>
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
                            <div class="detail-label">Course/Department</div>
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
                            <div class="detail-value sessions-count">
                                <?php echo isset($_SESSION['remaining_sessions']) ? $_SESSION['remaining_sessions'] : '30'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-page-container">
        <div class="profile-card">
            <div class="profile-header">
                <h2>Profile Settings</h2>
                <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <div class="tabs-container">
                <div class="tab-headers">
                    <button class="tab-btn active" data-tab="profile-tab">Edit Profile</button>
                    <button class="tab-btn" data-tab="password-tab">Change Password</button>
                </div>

                <div class="profile-content">
                    <!-- Profile Image Section - Visible in both tabs -->
                    <div class="profile-image-section">
                        <div class="profile-image">
                            <img src="<?php echo htmlspecialchars($user['profile_image'] ?? '../assets/images/logo/AVATAR.png'); ?>"
                                 alt="Profile Picture"
                                 id="profile-preview">
                            <form id="image-upload-form" class="upload-form">
                                <input type="file" id="profile-upload" name="profile_image" accept="image/*" class="hidden">
                                <button type="button" class="change-photo-btn" onclick="document.getElementById('profile-upload').click()">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- Edit Profile Tab -->
                        <div id="profile-tab" class="tab-pane active">
                            <form id="profile-form" class="profile-form">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Student ID</label>
                                        <input type="text" name="idno" value="<?php echo htmlspecialchars($user['idno']); ?>" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" value="<?php echo htmlspecialchars($user['lastname'] . ', ' . $user['firstname']); ?>" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter your email">
                                    </div>

                                    <div class="form-group">
                                        <label>Username</label>
                                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Course/Department</label>
                                        <select name="course">
                                            <?php
                                            $courses = [
                                                "BS-Information Technology",
                                                "BS-Computer Science",
                                                "COE",
                                                "CAS",
                                                "CHM",
                                                "CTE",
                                                "CCA",
                                                "CBA",
                                                "CCJ",
                                                "CON"
                                            ];
                                            
                                            foreach ($courses as $courseOption) {
                                                $selected = ($user['course'] === $courseOption) ? 'selected' : '';
                                                echo "<option value=\"" . htmlspecialchars($courseOption) . "\" $selected>" . 
                                                     htmlspecialchars($courseOption) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Year Level</label>
                                        <select name="year">
                                            <?php
                                            for ($i = 1; $i <= 4; $i++) {
                                                $selected = ($user['year'] == $i) ? 'selected' : '';
                                                echo "<option value=\"$i\" $selected>{$i}" . 
                                                    ($i == 1 ? 'st' : ($i == 2 ? 'nd' : ($i == 3 ? 'rd' : 'th'))) . 
                                                    " Year</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group full-width">
                                        <label>Home Address</label>
                                        <textarea name="address" rows="3" placeholder="Enter your complete address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="save-btn">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Change Password Tab -->
                        <div id="password-tab" class="tab-pane">
                            <form id="password-form" class="password-form">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Current Password</label>
                                        <input type="password" name="current_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" name="new_password" id="new_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm New Password</label>
                                        <input type="password" name="confirm_password" id="confirm_password" required>
                                        <small id="password-match-message"></small>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="save-btn">
                                        <i class="fas fa-key"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .profile-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            animation: fadeInUp 0.6s ease-out 0.2s backwards;
        }
    </style>

    <script>
        
            
            document.addEventListener('DOMContentLoaded', function() {
            // Initialize student notifications
                StudentNotifications.init();

            // Profile panel toggle functionality
            const profilePanel = document.getElementById('profile-panel');
            const backdrop = document.getElementById('backdrop');
            const profileTrigger = document.getElementById('profile-trigger');
            const passwordForm = document.getElementById('password-form');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatchMessage = document.getElementById('password-match-message');
            const profileForm = document.getElementById('profile-form');

            // Store original form values for comparison
            let originalProfileValues = {};

            // Function to toggle profile panel
            function toggleProfile(show) {
                profilePanel.classList.toggle('active', show);
                backdrop.classList.toggle('active', show);
                document.body.style.overflow = show ? 'hidden' : '';
            }

            // Auto open the panel
            setTimeout(() => toggleProfile(true), 100);

            // Close panel when clicking backdrop
            backdrop.addEventListener('click', () => toggleProfile(false));

            // Toggle panel when clicking profile trigger
            profileTrigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                toggleProfile(!profilePanel.classList.contains('active'));
            });

            // Close panel when clicking outside
            document.addEventListener('click', (e) => {
                if (profilePanel.classList.contains('active') && 
                    !profilePanel.contains(e.target) && 
                    !profileTrigger.contains(e.target)) {
                    toggleProfile(false);
                }
            });

            // Prevent panel close when clicking inside
            profilePanel.addEventListener('click', (e) => {
                e.stopPropagation();
            });

            // Store original profile form values
            const captureOriginalValues = () => {
                const formData = new FormData(profileForm);
                formData.forEach((value, key) => {
                    originalProfileValues[key] = value;
                });
            };
            
            // Capture initial values
            captureOriginalValues();

            // Reset password form
            passwordForm.reset();

            // Check if profile form has changes
            function hasProfileChanges() {
                const formData = new FormData(profileForm);
                let hasChanges = false;
                
                formData.forEach((value, key) => {
                    if (originalProfileValues[key] !== value) {
                        hasChanges = true;
                    }
                });
                
                return hasChanges;
            }

            // Check if passwords match
            function checkPasswordMatch() {
                if (confirmPassword.value === '') {
                    passwordMatchMessage.textContent = '';
                    passwordMatchMessage.className = '';
                } else if (newPassword.value === confirmPassword.value) {
                    passwordMatchMessage.textContent = 'Passwords match';
                    passwordMatchMessage.className = 'password-match success';
                } else {
                    passwordMatchMessage.textContent = 'Passwords do not match';
                    passwordMatchMessage.className = 'password-match error';
                }
            }

            // Add event listeners for password matching
            newPassword.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);

            // Notification display function
            function showNotification(message, type = 'success') {
                const notificationContainer = document.getElementById('notification-container');
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                
                // Create notification content
                notification.innerHTML = `
                    <div class="notification-icon">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    </div>
                    <div class="notification-message">${message}</div>
                    <button class="notification-close">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                // Add to container
                notificationContainer.appendChild(notification);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    notification.classList.add('fade-out');
                    setTimeout(() => notification.remove(), 500);
                }, 5000);
                
                // Close button event
                notification.querySelector('.notification-close').addEventListener('click', () => {
                    notification.classList.add('fade-out');
                    setTimeout(() => notification.remove(), 500);
                });
            }

            // Profile image upload handling
            const profileUpload = document.getElementById('profile-upload');
            const profilePreview = document.getElementById('profile-preview');

            profileUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const formData = new FormData();
                    formData.append('profile_image', file);

                    profilePreview.style.opacity = '0.5';
                    fetch('../profile/upload_image.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            profilePreview.src = data.image_path + '?t=' + new Date().getTime();
                            showNotification('Profile image successfully uploaded');
                        } else {
                            showNotification(data.message || 'Error uploading image', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error uploading image', 'error');
                    })
                    .finally(() => {
                        profilePreview.style.opacity = '1';
                    });
                }
            });

            // PROFILE FORM SUBMISSION HANDLER
            profileForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Check if any changes were made
                if (!hasProfileChanges()) {
                    showNotification('No changes were made to your profile', 'error');
                    return;
                }
                
                const formData = new FormData(this);
                
                try {
                    const response = await fetch('../profile/update_profile.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Show success notification
                        showNotification('Profile successfully updated');
                        
                        // Update original values to new values
                        captureOriginalValues();
                        
                        // Refresh the page after short delay
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        // Show error notification
                        showNotification(data.message || 'Error updating profile', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('An unexpected error occurred', 'error');
                }
            });

            // PASSWORD FORM SUBMISSION HANDLER
            passwordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Check if passwords match before submitting
                if (newPassword.value !== confirmPassword.value) {
                    showNotification('Passwords do not match', 'error');
                    return;
                }
                
                // Check if password fields are empty
                if (!newPassword.value || !document.querySelector('input[name="current_password"]').value) {
                    showNotification('Please enter all password fields', 'error');
                    return;
                }
                
                const formData = new FormData(this);
                
                try {
                    const response = await fetch('../profile/change_password.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Show success notification
                        showNotification('Password successfully updated');
                        
                        // Reset the form
                        passwordForm.reset();
                        passwordMatchMessage.textContent = '';
                        passwordMatchMessage.className = '';
                    } else {
                        // Show error notification
                        showNotification(data.message || 'Error updating password', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('An unexpected error occurred', 'error');
                }
            });

            // Tab switching functionality
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons and panes
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabPanes.forEach(p => p.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding pane
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });

        // Add CSS for notifications
        const style = document.createElement('style');
        style.textContent = `
            .notification-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 350px;
            }
            
            .notification {
                display: flex;
                align-items: center;
                padding: 12px 15px;
                border-radius: 8px;
                background-color: #fff;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
                margin-bottom: 10px;
                animation: slide-in 0.3s ease-out forwards;
                border-left: 5px solid;
            }
            
            .notification.success {
                border-left-color: #4CAF50;
            }
            
            .notification.error {
                border-left-color: #F44336;
            }
            
            .notification.success .notification-icon {
                color: #4CAF50;
            }
            
            .notification.error .notification-icon {
                color: #F44336;
            }
            
            .notification-message {
                flex: 1;
                font-size: 14px;
            }
            
            .notification-close {
                background: none;
                border: none;
                cursor: pointer;
                font-size: 14px;
                color: #777;
                padding: 0;
                margin-left: 10px;
            }
            
            .notification-close:hover {
                color: #333;
            }
            
            .notification.fade-out {
                opacity: 0;
                transform: translateX(30px);
                transition: opacity 0.3s ease, transform 0.3s ease;
            }
            
            @keyframes slide-in {
                from {
                    opacity: 0;
                    transform: translateX(30px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            .tabs-container {
                display: flex;
                flex-direction: column;
                width: 100%;
            }
            
            .tab-headers {
                display: flex;
                border-bottom: 1px solid #ddd;
                margin-bottom: 20px;
            }
            
            .tab-btn {
                font-family: Segoe UI, sans-serif;
                padding: 12px 20px;
                background: transparent;
                border: none;
                border-bottom: 3px solid transparent;
                color: #718096;
                font-weight: 500;
                font-size: 0.95rem;
                cursor: pointer;
                transition: all 0.3s;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .tab-btn:hover {
                color: #7556cc;
            }
            
            .tab-btn.active {
                color: #7556cc;
                border-bottom-color: #7556cc;
            }
            
            .tab-btn i {
                font-size: 16px;
            }
            
            .tab-content {
                flex: 1;
            }
            
            .tab-pane {
                display: none;
                animation: fadeIn 0.3s ease-in-out;
            }
            
            .tab-pane.active {
                display: block;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            /* Profile image section adjustments */
            .profile-image-section {
                display: flex;
                justify-content: center;
                margin-bottom: 25px;
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                .tab-headers {
                    flex-direction: row;
                    overflow-x: auto;
                }
                
                .tab-btn {
                    flex: 1;
                    white-space: nowrap;
                    padding: 10px 15px;
                    font-size: 14px;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
