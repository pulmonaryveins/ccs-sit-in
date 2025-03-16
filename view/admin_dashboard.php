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

// Get current sit-in count (combined from both reservations and sit_ins tables)
// First, count current students from reservations
$query = "SELECT COUNT(*) as count FROM reservations 
          WHERE DATE(date) = CURDATE() 
          AND time_in IS NOT NULL 
          AND time_out IS NULL
          AND status = 'approved'";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['current_sitin'] = $row['count'];
} else {
    $stats['current_sitin'] = 0;
}

// Next, add current students from sit_ins
$query = "SELECT COUNT(*) as count FROM sit_ins 
          WHERE time_out IS NULL 
          AND status = 'active'";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['current_sitin'] += $row['count'];
}

// Get total sit-in count from both tables
// First from reservations
$query = "SELECT COUNT(*) as count FROM reservations";
$result = $conn->query($query);
if ($result) {
    $stats['total_sitin'] = $result->fetch_assoc()['count'];
}

// Add count from sit_ins
$query = "SELECT COUNT(*) as count FROM sit_ins";
$result = $conn->query($query);
if ($result) {
    $stats['total_sitin'] += $result->fetch_assoc()['count'];
}

// Get student purposes from sit-ins table - IMPROVED QUERY
$purpose_stats = [];
$query = "SELECT purpose, COUNT(*) as count 
          FROM sit_ins 
          WHERE purpose IS NOT NULL 
          GROUP BY purpose 
          ORDER BY count DESC 
          LIMIT 5";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $purpose_stats[] = [
            'label' => $row['purpose'],
            'count' => $row['count']
        ];
    }
} else {
    // Fallback to reservations table if no data in sit_ins
    $query = "SELECT purpose, COUNT(*) as count 
              FROM reservations 
              WHERE purpose IS NOT NULL 
              GROUP BY purpose 
              ORDER BY count DESC 
              LIMIT 5";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $purpose_stats[] = [
                'label' => $row['purpose'],
                'count' => $row['count']
            ];
        }
    }
}

// Fetch announcements - fixed query
$announcements = [];
$query = "SELECT * FROM announcements ORDER BY created_at DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Modal styles - enhanced and consistent with site design */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            animation: modalFade 0.3s ease;
        }
        
        @keyframes modalFade {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .close {
            color: #999;
            float: right;
            font-size: 28px;
            font-weight: 300;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 0.8;
        }
        
        .close:hover {
            color: #7556cc;
        }
        
        .announcement-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        
        .edit-announcement, .delete-announcement {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: #555;
            transition: color 0.2s;
            padding: 4px;
        }
        
        .edit-announcement:hover {
            color: #7556cc;
        }
        
        .delete-announcement:hover {
            color: #ff5555;
        }
        
        /* Form styling in modal */
        #editAnnouncementForm .form-group {
            margin-bottom: 20px;
        }
        
        #editAnnouncementForm label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        
        #editAnnouncementForm textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            min-height: 120px;
            transition: border-color 0.2s;
        }
        
        #editAnnouncementForm textarea:focus {
            border-color: #7556cc;
            outline: none;
            box-shadow: 0 0 0 2px rgba(117, 86, 204, 0.2);
        }
        
        /* Button styling - consistent with other buttons */
        #editAnnouncementForm button.edit-btn2 {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            color: white;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        #editAnnouncementForm button.edit-btn2:hover {
            background: linear-gradient(135deg, #6445b8 0%, #8445b8 100%);
            box-shadow: 0 4px 12px rgba(117, 86, 204, 0.3);
        }
        
        /* New modern dashboard styles */
        .admin-dashboard {
            padding: 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Dashboard header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-top: 5rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
            text-align: center;  /* Added */
        }
        
        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
            display: inline-flex;  /* Changed from flex to inline-flex */
            align-items: center;
            gap: 0.75rem;
        }
        
        .dashboard-subtitle {
            color: #666;
            font-size: 1.25rem;
            text-align: center;  /* Added */
            display: block;  /* Added */
        }
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }
        
        .stat-title {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
        }
        
        .stat-value {
            font-size: 2.25rem;
            font-weight: 700;
        }
        
        /* Section divider */
        .section-divider {
            display: flex;
            align-items: center;
            margin: 2rem 0 1.5rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-line {
            flex-grow: 1;
            height: 1px;
            background: #eee;
        }
        
        /* Charts layout */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .chart-header {
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
        }
        
        .chart-header i {
            margin-right: 0.5rem;
            color: #7556cc;
        }
        
        .chart-container {
            position: relative;
            height: 400px; /* Increased from 240px */
            width: 100%;
        }
        
        /* Announcements section */
        .announcements-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 992px) {
            .announcements-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .announcements-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .announcements-header {
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.75rem;
        }
        
        .announcements-header i {
            margin-right: 0.5rem;
            color: #7556cc;
        }
        
        .announcement-form {
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #444;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            min-height: 120px;
            transition: border-color 0.2s;
        }
        
        .form-group textarea:focus {
            border-color: #7556cc;
            outline: none;
            box-shadow: 0 0 0 2px rgba(117, 86, 204, 0.2);
        }
        
        .edit-btn2 {
            display: block;
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            color: white;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .edit-btn2:hover {
            background: linear-gradient(135deg, #6445b8 0%, #8445b8 100%);
            box-shadow: 0 4px 12px rgba(117, 86, 204, 0.3);
        }
        
        /* Modernized announcement items */
        .announcement-list {
            max-height: 380px;
            overflow-y: auto;
        }
        
        .announcement-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        
        .announcement-item:first-child {
            padding-top: 0;
        }
        
        .announcement-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .announcement-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .announcement-title i {
            color: #7556cc;
        }
        
        .announcement-title h3 {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
            margin: 0;
        }
        
        .announcement-details p {
            color: #555;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .announcement-details .timestamp {
            font-size: 0.8rem;
            color: #888;
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
                <a href="admin_dashboard.php" class="nav-link active">
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

    <div class="admin-dashboard">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                Administrator Dashboard
            </div>
        </div>
        
        <!-- Stats Grid - Modernized -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Students</div>
                <div class="stat-value"><?php echo $stats['total_students']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Current Students in Lab</div>
                <div class="stat-value"><?php echo $stats['current_sitin']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Sit-In Records</div>
                <div class="stat-value"><?php echo $stats['total_sitin']; ?></div>
            </div>
        </div>

        <!-- Announcements Section -->
        <div class="announcements-grid">
            <!-- Left Column - Create Announcement -->
            <div class="announcements-card">
                <div class="announcements-header">
                    <i class="ri-add-line"></i>
                    <span>Create Announcement</span>
                </div>
                <form id="announcementForm" class="announcement-form">
                    <input type="hidden" id="title" name="title" value="CCS ADMIN">
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="4" placeholder="Type your announcement here..." required></textarea>
                    </div>
                    <button type="submit" class="edit-btn2">
                        <i class="ri-send-plane-fill"></i>
                        <span>Post Announcement</span>
                    </button>
                </form>
            </div>

            <!-- Right Column - Announcement List -->
            <div class="announcements-card">
                <div class="announcements-header">
                    <i class="ri-notification-3-line"></i>
                    <span>Recent Announcements</span>
                </div>
                <div class="announcement-list">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-item" data-id="<?php echo $announcement['id']; ?>">
                            <div class="announcement-title">
                                <i class="ri-notification-3-fill"></i>
                                <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                <div class="announcement-actions">
                                    <button class="edit-announcement" onclick="editAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo addslashes(htmlspecialchars($announcement['title'])); ?>', '<?php echo addslashes(htmlspecialchars($announcement['content'])); ?>')">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="delete-announcement" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="announcement-details">
                                <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                                <span class="timestamp"><?php echo date('F d, Y', strtotime($announcement['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Overall Stats Chart -->
        <div class="chart-card" style="margin-bottom: 1.5rem;">
            <div class="chart-header">
                <i class="ri-bar-chart-box-line"></i>
                <span>Overall Statistics</span>
            </div>
            <div class="chart-container">
                <canvas id="statsChart"></canvas>
            </div>
        </div>

        <!-- Charts Grid - Fixed structure -->
        <div class="charts-grid">
            <div class="chart-card" style="min-height: 500px; grid-column: 1 / -1;">  
                <div class="chart-header">
                    <i class="ri-questionnaire-line"></i>
                    <span>Student Sit-in Purposes</span>
                </div>
                <div class="chart-container">
                    <canvas id="purposeChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Enhanced Edit Announcement Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Announcement</h3>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <form id="editAnnouncementForm">
                    <input type="hidden" id="edit_id" name="id">
                    <input type="hidden" id="edit_title" name="title" value="CCS ADMIN">
                    <div class="form-group">
                        <label for="edit_content">Content</label>
                        <textarea id="edit_content" name="content" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="edit-btn2">
                        <i class="ri-save-line"></i>
                        <span>Update Announcement</span>
                    </button>
                </form>
            </div>
        </div>
    </div><!-- End of admin-dashboard div -->

    <script>
    // Wait for DOM to be fully loaded before initializing charts
    document.addEventListener('DOMContentLoaded', function() {
        // Overall Statistics Chart
        const statsCtx = document.getElementById('statsChart');
        if (statsCtx) {
            new Chart(statsCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Total Students', 'Current Students', 'Total Sit-Ins'],
                    datasets: [{
                        data: [
                            <?php echo $stats['total_students']; ?>,
                            <?php echo $stats['current_sitin']; ?>,
                            <?php echo $stats['total_sitin']; ?>
                        ],
                        backgroundColor: [
                            'rgba(117,86,204,0.8)',
                            'rgba(213,105,167,0.8)',
                            'rgba(155,95,185,0.8)'
                        ],
                        borderRadius: 6,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Purpose Distribution Chart - Enhanced with better tooltips and formatting
        const purposeCtx = document.getElementById('purposeChart');
        if (purposeCtx) {
            // Debug output to verify data
            console.log('Purpose Stats:', <?php echo json_encode($purpose_stats); ?>);
            
            // Check if we have any data
            const purposeData = <?php echo json_encode(array_column($purpose_stats, 'count')); ?>;
            const hasPurposeData = purposeData && purposeData.length > 0 && purposeData.some(count => count > 0);
            
            if (!hasPurposeData) {
                // If no data, show a message
                const container = purposeCtx.closest('.chart-container');
                container.innerHTML = '<div style="display:flex; height:100%; align-items:center; justify-content:center; color:#666; text-align:center;">No sit-in purpose data available.<br>Students need to specify purposes when sitting in.</div>';
            } else {
                // Create the chart with existing data
                new Chart(purposeCtx, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($purpose_stats, 'label')); ?>,
                        datasets: [{
                            data: purposeData,
                            backgroundColor: [
                                'rgba(117,86,204,0.8)',
                                'rgba(213,105,167,0.8)',
                                'rgba(155,95,185,0.8)',
                                'rgba(94,114,228,0.8)',
                                'rgba(45,206,137,0.8)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.raw + ' time' + (context.raw != 1 ? 's' : '') + ' selected';
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    });

    // Announcement Form Handler
    document.getElementById('announcementForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('../admin/process_announcement.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error posting announcement');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Delete Announcement Handler
    async function deleteAnnouncement(id) {
        if (!confirm('Are you sure you want to delete this announcement?')) return;
        
        try {
            const response = await fetch('../admin/delete_announcement.php', {  // Updated path
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            });
            
            const result = await response.json();
            if (result.success) {
                document.querySelector(`[data-id="${id}"]`).remove();
            } else {
                alert('Error deleting announcement');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    // Modal Functions
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    function editAnnouncement(id, title, content) {
        // Populate the form
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_content').value = content;
        // Show the modal
        document.getElementById('editModal').style.display = 'block';
    }

    // Edit Announcement Form Handler
    document.getElementById('editAnnouncementForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            id: formData.get('id'),
            title: formData.get('title'),
            content: formData.get('content')
        };
        
        try {
            const response = await fetch('../admin/update_announcement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error updating announcement');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>
</body>
</html>
