<?php
// Set timezone to Philippines (GMT+8)
date_default_timezone_set('Asia/Manila');

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

// Fetch history data from both reservations and sit_ins tables with feedback info
$history_records = [];

// Fetch from reservations table with feedback info
$sql = "SELECT r.*, 'reservation' as source, 
        CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END AS has_feedback,
        f.rating, f.message AS feedback_message
        FROM reservations r 
        LEFT JOIN feedback f ON r.id = f.reservation_id
        WHERE r.idno = ? 
        ORDER BY r.date DESC, r.time_in DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['idno']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $history_records[] = $row;
}
$stmt->close();

// Fetch from sit_ins table with feedback info
$sql = "SELECT s.*, 'sit_in' as source, 
        CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END AS has_feedback,
        f.rating, f.message AS feedback_message 
        FROM sit_ins s 
        LEFT JOIN feedback f ON s.id = f.sit_in_id
        WHERE s.idno = ? 
        ORDER BY s.date DESC, s.time_in DESC";
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
    <style>
        /* Add page opening animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .history-container {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .content-card {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .card-header {
            animation: fadeIn 0.7s ease-out forwards;
        }
        
        .card-content {
            animation: fadeIn 0.8s ease-out forwards;
        }

        /* Add these styles to your existing <style> section */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .notification-container.active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .notification-popup {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 16px;
            width: 320px;
            max-width: 90vw;
            overflow: hidden;
            position: relative;
            border-left: 4px solid var(--primary-color);
        }

        .notification-popup.success {
            border-left-color: #10b981;
        }

        .notification-popup.error {
            border-left-color: #ef4444;
        }

        .notification-popup.warning {
            border-left-color: #f59e0b;
        }

        .notification-popup.info {
            border-left-color: #3b82f6;
        }

        .notification-icon {
            margin-right: 12px;
            font-size: 16px;
        }

        .notification-popup.success .notification-icon {
            color: #10b981;
        }

        .notification-popup.error .notification-icon {
            color: #ef4444;
        }

        .notification-popup.warning .notification-icon {
            color: #f59e0b;
        }

        .notification-popup.info .notification-icon {
            color: #3b82f6;
        }

        .notification-content {
            flex: 1;
        }

        .notification-content h3 {
            margin: 0 0 4px 0;
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
        }

        .notification-content p {
            margin: 0;
            font-size: 0.875rem;
            color: #64748b;
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
    </style>
</head>
<body>
    <!-- Include notification system -->
    <?php include '../includes/notification.php'; ?>
    
    <!-- Notification Container -->
    <div class="notification-container" id="notificationContainer">
        <div class="notification-popup" id="notificationPopup">
            <div class="notification-icon">
                <i class="fas fa-check-circle" id="notificationIcon"></i>
            </div>
            <div class="notification-content">
                <h3 id="notificationTitle">Success</h3>
                <p id="notificationMessage">Operation completed successfully!</p>
            </div>
        </div>
    </div>
    
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
                <a href="student_leaderboard.php" class="nav-link">
                    <i class="ri-trophy-line"></i>
                    <span>Leaderboard</span>
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
                <!-- Notification Icon with Badge -->
                <div class="notification-icon">
                    <a href="#" class="action-link" id="notification-toggle">
                        <i class="fas fa-bell"></i>
                    </a>
                    <span class="notification-badge" id="notification-badge">0</span>
                    
                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notification-dropdown">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <button id="mark-all-read">Mark all as read</button>
                        </div>
                        <div class="notification-list" id="notification-list">
                            <!-- Notifications will be loaded here -->
                            <div class="notification-empty">
                                Loading notifications...
                            </div>
                        </div>
                    </div>
                </div>
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
                                <th>Rating</th>
                                <th>Feedback</th>
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
                                        <td>
                                            <?php if ($record['time_out'] && !$record['has_feedback']): ?>
                                                <button class="feedback-btn" 
                                                        data-id="<?php echo htmlspecialchars($record['id']); ?>"
                                                        data-type="<?php echo htmlspecialchars($record['source']); ?>">
                                                    Rate Experience
                                                </button>
                                            <?php elseif ($record['has_feedback']): ?>
                                                <div class="feedback-given">
                                                    <div class="star-display">
                                                        <?php 
                                                            $rating = intval($record['rating']);
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                if ($i <= $rating) {
                                                                    echo '<i class="fas fa-star"></i>';
                                                                } else {
                                                                    echo '<i class="far fa-star"></i>';
                                                                }
                                                            }
                                                        ?>
                                                    </div>
                                                </div>
                                            <?php elseif (!$record['time_out']): ?>
                                                <span class="feedback-unavailable">Not completed</span>
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
    
    <!-- Feedback Modal -->
    <div class="modal-backdrop" id="feedbackModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Rate Your Experience</h3>
                <button class="modal-close" onclick="closeFeedbackModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="feedbackForm">
                    <input type="hidden" id="feedback_id" name="id">
                    <input type="hidden" id="feedback_type" name="type">
                    
                    <div class="form-group">
                        <label>How would you rate your experience?</label>
                        <div class="rating-stars">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" id="rating_value" name="rating" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="feedback_message">Additional comments (optional)</label>
                        <textarea id="feedback_message" name="message" rows="4" placeholder="Share your thoughts about your sit-in experience..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeFeedbackModal()">Cancel</button>
                <button class="btn btn-primary" id="submit-feedback" disabled>Submit Feedback</button>
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
            border: 1px solid #e2e8f0;
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
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 500;
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
            background: white;
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
        
        /* Feedback related styles */
        .feedback-btn {
            padding: 0.35rem 0.75rem;
            border: none;
            border-radius: 20px;
            background: var(--primary-color);
            color: white;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .feedback-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-1px);
        }
        
        .feedback-given {
            display: flex;
            align-items: center;
        }
        
        .star-display {
            color: #fbbf24;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            gap: 2px;
        }
        
        .feedback-unavailable {
            color: #a0aec0;
            font-size: 0.75rem;
            font-style: italic;
        }
        
        /* Rating stars in modal */
        .rating-stars {
            display: flex;
            gap: 0.5rem;
            font-size: 1.5rem;
            color: #d1d5db;
            margin: 1rem 0;
        }
        
        .rating-stars i {
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .rating-stars i:hover,
        .rating-stars i.selected {
            color: #fbbf24;
        }
        
        /* Modal backdrop */
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
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .btn:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-1px);
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
        
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.875rem;
            resize: vertical;
            min-height: 80px;
            transition: all 0.2s;
        }
        
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(117,86,204,0.1);
            outline: none;
        }

        /* Enhanced announcement styles */
        .announcement-list {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 10px;
            scrollbar-width: thin;
            scrollbar-color: rgba(117, 86, 204, 0.5) transparent;
        }
        
        .announcement-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .announcement-list::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .announcement-list::-webkit-scrollbar-thumb {
            background-color: rgba(117, 86, 204, 0.5);
            border-radius: 10px;
        }
        
        .announcement-item {
            background-color: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px;
            transition: all 0.2s ease;
        }

        .announcement-item:hover {
            transform: translateY(-2px);
        }
        
        .announcement-title {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .announcement-title i {
            color: #7556cc;
            font-size: 1.25rem;
            margin-right: 8px;
        }
        
        .announcement-title h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #334155;
            margin: 0;
        }
        
        .announcement-details {
            padding-left: 32px;
        }
        
        .announcement-details p {
            margin-bottom: 10px;
            color: #475569;
            line-height: 1.5;
        }
        
        .timestamp {
            display: block;
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: right;
        }
        
        /* Notification styles */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.2s ease;
            opacity: 0;
            transform: scale(0);
        }
        
        .notification-badge.active {
            opacity: 1;
            transform: scale(1);
        }
        
        .notification-icon {
            position: relative;
            width: 24px;
            height: 24px;
            display: flex; /* Ensure proper alignment */
            align-items: center; /* Vertically align with logout icon */
            justify-content: center; /* Horizontally center the icon */
            margin-right: 15px; /* Add spacing similar to logout icon */
        }
        
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            width: 360px;
            max-width: 90vw;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            z-index: 50;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
        }
        
        .notification-dropdown.active {
            max-height: 500px;
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            transition: max-height 0.3s ease-out, opacity 0.2s ease-out, transform 0.2s ease-out;
        }
        
        .notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .notification-header h3 {
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
            margin: 0;
        }
        
        .notification-header button {
            background: none;
            border: none;
            color: #7556cc;
            font-size: 0.875rem;
            cursor: pointer;
            font-weight: 500;
        }
        
        .notification-list {
            max-height: 350px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(117, 86, 204, 0.5) transparent;
        }
        
        .notification-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .notification-list::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .notification-list::-webkit-scrollbar-thumb {
            background-color: rgba(117, 86, 204, 0.5);
            border-radius: 10px;
        }
        
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: flex-start;
        }
        
        .notification-item:hover {
            background-color: #f8fafc;
        }
        
        .notification-item.unread {
            background-color: #f0f9ff;
        }
        
        .notification-item.unread:hover {
            background-color: #e0f2fe;
        }
        
        .notification-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #7556cc;
            margin-top: 6px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .notification-item.unread .notification-indicator {
            background-color: #3b82f6;
        }
        
        .notification-content {
            flex-grow: 1;
        }
        
        .notification-content h4 {
            font-weight: 600;
            font-size: 0.9rem;
            margin: 0 0 4px 0;
            color: #334155;
        }
        
        .notification-content p {
            font-size: 0.8rem;
            margin: 0 0 6px 0;
            color: #64748b;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        .notification-empty {
            padding: 24px 16px;
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
        }

        .feedback-text {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.85rem;
            color: #4a5568;
        }

        .feedback-text:hover {
            cursor: pointer;
            text-decoration: underline;
        }


    </style>

    <script>

        // Add this function to your JavaScript code, preferably near the top of your script section
        function showNotification(title, message, type = 'success', duration = 3000) {
            const container = document.getElementById('notificationContainer');
            const popup = document.getElementById('notificationPopup');
            const titleElement = document.getElementById('notificationTitle');
            const messageElement = document.getElementById('notificationMessage');
            const iconElement = document.getElementById('notificationIcon');
            
            // Set content
            titleElement.textContent = title;
            messageElement.textContent = message;
            
            // Set appropriate icon
            iconElement.className = type === 'success' 
                ? 'fas fa-check-circle' 
                : type === 'error' 
                    ? 'fas fa-times-circle' 
                    : type === 'warning' 
                        ? 'fas fa-exclamation-circle' 
                        : 'fas fa-info-circle';
            
            // Add class based on notification type
            popup.className = 'notification-popup';
            popup.classList.add(type);
            
            // Show notification
            container.classList.add('active');
            
            // Hide after duration
            setTimeout(() => {
                container.classList.remove('active');
            }, duration);
        }
        // Notification functionality
        const notificationToggle = document.getElementById('notification-toggle');
        const notificationDropdown = document.getElementById('notification-dropdown');
        const notificationBadge = document.getElementById('notification-badge');
        const notificationList = document.getElementById('notification-list');
        const markAllReadBtn = document.getElementById('mark-all-read');
        
        // Load notifications
        async function loadNotifications() {
            try {
                const response = await fetch('../notifications/get_notifications.php');
                const data = await response.json();
                
                // Update notification badge
                if (data.unread_count > 0) {
                    notificationBadge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    notificationBadge.classList.add('active');
                } else {
                    notificationBadge.classList.remove('active');
                }
                
                // Update notification list
                notificationList.innerHTML = '';
                
                if (data.notifications.length === 0) {
                    notificationList.innerHTML = `
                        <div class="notification-empty">
                            You have no notifications
                        </div>
                    `;
                    return;
                }
                
                data.notifications.forEach(notification => {
                    const item = document.createElement('div');
                    item.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
                    item.dataset.id = notification.id;
                    
                    item.innerHTML = `
                        <div class="notification-indicator"></div>
                        <div class="notification-content">
                            <h4>${notification.title}</h4>
                            <p>${notification.content}</p>
                            <span class="notification-time">${notification.created_at}</span>
                        </div>
                    `;
                    
                    item.addEventListener('click', () => markAsRead(notification.id));
                    
                    notificationList.appendChild(item);
                });
            } catch (error) {
                console.error('Error loading notifications:', error);
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        Error loading notifications
                    </div>
                `;
            }
        }
        
        // Toggle notification dropdown
        notificationToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isOpen = notificationDropdown.classList.contains('active');
            
            if (!isOpen) {
                loadNotifications();
                notificationDropdown.classList.add('active');
            } else {
                notificationDropdown.classList.remove('active');
            }
        });
        
        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationDropdown.contains(e.target) && 
                !notificationToggle.contains(e.target)) {
                notificationDropdown.classList.remove('active');
            }
        });
        
        // Mark notification as read
        async function markAsRead(id) {
            try {
                const response = await fetch('../notifications/mark_as_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ notification_id: id }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update UI
                    const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                    if (item) {
                        item.classList.remove('unread');
                    }
                    
                    // Reload notifications to update badge count
                    loadNotifications();
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }
        
        // Mark all notifications as read
        markAllReadBtn.addEventListener('click', async function() {
            try {
                const response = await fetch('../notifications/mark_all_read.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update UI - remove unread class from all notifications
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    
                    // Update badge
                    notificationBadge.classList.remove('active');
                    
                    // Show confirmation
                    showNotification(
                        "Success", 
                        "All notifications marked as read",
                        "success",
                        3000
                    );
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        });
        
        // Load notifications on page load
        loadNotifications();
        
        // Set interval to refresh notifications (every 30 seconds)
        setInterval(loadNotifications, 30000);
        
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
        
        // Feedback modal functionality
        const feedbackModal = document.getElementById('feedbackModal');
        const ratingStars = document.querySelectorAll('.rating-stars i');
        const submitFeedbackBtn = document.getElementById('submit-feedback');
        
        // Open feedback modal when clicking on Rate Experience button
        document.querySelectorAll('.feedback-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const recordId = this.getAttribute('data-id');
                const recordType = this.getAttribute('data-type');
                
                // Reset modal state
                document.getElementById('feedback_id').value = recordId;
                document.getElementById('feedback_type').value = recordType;
                document.getElementById('rating_value').value = '0';
                document.getElementById('feedback_message').value = '';
                
                // Reset stars
                ratingStars.forEach(star => {
                    star.classList.remove('selected');
                    star.classList.remove('fas');
                    star.classList.add('far');
                });
                
                // Disable submit button until rating is selected
                submitFeedbackBtn.disabled = true;
                
                // Show modal
                feedbackModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });
        
        // Handle star rating selection
        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                document.getElementById('rating_value').value = rating;
                
                // Update star display
                ratingStars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas', 'selected');
                    } else {
                        s.classList.remove('fas', 'selected');
                        s.classList.add('far');
                    }
                });
                
                // Enable submit button
                submitFeedbackBtn.disabled = false;
            });
        });
        
        // Close feedback modal
        function closeFeedbackModal() {
            feedbackModal.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Submit feedback
        submitFeedbackBtn.addEventListener('click', function() {
            const recordId = document.getElementById('feedback_id').value;
            const recordType = document.getElementById('feedback_type').value;
            const rating = document.getElementById('rating_value').value;
            const message = document.getElementById('feedback_message').value;
            
            const data = {
                id: recordId,
                type: recordType,
                rating: rating,
                message: message
            };
            
            // Display loading or disabled state
            submitFeedbackBtn.disabled = true;
            submitFeedbackBtn.textContent = 'Submitting...';
            
            fetch('../controller/submit_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    // Close modal
                    closeFeedbackModal();
                    
                    // Show success notification
                    showNotification('Success', 'Thank you for your feedback!', 'success', 3000);
                    
                    // Reload page to show updated data
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification('Error', result.message || 'Failed to submit feedback', 'error', 4000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error', 'An error occurred while submitting your feedback', 'error', 4000);
            })
            .finally(() => {
                // Reset button state
                submitFeedbackBtn.disabled = false;
                submitFeedbackBtn.textContent = 'Submit Feedback';
            });
        });
        
        // Pagination functionality
        const allRecords = <?php echo json_encode($history_records); ?>;
        let recordsPerPage = 10;
        let currentPage = 1;
        
        document.getElementById('entries-per-page').addEventListener('change', function() {
            recordsPerPage = parseInt(this.value);
            currentPage = 1; // Reset to first page
            renderTable();
        });
        
        document.getElementById('searchInput').addEventListener('input', function() {
            currentPage = 1; // Reset to first page
            renderTable();
        });
        
        // Initial render
        renderTable();
        
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
                        {month: 'short', day: 'numeric', year: 'numeric', timeZone: 'Asia/Manila'});
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
                        <td colspan="8" class="empty-state">
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
                        {month: 'short', day: 'numeric', year: 'numeric', timeZone: 'Asia/Manila'});
                    
                    // Format time in with GMT+8 timezone
                    let timeIn = '';
                    if (record.time_in) {
                        const [hours, minutes] = record.time_in.split(':');
                        const hour = parseInt(hours);
                        const ampm = hour >= 12 ? 'PM' : 'AM';
                        const hour12 = hour % 12 || 12;
                        timeIn = `${hour12.toString().padStart(2, '0')}:${minutes} ${ampm}`;
                    }
                    
                    // Format time out with GMT+8 timezone
                    let timeOut = 'Not yet';
                    if (record.time_out) {
                        const [hours, minutes] = record.time_out.split(':');
                        const hour = parseInt(hours);
                        const ampm = hour >= 12 ? 'PM' : 'AM';
                        const hour12 = hour % 12 || 12;
                        timeOut = `${hour12.toString().padStart(2, '0')}:${minutes} ${ampm}`;
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
                    
                    // Create feedback column content
                    let ratingContent = '';
                    let feedbackContent = '';
                    
                    if (record.time_out && !record.has_feedback) {
                        ratingContent = `
                            <button class="feedback-btn" 
                                    data-id="${record.id}"
                                    data-type="${record.source}">
                                Rate Experience
                            </button>
                        `;
                        feedbackContent = '<span class="feedback-unavailable">No feedback</span>';
                    } else if (record.has_feedback) {
                        ratingContent = `
                            <div class="star-display">
                                ${generateStars(record.rating)}
                            </div>
                        `;
                        feedbackContent = `
                            <div class="feedback-text" title="${record.feedback_message || 'No comment provided'}">
                                ${record.feedback_message ? 
                                (record.feedback_message.length > 30 ? 
                                record.feedback_message.substring(0, 30) + '...' : 
                                record.feedback_message) : 
                                '<span class="feedback-unavailable">No comment</span>'}
                            </div>
                        `;
                    } else if (!record.time_out) {
                        ratingContent = '<span class="feedback-unavailable">Not completed</span>';
                        feedbackContent = '<span class="feedback-unavailable">Not completed</span>';
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
                        <td>${ratingContent}</td>
                        <td>${feedbackContent}</td>
                    `;
                    
                    tableBody.appendChild(row);
                });
                
                // Re-attach event listeners for feedback buttons
                document.querySelectorAll('.feedback-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const recordId = this.getAttribute('data-id');
                        const recordType = this.getAttribute('data-type');
                        
                        // Reset modal state
                        document.getElementById('feedback_id').value = recordId;
                        document.getElementById('feedback_type').value = recordType;
                        document.getElementById('rating_value').value = '0';
                        document.getElementById('feedback_message').value = '';
                        
                        // Reset stars
                        ratingStars.forEach(star => {
                            star.classList.remove('selected');
                            star.classList.remove('fas');
                            star.classList.add('far');
                        });
                        
                        // Disable submit button until rating is selected
                        submitFeedbackBtn.disabled = true;
                        
                        // Show modal
                        feedbackModal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    });
                });
            }
            
            // Update page info
            pageInfo.textContent = filteredRecords.length > 0 
                ? `Showing ${start + 1} to ${end} of ${filteredRecords.length} entries`
                : 'Showing 0 entries';
            
            // Update pagination buttons
            renderPaginationControls(totalPages);
        }
        
        // Helper function to generate star ratings
        function generateStars(rating) {
            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    starsHtml += '<i class="fas fa-star"></i>';
                } else {
                    starsHtml += '<i class="far fa-star"></i>';
                }
            }
            return starsHtml;
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