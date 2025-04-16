<?php
session_start();

// Check if user is logged in (either admin or student)
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['username'])) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
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
                <a href="admin_dashboard.php" class="nav-link">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="leaderboard.php" class="nav-link active">
                    <i class="ri-trophy-line"></i>
                    <span>Leaderboards</span>
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

    <div class="content-wrapper">
        <!-- Leaderboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                CCS Leaderboard
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
                $icons = ['🥈', '🥇', '🥉'];
                
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
                        <div class="position-icon"><?php echo $icons[$index]; ?></div>
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
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                                        <button class="add-point-btn" onclick="addPoint('<?php echo $student['idno']; ?>', '<?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>')">
                                            <i class="ri-add-circle-line"></i> Add Point
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Notification Container -->
    <div id="notification-container"></div>

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
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
                        border-left: 4px solid #7556cc;
                        animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
                    }
                    
                    .notification.removing {
                        animation: slideOut 0.4s forwards;
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
                    
                    .notification-icon {
                        flex-shrink: 0;
                        width: 24px;
                        height: 24px;
                        margin-right: 12px;
                        margin-top: 2px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    .notification-content {
                        flex-grow: 1;
                    }
                    
                    .notification-title {
                        font-weight: 600;
                        font-size: 1rem;
                        margin-bottom: 0.25rem;
                        color: #1e293b;
                    }
                    
                    .notification-message {
                        font-size: 0.875rem;
                        color: #64748b;
                        line-height: 1.4;
                    }
                    
                    .notification-close {
                        background: none;
                        border: none;
                        cursor: pointer;
                        color: #9ca3af;
                        margin-left: 12px;
                        padding: 0;
                        line-height: 1;
                        font-size: 18px;
                    }
                    
                    .notification-close:hover {
                        color: #4b5563;
                    }
                    
                    .notification.success {
                        border-left-color: #10b981;
                    }
                    
                    .notification.error {
                        border-left-color: #ef4444;
                    }
                    
                    .notification.warning {
                        border-left-color: #f59e0b;
                    }
                    
                    .notification.info {
                        border-left-color: #3b82f6;
                    }
                    
                    .notification.success .notification-icon {
                        color: #10b981;
                    }
                    
                    .notification.error .notification-icon {
                        color: #ef4444;
                    }
                    
                    .notification.warning .notification-icon {
                        color: #f59e0b;
                    }
                    
                    .notification.info .notification-icon {
                        color: #3b82f6;
                    }
                    
                    .view-section {
                        display: none;
                    }
                    
                    .view-section.active {
                        display: block;
                    }
                    
                    /* Container headers */
                    .container-header {
                        margin-bottom: 1.5rem;
                        padding: 1.25rem;
                        background: white;
                        border-radius: 12px;
                    }
                    
                    .container-header h2 {
                        color: #1e293b;
                        font-size: 1.3rem;
                        font-weight: 600;
                        margin-bottom: 0.5rem;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                    }
                    
                    .container-header h2 i {
                        color: #7556cc;
                    }
                    
                    .container-header p {
                        color: #64748b;
                        font-size: 0.9rem;
                        margin: 0;
                    }
                    
                    /* Sit-ins badge */
                    .sitin-badge {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        min-width: 50px;
                        height: 24px;
                        background: rgba(59, 130, 246, 0.1);
                        color: #3b82f6;
                        border-radius: 12px;
                        font-weight: 500;
                        font-size: 0.8rem;
                        border: 1px solid rgba(59, 130, 246, 0.2);
                        padding: 0 8px;
                        transition: all 0.2s ease;
                    }
                    
                    .sitin-badge:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
                    }
                </style>
            `);

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
        });

        // Function to show notifications
        function showNotification(title, message, type = 'info', duration = 5000) {
            const notificationContainer = document.getElementById('notification-container');
            
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
            
            notification.classList.add('removing');
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 400);
        }

        // Confirm modal functions
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
        
        // Function to add a point to a student
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
    </script>
</body>
</html>
