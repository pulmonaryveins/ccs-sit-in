<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db_connect.php';
// Set timezone for proper time display
date_default_timezone_set('Asia/Manila'); // Adjust to your timezone if needed

// Fetch all students
$students = [];
$query = "SELECT * FROM users 
          ORDER BY lastname ASC";

$result = $conn->query($query);
if (!$result) {
    // Add error reporting
    echo "Database query error: " . $conn->error;
    exit;
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Include all user types for now, we can filter in the display if needed
        $students[] = $row;
    }
}

// Function to count used sessions for a student - keeping for reference but not using it
function countUsedSessions($idno, $conn) {
    $used_sessions = 0;
    
    // Count reservations
    $query = "SELECT COUNT(*) as count FROM reservations WHERE idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $idno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $used_sessions += $row['count'];
    }
    
    // Count direct sit-ins
    $query = "SELECT COUNT(*) as count FROM sit_ins WHERE idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $idno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $used_sessions += $row['count'];
    }
    
    return $used_sessions;
}

// Total allowed sessions per student
$total_allowed_sessions = 30;

// Fetch sit-in records from both reservations and sit_ins tables
$sitin_records = [];

// Fetch reservation records with remaining_sessions
$query_reservations = "SELECT r.*, u.firstname, u.lastname, u.idno, u.remaining_sessions, 'reservation' as source 
                       FROM reservations r
                       JOIN users u ON r.idno = u.idno
                       ORDER BY r.date DESC, r.time_in DESC";

$result = $conn->query($query_reservations);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sitin_records[] = $row;
    }
}

// Fetch direct sit-in records with remaining_sessions
$query_sitins = "SELECT s.*, u.firstname, u.lastname, u.idno, u.remaining_sessions, 'sit_in' as source
                FROM sit_ins s
                JOIN users u ON s.idno = u.idno
                ORDER BY s.date DESC, s.time_in DESC";

$result = $conn->query($query_sitins);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sitin_records[] = $row;
    }
}

// Sort all records by date and time (newest first)
usort($sitin_records, function($a, $b) {
    $a_datetime = strtotime($a['date'] . ' ' . $a['time_in']);
    $b_datetime = strtotime($b['date'] . ' ' . $b['time_in']);
    return $b_datetime - $a_datetime; // Descending order
});

// Improved course mapping function to handle different field formats
function getCourseNameById($courseId) {
    if (empty($courseId)) {
        return 'Not specified';
    }
    
    // Check if the course is already a string (full course name)
    if (is_string($courseId) && strlen($courseId) > 2) {
        return $courseId; // Return the course name directly
    }
    
    // Handle numeric course IDs
    switch ($courseId) {
        case 1:
            return 'BS Computer Science';
        case 2:
            return 'BS Information Technology';
        case 3:
            return 'BS Information Systems';
        case 4:
            return 'BS Computer Engineering';
        default:
            return 'Course #' . $courseId; // Return course with ID if not in mapping
    }
}

// Function to display year level more readably
function getYearLevelDisplay($yearLevel) {
    switch ($yearLevel) {
        case 1:
            return '1st Year';
        case 2:
            return '2nd Year';
        case 3:
            return '3rd Year';
        case 4:
            return '4th Year';
        default:
            return 'Not specified';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
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
                <a href="request.php" class="nav-link">
                    <i class="ri-mail-check-line"></i>
                    <span>Request</span>
                </a>
                <a href="sit-in.php" class="nav-link">
                    <i class="ri-map-pin-user-line"></i>
                    <span>Sit-in</span>
                </a>
                <a href="records.php" class="nav-link active">
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

    <style>
        .content-wrapper {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - var(--nav-height));
            padding: 2rem;
            margin-top: var(--nav-height);
        }

        .table-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 1600px;
            padding: 0;
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-header h2 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
        }

        .search-box {
            position: relative;
            width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .search-box input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(117,86,204,0.1);
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table td {
            padding: 1rem;
            font-size: 0.875rem;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table tr:hover td {
            background-color: #f8fafc;
        }

        .empty-state {
            text-align: center;
            padding: 3rem !important;
        }

        .empty-state-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            color: #a0aec0;
        }

        .empty-state-content i {
            font-size: 2.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-button {
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            width: 32px;
            height: 32px;
        }

        .action-button.edit {
            background: #e0f2fe;
            color: #0369a1;
        }

        .action-button.delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .sessions-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            background: #dcfce7;
            color: #16a34a;
        }
        
        /* Add new styles for different session counts */
        .sessions-low {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .sessions-medium {
            background: #fff7ed;
            color: #ea580c;
        }

        /* Modal styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            overflow: hidden;
        }

        .modal-backdrop.active .modal {
            transform: translateY(0);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #4a5568;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #a0aec0;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: #4a5568;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: #4a5568;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(117,86,204,0.1);
            outline: none;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #64748b;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Filter tabs styles */
        .filter-tabs {
            display: flex;
            padding: 0 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .filter-tab {
            padding: 1rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: #718096;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .filter-tab:hover {
            color: var(--primary-color);
        }

        .filter-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .records-container {
            display: none;
        }
        
        .records-container.active {
            display: block;
        }

        .source-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .source-badge.reservation {
            background: #e0f2fe;
            color: #0369a1;
        }
        
        .source-badge.sit_in {
            background: #ddd6fe;
            color: #6d28d9;
        }
        .real-time-clock {
            display: flex;
            align-items: center;
            margin-right: 1rem;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 0.875rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .real-time-clock i {
            margin-right: 0.5rem;
        }

        .table-actions {
            display: flex;
            align-items: center;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge.completed {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.active {
            background: #fff7ed;
            color: #ea580c;
        }

        .status-badge.approved {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-badge.pending {
            background: #f3f4f6;
            color: #4b5563;
        }

        .realtime-out {
            color: #ea580c;
            font-weight: 500;
            position: relative;
        }

        .realtime-out::after {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            background-color: #ea580c;
            border-radius: 50%;
            margin-left: 5px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }
            50% {
                opacity: 0.3;
            }
            100% {
                opacity: 1;
            }
        }

        .action-button.reset {
            background: #e9e9ff;
            color: #4f46e5;
        }

        .action-button-tooltip {
            position: relative;
            display: inline-block;
        }

        .action-button-tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.7rem;
            pointer-events: none;
        }

        .action-button-tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>

    <div class="content-wrapper">
        <div class="table-wrapper">
            <div class="table-header">
                <h2>Records Management</h2>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="ri-search-line"></i>
                        <input type="text" id="searchInput" placeholder="Search records...">
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab active" data-target="student-records">Student Records</div>
                <div class="filter-tab" data-target="sitin-records">Sit-in Records</div>
            </div>
            
            <!-- Student Records Container -->
            <div id="student-records" class="records-container active">
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Full Name</th>
                                <th>Year Level</th>
                                <th>Course</th>
                                <th>Remaining Sessions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <div class="empty-state-content">
                                            <i class="ri-user-search-line"></i>
                                            <p>No students found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <?php if (isset($student['idno']) && !empty($student['idno'])): ?>
                                    <?php 
                                        // Get remaining sessions directly from the database
                                        $remaining_sessions = $student['remaining_sessions'] ?? $total_allowed_sessions;
                                        
                                        // Set CSS class based on remaining sessions
                                        $sessionsClass = 'sessions-badge';
                                        if ($remaining_sessions <= 5) {
                                            $sessionsClass .= ' sessions-low';
                                        } else if ($remaining_sessions <= 10) {
                                            $sessionsClass .= ' sessions-medium';
                                        }
                                    ?>
                                    <tr>
                                        <td class="font-mono"><?php echo htmlspecialchars($student['idno'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></td>
                                        <td>
                                            <?php 
                                                // Check for both 'year_level' and 'year' fields
                                                $yearLevel = $student['year_level'] ?? $student['year'] ?? 0;
                                                echo htmlspecialchars(getYearLevelDisplay($yearLevel)); 
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                                // Check for all possible course field names
                                                $courseInfo = $student['course_id'] ?? $student['course'] ?? 
                                                           $student['department'] ?? $student['course_name'] ?? null;
                                                echo htmlspecialchars(getCourseNameById($courseInfo)); 
                                            ?>
                                        </td>
                                        <td><span class="<?php echo $sessionsClass; ?>"><?php echo $remaining_sessions; ?> sessions</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-button edit" onclick="openEditModal(<?php echo json_encode($student); ?>)">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                                <button class="action-button delete" onclick="confirmDelete('<?php echo $student['id']; ?>', '<?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>')">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                                <div class="action-button-tooltip">
                                                    <button class="action-button reset" onclick="confirmResetSessions('<?php echo $student['idno']; ?>', '<?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>')">
                                                        <i class="ri-refresh-line"></i>
                                                    </button>
                                                    <span class="tooltiptext">Reset to 30 sessions</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Sit-in Records Container -->
            <div id="sitin-records" class="records-container">
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>ID Number</th>
                                <th>Full Name</th>
                                <th>Purpose</th>
                                <th>Laboratory</th>
                                <th>PC #</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                                <th>Remaining Sessions</th> <!-- Added new column -->
                            </tr>
                        </thead>
                        <tbody id="sitin-records-body">
                            <?php if (empty($sitin_records)): ?>
                                <tr>
                                    <td colspan="10" class="empty-state"> <!-- Updated colspan from 9 to 10 -->
                                        <div class="empty-state-content">
                                            <i class="ri-computer-line"></i>
                                            <p>No sit-in records found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sitin_records as $record): ?>
                                    <?php 
                                        // Get remaining sessions directly from the database (from the JOIN we did in the query)
                                        $remaining_sessions = $record['remaining_sessions'] ?? $total_allowed_sessions;
                                        
                                        // Set CSS class based on remaining sessions
                                        $sessionsClass = 'sessions-badge';
                                        if ($remaining_sessions <= 5) {
                                            $sessionsClass .= ' sessions-low';
                                        } else if ($remaining_sessions <= 10) {
                                            $sessionsClass .= ' sessions-medium';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                        <td class="font-mono"><?php echo htmlspecialchars($record['idno']); ?></td>
                                        <td><?php echo htmlspecialchars($record['firstname'] . ' ' . $record['lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($record['purpose'] ?? 'Not specified'); ?></td>
                                        <td>Laboratory <?php echo htmlspecialchars($record['laboratory']); ?></td>
                                        <td>PC <?php echo htmlspecialchars($record['pc_number']); ?></td>
                                        <td>
                                            <?php 
                                                if ($record['time_in']) {
                                                    // Convert time_in to Asia/Manila timezone
                                                    $timeIn = new DateTime($record['time_in'], new DateTimeZone('UTC'));
                                                    $timeIn->setTimezone(new DateTimeZone('Asia/Manila'));
                                                    echo $timeIn->format('h:i A'); // 12-hour format with Manila timezone
                                                } else {
                                                    echo 'Not yet';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                                if ($record['time_out']) {
                                                    // Convert time_out to Asia/Manila timezone
                                                    $timeOut = new DateTime($record['time_out'], new DateTimeZone('UTC')); // in UTC or local time
                                                    $timeOut->setTimezone(new DateTimeZone('Asia/Manila'));
                                                    echo $timeOut->format('h:i A'); // This uses 12-hour format with Manila timezone
                                                } else if ($record['status'] == 'approved' && $record['time_in']) {
                                                    // For active sessions, show current time in Manila timezone
                                                    $currentTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
                                                    echo '<span class="realtime-out">' . $currentTime->format('h:i A') . ' (PST)</span>';
                                                } else {
                                                    // if timestamp is invalid
                                                    echo 'Not yet'; 
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($record['time_out']): ?>
                                                <span class="status-badge completed">Completed</span>
                                            <?php elseif ($record['status'] == 'approved' && $record['time_in']): ?>
                                                <span class="status-badge active">Active</span>
                                            <?php elseif ($record['status'] == 'approved'): ?>
                                                <span class="status-badge approved">Approved</span>
                                            <?php else: ?>
                                                <span class="status-badge pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="<?php echo $sessionsClass; ?>"><?php echo $remaining_sessions; ?> sessions</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Student Modal -->
    <div class="modal-backdrop" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Edit Student</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editStudentForm">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="form-group">
                        <label for="edit_idno">ID Number</label>
                        <input type="text" id="edit_idno" name="idno" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_firstname">First Name</label>
                        <input type="text" id="edit_firstname" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_lastname">Last Name</label>
                        <input type="text" id="edit_lastname" name="lastname" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_year_level">Year Level</label>
                        <select id="edit_year_level" name="year_level" required>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_course_id">Course</label>
                        <select id="edit_course_id" name="course_id" required>
                            <option value="1">Bachelor of Science in Computer Science</option>
                            <option value="2">Bachelor of Science in Information Technology</option>
                            <option value="3">Bachelor of Science in Information Systems</option>
                            <option value="4">Bachelor of Science in Computer Engineering</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveStudentChanges()">Save Changes</button>
            </div>
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div class="modal-backdrop" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Delete Student</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="deleteStudentName"></span>?</p>
                <p>This action cannot be undone.</p>
                <input type="hidden" id="deleteStudentId">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" onclick="deleteStudent()">Delete</button>
            </div>
        </div>
    </div>
    <!-- Reset Sessions Confirmation Modal -->
    <div class="modal-backdrop" id="resetSessionsModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Reset Sessions</h3>
                <button class="modal-close" onclick="closeResetModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reset <span id="resetStudentName"></span>'s sessions back to 30?</p>
                <input type="hidden" id="resetStudentIdno">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeResetModal()">Cancel</button>
                <button class="btn btn-primary" onclick="resetSessions()">Reset Sessions</button>
            </div>
        </div>
    </div>
    <script>
    // Tab switching functionality
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            // Hide all record containers
            document.querySelectorAll('.records-container').forEach(container => {
                container.classList.remove('active');
            });
            // Show the target container
            const targetId = this.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
            // Clear search input when switching tabs
            document.getElementById('searchInput').value = '';
        });
    });

    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        let searchText = this.value.toLowerCase();
        let activeContainer = document.querySelector('.records-container.active');
        let tableRows = activeContainer.querySelectorAll('.modern-table tbody tr');
        tableRows.forEach(row => {
            if (!row.querySelector('.empty-state')) {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            }
        });
    });

    // Edit modal functions
    function openEditModal(student) {
        document.getElementById('edit_id').value = student.id;
        document.getElementById('edit_idno').value = student.idno;
        document.getElementById('edit_firstname').value = student.firstname;
        document.getElementById('edit_lastname').value = student.lastname;
        // Handle different field names for year level
        const yearLevel = student.year_level !== undefined ? student.year_level : 
                         (student.year !== undefined ? student.year : 1);
        document.getElementById('edit_year_level').value = yearLevel || 1;
        // Handle different field names for course
        const courseId = student.course_id !== undefined ? student.course_id : 
                        (student.course !== undefined ? student.course : 
                         (student.department !== undefined ? student.department : 1));
        document.getElementById('edit_course_id').value = courseId || 1;
        document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    function saveStudentChanges() {
        const formData = new FormData(document.getElementById('editStudentForm'));
        fetch('../controller/update_student.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Student updated successfully!');
                closeEditModal();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the student.');
        });
    }

    // Delete modal functions
    function confirmDelete(studentId, studentName) {
        document.getElementById('deleteStudentId').value = studentId;
        document.getElementById('deleteStudentName').textContent = studentName;
        document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
    }

    function deleteStudent() {
        const studentId = document.getElementById('deleteStudentId').value;
        
        fetch('../controller/delete_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + studentId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Student deleted successfully!');
                closeDeleteModal();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the student.');
        });
    }

    // Real-time clock functionality
    function updateClock() {
        // Create a date object for GMT+8 time
        const now = new Date();
        // GMT+8 adjustment
        const gmtPlus8 = new Date(now.getTime() + (8 * 60 * 60 * 1000)); // For GMT+8
        
        const options = {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
            timeZone: 'Asia/Manila'
        };
        
        // Check if element exists before updating
        const clockElement = document.getElementById('current-time');
        if (clockElement) {
            clockElement.textContent = now.toLocaleString('en-US', options);
        }
    }

    // Function to refresh sit-in records data
    function refreshSitInRecords() {
        fetch('../controller/get_sitin_records.php')
            .then(response => response.json())
            .then(data => {
                const recordsBody = document.getElementById('sitin-records-body');
                
                // Clear existing rows except for the "no records" row
                if (data.length > 0) {
                    recordsBody.innerHTML = '';
                }
                
                // Add new rows
                data.forEach(record => {
                    // Format time_in using GMT+8 - same function used for both time_in and time_out
                    const timeIn = record.time_in ? 
                        formatTimeGMT8(record.date + ' ' + record.time_in) : 'Not yet';
                    
                    // Handle time out display - show real-time for active sessions
                    let timeOut;
                    if (record.time_out) {
                        // Format time_out using GMT+8 - same function as time_in
                        timeOut = formatTimeGMT8(record.date + ' ' + record.time_out);
                    } else if (record.status == 'approved' && record.time_in) {
                        // For active sessions, display current time with PST indicator
                        const currentTime = formatCurrentTimeGMT8();
                        timeOut = `<span class="realtime-out">${currentTime} (PST)</span>`;
                    } else {
                        timeOut = 'Not yet';
                    }
                    
                    // Handle session count display
                    let remainingClass = 'sessions-badge';
                    if (record.remaining_sessions <= 5) {
                        remainingClass += ' sessions-low';
                    } else if (record.remaining_sessions <= 10) {
                        remainingClass += ' sessions-medium';
                    }

                    let statusBadge = '';
                    if (record.time_out) {
                        statusBadge = '<span class="status-badge completed">Completed</span>';
                    } else if (record.status == 'active' && record.time_in) {
                        statusBadge = '<span class="status-badge active">Active</span>';
                    } else if (record.status == 'approved') {
                        statusBadge = '<span class="status-badge approved">Approved</span>';
                    } else {
                        statusBadge = '<span class="status-badge pending">Pending</span>';
                    }
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${new Date(record.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td>
                        <td class="font-mono">${record.idno}</td>
                        <td>${record.firstname} ${record.lastname}</td>
                        <td>${record.purpose || 'Not specified'}</td>
                        <td>Laboratory ${record.laboratory}</td>
                        <td>PC ${record.pc_number}</td>
                        <td>${timeIn}</td>
                        <td>${timeOut}</td>
                        <td>${statusBadge}</td>
                        <td><span class="${remainingClass}">${record.remaining_sessions} sessions</span></td>
                    `;
                    recordsBody.appendChild(row);
                });
                
                // Show empty state if no records
                if (data.length === 0) {
                    recordsBody.innerHTML = `
                        <tr>
                            <td colspan="10" class="empty-state">
                                <div class="empty-state-content">
                                    <i class="ri-computer-line"></i>
                                    <p>No sit-in records found</p>
                                </div>
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching records:', error);
            });
    }

    // Function to update real-time elements
    function updateRealTimeElements() {
        // Update clock
        updateClock();
        
        // Update time out for active sessions
        const realTimeOuts = document.querySelectorAll('.realtime-out');
        if (realTimeOuts.length > 0) {
            // Get current time in GMT+8
            const currentTime = formatCurrentTimeGMT8();
            realTimeOuts.forEach(element => {
                element.innerHTML = `${currentTime} (PST)`;
            });
        }
    }

    // Helper function to format time in GMT+8
    function formatTimeGMT8(dateTimeStr) {
        const dateObj = new Date(dateTimeStr);
        // Format to 12-hour with AM/PM specifically using Manila timezone (GMT+8)
        return dateObj.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
            timeZone: 'Asia/Manila'
        });
    }
    
    // Helper function to get current time in GMT+8
    function formatCurrentTimeGMT8() {
        const now = new Date();
        // Set to Manila time (GMT+8)
        const options = {
            hour: '2-digit', 
            minute: '2-digit', 
            hour12: true,
            timeZone: 'Asia/Manila'
        };
        return now.toLocaleTimeString('en-US', options);
    }

    // Set intervals for updates
    setInterval(updateRealTimeElements, 1000); // Update real-time elements every second
    setInterval(refreshSitInRecords, 30000); // Refresh all records every 30 seconds

    // Initial load
    updateRealTimeElements();
    refreshSitInRecords();

    // Reset sessions modal functions
    function confirmResetSessions(idno, studentName) {
        document.getElementById('resetStudentIdno').value = idno;
        document.getElementById('resetStudentName').textContent = studentName;
        document.getElementById('resetSessionsModal').classList.add('active');
    }

    function closeResetModal() {
        document.getElementById('resetSessionsModal').classList.remove('active');
    }

    function resetSessions() {
        const studentIdno = document.getElementById('resetStudentIdno').value;
        
        fetch('../controller/reset_sessions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'idno=' + studentIdno
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sessions reset successfully!');
                closeResetModal();
                location.reload(); // Reload to show updated values
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resetting sessions.');
        });
    }
    </script>
</body>
</html>
