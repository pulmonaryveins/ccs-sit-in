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
            <div class="pagination-controls">
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
    </div>

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
    </script>
</body>
</html>
