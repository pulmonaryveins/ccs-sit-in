<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Include the ensure_tables script to make sure sit_ins table exists
require_once '../config/ensure_tables.php';
require_once '../config/db_connect.php';

// Get reservation_id from URL if exists (for redirects from request.php)
$highlight_id = isset($_GET['reservation_id']) ? intval($_GET['reservation_id']) : 0;

// Get active tab if provided in URL
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'current-students';

// Fetch Direct sit-in students from the sit_ins table
$current_students = [];

// Query for active direct sit-ins ONLY
$query = "SELECT 
            'sit_in' AS record_type,
            s.id,
            s.idno,
            s.fullname,
            s.purpose,
            s.laboratory,
            s.pc_number,
            s.time_in,
            s.date,
            s.status,
            u.firstname,
            u.lastname
          FROM sit_ins s
          LEFT JOIN users u ON s.idno = u.idno
          WHERE s.time_out IS NULL
          AND s.status = 'active'
          ORDER BY s.time_in DESC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $current_students[] = $row;
    }
}

// Get approved reservations separately (these are considered Reserved Sit-ins)
$approved_reservations = [];
$reservation_query = "SELECT 
                        'reservation' AS record_type,
                        r.id,
                        r.idno,
                        r.fullname,
                        r.purpose,
                        r.laboratory,
                        r.pc_number,
                        r.time_in,
                        r.date,
                        r.status,
                        u.firstname,
                        u.lastname
                      FROM reservations r
                      LEFT JOIN users u ON r.idno = u.idno
                      WHERE r.status = 'approved'
                      AND r.time_out IS NULL
                      AND r.date >= CURDATE()
                      ORDER BY r.date ASC, r.time_in ASC";

$result = $conn->query($reservation_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $approved_reservations[] = $row;
    }
}

// For debugging
echo "<!-- Found " . count($current_students) . " current students -->";
echo "<!-- Found " . count($approved_reservations) . " approved reservations -->";

// Debug: Log each found student with their record type
if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    echo "<div style='position:fixed; bottom:0; left:0; right:0; background:black; color:white; padding:10px; font-family:monospace; z-index:9999; max-height:200px; overflow:auto;'>";
    echo "<h3>Debug Info:</h3>";
    echo "<p>Records found: " . count($current_students) . " sit-ins, " . count($approved_reservations) . " reservations</p>";
    
    if (!empty($current_students) || !empty($approved_reservations)) {
        echo "<table border='1' style='width:100%; font-size:12px;'>";
        echo "<tr><th>Type</th><th>ID</th><th>Name</th><th>Lab</th><th>Date</th><th>Status</th></tr>";
        
        foreach ($current_students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['record_type']) . "</td>";
            echo "<td>" . htmlspecialchars($student['id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['fullname']) . "</td>";
            echo "<td>" . htmlspecialchars($student['laboratory']) . "</td>";
            echo "<td>" . htmlspecialchars($student['date']) . "</td>";
            echo "<td>" . htmlspecialchars($student['status']) . "</td>";
            echo "</tr>";
        }
        
        foreach ($approved_reservations as $reservation) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($reservation['record_type']) . "</td>";
            echo "<td>" . htmlspecialchars($reservation['id']) . "</td>";
            echo "<td>" . htmlspecialchars($reservation['fullname']) . "</td>";
            echo "<td>" . htmlspecialchars($reservation['laboratory']) . "</td>";
            echo "<td>" . htmlspecialchars($reservation['date']) . "</td>";
            echo "<td>" . htmlspecialchars($reservation['status']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Add base styles first -->
    <style>
        :root {
            --nav-height: 60px;
            --primary-color: #7556cc;
            --secondary-color: #d569a7;
        }
        /* Add a new style for the active student warning */
        .active-student-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .active-student-warning i {
            color: #e6a210;
            font-size: 24px;
        }
        
        .active-student-warning p {
            margin: 0;
            color: #856404;
        }
        
        /* Add page opening animation matching request.php */
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
        
        .view-container {
            animation: fadeIn 0.8s ease-out forwards;
        }

        /* Add styles for reservation badge */
        .status-badge.reservation {
            background-color: #ebf8ff;
            color: #3182ce;
            border: 1px solid rgba(49, 130, 206, 0.2);
        }
        
        /* Highlighted row for newly approved reservations */
        .highlighted-row {
            animation: highlight 2s ease-in-out;
        }
        
        @keyframes highlight {
            0%, 100% {
                background-color: transparent;
            }
            50% {
                background-color: rgba(117, 86, 204, 0.15);
            }
        }
        
        /* Style for action buttons */
        .action-button.primary {
            background-color: #3182ce;
        }
        
        .action-button.primary:hover {
            background-color: #2c5282;
        }

        /* Enhanced action button group styling */
        .action-button-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .action-button-group .action-button {
            width: 100%;
            padding: 0.5rem;
            font-size: 0.8rem;
        }
        
        /* Style specific to reservation rows */
        .highlighted-row {
            animation: highlight 2s ease-in-out;
        }

        @keyframes highlight {
            0%, 100% {
                background-color: transparent;
            }
            50% {
                background-color: rgba(117, 86, 204, 0.15);
            }
        }

        /* Add a subtle indicator to show its a future day */
        tr.future-date {
            background-color: #f8f9ff;
        }
        
        tr.current-date {
            background-color: #f0f7ff;
        }

        /* Add styling for the new type badge */
        .type-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 20px;
            text-align: center;
        }
        
        .type-badge.walkin {
            background-color: #ebf8ff;
            color: #3182ce;
            border: 1px solid rgba(49, 130, 206, 0.2);
        }
        
        .type-badge.reservation {
            background-color: #fef3c7;
            color: #d97706;
            border: 1px solid rgba(217, 119, 6, 0.2);
        }
        
        /* Ensure table header accommodates the new column */
        .table-container {
            overflow-x: auto;
        }
        
        .modern-table th, .modern-table td {
            white-space: nowrap;
            padding: 0.75rem 1rem;
        }
        
        /* Enhanced action buttons consistency */
        .action-button-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
        }

        .action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            border: none;
            color: white;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            width: 100%;
        }
        
        .action-button i {
            font-size: 1rem;
        }
        
        .action-button.primary {
            background-color: #3182ce;
        }
        
        .action-button.primary:hover {
            background-color: #2c5282;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }
        
        .action-button.danger {
            background-color: #e53e3e;
        }
        
        .action-button.danger:hover {
            background-color: #c53030;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }
        
        /* Ensure all action buttons have consistent width and height */
        td .action-button {
            min-width: 50px;
            height: 36px;
        }
        
        /* Fix for table cell containing action buttons */
        table.modern-table td:last-child {
            width: 140px;
            min-width: 140px;
            max-width: 140px;
            padding: 0.75rem 1rem;
        }

        .notification-icon i {
            font-size: 18px;
            padding-top: 14px;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/sit-in.css">
    <link rel="stylesheet" href="../assets/css/nav.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/javascript/nav.js" defer></script>
    <script src="../assets/javascript/notification.js" defer></script>
    <script src="../assets/javascript/admin_notifications.js" defer></script>
</head>
<body>
<div id="notification-container"></div>
    
    <?php include '../view/nav.php'; ?>
    <div class="content-wrapper">
        <div class="table-wrapper">
            <div class="table-header">
                <h2>Laboratory Management</h2>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="ri-search-line"></i>
                        <input type="text" id="searchInput" placeholder="Search...">
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab" data-target="add-sitin">Add Direct Sit-in</div>
                <div class="filter-tab <?php echo $active_tab === 'current-students' ? 'active' : ''; ?>" data-target="current-students">Direct Sit-in</div>
                <div class="filter-tab <?php echo $active_tab === 'reservations' ? 'active' : ''; ?>" data-target="reservations">Reserved Sit-in</div>
            </div>
            
            <!-- Current Students Container (Direct Sit-ins) -->
            <div id="current-students" class="view-container <?php echo $active_tab === 'current-students' ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Full Name</th>
                                <th>Purpose</th>
                                <th>Laboratory</th>
                                <th>Time in</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($current_students)): ?>
                                <tr>
                                    <td colspan="9" class="empty-state">
                                        <div class="empty-state-content">
                                            <i class="ri-computer-line"></i>
                                            <p>No students currently in direct sit-in</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($current_students as $student): ?>
                                    <tr class="<?php echo $student['id'] == $highlight_id && $student['record_type'] == 'sit_in' ? 'highlighted-row' : ''; ?>">
                                        <td class="font-mono"><?php echo htmlspecialchars($student['idno']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($student['firstname']) && !empty($student['lastname'])) {
                                                echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']);
                                            } else {
                                                echo htmlspecialchars($student['fullname']);
                                            } 
                                            ?>
                                        </td>
                                        <td><span class="purpose-badge"><?php echo htmlspecialchars($student['purpose']); ?></span></td>
                                        <td>Laboratory <?php echo htmlspecialchars($student['laboratory']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($student['time_in'])); ?></td>
                                        <td>
                                            <span class="type-badge walkin">Walk-in</span>
                                        </td>
                                        <td>
                                            <span class="status-badge active">Active</span>
                                        </td>
                                        <td>
                                            <button class="action-button danger" onclick="markTimeOut('<?php echo $student['id']; ?>', 'sit_in')">
                                                <i class="ri-logout-box-line"></i> Time Out
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Reserved Students Container (Approved Reservations) -->
            <div id="reservations" class="view-container <?php echo $active_tab === 'reservations' ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Full Name</th>
                                <th>Purpose</th>
                                <th>Laboratory</th>
                                <th>PC Number</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($approved_reservations)): ?>
                                <tr>
                                    <td colspan="9" class="empty-state">
                                        <div class="empty-state-content">
                                            <i class="ri-calendar-line"></i>
                                            <p>No approved reservations</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($approved_reservations as $reservation): ?>
                                    <tr class="<?php echo $reservation['id'] == $highlight_id ? 'highlighted-row' : ''; ?>">
                                        <td class="font-mono"><?php echo htmlspecialchars($reservation['idno']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($reservation['firstname']) && !empty($reservation['lastname'])) {
                                                echo htmlspecialchars($reservation['firstname'] . ' ' . $reservation['lastname']);
                                            } else {
                                                echo htmlspecialchars($reservation['fullname']);
                                            } 
                                            ?>
                                        </td>
                                        <td><span class="purpose-badge"><?php echo htmlspecialchars($reservation['purpose']); ?></span></td>
                                        <td>Laboratory <?php echo htmlspecialchars($reservation['laboratory']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['pc_number']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['date']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($reservation['time_in'])); ?></td>
                                        <td>
                                            <span class="type-badge reservation">Reservation</span>
                                        </td>
                                        <td>
                                            <button class="action-button danger" onclick="markTimeOut('<?php echo $reservation['id']; ?>', 'reservation')">
                                                <i class="ri-logout-box-line"></i> Time Out
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Add Sit-in Container -->
            <div id="add-sitin" class="view-container">
                <div class="dashboard-column" style="max-width: 1000px; margin: 0 auto;">
                    <div class="profile-card">
                        <div class="profile-header">
                            <h3>Add Student Sit-in</h3>
                        </div>
                        <form id="addSitInForm" class="reservation-form">
                            <!-- Student ID Search Field - Enhanced UI -->
                            <div class="search-container">
                                <div class="search-field">
                                    <input type="text" id="student_idno" name="idno" placeholder="Enter student ID number..." autocomplete="off">
                                    <div class="search-button-wrapper">
                                        <button type="button" id="searchStudentBtn">
                                            <i class="ri-search-line"></i> Search
                                        </button>
                                    </div>
                                </div>
                            

                                <!-- Student info with redesigned layout -->
                                <div id="studentInfo" class="student-info-grid" style="display: none;">
                                    <!-- Left Column - Profile and Sessions -->
                                    <div class="student-info-left">
                                        <!-- Student Profile -->
                                        <div class="student-profile-container">
                                            <div class="student-profile-image">
                                                <img src="../assets/images/logo/AVATAR.png" 
                                                     alt="Student Profile" 
                                                     id="display_profile_image"
                                                     onerror="this.src='../assets/images/logo/AVATAR.png'">
                                            </div>
                                            <div class="student-profile-name" id="display_student_name">Student Name</div>
                                            <div class="course-badge" id="display_course"></div>
                                        </div>
                                        
                                        <!-- Sessions Display -->
                                        <div class="sessions-container">
                                            <div class="sessions-number" id="remainingSessions">30</div>
                                            <div class="sessions-label">Remaining Sessions</div>
                                            <div class="sessions-progress">
                                                <div class="bg-gradient-to-r from-violet-500 to-fuchsia-500 h-full rounded-full transition-all duration-500" 
                                                     id="sessionsProgress" 
                                                     style="width: 100%">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Right Column - Student Info Cards -->
                                    <div class="student-info-right">
                                        <!-- ID Number -->
                                        <div class="info-card">
                                            <div class="info-icon"><i class="ri-profile-fill"></i></div>
                                            <div class="info-content">
                                                <div class="detail-label">Student ID</div>
                                                <div class="detail-value">
                                                    <input type="text" id="display_idno" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Year Level -->
                                        <div class="info-card">
                                            <div class="info-icon"><i class="ri-expand-up-down-fill"></i></div>
                                            <div class="info-content">
                                                <div class="detail-label">Year Level</div>
                                                <div class="detail-value">
                                                    <input type="text" id="display_year" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Laboratory -->
                                        <div class="info-card">
                                            <div class="info-icon"><i class="ri-computer-fill"></i></div>
                                            <div class="info-content">
                                                <div class="detail-label">Laboratory</div>
                                                <div class="detail-value">
                                                    <select id="laboratory" name="laboratory" required>
                                                        <option value="">Select Laboratory</option>
                                                        <option value="517">517</option>
                                                        <option value="524">524</option>
                                                        <option value="526">526</option>
                                                        <option value="528">528</option>
                                                        <option value="530">530</option>
                                                        <option value="542">542</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Purpose -->
                                        <div class="info-card">
                                            <div class="info-icon"><i class="ri-code-box-fill"></i></div>
                                            <div class="info-content">
                                                <div class="detail-label">Purpose</div>
                                                <div class="detail-value">
                                                    <select id="purpose" name="purpose" required>
                                                        <option value="">Select Purpose</option>
                                                        <option value="C Programming">C Programming</option>
                                                        <option value="Java Programming">Java Programming</option>
                                                        <option value="C#">C#</option>
                                                        <option value="PHP">PHP</option>
                                                        <option value="ASP.Net">ASP.Net</option>
                                                        <option value="MySQL Database">MySQL Database</option>
                                                        <option value="PHP">PHP</option>
                                                        <option value="Web Development">Web Development</option>
                                                        <option value="System Architecture">System Architecture</option>
                                                        <option value="System Analysis and Design">System Analysis and Design</option>
                                                        <option value="Information Security">Information Security</option>
                                                        <option value="Research">Research</option>


                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Form Submit Button -->
                                        <div class="form-controls">
                                            <button id="cancelSitinBtn" type="button">
                                                <i class="ri-close-line"></i>
                                                Cancel
                                            </button>
                                            <button id="submitSitinBtn" type="submit">
                                                <i class="ri-check-line"></i>
                                                Submit Sit-in
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden fields for current date and time will be set via JavaScript -->
                            <input type="hidden" id="sit_in_date" name="date">
                            <input type="hidden" id="sit_in_time" name="time">
                            <!-- Add default PC number since we removed the selection -->
                            <input type="hidden" id="selected_pc" name="pc_number" value="1">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification System -->
    <div id="notification-container"></div>
    
    <!-- Confirmation Modal -->
    <div class="confirm-modal-backdrop" id="confirmModal">
        <div class="confirm-modal">
            <div class="confirm-modal-header">
                <h3 class="confirm-modal-title" id="confirm-title">Confirm Action</h3>
                <button class="notification-close" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div class="confirm-modal-body">
                <p id="confirm-message">Are you sure you want to proceed with this action?</p>
            </div>
            <div class="confirm-modal-footer">
                <button class="confirm-btn confirm-btn-cancel" onclick="closeConfirmModal()">Cancel</button>
                <button class="confirm-btn confirm-btn-confirm" id="confirm-yes">Yes, Continue</button>
            </div>
        </div>
    </div>

    <script>
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Check if we should activate a specific tab from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const tabTarget = urlParams.get('tab');
        
        if (tabTarget) {
            // If tab is specified in URL, activate it
            const targetTab = document.querySelector(`.filter-tab[data-target="${tabTarget}"]`);
            if (targetTab) {
                // Simulate a click on the tab
                targetTab.click();
            }
        }
        
        // Tab switching (regular code continues)
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all view containers
                document.querySelectorAll('.view-container').forEach(container => {
                    container.classList.remove('active');
                });

                // Show the target container
                const targetId = this.getAttribute('data-target');
                document.getElementById(targetId).classList.add('active');
                
                // Clear search input when switching tabs
                document.getElementById('searchInput').value = '';
            });
        });
    });
    
    // Search functionality
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        let searchText = this.value.toLowerCase();
        let activeView = document.querySelector('.view-container.active');
        
        if (activeView.id === 'current-students') {
            let tableRows = activeView.querySelectorAll('.modern-table tbody tr');
            tableRows.forEach(row => {
                if (!row.querySelector('.empty-state')) {
                    let text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchText) ? '' : 'none';
                }
            });
        } else if (activeView.id === 'reservations') {
            let tableRows = activeView.querySelectorAll('.modern-table tbody tr');
            tableRows.forEach(row => {
                if (!row.querySelector('.empty-state')) {
                    let text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchText) ? '' : 'none';
                }
            });
        }
    });
    
    // Notification System Functions
    function showNotification(title, message, type = 'info', duration = 5000) {
        const notificationContainer = document.getElementById('notification-container');
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        // Set icon based on type
        let icon = 'information-line';
        if (type === 'success') icon = 'check-line';
        if (type === 'error') icon = 'error-warning-line';
        if (type === 'warning') icon = 'alert-line';
        
        // Create notification content
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
        
        // Force reflow before adding the 'show' class for proper animation
        notification.getBoundingClientRect();
        
        // Show notification with animation
        notification.classList.add('show');
        
        // Auto dismiss after duration (if specified)
        if (duration > 0) {
            setTimeout(() => closeNotification(notification), duration);
        }
        
        return notification;
    }
    
    function closeNotification(notification) {
        if (!notification) return;
        
        // Trigger hide animation
        notification.classList.remove('show');
        
        // Remove from DOM after animation
        setTimeout(() => {
            if (notification.parentElement) {
                notification.parentElement.removeChild(notification);
            }
        }, 300);
    }
    
    // Confirmation Modal Functions
    function showConfirmModal(message, title = 'Confirm Action', callback) {
        document.getElementById('confirm-title').textContent = title;
        document.getElementById('confirm-message').textContent = message;
        
        const confirmBtn = document.getElementById('confirm-yes');
        
        // Remove previous event listener
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        // Add new event listener
        newConfirmBtn.addEventListener('click', () => {
            closeConfirmModal();
            callback(true);
        });
        
        document.getElementById('confirmModal').classList.add('show');
        return false; // Prevent default behavior
    }
    
    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.remove('show');
    }

    function markTimeOut(id, recordType) {
        let confirmMessage = "Are you sure you want to mark this student as timed out?";
        let confirmTitle = "Confirm Time Out";
        
        // Variables to store laboratory and PC number
        let laboratory = null;
        let pcNumber = null;
        
        if (recordType === 'reservation') {
            confirmMessage = "Are you sure you want to time out this reservation?";
            confirmTitle = "Confirm Reservation Time Out";
            
            // Get laboratory and PC number directly from the row
            const rowSelector = `tr:has(button[onclick*="markTimeOut('${id}', '${recordType}')"]) td`;
            const laboratoryElement = document.querySelector(`${rowSelector}:nth-child(4)`);
            const pcNumberElement = document.querySelector(`${rowSelector}:nth-child(5)`);
            
            if (laboratoryElement && pcNumberElement) {
                laboratory = laboratoryElement.textContent.replace('Laboratory ', '').trim();
                pcNumber = pcNumberElement.textContent.trim();
                console.log(`Retrieved from row: Lab ${laboratory}, PC ${pcNumber}`);
            }
        } else {
            confirmMessage = "Are you sure you want to time out this direct sit-in?";
            confirmTitle = "Confirm Direct Sit-in Time Out";
            
            // For direct sit-ins, we don't need to get laboratory and PC number
            // as we don't want to show the computer update notification
        }
        
        showConfirmModal(confirmMessage, confirmTitle, (confirmed) => {
            if (confirmed) {
                console.log(`Timing out ${recordType} ID: ${id}`); // Debug log
                
                // Create form data to send
                const formData = new FormData();
                
                // Add the correct ID field based on the record type
                if (recordType === 'sit_in') {
                    formData.append('sit_in_id', id);
                } else if (recordType === 'reservation') {
                    formData.append('reservation_id', id);
                    
                    // Include laboratory and PC number for updating computer status
                    if (laboratory) formData.append('laboratory', laboratory);
                    if (pcNumber) formData.append('pc_number', pcNumber);
                }
                
                // Explicitly add the current time in Manila/GMT+8 timezone with consistent format
                const now = new Date();
                const manilaTime = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                    timeZone: 'Asia/Manila'
                });
                
                // Format time consistently as HH:MM:SS
                const timeParts = manilaTime.split(':');
                const formattedTime = timeParts.join(':');
                
                // Add record type to the form data for proper processing
                formData.append('record_type', recordType);
                formData.append('time_out', formattedTime);
                formData.append('timezone', 'Asia/Manila');
                formData.append('admin_timeout', 'true');
                
                // Send AJAX request to process time out
                fetch('../controller/time_out.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Response data:", data);
                    if (data.success) {
                        // Show success message with remaining sessions
                        const message = `Student has been marked as timed out successfully. \nRemaining sessions: ${data.remaining_sessions}`;
                        showNotification("Success", message, 'success');
                        
                        // Only show computer update notification for reservations, not for direct sit-ins
                        if (data.computer_updated && recordType === 'reservation') {
                            const computerMessage = `Computer ${data.pc_number} in Laboratory ${data.laboratory} has been set to available.`;
                            showNotification("Computer Updated", computerMessage, 'info');
                            
                            // Dispatch a custom event that request.php can listen for
                            const event = new CustomEvent('computerStatusUpdated', { 
                                detail: {
                                    laboratory: data.laboratory,
                                    pcNumber: data.pc_number,
                                    status: 'available'
                                }
                            });
                            document.dispatchEvent(event);
                        }
                        
                        // Reload the page to reflect changes after a short delay
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showNotification("Error", 'Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification("Error", 'An error occurred. Please try again.', 'error');
                });
            }
        });
    }

    function convertToSitIn(reservationId) {
        showConfirmModal("Are you sure you want to check in this student?", "Confirm Check In", (confirmed) => {
            if (confirmed) {
                console.log("Converting reservation ID: " + reservationId + " to sit-in"); // Debug log
                
                // Create form data to send
                const formData = new FormData();
                formData.append('reservation_id', reservationId);
                
                // Send AJAX request to convert reservation to sit-in
                fetch('../controller/convert_to_sitin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Response data:", data);
                    if (data.success) {
                        showNotification("Success", "Student has been checked in successfully!", 'success');
                        
                        // Reload the page to reflect changes after a short delay
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showNotification("Error", 'Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification("Error", 'An error occurred. Please try again.', 'error');
                });
            }
        });
    }
    
    function cancelReservation(reservationId) {
        showConfirmModal("Are you sure you want to cancel this reservation?", "Confirm Cancellation", (confirmed) => {
            if (confirmed) {
                console.log("Cancelling reservation ID: " + reservationId); // Debug log
                
                // Create form data to send
                const formData = new FormData();
                formData.append('reservation_id', reservationId);
                formData.append('action', 'cancel');
                
                // Send AJAX request to cancel reservation
                fetch('../controller/process_reservation_update.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Response data:", data);
                    if (data.success) {
                        showNotification("Success", "Reservation has been cancelled successfully!", 'success');
                        
                        // Reload the page to reflect changes after a short delay
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showNotification("Error", 'Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification("Error", 'An error occurred. Please try again.', 'error');
                });
            }
        });
    }
    
    // Consolidated function to update sessions display
    function updateSessionsDisplay(sessions) {
        const maxSessions = 30; // Maximum number of sessions
        const remainingElement = document.getElementById('remainingSessions');
        const progressBar = document.getElementById('sessionsProgress');
        
        // Update the number
        remainingElement.textContent = sessions;
        
        // Calculate percentage
        const percentage = (sessions / maxSessions) * 100;
        
        // Update progress bar width
        progressBar.style.width = `${percentage}%`;
        
        // Update colors based on remaining sessions
        if (sessions <= 5) {
            progressBar.className = 'bg-gradient-to-r from-red-500 to-red-400 h-full rounded-full transition-all duration-500';
            remainingElement.style.color = '#ef4444';
            remainingElement.style.background = 'linear-gradient(135deg, rgba(239,68,68,0.08), rgba(239,68,68,0.08))';
            remainingElement.style.borderColor = 'rgba(239,68,68,0.15)';
        } else if (sessions <= 10) {
            progressBar.className = 'bg-gradient-to-r from-yellow-500 to-yellow-400 h-full rounded-full transition-all duration-500';
            remainingElement.style.color = '#d97706';
            remainingElement.style.background = 'linear-gradient(135deg, rgba(217,119,6,0.08), rgba(217,119,6,0.08))';
            remainingElement.style.borderColor = 'rgba(217,119,6,0.15)';
        } else {
            progressBar.className = 'bg-gradient-to-r from-violet-500 to-fuchsia-500 h-full rounded-full transition-all duration-500';
            remainingElement.style.color = '#7556cc';
            remainingElement.style.background = 'linear-gradient(135deg, rgba(117,86,204,0.08), rgba(213,105,167,0.08))';
            remainingElement.style.borderColor = 'rgba(117,86,204,0.15)';
        }
    }

    // Consolidated student search functionality
    function searchStudent() {
        let idno = document.getElementById('student_idno').value.trim();
        const studentInfo = document.getElementById('studentInfo');
        
        if (idno.length < 5) {
            showNotification("Warning", 'Please enter at least 5 characters of the student ID', 'warning');
            return;
        }
        
        // Show loading indicator
        document.getElementById('searchStudentBtn').innerHTML = '<i class="ri-loader-4-line"></i> Searching...';
        document.getElementById('searchStudentBtn').disabled = true;
        
        // Reset animation by hiding and removing animation classes
        studentInfo.style.display = 'none';
        studentInfo.classList.remove('animated');
        
        // Remove any previous active warning
        const existingWarning = document.querySelector('.active-student-warning');
        if (existingWarning) {
            existingWarning.remove();
        }
        
        // Search for student with this ID
        fetch('../controller/search_student.php?idno=' + idno)
            .then(response => {
                // Check if the response is ok
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Search response:', data); // Debug log
                
                if (data.success) {
                    // Display student information
                    const student = data.student;
                    document.getElementById('display_idno').value = student.idno || '';
                    document.getElementById('display_course').textContent = student.course || 'Not specified';
                    document.getElementById('display_year').value = student.year_level_display || 'Not specified';
                    updateSessionsDisplay(student.remaining_sessions || 30);
                    
                    // Display student profile image and name
                    const profileImage = document.getElementById('display_profile_image');
                    profileImage.src = student.profile_image || '../assets/images/logo/AVATAR.png';
                    profileImage.onerror = function() {
                        this.src = '../assets/images/logo/AVATAR.png';
                    };
                    document.getElementById('display_student_name').textContent = 
                        student.firstname + ' ' + student.lastname;
                    
                    // Store the student ID for form submission
                    const hiddenIdField = document.createElement('input');
                    hiddenIdField.type = 'hidden';
                    hiddenIdField.name = 'student_id';
                    hiddenIdField.value = student.id;
                    
                    // Remove any existing hidden field before adding a new one
                    const existingField = document.querySelector('input[name="student_id"]');
                    if (existingField) existingField.remove();
                    document.getElementById('addSitInForm').appendChild(hiddenIdField);
                    
                    // Show the student info section with animation
                    studentInfo.style.display = 'flex'; // Changed from 'grid' to 'flex'
                    
                    // Force a reflow to ensure animation triggers properly
                    void studentInfo.offsetWidth;
                    
                    // Add animation class to trigger the animation
                    studentInfo.classList.add('animated');
                    
                    // Check if student is already active in a laboratory
                    if (student.is_active) {
                        // Create warning message
                        const warningDiv = document.createElement('div');
                        warningDiv.className = 'active-student-warning';
                        warningDiv.innerHTML = `

                            <i class="ri-error-warning-line"></i>
                            <p>This student is currently active in Laboratory ${student.active_lab} since ${student.active_time}. 
                            They must be timed out before adding a new sit-in record.</p>
                        `;
                        
                        // Add warning after student info
                        studentInfo.parentNode.insertBefore(warningDiv, studentInfo.nextSibling);
                        
                        // Disable the submit button
                        document.getElementById('submitSitinBtn').disabled = true;
                        document.getElementById('submitSitinBtn').classList.remove('active');
                    } else {
                        // Enable the button if all fields are filled
                        validateForm();
                    }
                } else {
                    studentInfo.style.display = 'none';
                    showNotification("Not Found", 'Student not found. Please check the ID number.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                studentInfo.style.display = 'none';
                
                // Provide more helpful error message
                if (error.message && error.message.includes('Network response was not ok')) {
                    showNotification("Server Error", 'Server error: ' + error.message, 'error');
                } else {
                    showNotification("Error", 'Error searching for student. Please try again.', 'error');
                }
            })
            .finally(() => {
                // Reset button state
                document.getElementById('searchStudentBtn').innerHTML = '<i class="ri-search-line"></i> Search';
                document.getElementById('searchStudentBtn').disabled = false;
            });
    }
    
    // Form validation
    function validateForm() {
        const requiredFields = [
            { id: 'student_idno', check: () => document.getElementById('studentInfo').style.display !== 'none' },
            { id: 'purpose', check: () => document.getElementById('purpose').value !== '' },
            { id: 'laboratory', check: () => document.getElementById('laboratory').value !== '' },
        ];

        const isValid = requiredFields.every(field => field.check());
        const submitBtn = document.getElementById('submitSitinBtn');
        
        // Check if there's an active warning
        const hasActiveWarning = document.querySelector('.active-student-warning') !== null;
        
        if (isValid && !hasActiveWarning) {
            submitBtn.classList.add('active');
            submitBtn.disabled = false;
        } else {
            submitBtn.classList.remove('active');
            submitBtn.disabled = true;
        }
        
        return isValid && !hasActiveWarning;
    }

    function submitAddSitIn() {
        if (!validateForm()) {
            showNotification("Form Incomplete", 'Please fill out all required fields.', 'warning');
            return;
        }
        
        // Set current date and time
        const now = new Date();
        
        // Format date as YYYY-MM-DD
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const dateString = `${year}-${month}-${day}`;
        
        // Format time as HH:MM:SS
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const timeString = `${hours}:${minutes}:${seconds}`;
        
        // Set hidden form fields
        document.getElementById('sit_in_date').value = dateString;
        document.getElementById('sit_in_time').value = timeString;
        
        // Ensure the pc_number field has a default value
        if (!document.getElementById('selected_pc').value) {
            document.getElementById('selected_pc').value = '1';
        }
        
        const formData = new FormData(document.getElementById('addSitInForm'));
        
        // For debugging - log form data
        console.log("Submitting form with data:");
        for (const [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        fetch('../controller/add_sitin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification("Success", 'Student added to sit-in successfully', 'success');
                
                // Reset the form
                document.getElementById('addSitInForm').reset();
                document.getElementById('studentInfo').style.display = 'none';
                
                // Switch back to the current students tab and reload after a short delay
                setTimeout(() => {
                    document.querySelector('.filter-tab[data-target="current-students"]').click();
                    location.reload();
                }, 1500);
            } else {
                showNotification("Error", 'Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification("Error", 'An error occurred while adding the student: ' + error.message, 'error');
        });
    }

    // Set up event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listener for laboratory selection (form validation only)
        document.getElementById('laboratory')?.addEventListener('change', validateForm);
        
        // Add event listener for purpose selection
        document.getElementById('purpose')?.addEventListener('change', validateForm);
        
        // Add search button click handler 
        document.getElementById('searchStudentBtn')?.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent form submission
            searchStudent();
        });

        // Add enter key support for student ID field
        document.getElementById('student_idno')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevent form submission
                searchStudent();
            }
        });
        
        // Remove the automatic search on input to eliminate double panel issue
        // We'll only search when the button is clicked or Enter is pressed
    });

    // Add this to your JavaScript file or in a script tag
    document.addEventListener('DOMContentLoaded', function() {
        const cancelButton = document.getElementById('cancelSitinBtn');
        
        if (cancelButton) {
            cancelButton.addEventListener('click', function() {
                // Hide the student info grid
                const studentInfo = document.getElementById('studentInfo');
                if (studentInfo) {
                    studentInfo.style.display = 'none';
                }
                
                // Clear form inputs
                document.getElementById('addSitInForm').reset();
                document.getElementById('student_idno').value = '';
                
                // Reset select dropdowns
                const selectElements = document.querySelectorAll('select');
                selectElements.forEach(select => {
                    select.value = '';
                });
                
                // Reset submit button state
                const submitBtn = document.getElementById('submitSitinBtn');
                if (submitBtn) {
                    submitBtn.classList.remove('active');
                    submitBtn.disabled = true;
                }
                
                // Remove any active student warnings
                const warnings = document.querySelectorAll('.active-student-warning');
                warnings.forEach(warning => {
                    warning.remove();
                });
            });
        }
        
        // Add the missing form submission handler
        const sitInForm = document.getElementById('addSitInForm');
        if (sitInForm) {
            sitInForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                submitAddSitIn();
            });
        }
    });
    </script>
</body>
</html>