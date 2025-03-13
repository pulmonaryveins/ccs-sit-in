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
        <div class="profile-card">
            <div class="profile-header">
                <h3>Activity History</h3>
                <div class="filter-controls">
                    <select id="historyFilter" class="filter-select">
                        <option value="all">All Activities</option>
                        <option value="reservation">Reservations</option>
                        <option value="sit_in">Sit-ins</option>
                    </select>
                </div>
            </div>
            <div class="profile-content">
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
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Laboratory</th>
                                <th>PC Number</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Purpose</th>
                                <th>Activity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history_records as $record): ?>
                                <tr data-type="<?php echo htmlspecialchars($record['source']); ?>">
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($record['date']))); ?></td>
                                    <td>Laboratory <?php echo htmlspecialchars($record['laboratory']); ?></td>
                                    <td>PC <?php echo htmlspecialchars($record['pc_number']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($record['time_in'])); ?></td>
                                    <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : 'Not yet'; ?></td>
                                    <td><?php echo htmlspecialchars($record['purpose']); ?></td>
                                    <td>
                                        <span class="activity-badge <?php echo htmlspecialchars($record['source']); ?>">
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
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Full height */
            margin: 0;
            background-color: #f8f9fa; /* Light background for contrast */
        }
        
        .history-container {
            width: 100%;
            max-width: 1700px;
            margin: auto; /* Centers it horizontally */
            padding: 1.5rem;
            overflow: auto; /* Enables scrolling if content overflows */
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .profile-card {
            width: 100%;
            overflow: hidden;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .filter-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fff;
            font-size: 0.875rem;
            color: #4a5568;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th, 
        .history-table td {
            padding: 15px;
            text-align: left;
            color: #4a5568;
            border-bottom: 1px solid #ddd;
        }

        .history-table th {
            font-weight: 500;
            padding-bottom: 1rem;
        }

        .history-table td {
            white-space: nowrap; /* Ensures text stays in a single line */
        }

        .activity-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .activity-badge.reservation {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        
        .activity-badge.sit_in {
            background-color: #ddd6fe;
            color: #6d28d9;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge.pending {
            background-color: #feebc8;
            color: #c05621;
        }

        .status-badge.approved {
            background-color: #c6f6d5;
            color: #2f855a;
        }
        
        .status-badge.active {
            background-color: #c6f6d5;
            color: #2f855a;
        }

        .status-badge.rejected {
            background-color: #fed7d7;
            color: #c53030;
        }

        .status-badge.completed {
            background-color: #e2e8f0;
            color: #2d3748;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
            color: #a0aec0;
        }
        
        .empty-state-icon i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .empty-state-message h4 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
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

        // Filter functionality for history table
        document.getElementById('historyFilter').addEventListener('change', function() {
            const filterValue = this.value;
            const tableRows = document.querySelectorAll('.history-table tbody tr');
            
            tableRows.forEach(row => {
                const type = row.getAttribute('data-type');
                if (filterValue === 'all' || type === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>