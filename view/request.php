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
$sql = "SELECT * FROM reservations WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($sql);
$pending_reservations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_reservations[] = $row;
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
                <a href="request.php" class="nav-link active">
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
                    <div class="computer-grid" id="computerGrid">
                        <!-- PCs will be loaded dynamically -->
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
                                            <span class="value"><?php echo htmlspecialchars($reservation['time_in']); ?></span>
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
        </div>
    </div>

    <style>
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
            font-weight: 600;
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
            max-height: 600px;
            overflow-y: auto;
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
        
        .request-item {
            background: white;
            border: 1px solid #e8edf5;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
        }
        
        .request-item:hover {
            border-color: #d3dce9;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        
        .request-item:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #f59e0b, #d97706);
            border-radius: 2px;
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

        function loadComputerStatus(laboratory) {
            if (!laboratory) return;

            fetch(`../controllers/get_computer_status.php?lab=${laboratory}`)
                .then(response => response.json())
                .then(data => {
                    const grid = document.getElementById('computerGrid');
                    grid.innerHTML = '';
                    
                    for (let i = 1; i <= 40; i++) {
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

        function processReservation(id, action) {
            if (!confirm(`Are you sure you want to ${action} this reservation?`)) {
                return;
            }

            fetch('../controllers/process_approval.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `reservation_id=${id}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Refresh to show updated status
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error processing request');
                console.error('Error:', error);
            });
        }
    </script>

</body>
</html>