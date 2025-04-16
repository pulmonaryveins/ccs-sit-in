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

        /* Export buttons styling */
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-right: 15px;
        }

        .export-btn {
            background: #e9e9ff;
            color: #6d28d9;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
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
        
        /* Date filter styling */
        .date-filter {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: 15px;
        }
        
        .date-picker-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .date-picker-wrapper i {
            position: absolute;
            left: 10px;
            color: #7556cc;
            pointer-events: none;
        }
        
        .date-picker {
            padding: 8px 10px 8px 35px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.875rem;
            background-color: #f8f9fa;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .date-picker:focus {
            border-color: #7556cc;
            box-shadow: 0 0 0 3px rgba(117,86,204,0.1);
            outline: none;
        }
        
        .reset-date-btn {
            background: #e9e9ff;
            color: #7556cc;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .reset-date-btn:hover {
            background: #d9d9ff;
            transform: rotate(180deg);
        }
        
        /* Active date filter indicator */
        .date-filter.active .date-picker {
            background-color: #f0e6ff;
            border-color: #7556cc;
            font-weight: 500;
        }
        
        .date-filter.active .date-picker-wrapper i {
            color: #7556cc;
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
                font-size: 1.5rem;
                background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
                -webkit-background-clip: text;
                background-clip: text;
                -webkit-text-fill-color: transparent;
                font-weight: 500;
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

        /* Print and PDF styles */
        @media print {
            @page {
                size: landscape;
                margin: 1.5cm 1cm;
            }
            
            body {
                font-family: Arial, sans-serif;
                padding: 0;
                margin: 0;
            }
            
            .print-header {
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #7556cc;
                padding-bottom: 15px;
            }
            
            .logo-container {
                display: flex !important;
                align-items: center;
                gap: 20px;
            }
            
            .logo-container img {
                height: 60px;
                width: auto;
            }
            
            .institution-info {
                text-align: center;
                flex-grow: 1;
            }
            
            .institution-info h1 {
                font-size: 18px;
                font-weight: bold;
                margin: 0;
                color: #333;
            }
            
            .institution-info h2 {
                font-size: 16px;
                font-weight: normal;
                margin: 5px 0 0;
                color: #333;
            }
            
            .print-date {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
            
            .report-title {
                font-size: 16px;
                font-weight: 600;
                text-align: center;
                margin: 10px 0;
                color: #333;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
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
                font-size: 12px;
                border: 1px solid #5d44a5;
            }
            
            .modern-table td {
                padding: 8px;
                border: 1px solid #ddd;
                font-size: 11px;
            }
            
            .modern-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            
            .source-badge {
                padding: 3px 6px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: normal;
                text-transform: uppercase;
            }
            
            .source-badge.reservation {
                background-color: #e0f2f1 !important;
                color: #00796b !important;
            }
            
            .source-badge.sit_in {
                background-color: #e8eaf6 !important;
                color: #3f51b5 !important;
            }

            .star-rating {
                font-size: 10px;
                color: #fbbf24 !important;
            }

            .star-rating i {
                color: #fbbf24 !important;
            }

            .nav-container, .filter-tabs, .pagination-controls, .table-actions, .export-buttons {
                display: none !important;
            }
            
            .content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            .print-footer {
                display: flex !important;
                justify-content: space-between;
                margin-top: 20px;
                font-size: 10px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
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
                <a href="leaderboard.php" class="nav-link">
                    <i class="ri-trophy-line"></i>
                    <span>Leaderboard</span>
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

    <!-- Print header - only visible when printing or in PDF export -->
    <div class="print-header" style="display: none;">
        <div class="logo-container">
            <img src="../assets/images/logo/uc.png" alt="UC Logo">
            <img src="../assets/images/logo/ccs.png" alt="CCS Logo">
        </div>
        <div class="institution-info">
            <h1>College of Computer Studies</h1>
            <h2>University of Cebu - Main Campus</h2>
            <div class="print-date">Generated on: <span id="print-date-value"></span></div>
        </div>
        <div style="width: 80px;"></div> <!-- Spacer for alignment -->
    </div>

    <div class="content-wrapper">
        <div class="table-wrapper">
            <!-- Report title - only visible when printing or in PDF export -->
            <div id="report-title" class="report-title" style="display: none;">Sit-in Activity Report</div>
            
            <div class="table-header">
                <h2>Sit-in Reports</h2>
                <div class="table-actions">
                    <div class="export-buttons">
                        <button class="export-btn" id="exportCSV"><i class="ri-file-text-line"></i> CSV</button>
                        <button class="export-btn" id="exportExcel"><i class="ri-file-excel-line"></i> Excel</button>
                        <button class="export-btn" id="exportPDF"><i class="ri-file-pdf-line"></i> PDF</button>
                        <button class="export-btn" id="printReport"><i class="ri-printer-line"></i> Print</button>
                    </div>
                    <div class="date-filter">
                        <div class="date-picker-wrapper">
                            <i class="ri-calendar-line"></i>
                            <input type="date" id="dateFilter" class="date-picker" placeholder="Filter by date">
                        </div>
                        <button id="resetDateFilter" class="reset-date-btn" title="Reset date filter">
                            <i class="ri-refresh-line"></i>
                        </button>
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
            
            <!-- Print footer - only visible when printing or in PDF export -->
            <div class="print-footer" style="display: none;">
                <div>Date Generated: <span id="print-footer-date"></span></div>
                <div>CCS Sit-In System © University of Cebu</div>
            </div>
        </div>
    </div>

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
        let selectedDate = null; // Track the selected date
        
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
            
            // Initialize date filter
            initDateFilter();
            
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
        
        // Initialize date filter functionality
        function initDateFilter() {
            const dateFilter = document.getElementById('dateFilter');
            const resetDateFilter = document.getElementById('resetDateFilter');
            const dateFilterContainer = document.querySelector('.date-filter');
            
            if (dateFilter && resetDateFilter) {
                // Date filter event handler
                dateFilter.addEventListener('change', function() {
                    selectedDate = this.value ? new Date(this.value) : null;
                    
                    // Add active class to the date filter container when a date is selected
                    if (selectedDate) {
                        dateFilterContainer.classList.add('active');
                    } else {
                        dateFilterContainer.classList.remove('active');
                    }
                    
                    // Apply filters based on which tab is active
                    const isActivityTabActive = document.getElementById('activity-reports').classList.contains('active');
                    
                    if (isActivityTabActive) {
                        filterActivityRows(currentFilter);
                        applyPagination();
                    } else {
                        filterFeedbackRows(currentFeedbackFilter);
                        applyFeedbackPagination();
                    }
                });
                
                // Reset date filter button
                resetDateFilter.addEventListener('click', function() {
                    dateFilter.value = '';
                    selectedDate = null;
                    dateFilterContainer.classList.remove('active');
                    
                    // Apply filters based on which tab is active
                    const isActivityTabActive = document.getElementById('activity-reports').classList.contains('active');
                    
                    if (isActivityTabActive) {
                        filterActivityRows(currentFilter);
                        applyPagination();
                    } else {
                        filterFeedbackRows(currentFeedbackFilter);
                        applyFeedbackPagination();
                    }
                });
            }
        }
        
        // FILTER FUNCTIONS
        function filterActivityRows(filterValue, resetPage = true) {
            // Update current filter
            currentFilter = filterValue;
            console.log(`Filtering activity rows by: ${filterValue}`);
            
            const rows = document.querySelectorAll('#reports-table-body tr');
            
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return; // Skip empty state row
                
                const rowType = row.getAttribute('data-type');
                const dateCell = row.querySelector('td:first-child');
                let meetDateCriteria = true;
                
                // Apply date filter if selected
                if (selectedDate && dateCell) {
                    const cellDate = new Date(dateCell.textContent);
                    // Compare year, month, and day only
                    meetDateCriteria = cellDate.getFullYear() === selectedDate.getFullYear() &&
                                      cellDate.getMonth() === selectedDate.getMonth() &&
                                      cellDate.getDate() === selectedDate.getDate();
                }
                
                if ((filterValue === 'all' || filterValue === rowType) && meetDateCriteria) {
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
                const dateCell = row.querySelector('td:first-child');
                let meetDateCriteria = true;
                
                // Apply date filter if selected
                if (selectedDate && dateCell) {
                    const cellDate = new Date(dateCell.textContent);
                    // Compare year, month, and day only
                    meetDateCriteria = cellDate.getFullYear() === selectedDate.getFullYear() &&
                                      cellDate.getMonth() === selectedDate.getMonth() &&
                                      cellDate.getDate() === selectedDate.getDate();
                }
                
                if ((filterValue === 'all' || filterValue === rowRating) && meetDateCriteria) {
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
                            const dateCell = row.querySelector('td:first-child');
                            let meetDateCriteria = true;
                            
                            // Apply date filter if selected
                            if (selectedDate && dateCell) {
                                const cellDate = new Date(dateCell.textContent);
                                meetDateCriteria = cellDate.getFullYear() === selectedDate.getFullYear() &&
                                                  cellDate.getMonth() === selectedDate.getMonth() &&
                                                  cellDate.getDate() === selectedDate.getDate();
                            }
                            
                            // Apply current filter, date filter, and search
                            if ((currentFilter === 'all' || currentFilter === rowType) && 
                                meetDateCriteria && 
                                text.includes(searchText)) {
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
                            const dateCell = row.querySelector('td:first-child');
                            let meetDateCriteria = true;
                            
                            // Apply date filter if selected
                            if (selectedDate && dateCell) {
                                const cellDate = new Date(dateCell.textContent);
                                meetDateCriteria = cellDate.getFullYear() === selectedDate.getFullYear() &&
                                                  cellDate.getMonth() === selectedDate.getMonth() &&
                                                  cellDate.getDate() === selectedDate.getDate();
                            }
                            
                            // Apply current filter, date filter, and search
                            if ((currentFeedbackFilter === 'all' || currentFeedbackFilter === rowRating) && 
                                meetDateCriteria && 
                                text.includes(searchText)) {
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

        function getFeedbackReportData() {
            const table = document.querySelector('#feedback-reports table');
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
            
            // Get all visible rows (not filtered out)
            const visibleRows = Array.from(table.querySelectorAll('tbody tr'))
                .filter(row => row.style.display !== 'none' && !row.querySelector('.empty-state'));
            
            const data = visibleRows.map(row => {
                return Array.from(row.querySelectorAll('td')).map(cell => {
                    // Special handling for the star rating
                    if (cell.querySelector('.star-rating')) {
                        return cell.textContent.trim();
                    }
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
            // Determine which report type is active
            const isActivityReport = document.getElementById('activity-reports').classList.contains('active');
            const { headers, data } = isActivityReport ? getActivityReportData() : getFeedbackReportData();
            
            if (data.length === 0) {
                alert('No data to export');
                return;
            }
            
            // Create PDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'pt', 'a4'); // landscape orientation
            
            // Add logos and header
            const pageWidth = doc.internal.pageSize.getWidth();
            const currentDate = new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Add UC logo
            doc.addImage('../assets/images/logo/uc.png', 'PNG', 40, 20, 50, 50);
            
            // Add CCS logo
            doc.addImage('../assets/images/logo/ccs.png', 'PNG', pageWidth - 90, 20, 50, 50);
            
            // Add institutional header
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('College of Computer Studies', pageWidth / 2, 40, { align: 'center' });
            
            doc.setFontSize(14);
            doc.setFont('helvetica', 'normal');
            doc.text('University of Cebu - Main Campus', pageWidth / 2, 60, { align: 'center' });
            
            // Add report title
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            const reportTitle = isActivityReport ? 'Sit-In Activity Report' : 'Feedback Report';
            doc.text(reportTitle, pageWidth / 2, 90, { align: 'center' });
            
            // Add date generated
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('Generated on: ' + currentDate, pageWidth / 2, 110, { align: 'center' });
            
            // Draw a line
            doc.setLineWidth(1);
            doc.setDrawColor(117, 86, 204);
            doc.line(40, 120, pageWidth - 40, 120);
            
            // Create table
            doc.autoTable({
                head: [headers],
                body: data,
                startY: 140,
                styles: {
                    fontSize: 9,
                    cellPadding: 3,
                    overflow: 'linebreak'
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
            
            // Add footer
            const bottomY = doc.internal.pageSize.getHeight() - 20;
            doc.setFontSize(8);
            doc.setTextColor(100, 100, 100);
            doc.text('CCS Sit-In System © University of Cebu', 40, bottomY);
            doc.text('Page ' + doc.internal.getNumberOfPages(), pageWidth - 40, bottomY, { align: 'right' });
            
            // Save file
            const filePrefix = isActivityReport ? 'activity_report_' : 'feedback_report_';
            doc.save(filePrefix + new Date().toISOString().slice(0,10) + '.pdf');
        }

        function printReport() {
            // Set the current date for the print view
            const currentDate = new Date().toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            document.getElementById('print-date-value').textContent = currentDate;
            document.getElementById('print-footer-date').textContent = currentDate;
            
            // Update the logo container structure for better print layout
            const logoContainer = document.querySelector('.logo-container');
            
            // Remove any spacer divs that might interfere with positioning
            const spacer = document.querySelector('.print-header > div[style="width: 80px;"]');
            if (spacer) {
                spacer.remove();
            }
            
            // Ensure the print elements are properly displayed
            document.querySelector('.print-header').style.display = 'flex';
            document.querySelector('.report-title').style.display = 'block';
            document.querySelector('.print-footer').style.display = 'flex';
            
            // Hide the regular table header
            document.querySelector('.table-header').style.display = 'none';
            
            // Determine which report is active and update title accordingly
            const reportTitle = document.getElementById('report-title');
            
            if (document.getElementById('activity-reports').classList.contains('active')) {
                reportTitle.textContent = 'Sit-In Activity Report';
                // Further filter by current tab
                if (currentFilter === 'reservation') {
                    reportTitle.textContent = 'Sit-In Reservation Report';
                } else if (currentFilter === 'sit_in') {
                    reportTitle.textContent = 'Direct Sit-In Report';
                }
            } else {
                reportTitle.textContent = 'Feedback Report';
                // Further filter by current feedback tab
                if (currentFeedbackFilter !== 'all') {
                    reportTitle.textContent = `${currentFeedbackFilter}-Star Feedback Report`;
                }
            }
            
            // Show all filtered-in rows for printing by removing display:none
            document.querySelectorAll('#reports-table-body tr:not(.filtered-out), #feedback-table-body tr:not(.filtered-out)').forEach(row => {
                row.style.display = 'table-row';
            });
            
            // Ensure all feedback messages are expanded for printing
            document.querySelectorAll('.feedback-message').forEach(msg => {
                msg.classList.add('expanded');
            });
            
            // Wait a moment for styles to apply
            setTimeout(() => {
                // Print the document
                window.print();
                
                // Restore the view after printing
                setTimeout(() => {
                    document.querySelector('.print-header').style.display = 'none';
                    document.querySelector('.report-title').style.display = 'none';
                    document.querySelector('.print-footer').style.display = 'none';
                    document.querySelector('.table-header').style.display = 'flex';
                    
                    // Remove expanded class from feedback messages
                    document.querySelectorAll('.feedback-message').forEach(msg => {
                        msg.classList.remove('expanded');
                    });
                    
                    // Restore pagination display
                    if (document.getElementById('activity-reports').classList.contains('active')) {
                        applyPagination();
                    } else {
                        applyFeedbackPagination();
                    }
                }, 500);
            }, 300);
        }
    </script>
</body>
</html>
