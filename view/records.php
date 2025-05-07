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
    <link rel="stylesheet" href="../assets/css/records.css">
    <link rel="stylesheet" href="../assets/css/nav.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/javascript/nav.js" defer></script>
    <script src="../assets/javascript/notification.js" defer></script>
    <script src="../assets/javascript/admin_notifications.js" defer></script>
    <style>
        /* Add page opening animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        
        
        .content-wrapper {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .table-wrapper {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .filter-tabs {
            animation: fadeIn 0.7s ease-out forwards;
        }
        
        .records-container {
            animation: fadeIn 0.8s ease-out forwards;
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

        .source-badge.reservation {
            background-color: #fef3c7;
            color: #d97706;
            border: 1px solid rgba(217, 119, 6, 0.2);
        }

        .source-badge.sit_in {
            background-color: #ebf8ff;
            color: #3182ce;
            border: 1px solid rgba(49, 130, 206, 0.2);
        }
    </style>
</head>
<body>
<div id="notification-container"></div>
    
    <?php include '../view/nav.php'; ?>
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
                <div class="filter-tab active" data-target="student-records">Student Records</div>
                <div class="filter-tab" data-target="sitin-records">Sit-in Records</div>
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
                                                <button class="action-button edit" onclick="openEditModal('<?php echo htmlspecialchars(json_encode($student), ENT_QUOTES, 'UTF-8'); ?>')">
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
                                <th>Type</th>
                                <th>Remaining Sessions</th>
                                <th>Status</th>
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
                                            <span class="source-badge <?php echo htmlspecialchars($record['source']); ?>">
                                                <?php echo ($record['source'] == 'reservation') ? 'Reservation' : 'Walk-in'; ?>
                                            </span>
                                        </td>
                                        <td><span class="<?php echo $sessionsClass; ?>"><?php echo $remaining_sessions; ?> sessions</span></td>
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

    <!-- Notification System -->
    <div id="notification-container"></div>

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
                        <label for="edit_course_id">Course/Department</label>
                        <select id="edit_course_id" name="course_id" required>
                            <option value="BS-Information Technology">BS-Information Technology</option>
                            <option value="BS-Computer Science">BS-Computer Science</option>
                            <option value="COE">COE</option>
                            <option value="CAS">CAS</option>
                            <option value="CHM">CHM</option>
                            <option value="CTE">CTE</option>
                            <option value="CCA">CCA</option>
                            <option value="CBA">CBA</option>
                            <option value="CCJ">CCJ</option>
                            <option value="CON">CON</option>
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

    <!-- Other modals remain the same -->
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
                            <option value="CHM">CHM</option>
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
    <script src="../assets/javascript/records.js"></script>
    <script>
    // Notification System Functions
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
        
        notification.classList.remove('show');
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.parentElement.removeChild(notification);
            }
        }, 300);
    }
    
    // Override the existing functions to use our notification system
    document.addEventListener('DOMContentLoaded', function() {
        // Add CSS for notifications
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
            
            .notification-icon i {
                font-size: 18px;
                padding-top: 14px;
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
        </style>
        `);
        
        // Override the addStudent function
        window.addStudent = function() {
            // Get form values
            const form = document.getElementById('addStudentForm');
            const password = document.getElementById('add_password').value;
            const confirmPassword = document.getElementById('add_confirm_password').value;
            
            // Validate password match
            if (password !== confirmPassword) {
                showNotification("Error", "Passwords do not match", "error");
                return;
            }
            
            // Create FormData object for AJAX
            const formData = new FormData(form);
            
            // Send AJAX request
            fetch('../controller/add_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Close modal
                    closeAddStudentModal();
                    
                    // Show success notification
                    showNotification(
                        "Success", 
                        `Student ${formData.get('firstname')} ${formData.get('lastname')} has been added successfully`,
                        "success"
                    );
                    
                    // Reload the page after delay
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showNotification("Error", data.message || "Failed to add student", "error");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification("Error", "An error occurred. Please try again.", "error");
            });
        };
        
        // Override resetSessions function
        window.resetSessions = function() {
            const idno = document.getElementById('resetStudentIdno').value;
            
            // Get the current remaining sessions from the table row
            const studentRow = Array.from(document.querySelectorAll('#students-table-body tr')).find(
                row => row.querySelector('td') && row.querySelector('td').textContent.trim() === idno
            );
            
            // Check if sessions are already at maximum
            if (studentRow) {
                const sessionsElement = studentRow.querySelector('.sessions-badge');
                if (sessionsElement) {
                    const sessionsText = sessionsElement.textContent;
                    const currentSessions = parseInt(sessionsText);
                    
                    if (currentSessions >= 30) {
                        closeResetModal();
                        showNotification(
                            "Information", 
                            "This student already has the maximum of 30 sessions. No need to reset.",
                            "info"
                        );
                        return;
                    }
                }
            }
            
            fetch('../controller/reset_student_sessions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'idno=' + encodeURIComponent(idno)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    closeResetModal();
                    
                    // Show success notification
                    showNotification(
                        "Success", 
                        `Sessions reset successfully for student ${data.student_name || ''}`,
                        "success"
                    );
                    
                    // Reload the page after delay
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showNotification("Error", data.message || "Failed to reset sessions", "error");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification("Error", "An error occurred while resetting sessions.", "error");
            });
        };
        
        // Override resetAllSessions function
        window.resetAllSessions = function() {
            // First check if all students already have 30 sessions
            const sessionsBadges = document.querySelectorAll('#students-table-body .sessions-badge');
            let allMaxed = true;
            let anyMaxed = false;
            
            sessionsBadges.forEach(badge => {
                const sessionsText = badge.textContent;
                const currentSessions = parseInt(sessionsText);
                
                if (currentSessions < 30) {
                    allMaxed = false;
                } else {
                    anyMaxed = true;
                }
            });
            
            if (allMaxed) {
                closeResetAllSessionsModal();
                showNotification(
                    "Information", 
                    "All students already have the maximum of 30 sessions. No need to reset.",
                    "info"
                );
                return;
            }
            
            if (anyMaxed) {
                // Some students already at max, show warning but allow to continue
                document.querySelector('#resetAllSessionsModal .modal-body').innerHTML = `
                    <p>Are you sure you want to reset <strong>ALL students'</strong> sessions back to 30?</p>
                    <p class="text-yellow-500 font-semibold">Note: Some students already have 30 sessions and won't be affected.</p>
                    <p class="text-red-500 font-semibold">This action cannot be undone!</p>
                `;
            }
            
            fetch('../controller/reset_all_sessions.php', {
                method: 'POST'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Close modal
                    closeResetAllSessionsModal();
                    
                    // Show success notification
                    showNotification(
                        "Success", 
                        `All student sessions have been reset to 30 successfully`,
                        "success"
                    );
                    
                    // Reload the page after delay
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showNotification("Error", data.message || "Failed to reset all sessions", "error");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification("Error", "An error occurred while resetting all sessions.", "error");
            });
        };
        
        // Override clearAllRecords function
        window.clearAllRecords = function() {
            fetch('../controller/clear_all_records.php', {
                method: 'POST'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Close modal
                    closeClearAllRecordsModal();
                    
                    // Show success notification
                    showNotification(
                        "Success", 
                        `All sit-in records have been cleared successfully`,
                        "success"
                    );
                    
                    // Reload the page after delay
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showNotification("Error", data.message || "Failed to clear records", "error");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification("Error", "An error occurred while clearing records.", "error");
            });
        };
    });
    
    // Add these functions to make the edit student feature work
    
    // Function to open edit modal and populate with student data
    function openEditModal(studentJson) {
        try {
            // Parse the student data from JSON string
            const student = JSON.parse(studentJson);
            console.log("Student data:", student); // Debug logging
            
            // Populate form fields with student data
            document.getElementById('edit_id').value = student.id;
            document.getElementById('edit_idno').value = student.idno;
            document.getElementById('edit_firstname').value = student.firstname;
            document.getElementById('edit_lastname').value = student.lastname;
            
            // Handle year level - check for both possible field names
            const yearLevel = student.year_level || student.year || 1;
            document.getElementById('edit_year_level').value = yearLevel;
            
            // Handle course - check for multiple possible field names and match with available options
            const courseValue = student.course_id || student.course || student.department || student.course_name || '';
            
            // Set course value - handle both numeric and string values
            const courseSelect = document.getElementById('edit_course_id');
            
            // Try to find the option matching the course value
            let found = false;
            for (let i = 0; i < courseSelect.options.length; i++) {
                // Try to match either by value or by text content
                if (courseSelect.options[i].value.toLowerCase() === courseValue.toString().toLowerCase() || 
                    courseSelect.options[i].text.toLowerCase().includes(courseValue.toString().toLowerCase())) {
                    courseSelect.selectedIndex = i;
                    found = true;
                    break;
                }
            }
            
            // If not found, default to first option
            if (!found && courseSelect.options.length > 0) {
                courseSelect.selectedIndex = 0;
            }
            
            // Show the modal
            document.getElementById('editModal').classList.add('active');
        } catch (error) {
            console.error("Error parsing student data:", error);
            showNotification("Error", "Failed to load student data. Please try again.", "error");
        }
    }
    
    // Function to close edit modal
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }
    
    // Function to save student changes
    function saveStudentChanges() {
        // Get form data
        const form = document.getElementById('editStudentForm');
        const formData = new FormData(form);
        
        console.log("Form data being sent:"); // Debug log
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        // Send data to server
        fetch('../controller/update_student.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log("Response from server:", data); // Debug log
            if (data.success) {
                // Close modal
                closeEditModal();
                
                // Show success notification
                showNotification(
                    "Success", 
                    `Student ${formData.get('firstname')} ${formData.get('lastname')} has been updated successfully`,
                    "success"
                );
                
                // Reload the page after delay
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showNotification("Error", data.message || "Failed to update student", "error");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification("Error", "An error occurred while updating the student: " + error.message, "error");
        });
    }
    
    // Add these functions to handle pagination properly
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize pagination for both tables
        initPagination();
        
        // Add CSS for notifications
        // ...existing code...
    });
    
    // Pagination variables for student records
    let studentsCurrentPage = 1;
    let studentsPerPage = 10;
    let studentsTotalPages = 1;
    
    // Pagination variables for sit-in records
    let sitinsCurrentPage = 1;
    let sitinsPerPage = 10;
    let sitinsTotalPages = 1;
    
    function initPagination() {
        // Initialize students pagination
        document.getElementById('students-per-page').value = studentsPerPage;
        document.getElementById('students-per-page').addEventListener('change', function() {
            studentsPerPage = parseInt(this.value);
            studentsCurrentPage = 1; // Reset to first page
            applyStudentsPagination();
        });
        
        // Initialize sitins pagination
        document.getElementById('sitins-per-page').value = sitinsPerPage;
        document.getElementById('sitins-per-page').addEventListener('change', function() {
            sitinsPerPage = parseInt(this.value);
            sitinsCurrentPage = 1; // Reset to first page
            applySitinsPagination();
        });
        
        // Apply initial pagination
        applyStudentsPagination();
        applySitinsPagination();
        
        // Set up tab switching to preserve pagination state
        document.querySelectorAll('.filter-tab[data-target]').forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                if (target === 'student-records') {
                    setTimeout(applyStudentsPagination, 100);
                } else if (target === 'sitin-records') {
                    setTimeout(applySitinsPagination, 100);
                }
            });
        });
        
        // Set up search to reset pagination
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            
            // Determine which tab is active
            const activeTab = document.querySelector('.filter-tab[data-target].active').getAttribute('data-target');
            
            if (activeTab === 'student-records') {
                filterStudentRows(searchText);
                studentsCurrentPage = 1;
                applyStudentsPagination();
            } else if (activeTab === 'sitin-records') {
                filterSitinRows(searchText);
                sitinsCurrentPage = 1;
                applySitinsPagination();
            }
        });
    }
    
    function filterStudentRows(searchText) {
        const rows = document.querySelectorAll('#students-table-body tr');
        
        rows.forEach(row => {
            if (row.querySelector('.empty-state')) return; // Skip empty state row
            
            const text = row.textContent.toLowerCase();
            if (text.includes(searchText)) {
                row.classList.remove('filtered-out');
            } else {
                row.classList.add('filtered-out');
            }
        });
    }
    
    function filterSitinRows(searchText) {
        const rows = document.querySelectorAll('#sitin-records-body tr');
        
        rows.forEach(row => {
            if (row.querySelector('.empty-state')) return; // Skip empty state row
            
            const text = row.textContent.toLowerCase();
            if (text.includes(searchText)) {
                row.classList.remove('filtered-out');
            } else {
                row.classList.add('filtered-out');
            }
        });
    }
    
    function applyStudentsPagination() {
        // Get visible rows (after filtering)
        const visibleRows = Array.from(document.querySelectorAll('#students-table-body tr'))
            .filter(row => !row.classList.contains('filtered-out') && !row.querySelector('.empty-state'));
        
        studentsTotalPages = Math.max(1, Math.ceil(visibleRows.length / studentsPerPage));
        
        // Ensure current page is valid
        if (studentsCurrentPage > studentsTotalPages) {
            studentsCurrentPage = studentsTotalPages;
        }
        
        const startIndex = (studentsCurrentPage - 1) * studentsPerPage;
        const endIndex = Math.min(startIndex + studentsPerPage, visibleRows.length);
        
        // Hide all rows first
        visibleRows.forEach(row => row.style.display = 'none');
        
        // Empty state handling
        if (visibleRows.length === 0) {
            const emptyRow = document.querySelector('#students-table-body tr .empty-state');
            if (emptyRow) {
                emptyRow.closest('tr').style.display = '';
            }
        } else {
            // Show only rows for current page
            for (let i = startIndex; i < endIndex; i++) {
                visibleRows[i].style.display = '';
            }
        }
        
        // Update page info
        document.getElementById('students-page-info').textContent = 
            `Showing ${visibleRows.length > 0 ? startIndex + 1 : 0} to ${endIndex} of ${visibleRows.length} entries`;
            
        // Update pagination buttons
        updateStudentsPaginationButtons();
    }
    
    function updateStudentsPaginationButtons() {
        const paginationContainer = document.getElementById('students-pagination');
        paginationContainer.innerHTML = '';
        
        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn';
        prevBtn.setAttribute('data-action', 'prev');
        prevBtn.textContent = 'Previous';
        prevBtn.disabled = studentsCurrentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (studentsCurrentPage > 1) {
                studentsCurrentPage--;
                applyStudentsPagination();
            }
        });
        paginationContainer.appendChild(prevBtn);
        
        // Page number buttons - show up to 5 pages
        const maxButtons = 5;
        let startPage = Math.max(1, studentsCurrentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(studentsTotalPages, startPage + maxButtons - 1);
        
        if (endPage - startPage < maxButtons - 1 && startPage > 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-btn ${i === studentsCurrentPage ? 'active' : ''}`;
            pageBtn.setAttribute('data-page', i);
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                studentsCurrentPage = i;
                applyStudentsPagination();
            });
            paginationContainer.appendChild(pageBtn);
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn';
        nextBtn.setAttribute('data-action', 'next');
        nextBtn.textContent = 'Next';
        nextBtn.disabled = studentsCurrentPage === studentsTotalPages || studentsTotalPages === 0;
        nextBtn.addEventListener('click', () => {
            if (studentsCurrentPage < studentsTotalPages) {
                studentsCurrentPage++;
                applyStudentsPagination();
            }
        });
        paginationContainer.appendChild(nextBtn);
    }
    
    function applySitinsPagination() {
        // Get visible rows (after filtering)
        const visibleRows = Array.from(document.querySelectorAll('#sitin-records-body tr'))
            .filter(row => !row.classList.contains('filtered-out') && !row.querySelector('.empty-state'));
        
        sitinsTotalPages = Math.max(1, Math.ceil(visibleRows.length / sitinsPerPage));
        
        // Ensure current page is valid
        if (sitinsCurrentPage > sitinsTotalPages) {
            sitinsCurrentPage = sitinsTotalPages;
        }
        
        const startIndex = (sitinsCurrentPage - 1) * sitinsPerPage;
        const endIndex = Math.min(startIndex + sitinsPerPage, visibleRows.length);
        
        // Hide all rows first
        visibleRows.forEach(row => row.style.display = 'none');
        
        // Empty state handling
        if (visibleRows.length === 0) {
            const emptyRow = document.querySelector('#sitin-records-body tr .empty-state');
            if (emptyRow) {
                emptyRow.closest('tr').style.display = '';
            }
        } else {
            // Show only rows for current page
            for (let i = startIndex; i < endIndex; i++) {
                visibleRows[i].style.display = '';
            }
        }
        
        // Update page info
        document.getElementById('sitins-page-info').textContent = 
            `Showing ${visibleRows.length > 0 ? startIndex + 1 : 0} to ${endIndex} of ${visibleRows.length} entries`;
            
        // Update pagination buttons
        updateSitinsPaginationButtons();
    }
    
    function updateSitinsPaginationButtons() {
        const paginationContainer = document.getElementById('sitins-pagination');
        paginationContainer.innerHTML = '';
        
        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn';
        prevBtn.setAttribute('data-action', 'prev');
        prevBtn.textContent = 'Previous';
        prevBtn.disabled = sitinsCurrentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (sitinsCurrentPage > 1) {
                sitinsCurrentPage--;
                applySitinsPagination();
            }
        });
        paginationContainer.appendChild(prevBtn);
        
        // Page number buttons - show up to 5 pages
        const maxButtons = 5;
        let startPage = Math.max(1, sitinsCurrentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(sitinsTotalPages, startPage + maxButtons - 1);
        
        if (endPage - startPage < maxButtons - 1 && startPage > 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-btn ${i === sitinsCurrentPage ? 'active' : ''}`;
            pageBtn.setAttribute('data-page', i);
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                sitinsCurrentPage = i;
                applySitinsPagination();
            });
            paginationContainer.appendChild(pageBtn);
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn';
        nextBtn.setAttribute('data-action', 'next');
        nextBtn.textContent = 'Next';
        nextBtn.disabled = sitinsCurrentPage === sitinsTotalPages || sitinsTotalPages === 0;
        nextBtn.addEventListener('click', () => {
            if (sitinsCurrentPage < sitinsTotalPages) {
                sitinsCurrentPage++;
                applySitinsPagination();
            }
        });
        paginationContainer.appendChild(nextBtn);
    }
    </script>
</body>
</html>