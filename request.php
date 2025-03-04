<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
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
                    <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : 'logo/AVATAR.png'; ?>" 
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
                <a href="request.php" class="nav-link active">
                    <i class="ri-mail-check-line"></i>
                    <span>Request</span>
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
                <a href="logout.php" class="action-link">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-grid">
        <!-- Left Column - Computer Control -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header">
                    <h3>Laboratory Computer Control</h3>
                    <select id="labSelect" class="lab-select">
                        <option value="">Select Laboratory</option>
                        <option value="524">Laboratory 524</option>
                        <option value="526">Laboratory 526</option>
                        <option value="528">Laboratory 528</option>
                        <option value="530">Laboratory 530</option>
                        <option value="542">Laboratory 542</option>
                    </select>
                </div>
                <div class="profile-content">
                    <div class="computer-grid">
                        <?php for($i = 1; $i <= 40; $i++): ?>
                            <div class="computer-unit" data-pc="<?php echo $i; ?>">
                                <div class="computer-icon">
                                    <i class="ri-computer-line"></i>
                                </div>
                                <div class="computer-info">
                                    <span class="pc-number">PC<?php echo $i; ?></span>
                                    <span class="status available">Available</span>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Reservation Requests -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header">
                    <h3>Reservation Requests</h3>
                </div>
                <div class="profile-content">
                    <div class="requests-list">
                        <!-- Sample Reservation Request -->
                        <div class="request-item">
                            <div class="request-header">
                                <span class="student-name">John Doe</span>
                                <span class="request-status pending">Pending</span>
                            </div>
                            <div class="request-details">
                                <div class="detail-row">
                                    <span class="label">ID Number:</span>
                                    <span class="value">2021-0001</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Laboratory:</span>
                                    <span class="value">524</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Date:</span>
                                    <span class="value">2024-01-20</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Time:</span>
                                    <span class="value">13:00 - 15:00</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Purpose:</span>
                                    <span class="value">C Programming</span>
                                </div>
                            </div>
                            <div class="request-actions">
                                <button class="action-btn approve">
                                    <i class="ri-check-line"></i> Approve
                                </button>
                                <button class="action-btn reject">
                                    <i class="ri-close-line"></i> Reject
                                </button>
                            </div>
                        </div>

                        <!-- More sample requests -->
                        <div class="request-item">
                            <div class="request-header">
                                <span class="student-name">Jane Smith</span>
                                <span class="request-status approved">Approved</span>
                            </div>
                            <div class="request-details">
                                <div class="detail-row">
                                    <span class="label">ID Number:</span>
                                    <span class="value">2021-0002</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Laboratory:</span>
                                    <span class="value">526</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Date:</span>
                                    <span class="value">2024-01-21</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Time:</span>
                                    <span class="value">09:00 - 11:00</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Purpose:</span>
                                    <span class="value">Java Programming</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .lab-select {
            padding: 0.5rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-left: 1rem;
        }

        .computer-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            padding: 1rem;
        }

        .computer-unit {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .computer-unit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .computer-icon {
            font-size: 1.5rem;
            color: #7556cc;
            margin-bottom: 0.5rem;
        }

        .computer-info {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .pc-number {
            font-weight: 600;
            color: #2d3748;
        }

        .status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            margin-top: 0.25rem;
        }

        .status.available {
            background: #c6f6d5;
            color: #2f855a;
        }

        .status.in-use {
            background: #fed7d7;
            color: #c53030;
        }

        .requests-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
        }

        .request-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .student-name {
            font-weight: 600;
            color: #2d3748;
        }

        .request-status {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
        }

        .request-status.pending {
            background: #feebc8;
            color: #c05621;
        }

        .request-status.approved {
            background: #c6f6d5;
            color: #2f855a;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .label {
            color: #718096;
        }

        .value {
            font-weight: 500;
            color: #2d3748;
        }

        .request-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.5rem;
            border-radius: 6px;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            transition: all 0.3s ease;
        }

        .action-btn.approve {
            background: #38a169;
    
        }

        .action-btn.reject {
            background: #e53e3e;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            filter: brightness(110%);
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
            // You can add logic here to load the actual computer status for the selected laboratory
            console.log('Selected laboratory:', this.value);
        });
    </script>

</body>
</html>