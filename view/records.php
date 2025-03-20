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
            background: #e9e9ff;
            color: #6d28d9;
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
        
        /* Pagination controls */
        .pagination-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        
        .entries-per-page {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .entries-per-page select {
            padding: 0.375rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background-color: white;
            font-size: 0.875rem;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .entries-per-page select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(117,86,204,0.1);
            outline: none;
        }
        
        .entries-per-page label {
            font-size: 0.875rem;
            color: #718096;
        }
        
        .page-navigation {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .page-btn {
            padding: 0.375rem 0.75rem;
            border: 1px solid #e2e8f0;
            background-color: white;
            border-radius: 6px;
            font-size: 0.875rem;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .page-btn:hover {
            background-color: #f8fafc;
            border-color: #cbd5e0;
        }
        
        .page-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .page-info {
            font-size: 0.875rem;
            color: #718096;
            margin: 0 0.75rem;
        }
        
        /* Bulk action buttons */
        .bulk-action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .bulk-action-btn.danger {
            background: #fee2e2;
            margin-right: 1rem; 
            color: #dc2626;
        }
        
        .bulk-action-btn.warning {
            margin-right: 1rem;
            background: #fff7ed;
            color: #ea580c;
        }
        
        .bulk-action-btn.primary {
            margin-right: 1rem;
            background: #e0f2fe;
            color: #0369a1;
        }
        
        .bulk-action-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        /* Adjust table container to have a fixed height */
        .table-container {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
            border-radius: 0;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        /* Horizontal form layout for add student modal */
        .horizontal-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding: 1rem;
        }
        
        .horizontal-form .form-group {
            margin-bottom: 1rem;
        }
        
        .horizontal-form .full-width {
            grid-column: 1 / -1;
        }
        
        /* Wider modal for add student */
        .wide-modal {
            max-width: 800px !important;
        }
    </style>

    <div class="content-wrapper">
        <div class="table-wrapper">
            <div class="table-header">
                <h2>Records Management</h2>
                <div class="table-actions">
                    <!-- This div will show different buttons based on the active tab -->
                    <div id="student-records-actions" style="display: flex; gap: 10px;">
                        <button class="bulk-action-btn primary" onclick="openAddStudentModal()">
                            <i class="ri-user-add-line"></i> Add Student
                        </button>
                        <button class="bulk-action-btn warning" onclick="confirmResetAllSessions()">
                            <i class="ri-refresh-line"></i> Reset All Sessions
                        </button>
                    </div>
                    <div id="sitin-records-actions" style="display: none; gap: 10px;">
                        <button class="bulk-action-btn danger" onclick="confirmClearAllRecords()">
                            <i class="ri-delete-bin-line"></i> Clear All Records
                        </button>
                    </div>
                    <div class="search-box">
                        <i class="ri-search-line"></i>
                        <input type="text" id="searchInput" placeholder="Search records...">
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab active" data-target="student-records"><i class="ri-graduation-cap-fill"></i> Student Records</div>
                <div class="filter-tab" data-target="sitin-records"><i class="ri-map-pin-add-fill"></i>  Sit-in Records</div>
            </div>
            
            <!-- Student Records Container -->
            <div id="student-records" class="records-container active">
                <div class="table-container">
                    <table class="modern-table" id="students-table">
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
                        <tbody id="students-table-body">
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
                                                $courseInfo = $student['course_id'] ?? $student['course'] ?? $student['department'] ?? $student['course_name'] ?? null;
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
                <!-- Pagination controls for students -->
                <div class="pagination-controls">
                    <div class="entries-per-page">
                        <label for="students-per-page">Show</label>
                        <select id="students-per-page">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label>entries</label>
                    </div>
                    <div class="page-info" id="students-page-info">
                        Showing 1 to 10 of 0 entries
                    </div>
                    <div class="page-navigation" id="students-pagination">
                        <button class="page-btn" disabled data-action="prev">Previous</button>
                        <button class="page-btn active" data-page="1">1</button>
                        <button class="page-btn" disabled data-action="next">Next</button>
                    </div>
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
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                                <th>Remaining Sessions</th>
                            </tr>
                        </thead>
                        <tbody id="sitin-records-body">
                            <?php if (empty($sitin_records)): ?>
                                <tr>
                                    <td colspan="10" class="empty-state">
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
                                        <td><?php echo date('h:i A', strtotime($record['time_in'])); ?></td>
                                        <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : 'Active'; ?></td>
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
                <!-- Pagination controls for sit-ins -->
                <div class="pagination-controls">
                    <div class="entries-per-page">
                        <label for="sitins-per-page">Show</label>
                        <select id="sitins-per-page">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label>entries</label>
                    </div>
                    <div class="page-info" id="sitins-page-info">
                        Showing 1 to 10 of 0 entries
                    </div>
                    <div class="page-navigation" id="sitins-pagination">
                        <button class="page-btn" disabled data-action="prev">Previous</button>
                        <button class="page-btn active" data-page="1">1</button>
                        <button class="page-btn" disabled data-action="next">Next</button>
                    </div>
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
    
    <!-- Reset All Sessions Confirmation Modal -->
    <div class="modal-backdrop" id="resetAllSessionsModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Reset All Sessions</h3>
                <button class="modal-close" onclick="closeResetAllSessionsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reset <strong>ALL students'</strong> sessions back to 30?</p>
                <p class="text-red-500 font-semibold">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeResetAllSessionsModal()">Cancel</button>
                <button class="btn btn-danger" onclick="resetAllSessions()">Reset All Sessions</button>
            </div>
        </div>
    </div>
    
    <!-- Clear All Records Confirmation Modal -->
    <div class="modal-backdrop" id="clearAllRecordsModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Clear All Records</h3>
                <button class="modal-close" onclick="closeClearAllRecordsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to clear <strong>ALL sit-in records</strong>?</p>
                <p class="text-red-500 font-semibold">This action cannot be undone and will remove all reservation and sit-in history!</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeClearAllRecordsModal()">Cancel</button>
                <button class="btn btn-danger" onclick="clearAllRecords()">Clear All Records</button>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal-backdrop" id="addStudentModal">
        <div class="modal wide-modal">
            <div class="modal-header">
                <h3>Add New Student</h3>
                <button class="modal-close" onclick="closeAddStudentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addStudentForm" class="horizontal-form">
                    <div class="form-group">
                        <label for="add_idno">ID Number</label>
                        <input type="text" id="add_idno" name="idno" pattern="\d{8}" title="ID Number must be exactly 8 digits" required>
                    </div>
                    <div class="form-group">
                        <label for="add_username">Username</label>
                        <input type="text" id="add_username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="add_firstname">First Name</label>
                        <input type="text" id="add_firstname" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="add_lastname">Last Name</label>
                        <input type="text" id="add_lastname" name="lastname" required>
                    </div>
                    <div class="form-group">
                        <label for="add_year_level">Year Level</label>
                        <select id="add_year_level" name="year_level" required>
                            <option value="" disabled selected>Select Year Level</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="add_course_id">Course/Department</label>
                        <select id="add_course_id" name="course_id" required>
                            <option value="" disabled selected>Select Course/Department</option>
                            <option value="BS-Information Technology">BS-Information Technology</option>
                            <option value="BS-Computer Science">BS-Computer Science</option>
                            <option value="COE">COE</option>
                            <option value="CAS">CAS</option>
                            <option value="SJH">SJH</option>
                            <option value="CTE">CTE</option>
                            <option value="CCA">CCA</option>
                            <option value="CBA">CBA</option>
                            <option value="CCJ">CCJ</option>
                            <option value="CON">CON</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="add_password">Password</label>
                        <input type="password" id="add_password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="add_confirm_password">Confirm Password</label>
                        <input type="password" id="add_confirm_password" name="confirm_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeAddStudentModal()">Cancel</button>
                <button class="btn btn-primary" onclick="addStudent()">Add Student</button>
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
            // Toggle action buttons based on active tab
            if (targetId === 'student-records') {
                document.getElementById('student-records-actions').style.display = 'flex';
                document.getElementById('sitin-records-actions').style.display = 'none';
            } else {
                document.getElementById('student-records-actions').style.display = 'none';
                document.getElementById('sitin-records-actions').style.display = 'flex';
            }
            // Clear search input when switching tabs
            document.getElementById('searchInput').value = '';
        });
    });

    // Add Student Modal Functions
    function openAddStudentModal() {
        // Reset form fields
        document.getElementById('addStudentForm').reset();
        document.getElementById('addStudentModal').classList.add('active');
    }
    
    function closeAddStudentModal() {
        document.getElementById('addStudentModal').classList.remove('active');
    }
    
    function addStudent() {
        // Get form data
        const idno = document.getElementById('add_idno').value;
        const firstname = document.getElementById('add_firstname').value;
        const lastname = document.getElementById('add_lastname').value;
        const username = document.getElementById('add_username').value;
        const yearLevel = document.getElementById('add_year_level').value;
        const course = document.getElementById('add_course_id').value;
        const password = document.getElementById('add_password').value;
        const confirmPassword = document.getElementById('add_confirm_password').value;
        
        // Validate form
        if (!idno || !firstname || !lastname || !username || !yearLevel || !course || !password || !confirmPassword) {
            alert('Please fill all required fields');
            return;
        }
        
        // Validate ID number format
        if (!/^\d{8}$/.test(idno)) {
            alert('ID Number must be exactly 8 digits');
            return;
        }
        
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }
        
        // Show loading state
        const addButton = document.querySelector('#addStudentModal .btn-primary');
        const originalText = addButton.textContent;
        addButton.textContent = 'Adding...';
        addButton.disabled = true;
        
        // Send data to server
        fetch('../controller/add_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                idno: idno,
                firstname: firstname,
                lastname: lastname,
                username: username,
                year_level: yearLevel,
                course_id: course,
                password: password
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                closeAddStudentModal();
                alert('Student added successfully!');
                // Refresh the page to show the new student
                location.reload();
            } else {
                alert('Error: ' + data.message);
                // Reset button state
                addButton.textContent = originalText;
                addButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your request. Please check the console for details.');
            // Reset button state
            addButton.textContent = originalText;
            addButton.disabled = false;
        });
    }

    // Functions for resetting all sessions
    function confirmResetAllSessions() {
        document.getElementById('resetAllSessionsModal').classList.add('active');
    }
    
    function closeResetAllSessionsModal() {
        document.getElementById('resetAllSessionsModal').classList.remove('active');
    }
    
    function resetAllSessions() {
        // Show loading state or disable button
        const resetButton = document.querySelector('#resetAllSessionsModal .btn-danger');
        const originalText = resetButton.textContent;
        resetButton.textContent = 'Processing...';
        resetButton.disabled = true;
        
        fetch('../controller/reset_all_sessions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeResetAllSessionsModal();
                alert(`Success! ${data.count} students' sessions have been reset to 30.`);
                location.reload(); // Reload to show updated values
            } else {
                alert('Error: ' + data.message);
                // Reset button state
                resetButton.textContent = originalText;
                resetButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your request.');
            // Reset button state
            resetButton.textContent = originalText;
            resetButton.disabled = false;
        });
    }
    
    // Functions for clearing all sit-in records
    function confirmClearAllRecords() {
        document.getElementById('clearAllRecordsModal').classList.add('active');
    }
    
    function closeClearAllRecordsModal() {
        document.getElementById('clearAllRecordsModal').classList.remove('active');
    }
    
    function clearAllRecords() {
        // Show loading state or disable button
        const clearButton = document.querySelector('#clearAllRecordsModal .btn-danger');
        const originalText = clearButton.textContent;
        clearButton.textContent = 'Processing...';
        clearButton.disabled = true;
        
        fetch('../controller/clear_all_records.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeClearAllRecordsModal();
                alert(`Success! ${data.count} sit-in records have been deleted.`);
                // Refresh the sit-in records
                refreshSitInRecords();
                sitinData = []; // Clear the local data
                setupSitinPagination(); // Rebuild pagination
            } else {
                alert('Error: ' + data.message);
                // Reset button state
                clearButton.textContent = originalText;
                clearButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your request.');
            // Reset button state
            clearButton.textContent = originalText;
            clearButton.disabled = false;
        });
    }
    
    // Initialize action buttons visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        setupStudentsPagination();
        setupSitinPagination();
        // Set initial visibility of action buttons based on active tab
        if (document.getElementById('student-records').classList.contains('active')) {
            document.getElementById('student-records-actions').style.display = 'flex';
            document.getElementById('sitin-records-actions').style.display = 'none';
        } else {
            document.getElementById('student-records-actions').style.display = 'none';
            document.getElementById('sitin-records-actions').style.display = 'flex';
        }
    });
    
    // ... existing code for pagination, search functionality, etc. ...
    </script>
</body>
</html>