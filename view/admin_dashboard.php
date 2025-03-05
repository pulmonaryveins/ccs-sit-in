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

// Get current sit-in count (currently in the laboratory and approved)
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
                <div class="stat-title">Total Sit-In</div>
                <div class="stat-value"><?php echo $stats['total_sitin']; ?></div>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="statsChart"></canvas>
        </div>

        <!-- Announcements Section -->
        <div class="dashboard-grid" style="margin-top: 2rem;">
            <!-- Left Column - Create Announcement -->
            <div class="dashboard-column">
                <div class="profile-card">
                    <div class="profile-header">
                        <h3>Create Announcement</h3>
                    </div>
                    <div class="profile-content">
                        <form id="announcementForm" class="announcement-form">
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="content">Content</label>
                                <textarea id="content" name="content" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="edit-btn2">
                                <i class="ri-send-plane-fill"></i>
                                <span>Post Announcement</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - Announcement List -->
            <div class="dashboard-column">
                <div class="profile-card">
                    <div class="profile-header">
                        <h3>Recent Announcements</h3>
                    </div>
                    <div class="profile-content">
                        <div class="announcement-list">
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-item" data-id="<?php echo $announcement['id']; ?>">
                                    <div class="announcement-title">
                                        <i class="ri-notification-3-fill"></i>
                                        <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                        <button class="delete-announcement" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
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
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('statsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Total Students', 'Current Sit-In', 'Total Sit-In'],
                datasets: [{
                    label: 'Statistics',
                    data: [
                        <?php echo $stats['total_students']; ?>,
                        <?php echo $stats['current_sitin']; ?>,
                        <?php echo $stats['total_sitin']; ?>
                    ],
                    backgroundColor: [
                        'rgba(117,86,204,0.8)',
                        'rgba(213,105,167,0.8)',
                        'rgba(155,95,185,0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
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
    </script>
</body>
</html>
