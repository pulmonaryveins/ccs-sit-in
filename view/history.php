<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get user details from database
require_once '../config/db_connect.php';
$username = $_SESSION['username'];
// Update the SQL query to include new fieldssessions
$sql = "SELECT idno, firstname, lastname, middlename, course, year, profile_image, email, address, remaining_sessions FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Format the full name
    $fullname = $row['lastname'] . ', ' . $row['firstname'];
    if (!empty($row['middlename'])) {
        $fullname .= ' ' . substr($row['middlename'], 0, 1) . '.';
    }
    
    // Store in session for easy access
    $_SESSION['idno'] = $row['idno'];
    $_SESSION['fullname'] = $fullname;
    $_SESSION['course'] = $row['course'];
    
    // Fix the year level formatting
    $year = intval($row['year']); // Ensure year is an integer
    $_SESSION['year'] = $year;
    $_SESSION['year_level'] = $year . (
        $year == 1 ? 'st' : 
        ($year == 2 ? 'nd' : 
        ($year == 3 ? 'rd' : 'th'))
    ) . ' Year';
    
    $_SESSION['profile_image'] = $row['profile_image'] ?? '../assets/images/logo/AVATAR.png';
    $_SESSION['email'] = $row['email'];
    $_SESSION['address'] = $row['address'];
    
    // Store remaining sessions in session
    $_SESSION['remaining_sessions'] = $row['remaining_sessions'] ?? 30;
}

$stmt->close();

// Fetch history data from both reservations and sit_ins tables
$history_records = [];

// Fetch from reservations table
$sql = "SELECT *, 'reservation' as source FROM reservations WHERE idno = ? ORDER BY date DESC, time_in DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['idno']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $history_records[] = $row;
}
$stmt->close();

// Fetch from sit_ins table
$sql = "SELECT *, 'sit_in' as source FROM sit_ins WHERE idno = ? ORDER BY date DESC, time_in DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['idno']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $history_records[] = $row;
}
$stmt->close();

// Sort all records by date and time (newest first)
usort($history_records, function($a, $b) {
    $a_datetime = strtotime($a['date'] . ' ' . $a['time_in']);
    $b_datetime = strtotime($b['date'] . ' ' . $b['time_in']);
    return $b_datetime - $a_datetime; // Descending order
});

// Close the connection here, after all database operations are done
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity History | CCS Sit-in</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
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
                <a href="dashboard.php" class="nav-link">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="reservation.php" class="nav-link">
                    <i class="ri-calendar-line"></i>
                    <span>Reservation</span>
                </a>
                <a href="history.php" class="nav-link active">
                    <i class="ri-history-line"></i>
                    <span>History</span>
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="ri-user-3-line"></i>
                    <span>Profile</span>
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

    <!-- Backdrop -->
    <div class="backdrop" id="backdrop"></div>

    <!-- Profile Panel -->
    <div class="profile-panel" id="profile-panel">
        <div class="profile-content">
            <div class="profile-header">
                <h3>STUDENT INFORMATION</h3>
            </div>
            <div class="profile-body">
                <div class="profile-image-container">
                    <div class="profile-image">
                        <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : '../assets/images/logo/AVATAR.png'; ?>" 
                             alt="Profile Picture" 
                             id="profile-preview">
                    </div>
                    <div class="profile-name">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($_SESSION['fullname']); ?></h3>
                        </div>  
                    </div>
                </div>

                <div class="student-info-grid">
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-profile-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Student ID</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['idno']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-user-3-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Full Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-graduation-cap-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Course</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['course']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-expand-up-down-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Year Level</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['year_level']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-mail-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['email']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-home-9-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Address</div>
                            <div class="detail-value"><?php echo htmlspecialchars($_SESSION['address']); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="ri-timer-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Session</div>
                            <div class="detail-value sessions-count">
                                <?php echo isset($_SESSION['remaining_sessions']) ? $_SESSION['remaining_sessions'] : '30'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="edit-controls">
                        <a href="profile.php" class="edit-btn">
                            <span>Edit Profile</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- History Page Content -->
    <div class="history-container">
        <div class="content-card">
            <div class="card-header">
                <h3>Activity History</h3>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="ri-search-line"></i>
                        <input type="text" id="searchInput" placeholder="Search records...">
                    </div>
                    <div class="filter-controls">
                        <select id="historyFilter" class="filter-select">
                            <option value="all">All Activities</option>
                            <option value="reservation">Reservations</option>
                            <option value="sit_in">Sit-ins</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-content">
                <?php if (empty($history_records)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="ri-history-line"></i>
                        </div>
                        <div class="empty-state-message">
                            <h4>No Activity Records</h4>
                            <p>You haven't made any reservations or used the laboratory yet.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Laboratory</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Purpose</th>
                                    <th>Activity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="history-table-body">
                                <?php foreach ($history_records as $record): ?>
                                    <tr data-type="<?php echo htmlspecialchars($record['source']); ?>">
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($record['date']))); ?></td>
                                        <td>Laboratory <?php echo htmlspecialchars($record['laboratory']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($record['time_in'])); ?></td>
                                        <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : 'Not yet'; ?></td>
                                        <td><?php echo htmlspecialchars($record['purpose']); ?></td>
                                        <td>
                                            <span class="source-badge <?php echo htmlspecialchars($record['source']); ?>">
                                                <?php echo $record['source'] === 'reservation' ? 'Reservation' : 'Sit-in'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($record['time_out']): ?>
                                                <span class="status-badge completed">Completed</span>
                                            <?php elseif ($record['status'] == 'approved'): ?>
                                                <span class="status-badge approved">Approved</span>
                                            <?php elseif ($record['status'] == 'active'): ?>
                                                <span class="status-badge active">Active</span>
                                            <?php elseif ($record['status'] == 'rejected'): ?>
                                                <span class="status-badge rejected">Rejected</span>
                                            <?php else: ?>
                                                <span class="status-badge pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
                    Showing 1 to <?php echo min(10, count($history_records)); ?> of <?php echo count($history_records); ?> entries
                </div>
                <div class="page-navigation" id="pagination">
                    <button class="page-btn" disabled data-action="prev">Previous</button>
                    <button class="page-btn active" data-page="1">1</button>
                    <button class="page-btn" disabled data-action="next">Next</button>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
        }
        
        .history-container {
            width: 100%;
            max-width: 1600px;
            margin: 80px auto 20px;
            padding: 0 2rem;
        }

        .content-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .card-header h3 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
            margin: 0;
        }
        
        .table-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
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
        
        .card-content {
            padding: 0;
        }
        
        .filter-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            font-size: 0.875rem;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .filter-select:hover {
            border-color: #cbd5e0;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.2);
        }

        .table-container {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
            border-radius: 0;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table th, 
        .modern-table td {
            padding: 1rem;
            text-align: left;
            color: #4a5568;
        }

        .modern-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .modern-table td {
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        
        .modern-table tr:hover td {
            background-color: #f8fafc;
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

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge.pending {
            background: #f3f4f6;
            color: #4b5563;
        }

        .status-badge.approved {
            background: #e0f2fe;
            color: #0369a1;
        }
        
        .status-badge.active {
            background: #fff7ed;
            color: #ea580c;
        }

        .status-badge.rejected {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-badge.completed {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            text-align: center;
            color: #a0aec0;
        }
        
        .empty-state-icon i {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }
        
        .empty-state-message h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.75rem;
        }
        
        .empty-state-message p {
            color: #718096;
            max-width: 24rem;
            margin: 0 auto;
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

        @media (max-width: 768px) {
            .history-container {
                padding: 0 1rem;
                margin-top: 70px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                padding: 1.25rem 1.5rem;
            }
            
            .table-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                width: 100%;
            }
            
            .filter-controls {
                width: 100%;
            }
            
            .filter-select {
                width: 100%;
            }
            
            .pagination-controls {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .page-navigation {
                align-self: center;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 0.75rem;
                font-size: 0.875rem;
            }
        }
    </style>

    <script>
        // Profile panel functionality
        const profilePanel = document.getElementById('profile-panel');
        const backdrop = document.getElementById('backdrop');
        const profileTrigger = document.getElementById('profile-trigger');

        function toggleProfile(show) {
            profilePanel.classList.toggle('active', show);
            backdrop.classList.toggle('active', show);
            document.body.style.overflow = show ? 'hidden' : '';
        }

        profileTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleProfile(true);
        });

        // Close profile panel when clicking outside
        document.addEventListener('click', (e) => {
            if (profilePanel.classList.contains('active') && 
                !profilePanel.contains(e.target) && 
                !profileTrigger.contains(e.target)) {
                toggleProfile(false);
            }
        });

        // Prevent clicks inside panel from closing it
        profilePanel.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Close on backdrop click
        backdrop.addEventListener('click', () => toggleProfile(false));

        // Profile data update function
        async function updateProfilePanel() {
            try {
                const response = await fetch('../profile/get_profile_data.php');
                const data = await response.json();
                
                // Update profile image
                const profileImages = document.querySelectorAll('.profile-image img');
                profileImages.forEach(img => {
                    img.src = data.profile_image + '?t=' + new Date().getTime();
                });

                // Update info
                document.querySelector('.user-info h3').textContent = data.fullname;
                
                // Update info cards
                const detailValues = document.querySelectorAll('.info-card .detail-value');
                detailValues[0].textContent = data.idno;
                detailValues[1].textContent = data.fullname;
                detailValues[2].textContent = data.course;
                detailValues[3].textContent = data.year_level;
            } catch (error) {
                console.error('Error updating profile:', error);
            }
        }

        // Update profile when panel is opened
        profileTrigger.addEventListener('click', updateProfilePanel);

        // Pagination functionality
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination();
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('keyup', function() {
                currentPage = 1; // Reset to first page when searching
                renderTable();
            });
        });
        
        let allRecords = <?php echo json_encode($history_records); ?>;
        let currentPage = 1;
        let recordsPerPage = 10;
        
        function setupPagination() {
            const perPageSelect = document.getElementById('entries-per-page');
            
            // Update entries per page when selection changes
            perPageSelect.addEventListener('change', function() {
                recordsPerPage = parseInt(this.value);
                currentPage = 1; // Reset to first page
                renderTable();
            });
            
            // Initial render
            renderTable();
        }
        
        function renderTable() {
            const tableBody = document.getElementById('history-table-body');
            const pagination = document.getElementById('pagination');
            const pageInfo = document.getElementById('page-info');
            
            // Apply filters
            const filterValue = document.getElementById('historyFilter').value;
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            
            let filteredRecords = allRecords.filter(record => {
                // First apply activity type filter
                if (filterValue !== 'all' && record.source !== filterValue) {
                    return false;
                }
                
                // Then apply search text filter
                if (searchText) {
                    // Search in all text fields
                    const date = new Date(record.date).toLocaleDateString('en-US', 
                        {month: 'short', day: 'numeric', year: 'numeric'});
                    const laboratory = `Laboratory ${record.laboratory}`;
                    const purpose = record.purpose || '';
                    
                    const searchableText = [
                        date,
                        laboratory,
                        purpose,
                        record.source === 'reservation' ? 'Reservation' : 'Sit-in'
                    ].join(' ').toLowerCase();
                    
                    return searchableText.includes(searchText);
                }
                
                return true;
            });
            
            // Calculate pagination
            const totalPages = Math.ceil(filteredRecords.length / recordsPerPage);
            const start = (currentPage - 1) * recordsPerPage;
            const end = Math.min(start + recordsPerPage, filteredRecords.length);
            const displayedRecords = filteredRecords.slice(start, end);
            
            // Clear table body
            tableBody.innerHTML = '';
            
            // Render records
            if (displayedRecords.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-state-content">
                                <i class="ri-history-line"></i>
                                <p>No matching records found</p>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                displayedRecords.forEach(record => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-type', record.source);
                    
                    // Format date
                    const date = new Date(record.date).toLocaleDateString('en-US', 
                        {month: 'short', day: 'numeric', year: 'numeric'});
                    
                    // Format time in
                    const timeIn = new Date('1970-01-01T' + record.time_in + 'Z')
                        .toLocaleTimeString('en-US', 
                            {hour: '2-digit', minute: '2-digit', hour12: true});
                    
                    // Format time out
                    let timeOut = 'Not yet';
                    if (record.time_out) {
                        timeOut = new Date('1970-01-01T' + record.time_out + 'Z')
                            .toLocaleTimeString('en-US', 
                                {hour: '2-digit', minute: '2-digit', hour12: true});
                    }
                    
                    // Determine status badge
                    let statusBadge = '';
                    if (record.time_out) {
                        statusBadge = '<span class="status-badge completed">Completed</span>';
                    } else if (record.status == 'approved') {
                        statusBadge = '<span class="status-badge approved">Approved</span>';
                    } else if (record.status == 'active') {
                        statusBadge = '<span class="status-badge active">Active</span>';
                    } else if (record.status == 'rejected') {
                        statusBadge = '<span class="status-badge rejected">Rejected</span>';
                    } else {
                        statusBadge = '<span class="status-badge pending">Pending</span>';
                    }
                    
                    row.innerHTML = `
                        <td>${date}</td>
                        <td>Laboratory ${record.laboratory}</td>
                        <td>${timeIn}</td>
                        <td>${timeOut}</td>
                        <td>${record.purpose}</td>
                        <td>
                            <span class="source-badge ${record.source}">
                                ${record.source === 'reservation' ? 'Reservation' : 'Sit-in'}
                            </span>
                        </td>
                        <td>${statusBadge}</td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            }
            
            // Update page info
            pageInfo.textContent = filteredRecords.length > 0 
                ? `Showing ${start + 1} to ${end} of ${filteredRecords.length} entries`
                : 'Showing 0 entries';
            
            // Update pagination buttons
            renderPaginationControls(totalPages);
        }
        
        function renderPaginationControls(totalPages) {
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            
            // Previous button
            const prevBtn = document.createElement('button');
            prevBtn.className = 'page-btn';
            prevBtn.textContent = 'Previous';
            prevBtn.disabled = currentPage === 1;
            prevBtn.setAttribute('data-action', 'prev');
            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            });
            pagination.appendChild(prevBtn);
            
            // Page buttons
            const maxButtons = 5; // Maximum number of page buttons to show
            let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
            let endPage = Math.min(totalPages, startPage + maxButtons - 1);
            
            // Adjust if we're near the end
            if (endPage - startPage < maxButtons - 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
                pageBtn.textContent = i;
                pageBtn.setAttribute('data-page', i);
                pageBtn.addEventListener('click', () => {
                    currentPage = i;
                    renderTable();
                });
                pagination.appendChild(pageBtn);
            }
            
            // Next button
            const nextBtn = document.createElement('button');
            nextBtn.className = 'page-btn';
            nextBtn.textContent = 'Next';
            nextBtn.disabled = currentPage === totalPages || totalPages === 0;
            nextBtn.setAttribute('data-action', 'next');
            nextBtn.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderTable();
                }
            });
            pagination.appendChild(nextBtn);
        }
        
        // Filter functionality for history table
        document.getElementById('historyFilter').addEventListener('change', function() {
            currentPage = 1; // Reset to first page when filtering
            renderTable();
        });
    </script>
</body>
</html>