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
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
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
            border: 1px solid #e0e0e0;
        }
        
        .filter-tabs {
            animation: fadeIn 0.7s ease-out forwards;
        }
        
        .report-container {
            animation: fadeIn 0.8s ease-out forwards;
        }
    </style>
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
                <div class="export-buttons">
                    <button class="export-btn" id="exportCSV"><i class="ri-file-text-line"></i> CSV</button>
                    <button class="export-btn" id="exportExcel"><i class="ri-file-excel-line"></i> Excel</button>
                    <button class="export-btn" id="exportPDF"><i class="ri-file-pdf-line"></i> PDF</button>
                    <button class="export-btn" id="printReport"><i class="ri-printer-line"></i> Print</button>
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
                                        <td><?php echo date('h:i A', strtotime($record['time_in'])); ?></td>
                                        <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : 'Active'; ?></td>
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
        /* Export buttons styling */
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-right: 15px;
        }

        .export-btn {
            background-color:rgba(118, 86, 204, 0.26);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            color: #7556CC;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.2s;
            transition: all 0.2s;
            text-decoration: none;
        }

        .export-btn:hover {
            transform: translateY(-2px);
        }

        .export-btn i {
            font-size: 1rem;
        }

        .table-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        @media print {
            @page {
                size: landscape;
                margin: 1cm;
            }
            
            body {
                font-family: Arial, sans-serif;
            }
            
            .table-header h2 {
                text-align: center;
                margin-bottom: 20px;
            }
            
            .modern-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .modern-table th {
                background-color: #7556CC !important;
                color: white !important;
                padding: 8px;
                text-align: left;
            }
            
            .modern-table td {
                padding: 8px;
                border-bottom: 1px solid #ddd;
            }
            
            .modern-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            
            .source-badge {
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
            }
            
            .source-badge.reservation {
                background-color: #e0f2f1 !important;
                color: #00796b !important;
            }
            
            .source-badge.sit_in {
                background-color: #e8eaf6 !important;
                color: #3f51b5 !important;
            }

            .export-buttons {
                display: flex;
                gap: 10px;
                margin-right: 15px;
            }

            .export-btn {
                background-color: #7556CC;
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.85rem;
                display: flex;
                align-items: center;
                gap: 5px;
                transition: background-color 0.2s;
            }

            .export-btn:hover {
                background-color:rgba(99, 74, 173, 0.53);
            }

            .export-btn i {
                font-size: 1rem;
            }

            .table-actions {
                display: flex;
                align-items: center;
            }

            @media print {
                .nav-container, .filter-tabs, .pagination-controls, .table-actions, .export-buttons {
                    display: none !important;
                }
                
                .content-wrapper {
                    margin: 0;
                    padding: 0;
                }
                
                .table-header h2 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                
                body {
                    print-color-adjust: exact;
                    -webkit-print-color-adjust: exact;
                }
            }
        }
    </style>

    <script>
        // Add the missing updateClock function
        function updateClock() {
            // Simple placeholder for clock functionality
        }
        
        // Update realtime elements
        function updateRealTimeElements() {
            // Simple placeholder for clock functionality
        }

        // Pagination functionality - FIXED
        let currentPage = 1;
        let entriesPerPage = 10;
        let feedbackCurrentPage = 1;
        let feedbackEntriesPerPage = 10;
        let currentFilter = 'all'; // Track the current filter
        let currentFeedbackFilter = 'all'; // Track the current feedback filter
        
        // COMPLETELY REWRITTEN TAB NAVIGATION CODE
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM Loaded - Setting up tab navigation");
            
            // REMOVE ALL EXISTING CLICK HANDLERS to prevent duplicates
            document.querySelectorAll('.filter-tab').forEach(tab => {
                const clone = tab.cloneNode(true);
                tab.parentNode.replaceChild(clone, tab);
            });
            
            // 1. MAIN TABS (Activity vs Feedback)
            const mainTabs = document.querySelectorAll('.report-type-tabs .filter-tab');
            mainTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const reportType = this.getAttribute('data-report');
                    console.log(`Main tab clicked: ${reportType}`);
                    
                    // Update active states
                    mainTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show/hide containers
                    document.querySelectorAll('.report-container').forEach(container => {
                        container.classList.remove('active');
                    });
                    
                    const targetContainer = document.getElementById(`${reportType}-reports`);
                    targetContainer.classList.add('active');
                    
                    // Reset search
                    document.getElementById('searchInput').value = '';
                    
                    // Apply correct pagination
                    if (reportType === 'activity') {
                        filterActivityRows(currentFilter);
                        applyPagination();
                    } else {
                        filterFeedbackRows(currentFeedbackFilter);
                        applyFeedbackPagination();
                    }
                });
            });
            
            // 2. ACTIVITY FILTER TABS (All/Reservations/Direct Sit-ins)
            const activityFilterTabs = document.querySelectorAll('#activity-reports .filter-tabs .filter-tab');
            activityFilterTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const filterValue = this.getAttribute('data-filter');
                    console.log(`Activity filter clicked: ${filterValue}`);
                    
                    // Update active states
                    activityFilterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Apply filter
                    filterActivityRows(filterValue);
                    applyPagination();
                });
            });
            
            // 3. FEEDBACK FILTER TABS (All/5 Stars/4 Stars/etc)
            const feedbackFilterTabs = document.querySelectorAll('#feedback-reports .filter-tabs .filter-tab');
            feedbackFilterTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const filterValue = this.getAttribute('data-feedback-filter');
                    console.log(`Feedback filter clicked: ${filterValue}`);
                    
                    // Update active states
                    feedbackFilterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Apply filter
                    filterFeedbackRows(filterValue);
                    applyFeedbackPagination();
                });
            });
            
            // Initialize pagination controls
            initPaginationControls();
            
            // Apply initial filters and pagination
            filterActivityRows('all');
            filterFeedbackRows('all');
            applyPagination();
            applyFeedbackPagination();
            
            // Update realtime elements
            updateClock();
            setInterval(updateRealTimeElements, 1000);
            
            // Initialize search
            setupSearch();
            
            // Make feedback messages expandable
            initializeFeedbackMessages();
            
            // Initialize export buttons
            document.getElementById('exportCSV').addEventListener('click', exportToCSV);
            document.getElementById('exportExcel').addEventListener('click', exportToExcel);
            document.getElementById('exportPDF').addEventListener('click', exportToPDF);
            document.getElementById('printReport').addEventListener('click', printReport);
        });
        
        // FILTER FUNCTIONS
        function filterActivityRows(filterValue, resetPage = true) {
            // Update current filter
            currentFilter = filterValue;
            console.log(`Filtering activity rows by: ${filterValue}`);
            
            const rows = document.querySelectorAll('#reports-table-body tr');
            
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return; // Skip empty state row
                
                const rowType = row.getAttribute('data-type');
                if (filterValue === 'all' || filterValue === rowType) {
                    row.classList.remove('filtered-out');
                } else {
                    row.classList.add('filtered-out');
                }
            });
            
            // Reset pagination to first page if requested
            if (resetPage) {
                currentPage = 1;
            }
        }
        
        function filterFeedbackRows(filterValue, resetPage = true) {
            // Update current filter
            currentFeedbackFilter = filterValue;
            console.log(`Filtering feedback rows by: ${filterValue}`);
            
            const rows = document.querySelectorAll('#feedback-table-body tr');
            
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return; // Skip empty state row
                
                const rowRating = row.getAttribute('data-rating');
                if (filterValue === 'all' || filterValue === rowRating) {
                    row.classList.remove('filtered-out');
                } else {
                    row.classList.add('filtered-out');
                }
            });
            
            // Reset pagination to first page if requested
            if (resetPage) {
                feedbackCurrentPage = 1;
            }
        }
        
        // SEARCH FUNCTIONALITY
        function setupSearch() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchText = this.value.toLowerCase();
                    
                    // Determine which tab is active
                    const isActivityActive = document.getElementById('activity-reports').classList.contains('active');
                    
                    if (isActivityActive) {
                        // Apply both filter and search for activity records
                        const rows = document.querySelectorAll('#reports-table-body tr');
                        
                        rows.forEach(row => {
                            if (row.querySelector('.empty-state')) return; // Skip empty state row
                            
                            const text = row.textContent.toLowerCase();
                            const rowType = row.getAttribute('data-type');
                            
                            // Apply both current filter and search
                            if ((currentFilter === 'all' || currentFilter === rowType) && text.includes(searchText)) {
                                row.classList.remove('filtered-out');
                            } else {
                                row.classList.add('filtered-out');
                            }
                        });
                        
                        // Reset to first page and apply pagination
                        currentPage = 1;
                        applyPagination();
                    } else {
                        // Apply both filter and search for feedback records
                        const rows = document.querySelectorAll('#feedback-table-body tr');
                        
                        rows.forEach(row => {
                            if (row.querySelector('.empty-state')) return; // Skip empty state row
                            
                            const text = row.textContent.toLowerCase();
                            const rowRating = row.getAttribute('data-rating');
                            
                            // Apply both current filter and search
                            if ((currentFeedbackFilter === 'all' || currentFeedbackFilter === rowRating) && text.includes(searchText)) {
                                row.classList.remove('filtered-out');
                            } else {
                                row.classList.add('filtered-out');
                            }
                        });
                        
                        // Reset to first page and apply pagination
                        feedbackCurrentPage = 1;
                        applyFeedbackPagination();
                    }
                });
            }
        }
        
        // PAGINATION FUNCTIONS
        function applyPagination() {
            // Get all rows first
            const allRows = Array.from(document.querySelectorAll('#reports-table-body tr'))
                .filter(row => !row.querySelector('.empty-state'));
            
            // Apply current filter to determine visible rows
            allRows.forEach(row => {
                const rowType = row.getAttribute('data-type');
                if (currentFilter === 'all' || currentFilter === rowType) {
                    row.classList.remove('filtered-out');
                } else {
                    row.classList.add('filtered-out');
                }
            });
            
            // Get filtered rows
            const visibleRows = allRows.filter(row => !row.classList.contains('filtered-out'));
            
            const totalPages = Math.max(1, Math.ceil(visibleRows.length / entriesPerPage));
            
            // Ensure current page is valid
            if (currentPage > totalPages) {
                currentPage = totalPages;
            }
            
            const startIndex = (currentPage - 1) * entriesPerPage;
            const endIndex = Math.min(startIndex + entriesPerPage, visibleRows.length);
            
            // Hide all rows first
            allRows.forEach(row => {
                row.style.display = 'none';
            });
            
            // Empty state handling
            if (visibleRows.length === 0) {
                const emptyRow = document.querySelector('#reports-table-body tr .empty-state');
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
            prevBtn.setAttribute('data-action', 'prev');
            prevBtn.textContent = 'Previous';
            prevBtn.disabled = currentPage === 1;
            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    applyPagination();
                }
            });
            paginationContainer.appendChild(prevBtn);
            
            // Page number buttons - show up to 5 pages
            const maxButtons = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
            let endPage = Math.min(totalPages, startPage + maxButtons - 1);
            
            if (endPage - startPage < maxButtons - 1 && startPage > 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
                pageBtn.setAttribute('data-page', i);
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
            nextBtn.setAttribute('data-action', 'next');
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
        
        function applyFeedbackPagination() {
            // Get all rows first
            const allRows = Array.from(document.querySelectorAll('#feedback-table-body tr'))
                .filter(row => !row.querySelector('.empty-state'));
            
            // Get filtered rows
            const visibleRows = allRows.filter(row => !row.classList.contains('filtered-out'));
            
            const totalPages = Math.max(1, Math.ceil(visibleRows.length / feedbackEntriesPerPage));
            
            // Ensure current page is valid
            if (feedbackCurrentPage > totalPages) {
                feedbackCurrentPage = totalPages;
            }
            
            const startIndex = (feedbackCurrentPage - 1) * feedbackEntriesPerPage;
            const endIndex = Math.min(startIndex + feedbackEntriesPerPage, visibleRows.length);
            
            // Hide all rows first
            allRows.forEach(row => {
                row.style.display = 'none';
            });
            
            // Empty state handling
            if (visibleRows.length === 0) {
                const emptyRow = document.querySelector('#feedback-table-body tr .empty-state');
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
            prevBtn.setAttribute('data-action', 'prev');
            prevBtn.textContent = 'Previous';
            prevBtn.disabled = feedbackCurrentPage === 1;
            prevBtn.addEventListener('click', () => {
                if (feedbackCurrentPage > 1) {
                    feedbackCurrentPage--;
                    applyFeedbackPagination();
                }
            });
            paginationContainer.appendChild(prevBtn);
            
            // Page number buttons - show up to 5 pages
            const maxButtons = 5;
            let startPage = Math.max(1, feedbackCurrentPage - Math.floor(maxButtons / 2));
            let endPage = Math.min(totalPages, startPage + maxButtons - 1);
            
            if (endPage - startPage < maxButtons - 1 && startPage > 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `page-btn ${i === feedbackCurrentPage ? 'active' : ''}`;
                pageBtn.setAttribute('data-page', i);
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
            nextBtn.setAttribute('data-action', 'next');
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
        
        function initPaginationControls() {
            const entriesPerPageSelect = document.getElementById('entries-per-page');
            const feedbackEntriesPerPageSelect = document.getElementById('feedback-entries-per-page');
            
            if (entriesPerPageSelect) {
                entriesPerPageSelect.value = entriesPerPage;
                entriesPerPageSelect.addEventListener('change', function() {
                    entriesPerPage = parseInt(this.value);
                    currentPage = 1; // Reset to first page
                    
                    // Apply current filter when entries per page changes
                    filterActivityRows(currentFilter, false);
                    applyPagination();
                });
            }
            
            if (feedbackEntriesPerPageSelect) {
                feedbackEntriesPerPageSelect.value = feedbackEntriesPerPage;
                feedbackEntriesPerPageSelect.addEventListener('change', function() {
                    feedbackEntriesPerPage = parseInt(this.value);
                    feedbackCurrentPage = 1; // Reset to first page
                    
                    // Apply current filter when entries per page changes
                    filterFeedbackRows(currentFeedbackFilter, false);
                    applyFeedbackPagination();
                });
            }
        }
        
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

        // EXPORT FUNCTIONS
        function getActivityReportData() {
            const table = document.querySelector('#activity-reports table');
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
            
            // Get all visible rows (not filtered out)
            const visibleRows = Array.from(table.querySelectorAll('tbody tr'))
                .filter(row => row.style.display !== 'none' && !row.querySelector('.empty-state'));
            
            const data = visibleRows.map(row => {
                return Array.from(row.querySelectorAll('td')).map(cell => {
                    // Special handling for the badge in the last column
                    if (cell.querySelector('.source-badge')) {
                        return cell.textContent.trim();
                    }
                    return cell.textContent.trim();
                });
            });
            
            return { headers, data };
        }

        function exportToCSV() {
            const { headers, data } = getActivityReportData();
            
            if (data.length === 0) {
                alert('No data to export');
                return;
            }
            
            // Create CSV content
            let csvContent = headers.join(',') + '\n';
            
            data.forEach(row => {
                // Properly escape CSV values
                const csvRow = row.map(cell => {
                    // Quote values that contain commas, quotes, or newlines
                    if (cell.includes(',') || cell.includes('"') || cell.includes('\n')) {
                        return '"' + cell.replace(/"/g, '""') + '"';
                    }
                    return cell;
                });
                csvContent += csvRow.join(',') + '\n';
            });
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'activity_report_' + new Date().toISOString().slice(0,10) + '.csv');
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function exportToExcel() {
            const { headers, data } = getActivityReportData();
            
            if (data.length === 0) {
                alert('No data to export');
                return;
            }
            
            // Create workbook with worksheet
            const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Activity Report");
            
            // Column widths
            const colWidths = headers.map(h => ({ wch: Math.max(h.length, 15) }));
            ws['!cols'] = colWidths;
            
            // Save file
            XLSX.writeFile(wb, 'activity_report_' + new Date().toISOString().slice(0,10) + '.xlsx');
        }

        function exportToPDF() {
            const { headers, data } = getActivityReportData();
            
            if (data.length === 0) {
                alert('No data to export');
                return;
            }
            
            // Create PDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'pt', 'a4'); // landscape orientation
            
            // Add title
            doc.setFontSize(16);
            doc.text('Activity Report', 40, 40);
            
            // Add date
            doc.setFontSize(10);
            doc.text('Generated on: ' + new Date().toLocaleString(), 40, 60);
            
            // Create table
            doc.autoTable({
                head: [headers],
                body: data,
                startY: 70,
                styles: {
                    fontSize: 9,
                    cellPadding: 3,
                    overflow: 'linebreak'
                },
                columnStyles: {
                    0: { cellWidth: 70 }, // Date
                    1: { cellWidth: 60 }, // ID Number
                    2: { cellWidth: 90 }, // Name
                    3: { cellWidth: 90 }, // Purpose
                    4: { cellWidth: 80 }, // Laboratory
                    5: { cellWidth: 60 }, // Time In
                    6: { cellWidth: 60 }, // Time Out
                    7: { cellWidth: 60 }  // Type
                },
                headStyles: {
                    fillColor: [117, 86, 204],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [245, 245, 250]
                }
            });
            
            // Save file
            doc.save('activity_report_' + new Date().toISOString().slice(0,10) + '.pdf');
        }

        function printReport() {
            window.print();
        }
    </script>
</body>
</html>
