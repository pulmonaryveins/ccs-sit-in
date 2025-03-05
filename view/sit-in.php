<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db_connect.php';
// Fetch current sit-in students
$current_students = [];
$query = "SELECT r.*, u.idno, u.firstname, u.lastname 
          FROM reservations r
          JOIN users u ON r.idno = u.idno
          WHERE DATE(r.date) = CURDATE() 
          AND r.time_in IS NOT NULL 
          AND r.time_out IS NULL
          AND r.status = 'approved'
          ORDER BY r.time_in DESC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $current_students[] = $row;
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
                <a href="admin_dashboard.php" class="nav-link ">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="request.php" class="nav-link">
                    <i class="ri-mail-check-line"></i>
                    <span>Request</span>
                </a>
                <a href="sit-in.php" class="nav-link active">
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

    <style>
        .content-wrapper {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - var(--nav-height));
            padding: 2rem;
            margin-top: var(--nav-height);
        }

        .table-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 1600px;
            padding: 0;
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-header h2 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
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

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table td {
            padding: 1rem;
            font-size: 0.875rem;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table tr:hover td {
            background-color: #f8fafc;
        }

        .empty-state {
            text-align: center;
            padding: 3rem !important;
        }

        .empty-state-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            color: #a0aec0;
        }

        .empty-state-content i {
            font-size: 2.5rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge.active {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.approved {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-badge.completed {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #b45309;
        }

        .action-button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-button.danger {
            background: #ef4444;
            color: white;
        }

        .action-button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>

    <div class="content-wrapper">
        <div class="table-wrapper">
            <div class="table-header">
                <h2>Current Students in Laboratory</h2>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="ri-search-line"></i>
                        <input type="text" id="searchInput" placeholder="Search students...">
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID Number</th>
                            <th>Full Name</th>
                            <th>Purpose</th>
                            <th>Laboratory</th>
                            <th>PC Number</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($current_students)): ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <div class="empty-state-content">
                                        <i class="ri-computer-line"></i>
                                        <p>No students currently sitting in</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($current_students as $student): ?>
                                <tr>
                                    <td class="font-mono"><?php echo htmlspecialchars($student['idno']); ?></td>
                                    <td><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></td>
                                    <td><span class="purpose-badge"><?php echo htmlspecialchars($student['purpose']); ?></span></td>
                                    <td>Laboratory <?php echo htmlspecialchars($student['laboratory']); ?></td>
                                    <td>PC <?php echo htmlspecialchars($student['pc_number']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($student['time_in'])); ?></td>
                                    <td><span class="status-badge active">Active</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        let searchText = this.value.toLowerCase();
        let tableRows = document.querySelectorAll('.modern-table tbody tr');
        
        tableRows.forEach(row => {
            if (!row.querySelector('.empty-state')) {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            }
        });
    });
    </script>
</body>
</html>