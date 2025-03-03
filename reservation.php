<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get user details from database
require_once 'db_connect.php';
$username = $_SESSION['username'];
// Update the SQL query to include new fields
$sql = "SELECT idno, firstname, lastname, middlename, course, year, profile_image, email, address FROM users WHERE username = ?";
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
    
    $_SESSION['profile_image'] = $row['profile_image'] ?? 'default-avatar.png';
    $_SESSION['email'] = $row['email'];
    $_SESSION['address'] = $row['address'];
}

$stmt->close();
$conn->close();
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
</head>
<body>
    <!-- Navigation Bar -->
    <div class="nav-container">
        <div class="nav-wrapper">
            <!-- Left side - Profile -->
            <div class="nav-profile">
                <div class="profile-trigger" id="profile-trigger">
                    <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : 'default-avatar.png'; ?>" 
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
                <a href="reservation.php" class="nav-link active">
                    <i class="ri-calendar-line"></i>
                    <span>Reservation</span>
                </a>
                <a href="history.php" class="nav-link">
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
                <a href="logout.php" class="action-link">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="dashboard-grid">
        <!-- Left Column - Reservation Form -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header">
                    <h3>Make a Reservation</h3>
                </div>
                <div class="profile-content" style="padding: 0;">
                    <form action="process_reservation.php" method="POST" class="reservation-form">
                        <div class="student-info-grid">
                            <!-- ID Number -->
                            <div class="info-card">
                                <div class="info-icon"><i class="ri-profile-fill"></i></div>
                                <div class="info-content">
                                    <div class="detail-label">Student ID</div>
                                    <div class="detail-value">
                                        <input type="text" name="idno" value="<?php echo htmlspecialchars($_SESSION['idno']); ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Full Name -->
                            <div class="info-card">
                                <div class="info-icon"><i class="ri-user-3-fill"></i></div>
                                <div class="info-content">
                                    <div class="detail-label">Full Name</div>
                                    <div class="detail-value">
                                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($_SESSION['fullname']); ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Course and Year -->
                            <div class="info-card">
                                <div class="info-icon"><i class="ri-book-fill"></i></div>
                                <div class="info-content">
                                    <div class="detail-label">Course & Year</div>
                                    <div class="detail-value">
                                        <input type="text" value="<?php echo htmlspecialchars($_SESSION['course'] . ' - ' . $_SESSION['year_level']); ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Purpose -->
                            <div class="info-card">
                                <div class="info-icon"><i class="ri-code-box-fill"></i></div>
                                <div class="info-content">
                                    <div class="detail-label">Purpose</div>
                                    <div class="detail-value">
                                        <select name="purpose" required>
                                            <option value="">Select Purpose</option>
                                            <option value="c_programming">C Programming</option>
                                            <option value="java_programming">Java Programming</option>
                                            <option value="csharp">C#</option>
                                            <option value="php">PHP</option>
                                            <option value="aspnet">ASP.Net</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Laboratory -->
                            <div class="info-card">
                                <div class="info-icon"><i class="ri-computer-fill"></i></div>
                                <div class="info-content">
                                    <div class="detail-label">Laboratory</div>
                                    <div class="detail-value">
                                        <select name="laboratory" required>
                                            <option value="">Select Laboratory</option>
                                            <option value="524">524</option>
                                            <option value="526">526</option>
                                            <option value="528">528</option>
                                            <option value="530">530</option>
                                            <option value="542">542</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Date -->
                            <div class="info-card">
                                <div class="info-icon"><i class="ri-calendar-fill"></i></div>
                                <div class="info-content">
                                    <div class="detail-label">Date</div>
                                    <div class="detail-value">
                                        <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Time In -->
                            <div class="info-card">
                                <div class="info-icon"><i class="ri-time-fill"></i></div>
                                <div class="info-content">
                                    <div class="detail-label">Time In</div>
                                    <div class="detail-value">
                                        <input type="time" name="time_in" required min="07:00" max="17:00">
                                    </div>
                                </div>
                            </div>
                            <!-- Remaining Sessions -->
                            <div class="info-card">
                                <div class="info-icon"><i class="ri-timer-fill"></i></div>
                                <div class="info-content">
                                    <div class="detail-label">Remaining Sessions</div>
                                    <div class="detail-value sessions-count">
                                        <?php echo isset($_SESSION['remaining_sessions']) ? $_SESSION['remaining_sessions'] : '30'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Submit Button -->
                            <div class="edit-controls" style="grid-column: span 2;">
                                <button type="submit" class="edit-btn">
                                    <i class="ri-check-line"></i>
                                    <span>Confirm Reservation</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column - Laboratory Schedule -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header">
                    <h3>Laboratory Schedule</h3>
                </div>
                <div class="profile-content">
                    <div class="schedule-container" id="schedule-container">
                        <div class="schedule-info" id="schedule-info">
                            <p>Select a laboratory and date to view available time slots</p>
                        </div>
                        <div id="schedule-grid" class="schedule-grid" style="display: none;">
                            <!-- Schedule will be dynamically populated here -->
                        </div>
                    </div>
                </div>
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
                        <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : 'default-avatar.png'; ?>" 
                             alt="Profile Picture" 
                             id="profile-preview">
                    </div>
                    <div class="profile-name">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($_SESSION['fullname']); ?></h3>
                        </div>  
                    </div>
                </div>
        </div>
    </div>

    <!-- Add JavaScript for dynamic schedule loading -->
    <script>
        document.querySelector('select[name="laboratory"]').addEventListener('change', loadSchedule);
        document.querySelector('input[name="date"]').addEventListener('change', loadSchedule);

        function loadSchedule() {
            const lab = document.querySelector('select[name="laboratory"]').value;
            const date = document.querySelector('input[name="date"]').value;
            
            if (lab && date) {
                // Add AJAX call to fetch schedule
                fetch(`get_schedule.php?lab=${lab}&date=${date}`)
                    .then(response => response.json())
                    .then(data => {
                        updateScheduleDisplay(data);
                    });
            }
        }

        function updateScheduleDisplay(scheduleData) {
            const scheduleInfo = document.getElementById('schedule-info');
            const scheduleGrid = document.getElementById('schedule-grid');
            
            // Update display logic here
        }
    </script>
</body>
</html>