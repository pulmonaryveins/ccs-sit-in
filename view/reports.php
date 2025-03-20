<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db_connect.php';
// Set timezone for proper time display
date_default_timezone_set('Asia/Manila');

// Fetch sit-in records from both reservations and sit_ins tables
$sitin_records = [];

// Fetch reservation records
$query_reservations = "SELECT r.*, u.firstname, u.lastname, u.idno, 'reservation' as source 
                       FROM reservations r
                       JOIN users u ON r.idno = u.idno
                       ORDER BY r.date DESC, r.time_in DESC";

$result = $conn->query($query_reservations);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sitin_records[] = $row;
    }
}

// Fetch direct sit-in records
$query_sitins = "SELECT s.*, u.firstname, u.lastname, u.idno, 'sit_in' as source
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

// Fetch feedback data with error handling
$feedback_records = [];

// Check if feedback table exists (prevents errors if table doesn't exist yet)
$table_check = $conn->query("SHOW TABLES LIKE 'feedback'");
$feedback_table_exists = $table_check->num_rows > 0;

if ($feedback_table_exists) {
    // Fix the SQL query to not fail when columns don't exist in the feedback table
    $query_feedback = "SELECT f.id as feedback_id, f.rating, f.message, f.created_at, 
                      COALESCE(s.id, r.id) as record_id,
                      COALESCE(s.idno, r.idno) as idno,
                      u.firstname, u.lastname,
                      COALESCE(s.laboratory, r.laboratory) as laboratory,
                      COALESCE(s.purpose, r.purpose) as purpose,
                      COALESCE(s.date, r.date) as date,
                      COALESCE(s.time_in, r.time_in) as time_in,
                      COALESCE(s.time_out, r.time_out) as time_out,
                      CASE WHEN s.id IS NOT NULL THEN 'sit_in' ELSE 'reservation' END as source,
                      COALESCE(s.status, r.status) as status
                  FROM feedback f
                  LEFT JOIN sit_ins s ON f.sit_in_id = s.id
                  LEFT JOIN reservations r ON f.reservation_id = r.id
                  LEFT JOIN users u ON COALESCE(s.idno, r.idno) = u.idno
                  WHERE (s.id IS NOT NULL OR r.id IS NOT NULL)
                  ORDER BY f.created_at DESC";

    $result = $conn->query($query_feedback);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $feedback_records[] = $row;
        }
    } else {
        // Add error logging
        error_log("Error in feedback query: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/reports.css">
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
                <a href="records.php" class="nav-link">
                    <i class="ri-bar-chart-line"></i>
                    <span>Records</span>
                </a>
                <a href="reports.php" class="nav-link active">
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
        <div class="table-wrapper">
            <div class="table-header">
                <h2>Sit-in Reports</h2>
                <div class="table-actions">
                    <div class="real-time-clock">
                        <i class="ri-time-line"></i>
                        <span id="current-time">Loading...</span>
                    </div>
                    <div class="search-box">
                        <i class="ri-search-line"></i>
                        <input type="text" id="searchInput" placeholder="Search records...">
                    </div>
                </div>
            </div>
            
            <!-- Report Type Tabs -->
            <div class="filter-tabs report-type-tabs">
                <div class="filter-tab active" data-report="activity">Activity Reports</div>
                <div class="filter-tab" data-report="feedback">Feedback Reports</div>
            </div>
            
            <!-- Activity Reports Container -->
            <div id="activity-reports" class="report-container active">
                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <div class="filter-tab active" data-filter="all">All Records</div>
                    <div class="filter-tab" data-filter="reservation">Reservations</div>
                    <div class="filter-tab" data-filter="sit_in">Direct Sit-ins</div>
                </div>
                
                <!-- Records Container -->
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
                            </tr>
                        </thead>
                        <tbody id="reports-table-body">
                            <?php if (empty($sitin_records)): ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <div class="empty-state-content">
                                            <i class="ri-computer-line"></i>
                                            <p>No records found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sitin_records as $record): ?>
                                    <tr data-type="<?php echo htmlspecialchars($record['source']); ?>">
                                        <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                        <td class="font-mono"><?php echo htmlspecialchars($record['idno']); ?></td>
                                        <td><?php echo htmlspecialchars($record['firstname'] . ' ' . $record['lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($record['purpose'] ?? 'Not specified'); ?></td>
                                        <td>Laboratory <?php echo htmlspecialchars($record['laboratory']); ?></td>
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
                                                    $timeOut = new DateTime($record['time_out'], new DateTimeZone('UTC'));
                                                    $timeOut->setTimezone(new DateTimeZone('Asia/Manila'));
                                                    echo $timeOut->format('h:i A'); // 12-hour format with Manila timezone
                                                } else if ($record['status'] == 'approved' && $record['time_in']) {
                                                    // For active sessions, show current time in Manila timezone
                                                    $currentTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
                                                    echo '<span class="realtime-out">' . $currentTime->format('h:i A') . ' (PST)</span>';
                                                } else {
                                                    echo 'Not yet'; 
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="source-badge <?php echo htmlspecialchars($record['source']); ?>">
                                                <?php echo ($record['source'] == 'reservation') ? 'Reservation' : 'Sit-in'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination controls -->
                <div class="pagination-controls" id="activity-pagination-controls">
                    <div class="entries-per-page">
                        <label for="entries-per-page">Show</label>
                        <select id="entries-per-page">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label>entries</label>
                    </div>
                    <div class="page-info" id="page-info">
                        Showing 1 to 10 of 0 entries
                    </div>
                    <div class="page-navigation" id="pagination">
                        <button class="page-btn" disabled data-action="prev">Previous</button>
                        <button class="page-btn active" data-page="1">1</button>
                        <button class="page-btn" disabled data-action="next">Next</button>
                    </div>
                </div>
            </div>
            
            <!-- Feedback Reports Container -->
            <div id="feedback-reports" class="report-container">
                <!-- Filter Tabs for Feedback -->
                <div class="filter-tabs">
                    <div class="filter-tab active" data-feedback-filter="all">All Feedback</div>
                    <div class="filter-tab" data-feedback-filter="5">5 Stars</div>
                    <div class="filter-tab" data-feedback-filter="4">4 Stars</div>
                    <div class="filter-tab" data-feedback-filter="3">3 Stars</div>
                    <div class="filter-tab" data-feedback-filter="2">2 Stars</div>
                    <div class="filter-tab" data-feedback-filter="1">1 Star</div>
                </div>
                
                <!-- Feedback Records Container -->
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>ID Number</th>
                                <th>Student Name</th>
                                <th>Laboratory</th>
                                <th>Purpose</th>
                                <th>Rating</th>
                                <th>Feedback</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody id="feedback-table-body">
                            <?php if (empty($feedback_records)): ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <div class="empty-state-content">
                                            <i class="ri-feedback-line"></i>
                                            <p>No feedback records found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($feedback_records as $feedback): ?>
                                    <tr data-rating="<?php echo htmlspecialchars($feedback['rating']); ?>" data-type="<?php echo htmlspecialchars($feedback['source']); ?>">
                                        <td><?php echo date('M d, Y', strtotime($feedback['date'])); ?></td>
                                        <td class="font-mono"><?php echo htmlspecialchars($feedback['idno']); ?></td>
                                        <td><?php echo htmlspecialchars($feedback['firstname'] . ' ' . $feedback['lastname']); ?></td>
                                        <td>Laboratory <?php echo htmlspecialchars($feedback['laboratory']); ?></td>
                                        <td><?php echo htmlspecialchars($feedback['purpose'] ?? 'Not specified'); ?></td>
                                        <td>
                                            <div class="star-rating">
                                                <?php 
                                                    $rating = intval($feedback['rating']);
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($feedback['message'] ?? 'No comment provided'); ?></td>
                                        <td>
                                            <span class="source-badge <?php echo htmlspecialchars($feedback['source']); ?>">
                                                <?php echo ($feedback['source'] == 'reservation') ? 'Reservation' : 'Sit-in'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination controls for feedback -->
                <div class="pagination-controls" id="feedback-pagination-controls">
                    <div class="entries-per-page">
                        <label for="feedback-entries-per-page">Show</label>
                        <select id="feedback-entries-per-page">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label>entries</label>
                    </div>
                    <div class="page-info" id="feedback-page-info">
                        Showing 1 to 10 of 0 entries
                    </div>
                    <div class="page-navigation" id="feedback-pagination">
                        <button class="page-btn" disabled data-action="prev">Previous</button>
                        <button class="page-btn active" data-page="1">1</button>
                        <button class="page-btn" disabled data-action="next">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Star rating styles */
        .star-rating {
            color: #fbbf24;
            display: flex;
            align-items: center;
            font-size: 1rem;
            gap: 2px;
        }
        
        .report-container {
            display: none;
        }
        
        .report-container.active {
            display: block;
        }
        
        .report-type-tabs {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .report-type-tabs .filter-tab {
            padding: 1rem 1.75rem;
            font-weight: 600;
        }
        
        /* Feedback message cell styling */
        #feedback-table-body td:nth-child(7) {
            max-width: 300px;
            white-space: normal;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
        }
        
        /* Add a class for expandable feedback messages */
        .feedback-message {
            max-height: 3em;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: max-height 0.3s ease;
        }
        
        .feedback-message.expanded {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .feedback-message::after {
            content: "...";
            position: absolute;
            bottom: 0;
            right: 0;
            background: linear-gradient(to right, transparent, white 40%);
            padding-left: 20px;
            display: block;
        }
        
        .feedback-message.expanded::after {
            display: none;
        }
    </style>

    <script>
        // Real-time clock functionality
        function updateClock() {
            const now = new Date();
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
            
            const clockElement = document.getElementById('current-time');
            if (clockElement) {
                clockElement.textContent = now.toLocaleString('en-US', options);
            }
        }

        // Update realtime elements
        function updateRealTimeElements() {
            // Update clock
            updateClock();
            
            // Update time out for active sessions
            const realTimeOuts = document.querySelectorAll('.realtime-out');
            if (realTimeOuts.length > 0) {
                // Get current time in Asia/Manila timezone
                const now = new Date();
                const options = {
                    hour: '2-digit', 
                    minute: '2-digit', 
                    hour12: true,
                    timeZone: 'Asia/Manila'
                };
                const currentTime = now.toLocaleTimeString('en-US', options);
                
                realTimeOuts.forEach(element => {
                    element.innerHTML = `${currentTime} (PST)`;
                });
            }
        }

        // Filter functionality
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Get filter value
                const filterValue = this.getAttribute('data-filter');
                
                // Filter table rows
                const rows = document.querySelectorAll('#reports-table-body tr');
                rows.forEach(row => {
                    if (row.querySelector('.empty-state')) return; // Skip empty state row
                    
                    const rowType = row.getAttribute('data-type');
                    if (filterValue === 'all' || filterValue === rowType) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Reset pagination to first page and update
                currentPage = 1;
                applyPagination();
            });
        });

        // Search functionality
        document.getElementById('searchInput')?.addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('#reports-table-body tr');
            
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return; // Skip empty state row
                
                let text = row.textContent.toLowerCase();
                const filterValue = document.querySelector('.filter-tab.active').getAttribute('data-filter');
                const rowType = row.getAttribute('data-type');
                
                // Check if row matches both filter and search
                if ((filterValue === 'all' || filterValue === rowType) && text.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Reset pagination to first page and update
            currentPage = 1;
            applyPagination();
        });

        // Pagination functionality
        let currentPage = 1;
        let entriesPerPage = 10;
        
        function applyPagination() {
            // Get visible rows (after filtering)
            const visibleRows = Array.from(document.querySelectorAll('#reports-table-body tr'))
                .filter(row => row.style.display !== 'none' && !row.querySelector('.empty-state'));
            
            const totalPages = Math.ceil(visibleRows.length / entriesPerPage);
            const startIndex = (currentPage - 1) * entriesPerPage;
            const endIndex = Math.min(startIndex + entriesPerPage, visibleRows.length);
            
            // Hide all rows first
            visibleRows.forEach(row => row.style.display = 'none');
            
            // Show only rows for current page
            for (let i = startIndex; i < endIndex; i++) {
                visibleRows[i].style.display = '';
            }
            
            // Update page info
            document.getElementById('page-info').textContent = 
                `Showing ${visibleRows.length > 0 ? startIndex + 1 : 0} to ${endIndex} of ${visibleRows.length} entries`;
                
            // Update pagination buttons
            updatePaginationButtons(totalPages);
        }
        
        function updatePaginationButtons(totalPages) {
            const paginationContainer = document.getElementById('pagination');
            paginationContainer.innerHTML = '';
            
            // Previous button
            const prevBtn = document.createElement('button');
            prevBtn.className = 'page-btn';
            prevBtn.textContent = 'Previous';
            prevBtn.disabled = currentPage === 1;
            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    applyPagination();
                }
            });
            paginationContainer.appendChild(prevBtn);
            
            // Page number buttons
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            
            if (endPage - startPage < 4 && startPage > 1) {
                startPage = Math.max(1, endPage - 4);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
                pageBtn.textContent = i;
                pageBtn.addEventListener('click', () => {
                    currentPage = i;
                    applyPagination();
                });
                paginationContainer.appendChild(pageBtn);
            }
            
            // Next button
            const nextBtn = document.createElement('button');
            nextBtn.className = 'page-btn';
            nextBtn.textContent = 'Next';
            nextBtn.disabled = currentPage === totalPages || totalPages === 0;
            nextBtn.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    applyPagination();
                }
            });
            paginationContainer.appendChild(nextBtn);
        }
        
        // Update entries per page
        document.getElementById('entries-per-page')?.addEventListener('change', function() {
            entriesPerPage = parseInt(this.value);
            currentPage = 1; // Reset to first page
            applyPagination();
        });

        // Initialize functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Start the clock
            updateClock();
            setInterval(updateRealTimeElements, 1000);
            
            // Initialize pagination
            applyPagination();
        });
        
        // Report type tab switching
        document.querySelectorAll('.report-type-tabs .filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all report type tabs
                document.querySelectorAll('.report-type-tabs .filter-tab').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Get report type
                const reportType = this.getAttribute('data-report');
                
                // Hide all report containers
                document.querySelectorAll('.report-container').forEach(container => {
                    container.classList.remove('active');
                });
                
                // Show the selected report container
                document.getElementById(reportType + '-reports').classList.add('active');
                
                // Reset search input when switching report types
                document.getElementById('searchInput').value = '';
                
                // Initialize appropriate pagination
                if (reportType === 'activity') {
                    currentPage = 1;
                    applyPagination();
                } else if (reportType === 'feedback') {
                    feedbackCurrentPage = 1;
                    applyFeedbackPagination();
                }
            });
        });
        
        // Feedback filter functionality
        document.querySelectorAll('[data-feedback-filter]').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all feedback filter tabs
                document.querySelectorAll('[data-feedback-filter]').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Get filter value (rating)
                const filterValue = this.getAttribute('data-feedback-filter');
                
                // Filter feedback table rows
                const rows = document.querySelectorAll('#feedback-table-body tr');
                rows.forEach(row => {
                    if (row.querySelector('.empty-state')) return; // Skip empty state row
                    
                    const rowRating = row.getAttribute('data-rating');
                    if (filterValue === 'all' || filterValue === rowRating) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Reset pagination to first page and update
                feedbackCurrentPage = 1;
                applyFeedbackPagination();
            });
        });
        
        // Activity tab pagination functionality (existing)
        // Note: currentPage and entriesPerPage are already declared above
        
        // Feedback tab pagination functionality
        let feedbackCurrentPage = 1;
        let feedbackEntriesPerPage = 10;
        
        function applyFeedbackPagination() {
            // Get visible rows (after filtering)
            const visibleRows = Array.from(document.querySelectorAll('#feedback-table-body tr'))
                .filter(row => row.style.display !== 'none' && !row.querySelector('.empty-state'));
            
            const totalPages = Math.ceil(visibleRows.length / feedbackEntriesPerPage);
            const startIndex = (feedbackCurrentPage - 1) * feedbackEntriesPerPage;
            const endIndex = Math.min(startIndex + feedbackEntriesPerPage, visibleRows.length);
            
            // Hide all rows first
            visibleRows.forEach(row => row.style.display = 'none');
            
            // Show only rows for current page
            for (let i = startIndex; i < endIndex; i++) {
                visibleRows[i].style.display = '';
            }
            
            // Update page info
            document.getElementById('feedback-page-info').textContent = 
                `Showing ${visibleRows.length > 0 ? startIndex + 1 : 0} to ${endIndex} of ${visibleRows.length} entries`;
                
            // Update pagination buttons
            updateFeedbackPaginationButtons(totalPages);
        }
        
        function updateFeedbackPaginationButtons(totalPages) {
            const paginationContainer = document.getElementById('feedback-pagination');
            paginationContainer.innerHTML = '';
            
            // Previous button
            const prevBtn = document.createElement('button');
            prevBtn.className = 'page-btn';
            prevBtn.textContent = 'Previous';
            prevBtn.disabled = feedbackCurrentPage === 1;
            prevBtn.addEventListener('click', () => {
                if (feedbackCurrentPage > 1) {
                    feedbackCurrentPage--;
                    applyFeedbackPagination();
                }
            });
            paginationContainer.appendChild(prevBtn);
            
            // Page number buttons
            let startPage = Math.max(1, feedbackCurrentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            
            if (endPage - startPage < 4 && startPage > 1) {
                startPage = Math.max(1, endPage - 4);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `page-btn ${i === feedbackCurrentPage ? 'active' : ''}`;
                pageBtn.textContent = i;
                pageBtn.addEventListener('click', () => {
                    feedbackCurrentPage = i;
                    applyFeedbackPagination();
                });
                paginationContainer.appendChild(pageBtn);
            }
            
            // Next button
            const nextBtn = document.createElement('button');
            nextBtn.className = 'page-btn';
            nextBtn.textContent = 'Next';
            nextBtn.disabled = feedbackCurrentPage === totalPages || totalPages === 0;
            nextBtn.addEventListener('click', () => {
                if (feedbackCurrentPage < totalPages) {
                    feedbackCurrentPage++;
                    applyFeedbackPagination();
                }
            });
            paginationContainer.appendChild(nextBtn);
        }
        
        // Update entries per page for feedback
        document.getElementById('feedback-entries-per-page')?.addEventListener('change', function() {
            feedbackEntriesPerPage = parseInt(this.value);
            feedbackCurrentPage = 1; // Reset to first page
            applyFeedbackPagination();
        });
        
        // Apply search to both tables
        document.getElementById('searchInput')?.addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            
            // If activity reports are active
            if (document.getElementById('activity-reports').classList.contains('active')) {
                const rows = document.querySelectorAll('#reports-table-body tr');
                
                rows.forEach(row => {
                    if (row.querySelector('.empty-state')) return; // Skip empty state row
                    
                    let text = row.textContent.toLowerCase();
                    const filterValue = document.querySelector('#activity-reports .filter-tab.active').getAttribute('data-filter');
                    const rowType = row.getAttribute('data-type');
                    
                    // Check if row matches both filter and search
                    if ((filterValue === 'all' || filterValue === rowType) && text.includes(searchText)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Reset pagination to first page and update
                currentPage = 1;
                applyPagination();
            }
            
            // If feedback reports are active
            if (document.getElementById('feedback-reports').classList.contains('active')) {
                const rows = document.querySelectorAll('#feedback-table-body tr');
                
                rows.forEach(row => {
                    if (row.querySelector('.empty-state')) return; // Skip empty state row
                    
                    let text = row.textContent.toLowerCase();
                    const filterValue = document.querySelector('#feedback-reports .filter-tab.active').getAttribute('data-feedback-filter');
                    const rowRating = row.getAttribute('data-rating');
                    
                    // Check if row matches both filter and search
                    if ((filterValue === 'all' || filterValue === rowRating) && text.includes(searchText)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Reset pagination to first page and update
                feedbackCurrentPage = 1;
                applyFeedbackPagination();
            }
        });

        // Initialize functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Start the clock
            updateClock();
            setInterval(updateRealTimeElements, 1000);
            
            // Initialize activity pagination
            applyPagination();
            
            // Initialize feedback pagination
            applyFeedbackPagination();
            
            // Make feedback messages expandable
            initializeFeedbackMessages();
        });
        
        // Function to make feedback messages expandable
        function initializeFeedbackMessages() {
            // Get all message cells in the feedback table
            const messageCells = document.querySelectorAll('#feedback-table-body td:nth-child(7)');
            
            messageCells.forEach(cell => {
                const text = cell.textContent;
                
                // Only apply to cells with substantial text content
                if (text && text.length > 50 && text !== 'No comment provided') {
                    // Create wrapper for the message
                    const wrapper = document.createElement('div');
                    wrapper.className = 'feedback-message';
                    wrapper.textContent = text;
                    
                    // Clear the cell and append the wrapper
                    cell.textContent = '';
                    cell.appendChild(wrapper);
                    
                    // Add click event to toggle expansion
                    cell.addEventListener('click', function() {
                        const messageEl = this.querySelector('.feedback-message');
                        messageEl.classList.toggle('expanded');
                    });
                }
            });
        }
    </script>
</body>
</html>
