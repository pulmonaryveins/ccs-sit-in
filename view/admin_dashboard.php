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
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_sitin'] += $row['count'];
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
            color: #333;
            font-weight: 600;
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
                            <!-- Hidden input with fixed title -->
                            <input type="hidden" id="title" name="title" value="CCS ADMIN">
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
