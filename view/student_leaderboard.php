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
                <!-- Student Navigation -->
                <a href="student_dashboard.php" class="nav-link">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="student_leaderboard.php" class="nav-link active">
                    <i class="ri-trophy-line"></i>
                    <span>Leaderboard</span>
                </a>
                <a href="reserve.php" class="nav-link">
                    <i class="ri-calendar-check-line"></i>
                    <span>Reserve</span>
                </a>
                <a href="history.php" class="nav-link">
                    <i class="ri-history-line"></i>
                    <span>History</span>
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
                <i class="ri-trophy-line"></i>
                <span>Student Leaderboard</span>
            </div>  
        </div>
        
        <!-- My Ranking Section - Student Only -->
        <?php if ($current_student_rank > 0): ?>
        <?php 
            $current_student = null;
            foreach ($all_students as $student) {
                if ($student['idno'] == $current_student_idno) {
                    $current_student = $student;
                    break;
                }
            }
        ?>
        <div class="my-ranking">
            <h2><i class="ri-award-fill"></i> Your Current Ranking</h2>
            <div class="my-ranking-value">
                <div class="rank-number">
                    <?php echo $current_student_rank; ?><sup><?php echo ordinalSuffix($current_student_rank); ?></sup>
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
            
            // Initialize pagination for all students table
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
        });
    </script>
</body>
</html>
