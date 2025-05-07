<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db_connect.php';

// Get statistics
$stats = [
    'total_students' => 0,
    'current_sitin' => 0,
    'total_sitin' => 0
];

// Get total registered students (modified query)
$query = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($query);
if ($result) {
    $stats['total_students'] = $result->fetch_assoc()['count'];
}

// Get current sit-in count (today)
$query = "SELECT COUNT(*) as count FROM reservations WHERE DATE(date) = CURDATE()";
$result = $conn->query($query);
if ($result) {
    $stats['current_sitin'] = $result->fetch_assoc()['count'];
}

// Get total sit-in count
$query = "SELECT COUNT(*) as count FROM reservations";
$result = $conn->query($query);
if ($result) {
    $stats['total_sitin'] = $result->fetch_assoc()['count'];
}

// Fetch announcements
$announcements = [];
$query = "SELECT * FROM announcements ORDER BY created_at DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Get pending reservations
$sql = "SELECT *, TIME_FORMAT(time_in, '%h:%i %p') as formatted_time FROM reservations WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($sql);
$pending_reservations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_reservations[] = $row;
    }
}

// Get approved reservations - Modified to include all approved reservations regardless of active status
$sql = "SELECT *, 
        TIME_FORMAT(time_in, '%h:%i %p') as formatted_time, 
        DATE_FORMAT(updated_at, '%m/%d/%Y %h:%i %p') as formatted_updated_at 
        FROM reservations 
        WHERE status = 'approved' 
        ORDER BY created_at DESC LIMIT 50";
$result = $conn->query($sql);
$approved_reservations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $approved_reservations[] = $row;
    }
}

// Get rejected reservations - Modified to include all rejected reservations
$sql = "SELECT *, 
        TIME_FORMAT(time_in, '%h:%i %p') as formatted_time,
        DATE_FORMAT(updated_at, '%m/%d/%Y %h:%i %p') as formatted_updated_at 
        FROM reservations 
        WHERE status = 'rejected' 
        ORDER BY created_at DESC LIMIT 50";
$result = $conn->query($sql);
$rejected_reservations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rejected_reservations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
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
    <!-- Main Content -->
    <div class="dashboard-grid">
        <!-- Left Column - Computer Control -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header">
                    <h3>Laboratory Computer Control</h3>
                    <select id="labSelect" class="lab-select">
                        <option value="">Select Laboratory</option>
                        <option value="517">Laboratory 517</option>
                        <option value="524">Laboratory 524</option>
                        <option value="526">Laboratory 526</option>
                        <option value="528">Laboratory 528</option>
                        <option value="530">Laboratory 530</option>
                        <option value="542">Laboratory 542</option>
                    </select>
                </div>
                <div class="profile-content">
                    <div class="computer-grid" id="computerGrid">
                        <!-- PCs will be loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Reservation Requests with Tabs -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header">
                    <h3>Reservation Requests</h3>
                </div>
                <div class="tabs-container">
                    <div class="tabs-navigation">
                        <button class="tab-button active" data-tab="pending-tab">Pending Requests</button>
                        <button class="tab-button" data-tab="logs-tab">Request Logs</button>
                    </div>
                    
                    <div class="tab-content active" id="pending-tab">
                        <div class="profile-content">
                            <div class="requests-list">
                                <?php if (empty($pending_reservations)): ?>
                                    <div class="no-requests">
                                        No pending reservation requests
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($pending_reservations as $reservation): ?>
                                        <div class="request-item">
                                            <div class="request-header">
                                                <span class="student-name"><?php echo htmlspecialchars($reservation['fullname']); ?></span>
                                                <span class="request-status pending">Pending</span>
                                            </div>
                                            <div class="request-details">
                                                <div class="detail-row">
                                                    <span class="label">ID Number:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['idno']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Laboratory:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['laboratory']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">PC Number:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['pc_number']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Date:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['date']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Time:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['formatted_time']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Purpose:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['purpose']); ?></span>
                                                </div>
                                            </div>
                                            <div class="request-actions">
                                                <button class="action-btn approve" onclick="processReservation(<?php echo $reservation['id']; ?>, 'approve')">
                                                    <i class="ri-check-line"></i> Approve
                                                </button>
                                                <button class="action-btn reject" onclick="processReservation(<?php echo $reservation['id']; ?>, 'reject')">
                                                    <i class="ri-close-line"></i> Reject
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="logs-tab">
                        <div class="logs-filter">
                            <div class="filter-option active" data-filter="approved">Approved Requests</div>
                            <div class="filter-option" data-filter="rejected">Rejected Requests</div>
                        </div>
                        
                        <!-- Approved Requests Log -->
                        <div class="logs-container active" id="approved-logs">
                            <div class="requests-list">
                                <?php if (empty($approved_reservations)): ?>
                                    <div class="no-requests">
                                        No approved reservation requests
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($approved_reservations as $reservation): ?>
                                        <div class="request-item approved-item">
                                            <div class="request-header">
                                                <span class="student-name"><?php echo htmlspecialchars($reservation['fullname']); ?></span>
                                                <span class="request-status approved">Approved</span>
                                            </div>
                                            <div class="request-details">
                                                <div class="detail-row">
                                                    <span class="label">ID Number:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['idno']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Laboratory:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['laboratory']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">PC Number:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['pc_number']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Date:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['date']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Time:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['formatted_time']); ?></span>
                                                </div>
                                                <div class="detail-row timestamp">
                                                    <span class="label">Approved on:</span>
                                                    <span class="value"><?php echo isset($reservation['formatted_updated_at']) ? htmlspecialchars($reservation['formatted_updated_at']) : htmlspecialchars($reservation['created_at']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Rejected Requests Log -->
                        <div class="logs-container" id="rejected-logs">
                            <div class="requests-list">
                                <?php if (empty($rejected_reservations)): ?>
                                    <div class="no-requests">
                                        No rejected reservation requests
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($rejected_reservations as $reservation): ?>
                                        <div class="request-item rejected-item">
                                            <div class="request-header">
                                                <span class="student-name"><?php echo htmlspecialchars($reservation['fullname']); ?></span>
                                                <span class="request-status rejected">Rejected</span>
                                            </div>
                                            <div class="request-details">
                                                <div class="detail-row">
                                                    <span class="label">ID Number:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['idno']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Laboratory:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['laboratory']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">PC Number:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['pc_number']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Date:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['date']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Time:</span>
                                                    <span class="value"><?php echo htmlspecialchars($reservation['formatted_time']); ?></span>
                                                </div>
                                                <div class="detail-row timestamp">
                                                    <span class="label">Rejected on:</span>
                                                    <span class="value"><?php echo isset($reservation['formatted_updated_at']) ? htmlspecialchars($reservation['formatted_updated_at']) : htmlspecialchars($reservation['created_at']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="confirmation-content">
            <div class="confirmation-header">
                <h3 id="confirmationTitle">Confirm Action</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="confirmation-body">
                <div class="confirmation-message">
                    <p id="confirmationMessage">Are you sure you want to proceed with this action?</p>
                    <div id="reservationDetails" class="reservation-details">
                        <!-- Reservation details will be inserted here -->
                    </div>
                </div>
            </div>
            <div class="confirmation-footer">
                <button id="cancelButton" class="modal-button cancel">Cancel</button>
                <button id="confirmButton" class="modal-button confirm">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <style>
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

        .notification-icon i {
            font-size: 18px;
            padding-top: 14px;
        }

        .lab-select {
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-left: 1rem;
        }
        
        .lab-select:hover, .lab-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 2px 5px rgba(117, 86, 204, 0.15);
        }
        
        /* Enhanced Lab Controls Section */
        .profile-card {
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            background: white;
            border-bottom: 1px solid #e8edf5;
        }
        
        .profile-header h3 {
            font-size: 1.25rem;
            font-weight: 500;
            color: #2d3748;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .lab-select {
            background: white;
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #4a5568;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease;
            cursor: pointer;
            outline: none;
            width: 180px;
        }
        
        .lab-select:hover, .lab-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 2px 5px rgba(117, 86, 204, 0.15);
        }
        
        /* Computer Grid Enhancement */
        .computer-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr); /* Changed from auto-fill to exactly 5 columns */
            gap: 1rem;
            padding: 1.5rem;
            background: white;
        }
        
        .computer-unit {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }
        
        .computer-unit:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
            border-color: #d3dce9;
        }
        
        .computer-icon {
            font-size: 1.75rem;
            color: #7556cc;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .computer-unit:hover .computer-icon {
            transform: scale(1.1);
            color: var(--secondary-color);
        }
        
        .computer-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }
        
        .pc-number {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        
        .status {
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            text-align: center;
            width: 100%;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        
        .status.available {
            background: #c6f6d5;
            color: #2f855a;
        }
        
        .status.in-use {
            background: #fed7d7;
            color: #c53030;
        }
        
        /* Reservation Request Enhancements */
        .requests-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1.5rem;
            max-height: 550px;
            overflow-y: auto;
            position: relative; /* Added for stacking context */
        }
        
        .no-requests {
            text-align: center;
            padding: 3rem 0;
            color: #a0aec0;
            font-size: 1.1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        .no-requests:before {
            content: '\ea8d';
            font-family: 'remixicon';
            font-size: 3rem;
            opacity: 0.5;
        }
        
        /* Fix for request-item overflow hiding the buttons */
        .request-item {
            background: white;
            border: 1px solid #e8edf5;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: visible; /* Changed from hidden to visible to prevent buttons from being cut off */
            margin-bottom: 0.5rem;
        }
        
        /* Ensure buttons remain visible */
        .request-actions {
            display: flex;
            gap: 1rem;
            position: relative; /* Added to ensure proper stacking context */
            z-index: 5; /* Higher z-index to appear above other elements */
            width: 100%;
            margin-top: 0.5rem;
        }
        
        .action-btn {
            flex: 1;
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 600;
            letter-spacing: 0.01em;
            font-size: 0.9rem;
            min-width: 100px; /* Ensure minimum width */
            position: relative; /* Added positioning context */
        }
        
        /* Reset the overflow behavior for the containers */
        .requests-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1.5rem;
            max-height: 550px;
            overflow-y: auto;
            position: relative; /* Added for stacking context */
        }
        
        /* Additional style for the gradient accent to not interfere with buttons */
        .request-item:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #f59e0b, #d97706);
            border-radius: 2px;
            z-index: 1; /* Lower z-index to stay behind content */
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            position: relative;
        }
        
        .student-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.15rem;
            letter-spacing: -0.01em;
        }
        
        .request-status {
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        
        .request-status.pending {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid rgba(194, 65, 12, 0.2);
        }
        
        .request-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem 2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid #f0f3f8;
        }
        
        .detail-row {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .label {
            color: #718096;
            font-size: 0.8rem;
        }
        
        .value {
            font-weight: 500;
            color: #2d3748;
            font-size: 0.95rem;
        }
        
        .request-actions {
            display: flex;
            gap: 1rem;
        }
        
        .action-btn {
            flex: 1;
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 600;
            letter-spacing: 0.01em;
            font-size: 0.9rem;
        }
        
        .action-btn.approve {
            background: #38a169;
            box-shadow: 0 2px 5px rgba(56, 161, 105, 0.3);
        }
        
        .action-btn.reject {
            background: #e53e3e;
            box-shadow: 0 2px 5px rgba(229, 62, 62, 0.3);
        }
        
        .action-btn.approve:hover {
            background: #2f855a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(56, 161, 105, 0.4);
        }
        
        .action-btn.reject:hover {
            background: #c53030;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(229, 62, 62, 0.4);
        }
        
        .action-btn i {
            font-size: 1.1rem;
        }

        /* Tab Navigation Styles */
        .tabs-container {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .tabs-navigation {
            display: flex;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e8edf5;
        }
        
        .tab-button {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            background: white;
            color: #4a5568;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e8edf5;
        }
        
        .tab-button.active {
            background: #7556cc;
            color: white;
            border-color: #7556cc;
            box-shadow: 0 2px 5px rgba(117, 86, 204, 0.2);
        }
        
        .tab-button:hover:not(.active) {
            background: #f1f5f9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }
        
        .tab-content {
            display: none;
            padding: 0;
            flex: 1;
            overflow: hidden;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Logs Filter Styles */
        .logs-filter {
            display: flex;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            background: white;
            border-bottom: 1px solid #f0f3f8;
        }
        
        .filter-option {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #4a5568;
            background: #f8fafc;
            border: 1px solid #e8edf5;
        }
        
        .filter-option.active {
            background: #ebf4ff;
            color: #3182ce;
            border-color: #bee3f8;
            font-weight: 500;
        }
        
        .filter-option:hover:not(.active) {
            background: #f1f5f9;
        }
        
        .logs-container {
            display: none;
            height: 100%;
            overflow: auto;
        }
        
        .logs-container.active {
            display: block;
        }
        
        /* Request Item Status-specific Styles */
        .request-item.approved-item:before {
            background: linear-gradient(to bottom, #38a169, #2f855a);
        }
        
        .request-item.rejected-item:before {
            background: linear-gradient(to bottom, #e53e3e, #c53030);
        }
        
        .request-status.approved {
            background: #f0fff4;
            color: #2f855a;
            border: 1px solid rgba(47, 133, 90, 0.2);
        }
        
        .request-status.rejected {
            background: #fff5f5;
            color: #c53030;
            border: 1px solid rgba(197, 48, 48, 0.2);
        }
        
        .timestamp {
            grid-column: span 2;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px dashed #e8edf5;
        }
        
        .timestamp .value {
            color: #718096;
            font-size: 0.85rem;
            font-style: italic;
        }

        /* Style for computers that were just approved */
        .computer-unit.just-approved {
            animation: pulse 1.5s infinite;
            box-shadow: 0 0 0 0 rgba(117, 86, 204, 0.7);
            border: 2px solid #7556cc;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(117, 86, 204, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(117, 86, 204, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(117, 86, 204, 0);
            }
        }
        
        /* Student assignment style */
        .computer-unit.student-assigned {
            position: relative;
        }
        
        .computer-unit.student-assigned::after {
            content: "Student";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #7556cc;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.6rem;
            letter-spacing: 0.02em;
            opacity: 0.9;
        }

        /* Confirmation Modal Styles */
        .confirmation-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .confirmation-content {
            position: relative;
            background-color: #fff;
            margin: 10% auto;
            padding: 0;
            width: 450px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .confirmation-header {
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .confirmation-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #333;
            font-weight: 600;
        }

        .close-modal {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #555;
        }

        .confirmation-body {
            padding: 25px;
            text-align: center;
        }

        .confirmation-message {
            width: 100%;
        }

        .confirmation-message p {
            margin: 0 0 15px;
            font-size: 1rem;
            color: #4a5568;
            line-height: 1.5;
        }

        .reservation-details {
            background-color: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 0.9rem;
            text-align: left;
        }

        .reservation-detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #4a5568;
        }

        .reservation-detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 500;
            color: #718096;
        }

        .detail-value {
            font-weight: 600;
            color: #2d3748;
        }

        .confirmation-footer {
            padding: 15px 25px;
            display: flex;
            justify-content: center;
            gap: 10px;
            border-top: 1px solid #f0f0f0;
        }

        .modal-button {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .modal-button.cancel {
            background: #e2e8f0;
            color: #4a5568;
            border: 1px solid #cbd5e0;
        }

        .modal-button.cancel:hover {
            background: #cbd5e0;
        }

        .modal-button.confirm {
            background: #7556cc;
            color: white;
            border: 1px solid #7556cc;
        }

        .modal-button.confirm:hover {
            background: #6345bb;
        }

        .modal-button.confirm.reject {
            background: #e53e3e;
            border-color: #e53e3e;
        }

        .modal-button.confirm.reject:hover {
            background: #c53030;
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            width: 350px;
            max-width: calc(100vw - 40px);
            transform: translateX(100%);
            opacity: 0;
            animation: slideInToast 0.3s forwards;
            border-left: 4px solid #7556cc;
        }

        @keyframes slideInToast {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast.hide {
            animation: slideOutToast 0.3s forwards;
        }

        @keyframes slideOutToast {
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .toast-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast-icon i {
            font-size: 24px;
        }

        .toast.success {
            border-left-color: #38a169;
        }

        .toast.success .toast-icon i {
            color: #38a169;
        }

        .toast.error {
            border-left-color: #e53e3e;
        }

        .toast.error .toast-icon i {
            color: #e53e3e;
        }

        .toast.warning {
            border-left-color: #dd6b20;
        }

        .toast.warning .toast-icon i {
            color: #dd6b20;
        }

        .toast.info {
            border-left-color: #3182ce;
        }

        .toast.info .toast-icon i {
            color: #3182ce;
        }

        .toast-content {
            flex-grow: 1;
        }

        .toast-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: #1a202c;
            margin: 0 0 4px;
            line-height: 1.4;
        }

        .toast-message {
            font-size: 0.875rem;
            color: #4a5568;
            margin: 0;
            line-height: 1.5;
        }

        .toast-close {
            color: #a0aec0;
            font-size: 18px;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            transition: color 0.2s;
        }

        .toast-close:hover {
            color: #718096;
        }
    </style>

    <script>
        // Simple script to toggle computer status
        document.querySelectorAll('.computer-unit').forEach(unit => {
            unit.addEventListener('click', () => {
                const status = unit.querySelector('.status');
                if (status.classList.contains('available')) {
                    status.classList.remove('available');
                    status.classList.add('in-use');
                    status.textContent = 'In Use';
                } else {
                    status.classList.remove('in-use');
                    status.classList.add('available');
                    status.textContent = 'Available';
                }
            });
        });

        // Laboratory selection handler
        document.getElementById('labSelect').addEventListener('change', function() {
            loadComputerStatus(this.value);
        });

        function loadComputerStatus(laboratory, callback) {
            if (!laboratory) {
                if (typeof callback === 'function') callback();
                return;
            }

            fetch(`../controllers/get_computer_status.php?lab=${laboratory}`)
                .then(response => response.json())
                .then(data => {
                    const grid = document.getElementById('computerGrid');
                    grid.innerHTML = '';
                    
                    for (let i = 1; i <= 50; i++) {
                        const status = data[i] || 'available';
                        grid.innerHTML += `
                            <div class="computer-unit" data-pc="${i}" data-lab="${laboratory}">
                                <div class="computer-icon">
                                    <i class="ri-computer-line"></i>
                                </div>
                                <div class="computer-info">
                                    <span class="pc-number">PC${i}</span>
                                    <span class="status ${status}">${status === 'available' ? 'Available' : 'In Use'}</span>
                                </div>
                            </div>
                        `;
                    }

                    // Add click handlers to new elements
                    attachComputerClickHandlers();
                    
                    // Run callback if provided
                    if (typeof callback === 'function') callback();
                });
        }

        function attachComputerClickHandlers() {
            document.querySelectorAll('.computer-unit').forEach(unit => {
                unit.addEventListener('click', function() {
                    const pcNumber = this.dataset.pc;
                    const laboratory = this.dataset.lab;
                    const status = this.querySelector('.status');
                    const newStatus = status.classList.contains('available') ? 'in-use' : 'available';

                    // Update status in database
                    fetch('../controllers/update_computer_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            laboratory: laboratory,
                            pc_number: pcNumber,
                            status: newStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            status.classList.toggle('available');
                            status.classList.toggle('in-use');
                            status.textContent = newStatus === 'available' ? 'Available' : 'In Use';
                        }
                    });
                });
            });
        }

        // Confirmation modal functions
        const modal = document.getElementById('confirmationModal');
        const closeBtn = document.querySelector('.close-modal');
        const cancelBtn = document.getElementById('cancelButton');
        const confirmBtn = document.getElementById('confirmButton');

        // Close modal when clicking the X or Cancel button
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Close modal when clicking outside the modal content
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        function closeModal() {
            modal.style.display = 'none';
            // Reset confirmation button
            confirmBtn.className = 'modal-button confirm';
            confirmBtn.removeAttribute('data-reservation-id');
            confirmBtn.removeAttribute('data-action');
        }

        // Toast notification functions
        function showToast(title, message, type = 'info', duration = 5000) {
            const toastContainer = document.getElementById('toast-container');
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            // Set icon based on type
            let iconClass = 'ri-information-line';
            if (type === 'success') iconClass = 'ri-check-line';
            if (type === 'error') iconClass = 'ri-error-warning-line';
            if (type === 'warning') iconClass = 'ri-alert-line';
            
            // Create toast content
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="${iconClass}"></i>
                </div>
                <div class="toast-content">
                    <h4 class="toast-title">${title}</h4>
                    <p class="toast-message">${message}</p>
                </div>
                <button class="toast-close">&times;</button>
            `;
            
            toastContainer.appendChild(toast);
            
            // Handle close button click
            const closeToastBtn = toast.querySelector('.toast-close');
            closeToastBtn.addEventListener('click', () => {
                closeToast(toast);
            });
            
            // Auto-close after duration
            if (duration > 0) {
                setTimeout(() => closeToast(toast), duration);
            }
            
            return toast;
        }

        function closeToast(toast) {
            toast.classList.add('hide');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }

        function processReservation(id, action) {
            // Get reservation details for the confirmation modal
            const reservationItem = document.querySelector(`.request-item:has(button[onclick*="processReservation(${id}, '${action}')"])`);
            
            if (!reservationItem) {
                showToast('Error', 'Could not find reservation details', 'error');
                return;
            }
            
            // Extract reservation details
            const studentName = reservationItem.querySelector('.student-name').textContent;
            const detailRows = reservationItem.querySelectorAll('.detail-row');
            let reservationDetails = {};
            
            detailRows.forEach(row => {
                const label = row.querySelector('.label').textContent.replace(':', '').trim();
                const value = row.querySelector('.value').textContent.trim();
                reservationDetails[label] = value;
            });
            
            // Set modal content based on action
            const modalTitle = document.getElementById('confirmationTitle');
            const modalMessage = document.getElementById('confirmationMessage');
            const reservationDetailsContainer = document.getElementById('reservationDetails');
            
            if (action === 'approve') {
                modalTitle.textContent = 'Approve Reservation';
                modalMessage.textContent = `Are you sure you want to approve the reservation for ${studentName}?`;
                confirmBtn.className = 'modal-button confirm';
            } else {
                modalTitle.textContent = 'Reject Reservation';
                modalMessage.textContent = `Are you sure you want to reject the reservation for ${studentName}?`;
                confirmBtn.className = 'modal-button confirm reject';
            }
            
            // Populate reservation details
            reservationDetailsContainer.innerHTML = '';
            const detailsToShow = [
                { key: 'ID Number', value: reservationDetails['ID Number'] },
                { key: 'Laboratory', value: reservationDetails['Laboratory'] },
                { key: 'PC Number', value: reservationDetails['PC Number'] },
                { key: 'Date', value: reservationDetails['Date'] },
                { key: 'Time', value: reservationDetails['Time'] },
                { key: 'Purpose', value: reservationDetails['Purpose'] }
            ];
            
            detailsToShow.forEach(detail => {
                if (detail.value) {
                    const detailItem = document.createElement('div');
                    detailItem.className = 'reservation-detail-item';
                    detailItem.innerHTML = `
                        <span class="detail-label">${detail.key}:</span>
                        <span class="detail-value">${detail.value}</span>
                    `;
                    reservationDetailsContainer.appendChild(detailItem);
                }
            });
            
            // Set data attributes for the confirm button
            confirmBtn.setAttribute('data-reservation-id', id);
            confirmBtn.setAttribute('data-action', action);
            
            // Setup confirmation action
            confirmBtn.onclick = function() {
                closeModal();
                submitReservationAction(id, action);
            };
            
            // Show modal
            modal.style.display = 'block';
        }

        function submitReservationAction(id, action) {
            // Show loading toast
            const loadingToast = showToast(
                `Processing ${action}...`, 
                'Please wait while we process your request.', 
                'info', 
                0
            );
            
            // Disable all action buttons
            const buttons = document.querySelectorAll('.action-btn');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = 0.6;
            });

            fetch('../controllers/process_approval.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `reservation_id=${id}&action=${action}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                // Close loading toast
                closeToast(loadingToast);
                
                try {
                    const data = JSON.parse(text);
                    console.log("Received response:", data);
                    
                    if (data.success) {
                        // Show success toast
                        if (action === 'approve') {
                            showToast(
                                'Reservation Approved', 
                                `The reservation has been successfully approved. ${data.reservation ? 'The computer status has been updated.' : ''}`, 
                                'success'
                            );
                            
                            // If we have reservation data, update computer status
                            if (data.reservation) {
                                updateComputerStatusFromReservation(data.reservation);
                                
                                // Redirect to sit-in page after a delay
                                setTimeout(() => {
                                    window.location.href = 'sit-in.php?reservation_id=' + id + '&tab=reservations';
                                }, 2500);
                            } else {
                                // Reload the page after a delay if no reservation data
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            }
                        } else {
                            showToast(
                                'Reservation Rejected', 
                                'The reservation has been rejected successfully.', 
                                'info'
                            );
                            
                            // Reload the page after a delay
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        showToast('Error', data.message, 'error');
                        
                        // Re-enable buttons
                        buttons.forEach(btn => {
                            btn.disabled = false;
                            btn.style.opacity = 1;
                        });
                    }
                } catch (parseError) {
                    console.error('Error parsing response:', text);
                    showToast(
                        'Server Error', 
                        'The server returned an invalid response. Please check the console for details.', 
                        'error'
                    );
                    
                    // Re-enable buttons
                    buttons.forEach(btn => {
                        btn.disabled = false;
                        btn.style.opacity = 1;
                    });
                }
            })
            .catch(error => {
                // Close loading toast
                closeToast(loadingToast);
                
                console.error('Error processing request:', error);
                showToast(
                    'Request Failed', 
                    'Error processing request: ' + error.message, 
                    'error'
                );
                
                // Re-enable buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = 1;
                });
            });
        }

        // Function to update computer status after reservation approval
        function updateComputerStatusFromReservation(reservation) {
            console.log("Updating computer status for:", reservation); // Debug log
            const lab = reservation.laboratory;
            const pcNumber = reservation.pc_number;
            const studentName = reservation.fullname;
            
            // First select the lab if it's not already selected
            if (document.getElementById('labSelect').value !== lab) {
                document.getElementById('labSelect').value = lab;
                loadComputerStatus(lab, function() {
                    // This callback runs after the computers are loaded
                    markComputerAsInUse(pcNumber, studentName);
                });
            } else {
                // Lab is already selected, just mark the computer
                markComputerAsInUse(pcNumber, studentName);
            }
        }
        
        // Function to mark a specific computer as in use
        function markComputerAsInUse(pcNumber, studentName) {
            const computerUnit = document.querySelector(`.computer-unit[data-pc="${pcNumber}"]`);
            if (computerUnit) {
                const statusElement = computerUnit.querySelector('.status');
                if (statusElement) {
                    statusElement.classList.remove('available');
                    statusElement.classList.add('in-use');
                    statusElement.textContent = 'In Use';
                    
                    // Add student info tooltip
                    computerUnit.setAttribute('title', `In use by ${studentName}`);
                    computerUnit.classList.add('student-assigned');
                    
                    // Visual feedback for the just-approved reservation
                    computerUnit.classList.add('just-approved');
                    
                    // Update in database
                    const laboratory = computerUnit.dataset.lab;
                    fetch('../controllers/update_computer_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            laboratory: laboratory,
                            pc_number: pcNumber,
                            status: 'in-use'
                        })
                    });
                }
            }
        }

        // Add function to ensure buttons are visible after content loads
        document.addEventListener('DOMContentLoaded', function() {
            // Fix for disappearing buttons
            const fixButtonVisibility = function() {
                document.querySelectorAll('.request-item').forEach(item => {
                    const actionsContainer = item.querySelector('.request-actions');
                    if (actionsContainer) {
                        // Ensure actions container is visible
                        actionsContainer.style.display = 'flex';
                        
                        // Make sure each button is visible
                        const buttons = actionsContainer.querySelectorAll('.action-btn');
                        buttons.forEach(btn => {
                            // Reset any styles that might hide the button
                            btn.style.visibility = 'visible';
                            btn.style.display = 'flex';
                            btn.style.opacity = '1';
                        });
                    }
                });
            };
            
            // Run the fix immediately and after a small delay to catch dynamically loaded content
            fixButtonVisibility();
            setTimeout(fixButtonVisibility, 300);
            
            // Also run the fix whenever new content might be loaded
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.addEventListener('click', function() {
                    setTimeout(fixButtonVisibility, 300);
                });
            });
            
            // Tab switching
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(this.dataset.tab).classList.add('active');
                });
            });
            
            // Logs filter functionality
            const filterOptions = document.querySelectorAll('.filter-option');
            filterOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove active class from all filters
                    filterOptions.forEach(opt => opt.classList.remove('active'));
                    document.querySelectorAll('.logs-container').forEach(container => container.classList.remove('active'));
                    
                    // Add active class to clicked filter and corresponding content
                    this.classList.add('active');
                    document.getElementById(this.dataset.filter + '-logs').classList.add('active');
                });
            });

            // Listen for computer status update events from sit-in.php
            document.addEventListener('computerStatusUpdated', function(event) {
                console.log('Received computer status update:', event.detail);
                const { laboratory, pcNumber, status } = event.detail;
                
                // Update the UI if the laboratory is currently selected
                if (document.getElementById('labSelect').value === laboratory) {
                    const computerUnit = document.querySelector(`.computer-unit[data-pc="${pcNumber}"]`);
                    if (computerUnit) {
                        const statusElement = computerUnit.querySelector('.status');
                        if (statusElement) {
                            // Update status class and text
                            statusElement.classList.remove('in-use', 'available');
                            statusElement.classList.add(status === 'available' ? 'available' : 'in-use');
                            statusElement.textContent = status === 'available' ? 'Available' : 'In Use';
                            
                            // Remove student assignment indicator if it exists
                            computerUnit.classList.remove('student-assigned', 'just-approved');
                            computerUnit.removeAttribute('title');
                            
                            // Add visual feedback
                            computerUnit.classList.add('just-updated');
                            setTimeout(() => {
                                computerUnit.classList.remove('just-updated');
                            }, 2000);
                        }
                    }
                } else {
                    // If the lab isn't currently selected, show a notification
                    // that prompts the user to switch to the lab to see the change
                    const labSelectElement = document.getElementById('labSelect');
                    const option = new Option(`Laboratory ${laboratory}`, laboratory);
                    
                    // Update the lab select to highlight the change
                    if (confirm(`Computer ${pcNumber} in Laboratory ${laboratory} has been updated to ${status}. Would you like to view this laboratory?`)) {
                        labSelectElement.value = laboratory;
                        loadComputerStatus(laboratory);
                    }
                }
            });
        });

        // Add styling for the updated computer status
        document.head.insertAdjacentHTML('beforeend', `
            <style>
                .computer-unit.just-updated {
                    animation: flash 1.5s;
                }
                
                @keyframes flash {
                    0%, 100% { background-color: transparent; }
                    50% { background-color: rgba(56, 161, 105, 0.3); }
                }
            </style>
        `);
    </script>

</body>
</html>