<?php
session_start();

// Check if user is logged in (either admin or student)
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db_connect.php';

// Get top 3 most active students based on sit-in count AND points
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
          COUNT(DISTINCT CASE WHEN r.id IS NOT NULL THEN r.id END) as total_sitins,
          (3 - (u.points % 3)) as points_until_next_session
          FROM users u
          LEFT JOIN sit_ins s ON u.idno = s.idno
          LEFT JOIN reservations r ON u.idno = r.idno
          GROUP BY u.idno
          ORDER BY total_sitins DESC, u.points DESC";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // If points is divisible by 3, set points_until_next_session to 3
        if ($row['points'] % 3 == 0 && $row['points'] > 0) {
            $row['points_until_next_session'] = 3;
        }
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
    <link rel="stylesheet" href="../assets/css/nav.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="../assets/javascript/nav.js" defer></script>
    <script src="../assets/javascript/notification.js" defer></script>
    <script src="../assets/javascript/admin_notifications.js" defer></script>
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

        .notification-icon i {
            font-size: 18px;
            padding-top: 14px;
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
        
        /* Points progress styles */
        .points-progress {
            margin-top: 4px;
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .points-progress-text {
            font-style: italic;
        }
        
        /* Action buttons styles */
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: left;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        
        .action-btn.primary {
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            color: white;
        }
        
        .action-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(117, 86, 204, 0.3);
        }
        
        .action-btn.danger {
            background: #fff;
            color: #ef4444;
            border: 1px solid #ef4444;
        }
        
        .action-btn.danger:hover {
            background: #fef2f2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.15);
        }
        
        .action-btn i {
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .action-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div id="notification-container"></div>
    
    <?php include '../view/nav.php'; ?>

    <div class="content-wrapper">
        <!-- Leaderboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="ri-trophy-line"></i>
                <span>Student Leaderboard</span>
            </div>  
        </div>

        <!-- Tabs Section -->
        <div class="filter-tabs">
            <div class="filter-tab active" data-target="all-students">All Students</div>
            <?php if (isset($_SESSION['admin_logged_in'])): ?>
            <div class="filter-tab" data-target="students-points">Student's Points</div>
            <?php endif; ?>
        </div>

        <!-- Top 3 Students Section -->
        <div class="top-students-section view-section active">
            <h2>Top Active Students</h2>
            <div class="top-students-grid">
                <?php 
                $positions = ['second', 'first', 'third'];
                $ranks = ['2', '1', '3']; // Change emoji icons to rank numbers
                
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
                    <div class="top-student <?php echo $positions[$index]; ?>">
                        <div class="position-icon"><?php echo $ranks[$index]; ?></div>
                        <div class="student-avatar">
                            <img src="<?php echo isset($student['profile_image']) && $student['profile_image'] 
                                ? htmlspecialchars($student['profile_image']) 
                                : '../assets/images/logo/AVATAR.png'; ?>" 
                                alt="Student Profile" onerror="this.src='../assets/images/logo/AVATAR.png'">
                        </div>
                        <div class="student-name">
                            <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>
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
        <div id="all-students" class="view-container active">   
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
                            <th class="text-center">Progession</th>
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
                                <tr>
                                    <td class="text-center"><?php echo $rank + 1; ?></td>
                                    <td class="font-mono"><?php echo htmlspecialchars($student['idno']); ?></td>
                                    <td><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></td>
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
                                    <td class="text-center">
                                        <div class="points-progress-container">
                                            <div class="points-progress-bar">
                                                <div class="points-progress-fill" style="width: <?php echo ($student['points'] % 3 == 0 && $student['points'] > 0) ? '100' : (($student['points'] % 3) / 3 * 100); ?>%">
                                                </div>
                                            </div>
                                            <div class="points-progress-text">
                                                <?php echo $student['points'] % 3; ?>/3 points
                                            </div>
                                        </div>
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

        <?php if (isset($_SESSION['admin_logged_in'])): ?>
        <!-- Student's Points Container (Admin Only) -->
        <div id="students-points" class="view-container">
            <div class="table-container">
            <div class="container-header">
                <div class="header-content">
                    <div class="header-left">
                        <h2><i class="ri-star-line"></i> Student Points Management</h2>
                        <p>Add or manage points for students in the CCS Sit-In system</p>
                    </div>
                    <div class="header-right">
                        <div class="search-container">
                            <input type="text" id="studentSearchPoints" class="search-input" placeholder="Search students...">
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
                            <th>ID Number</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th class="text-center">Points</th>
                            <th class="text-center">Sessions</th>
                            <th class="text-center">Progression</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_students)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <div class="empty-state-content">
                                        <i class="ri-user-search-line"></i>
                                        <p>No student records found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_students as $student): ?>
                                <tr>
                                    <td class="font-mono"><?php echo htmlspecialchars($student['idno']); ?></td>
                                    <td><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                                    <td><?php echo formatYearLevel($student['year']); ?></td>
                                    <td class="text-center">
                                        <span class="points-badge"><?php echo $student['points']; ?> points</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="sessions-badge <?php echo $student['remaining_sessions'] <= 5 ? 'sessions-low' : ($student['remaining_sessions'] <= 10 ? 'sessions-medium' : ''); ?>">
                                            <?php echo $student['remaining_sessions']; ?> sessions
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="points-progress-container">
                                            <div class="points-progress-bar">
                                                <div class="points-progress-fill" style="width: <?php echo ($student['points'] % 3 == 0 && $student['points'] > 0) ? '100' : (($student['points'] % 3) / 3 * 100); ?>%">
                                                </div>
                                            </div>
                                            <div class="points-progress-text">
                                                <?php echo $student['points'] % 3; ?>/3 points
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center action-buttons">
                                        <button class="action-btn primary" onclick="addPoint('<?php echo $student['idno']; ?>', '<?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>')">
                                            <i class="ri-add-circle-line"></i>
                                        </button>
                                        <button class="action-btn danger" onclick="clearPoints('<?php echo $student['idno']; ?>', '<?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>')">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination for Student Points -->
                <div class="pagination-container">
                <div class="entries-selector">
                            <label for="points-students-entries">Show</label>
                            <select id="points-students-entries" class="entries-select">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <label for="points-students-entries">entries</label>
                        </div>
                    <div class="pagination-info">
                        Showing <span id="points-students-start">1</span>-<span id="points-students-end">10</span> of <span id="points-students-total"><?php echo count($all_students); ?></span> students
                    </div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" id="points-students-prev" disabled>
                            <i class="ri-arrow-left-s-line"></i> Previous
                        </button>
                        <div class="pagination-pages" id="points-students-pages"></div>
                        <button class="pagination-btn" id="points-students-next">
                            Next <i class="ri-arrow-right-s-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Confirmation Modal -->
    <div class="confirm-modal-backdrop" id="confirmModal" style="display: none;">
        <div class="confirm-modal">
            <div class="confirm-modal-header">
                <h3 class="confirm-modal-title" id="confirm-title">Confirm Action</h3>
            </div>
            <div class="confirm-modal-body" id="confirm-message">
                Are you sure you want to perform this action?
            </div>
            <div class="confirm-modal-footer">
                <button class="confirm-btn-cancel" onclick="hideConfirmModal()">Cancel</button>
                <button class="confirm-btn-confirm" id="confirm-button">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // Improved notification functions (not inside DOMContentLoaded)
        function showNotification(title, message, type = 'info', duration = 5000) {
            const notificationContainer = document.getElementById('notification-container');
            if (!notificationContainer) {
                console.error('Notification container not found');
                return;
            }
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            // Set icon based on type
            let iconClass = 'ri-information-line';
            if (type === 'success') iconClass = 'ri-check-line';
            if (type === 'error') iconClass = 'ri-error-warning-line';
            if (type === 'warning') iconClass = 'ri-alert-line';
            
            notification.innerHTML = `
                <div class="notification-icon">
                    <i class="${iconClass}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close" onclick="closeNotification(this)">×</button>
            `;
            
            // Add to container
            notificationContainer.appendChild(notification);
            
            // Force reflow to enable animation
            notification.getBoundingClientRect();
            notification.classList.add('show');
            
            // Auto-remove after duration
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
            
            notification.classList.remove('show');
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 400);
        }
        
        // Make confirm modal functions globally available
        function showConfirmModal(message, title, callback) {
            document.getElementById('confirm-message').textContent = message;
            document.getElementById('confirm-title').textContent = title;
            
            const confirmButton = document.getElementById('confirm-button');
            confirmButton.onclick = function() {
                hideConfirmModal();
                callback(true);
            };
            
            document.getElementById('confirmModal').style.display = 'flex';
        }
        
        function hideConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }
        
        // Improved point functions
        function addPoint(idno, studentName) {
            showConfirmModal(
                `Are you sure you want to add 1 point to ${studentName}?`,
                "Confirm Point Addition",
                (confirmed) => {
                    if (confirmed) {
                        // Show pending notification
                        const pendingNotification = showNotification(
                            "Processing", 
                            "Adding point to student...",
                            "info",
                            0
                        );
                        
                        // Send request to add point
                        fetch('../controller/add_student_point.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `idno=${encodeURIComponent(idno)}`
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Remove pending notification
                            closeNotification(pendingNotification);
                            
                            if (data.success) {
                                let message = data.message;
                                
                                // Show success notification
                                showNotification(
                                    "Success", 
                                    message,
                                    "success"
                                );
                                
                                // Reload the page after a short delay
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                showNotification(
                                    "Error", 
                                    data.message || "Failed to add point",
                                    "error"
                                );
                            }
                        })
                        .catch(error => {
                            // Remove pending notification
                            closeNotification(pendingNotification);
                            
                            console.error('Error:', error);
                            showNotification(
                                "Error", 
                                "An error occurred while processing your request.",
                                "error"
                            );
                        });
                    }
                }
            );
        }
        
        function clearPoints(idno, studentName) {
            showConfirmModal(
                `Are you sure you want to clear all points for ${studentName}? This action cannot be undone.`,
                "Confirm Clear Points",
                (confirmed) => {
                    if (confirmed) {
                        // Show pending notification
                        const pendingNotification = showNotification(
                            "Processing", 
                            "Clearing student points...",
                            "info",
                            0
                        );
                        
                        // Send request to clear points
                        fetch('../controller/clear_student_points.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `idno=${encodeURIComponent(idno)}`
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Remove pending notification
                            closeNotification(pendingNotification);
                            
                            if (data.success) {
                                // Show success notification
                                showNotification(
                                    "Success", 
                                    data.message || "Student points have been cleared successfully",
                                    "success"
                                );
                                
                                // Reload the page after a short delay
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                showNotification(
                                    "Error", 
                                    data.message || "Failed to clear points",
                                    "error"
                                );
                            }
                        })
                        .catch(error => {
                            // Remove pending notification
                            closeNotification(pendingNotification);
                            
                            console.error('Error:', error);
                            showNotification(
                                "Error", 
                                "An error occurred while processing your request.",
                                "error"
                            );
                        });
                    }
                }
            );
        }
        
        document.addEventListener('DOMContentLoaded', function() {
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
                    
                    // Show/hide the top students section based on the selected tab
                    const topStudentsSection = document.querySelector('.top-students-section');
                    if (targetId === 'students-points') {
                        topStudentsSection.classList.remove('active');
                    } else {
                        topStudentsSection.classList.add('active');
                    }
                });
            });

            // Add search functionality for the leaderboard
            const studentSearchAll = document.getElementById('studentSearchAll');
            const studentSearchPoints = document.getElementById('studentSearchPoints');
            
            // Search function for re-use
            function setupSearchForTable(searchInput, tableSelector, colIndexes) {
                if (!searchInput) return;
                
                searchInput.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase().trim();
                    const tableBody = document.querySelector(tableSelector);
                    
                    if (!tableBody) return;
                    
                    const rows = tableBody.querySelectorAll('tr');
                    let hasVisibleRows = false;
                    
                    // Check if there's an empty search row already
                    const emptySearch = tableBody.querySelector('.empty-search');
                    
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
                    if (!hasVisibleRows && searchValue !== '') {
                        if (!emptySearch) {
                            const colspan = tableSelector.includes('all-students') ? 8 : 7;
                            const emptyRow = document.createElement('tr');
                            emptyRow.classList.add('empty-search');
                            emptyRow.innerHTML = `
                                <td colspan="${colspan}" class="empty-state">
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
            
            // Setup search for each table
            setupSearchForTable(studentSearchAll, '#all-students .modern-table tbody', [2, 3, 4]);
            setupSearchForTable(studentSearchPoints, '#students-points .modern-table tbody', [1, 2, 3]);
            
            // Clear search when switching tabs
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    if (studentSearchAll) studentSearchAll.value = '';
                    if (studentSearchPoints) studentSearchPoints.value = '';
                    
                    // Trigger keyup to reset table view
                    if (studentSearchAll) studentSearchAll.dispatchEvent(new Event('keyup'));
                    if (studentSearchPoints) studentSearchPoints.dispatchEvent(new Event('keyup'));
                });
            });

            // Add CSS for the entries selector
            document.head.insertAdjacentHTML('beforeend', `
                <style>
                    /* ...existing styles... */
                    
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
                </style>
            `);
            
            // Pagination functionality with entries per page option
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
                            // Remove any existing animation classes
                            row.style.removeProperty('animation-delay');
                            
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
            
            // Initialize pagination with entries per page
            const resetAllStudentsPagination = setupPagination(
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
            
            const resetPointsPagination = setupPagination(
                '#students-points .modern-table', 
                {
                    prev: 'points-students-prev',
                    next: 'points-students-next',
                    pages: 'points-students-pages',
                    start: 'points-students-start',
                    end: 'points-students-end',
                    total: 'points-students-total',
                    entries: 'points-students-entries'
                }
            );
            
            // Update search functions to work with pagination
            function setupSearchForTable(searchInput, tableSelector, colIndexes, resetPaginationFn) {
                if (!searchInput) return;
                
                searchInput.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase().trim();
                    const tableBody = document.querySelector(tableSelector);
                    
                    if (!tableBody) return;
                    
                    const rows = tableBody.querySelectorAll('tr');
                    let hasVisibleRows = false;
                    
                    // Remove existing empty search row if any
                    const emptySearch = tableBody.querySelector('.empty-search');
                    if (emptySearch) tableBody.removeChild(emptySearch);
                    
                    // Show all rows first
                    rows.forEach(row => {
                        if (!row.querySelector('.empty-state') && !row.classList.contains('empty-search')) {
                            // Remove any display: none style from previous pagination
                            row.style.display = '';
                            
                            let match = false;
                            
                            // Check each column we want to search in
                            colIndexes.forEach(idx => {
                                const cell = row.querySelector(`td:nth-child(${idx})`);
                                if (cell && cell.textContent.toLowerCase().includes(searchValue)) {
                                    match = true;
                                }
                            });
                            
                            // Mark rows that don't match for later filtering
                            if (match || searchValue === '') {
                                row.dataset.searchMatch = 'true';
                                hasVisibleRows = true;
                            } else {
                                row.dataset.searchMatch = 'false';
                                row.style.display = 'none';
                            }
                        }
                    });
                    
                    // Show empty state if no results
                    if (!hasVisibleRows && searchValue !== '') {
                        const colspan = tableSelector.includes('all-students') ? 8 : 7;
                        const emptyRow = document.createElement('tr');
                        emptyRow.classList.add('empty-search');
                        emptyRow.innerHTML = `
                            <td colspan="${colspan}" class="empty-state">
                                <div class="empty-state-content">
                                    <i class="ri-search-line"></i>
                                    <p>No students match your search</p>
                                </div>
                            </td>
                        `;
                        tableBody.appendChild(emptyRow);
                    } else if (searchValue === '' && resetPaginationFn) {
                        // If search is cleared, reset pagination
                        resetPaginationFn();
                    }
                });
            }
            
            // Setup improved search for each table
            setupSearchForTable(studentSearchAll, '#all-students .modern-table tbody', [2, 3, 4], resetAllStudentsPagination);
            setupSearchForTable(studentSearchPoints, '#students-points .modern-table tbody', [1, 2, 3], resetPointsPagination);
            
            // Clear search and reset pagination when switching tabs
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    
                    if (targetId === 'all-students') {
                        if (studentSearchAll) {
                            studentSearchAll.value = '';
                            studentSearchAll.dispatchEvent(new Event('keyup'));
                        }
                        if (resetAllStudentsPagination) resetAllStudentsPagination();
                    } else if (targetId === 'students-points') {
                        if (studentSearchPoints) {
                            studentSearchPoints.value = '';
                            studentSearchPoints.dispatchEvent(new Event('keyup'));
                        }
                        if (resetPointsPagination) resetPointsPagination();
                    }
                });
            });

            // Add CSS for the improved progression bar
            document.head.insertAdjacentHTML('beforeend', `
                <style>
                    // ...existing styles...
                    
                    /* Improved Points progression bar styles */
                    .points-progress-container {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 6px;
                        width: 100%;
                        padding: 5px 0;
                    }
                    
                    .points-progress-bar {
                        width: 100%;
                        max-width: 100px;
                        height: 6px;
                        background: #edf2f7;
                        border-radius: 10px;
                        overflow: hidden;
                        position: relative;
                        box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
                    }
                    
                    .points-progress-fill {
                        height: 100%;
                        background: linear-gradient(90deg, #a78bfa, #7556cc);
                        border-radius: 10px;
                        transition: width 0.7s cubic-bezier(0.34, 1.56, 0.64, 1);
                        position: relative;
                        box-shadow: 0 1px 2px rgba(117, 86, 204, 0.3);
                    }
                    
                    .points-progress-fill.complete {
                        background: linear-gradient(90deg, #10b981, #34d399);
                        box-shadow: 0 1px 3px rgba(16, 185, 129, 0.3);
                    }
                    
                    .points-progress-text {
                        font-size: 0.7rem;
                        color: #64748b;
                        font-weight: 600;
                        letter-spacing: 0.03em;
                        opacity: 0.8;
                        transition: opacity 0.3s ease;
                        display: flex;
                        align-items: center;
                        gap: 3px;
                    }
                    
                    .points-progress-text i {
                        font-size: 0.75rem;
                        color: #7556cc;
                    }
                    
                    tr:hover .points-progress-text {
                        opacity: 1;
                    }
                    
                    /* Points markers to show progress segments */
                    .points-progress-bar::before,
                    .points-progress-bar::after {
                        content: '';
                        position: absolute;
                        width: 1px;
                        height: 4px;
                        background-color: rgba(255, 255, 255, 0.5);
                        top: 1px;
                    }
                    
                    .points-progress-bar::before {
                        left: 33.33%;
                    }
                    
                    .points-progress-bar::after {
                        left: 66.66%;
                    }
                    
                    /* Shimmer effect on hover */
                    tr:hover .points-progress-fill::after {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: -100%;
                        width: 100%;
                        height: 100%;
                        background: linear-gradient(
                            90deg,
                            transparent,
                            rgba(255, 255, 255, 0.2),
                            transparent
                        );
                        animation: shimmer 1.5s infinite;
                    }
                    
                    @keyframes shimmer {
                        100% {
                            left: 100%;
                        }
                    }
                    
                    /* Responsive adjustments */
                    @media (max-width: 768px) {
                        .points-progress-bar {
                            max-width: 80px;
                        }
                    }
                </style>
            `);

            // Replace the points-progress-container elements with improved design
            document.querySelectorAll('.points-progress-container').forEach(container => {
                const progressFill = container.querySelector('.points-progress-fill');
                const progressText = container.querySelector('.points-progress-text');
                
                if (progressFill && progressText) {
                    // Extract current points value
                    const pointsText = progressText.textContent.trim();
                    const [current, total] = pointsText.split('/').map(num => parseInt(num));
                    
                    // Check if it's a complete segment
                    if (current === 0 && parseInt(progressFill.style.width) > 0) {
                        progressFill.classList.add('complete');
                    }
                    
                    // Update the text with icon
                    progressText.innerHTML = `<i class="ri-star-line"></i> ${pointsText}`;
                }
            });
            
            // Add animation for progress bars when they come into view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const progressBars = entry.target.querySelectorAll('.points-progress-fill');
                        progressBars.forEach(bar => {
                            // Force reflow for animation
                            const currentWidth = bar.style.width;
                            bar.style.width = '0';
                            setTimeout(() => {
                                bar.style.width = currentWidth;
                            }, 50);
                        });
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            // Observe tables
            document.querySelectorAll('.modern-table').forEach(table => {
                observer.observe(table);
            });

            // Add CSS for the improved headers
            document.head.insertAdjacentHTML('beforeend', `
                <style>
                    /* ...existing styles... */
                    
                    /* Container header styles consistency */
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
                    
                    /* ...existing styles... */
                </style>
            `);
        });
    </script>
    <style>
        /* Points progression bar styles */
        .points-progress-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            width: 100%;
        }
        
        .points-progress-bar {
            width: 100%;
            max-width: 120px;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .points-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #7556cc, #9556cc);
            border-radius: 4px;
            transition: width 0.5s ease;
            position: relative;
        }
        
        .points-progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg, 
                rgba(255,255,255,0.15) 25%, 
                transparent 25%, 
                transparent 50%, 
                rgba(255,255,255,0.15) 50%, 
                rgba(255,255,255,0.15) 75%, 
                transparent 75%, 
                transparent
            );
            background-size: 10px 10px;
            animation: progress-animation 1s linear infinite;
            border-radius: 4px;
        }
        
        @keyframes progress-animation {
            0% {
                background-position: 0 0;
            }
            100% {
                background-position: 10px 0;
            }
        }
        
        .points-progress-fill.complete {
            background: linear-gradient(90deg, #10b981, #34d399);
        }
        
        .points-progress-text {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
        }
        
        /* Add hover effect to rows */
        .modern-table tbody tr:hover .points-progress-fill::after {
            animation-duration: 0.5s;
        }
        
        /* Responsive adjustments for progress bar */
        @media (max-width: 768px) {
            .points-progress-bar {
                max-width: 80px;
            }
            
            .points-progress-text {
                font-size: 0.7rem;
            }
        }
    </style>
</body>
</html>
