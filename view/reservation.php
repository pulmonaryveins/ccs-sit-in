<?php
session_start();

// Define htmlspecialchars if it doesn't exist
if (!function_exists('htmlspecialchars')) {
    function htmlspecialchars($string, $flags = ENT_COMPAT | ENT_HTML401, $encoding = 'UTF-8', $double_encode = true) {
        $string = str_replace('&', '&amp;', $string);
        $string = str_replace('<', '&lt;', $string);
        $string = str_replace('>', '&gt;', $string);
        $string = str_replace('"', '&quot;', $string);
        return $string;
    }
}

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
$conn->close();
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
                <a href="../auth/logout.php" class="action-link">
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
                    <form action="../controllers/process_reservation.php" method="POST" class="reservation-form">
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
                                    <div class="detail-label">Course/Depart. & Year</div>
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
                                    <div class="detail-value sessions-count <?php 
                                        $remaining = isset($_SESSION['remaining_sessions']) ? (int)$_SESSION['remaining_sessions'] : 30;
                                        echo $remaining <= 5 ? 'low' : ($remaining <= 10 ? 'medium' : ''); 
                                    ?>">
                                        <?php echo $remaining; ?>
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

        <!-- Right Column - PC Availability -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header">
                    <h3>Select a PC</h3>
                    <select id="labSelect" class="lab-select" name="laboratory">
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
                        <div class="initial-message">
                            Please select a laboratory to view available PCs
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
                            <div class="detail-label">Course/Department</div>
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

    <style>
        .lab-select {
            padding: 0.5rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-left: 1rem;
            background: white;
        }

        .computer-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            padding: 1rem;
        }

        .initial-message {
            grid-column: 1 / -1;
            text-align: center;
            padding: 2rem;
            color: #718096;
        }

        .computer-unit {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .computer-unit.available {
            cursor: pointer;
        }

        .computer-unit.available:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .computer-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .computer-icon i {
            color: #7556cc;
        }

        .pc-number {
            font-weight: 600;
            color: #2d3748;
            display: block;
            margin-bottom: 0.25rem;
        }

        .status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            display: inline-block;
        }

        .status.available {
            background: #c6f6d5;
            color: #2f855a;
        }

        .status.in-use {
            background: #fed7d7;
            color: #c53030;
        }
        
        .computer-unit.selected {
            border: 2px solid #7556cc;
            background: #f0f0ff;
        }
        
        .computer-unit.in-use {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .sessions-count.low {
            color: #dc2626;
            font-weight: 600;
        }
        
        .sessions-count.medium {
            color: #ea580c;
            font-weight: 600;
        }
    </style>

    <script>
        // Lab selection functionality
        document.getElementById('labSelect').addEventListener('change', function() {
            loadComputerStatus(this.value);
            // Update the laboratory field in the form
            document.querySelector('select[name="laboratory"]').value = this.value;
        });

        function loadComputerStatus(laboratory) {
            if (!laboratory) {
                document.getElementById('computerGrid').innerHTML = `
                    <div class="initial-message">
                        Please select a laboratory to view available PCs
                    </div>`;
                return;
            }
            
            fetch(`../controllers/get_computer_status.php?lab=${laboratory}`)
                .then(response => response.json())
                .then(data => {
                    const grid = document.getElementById('computerGrid');
                    grid.innerHTML = '';
                    
                    for (let i = 1; i <= 40; i++) {
                        const status = data[i] || 'available';
                        grid.innerHTML += `
                            <div class="computer-unit ${status}" data-pc="${i}">
                                <div class="computer-icon">
                                    <i class="ri-computer-line"></i>
                                </div>
                                <div class="computer-info">
                                    <span class="pc-number">PC ${i}</span>
                                    <span class="status ${status}">
                                        ${status === 'available' ? 'Available' : 'In Use'}
                                    </span>
                                </div>
                            </div>
                        `;
                    }
                    
                    // Add click handlers for selecting a PC
                    document.querySelectorAll('.computer-unit.available').forEach(unit => {
                        unit.addEventListener('click', function() {
                            // Remove selection from other PCs
                            document.querySelectorAll('.computer-unit').forEach(pc => {
                                pc.classList.remove('selected');
                            });
                            
                            // Select this PC
                            this.classList.add('selected');
                            
                            // Update hidden input for PC number
                            let pcInput = document.querySelector('input[name="pc_number"]');
                            if (!pcInput) {
                                pcInput = document.createElement('input');
                                pcInput.type = 'hidden';
                                pcInput.name = 'pc_number';
                                document.querySelector('.reservation-form').appendChild(pcInput);
                            }
                            pcInput.value = this.dataset.pc;
                            
                            // Enable submit button if everything is selected
                            validateForm();
                        });
                    });
                });
        }

        // Add validation function   
        function validateForm() {
            const requiredFields = [
                'purpose',
                'laboratory',
                'date',
                'time_in',
                'pc_number'
            ];
            
            const submitButton = document.querySelector('.edit-btn');
            const allFieldsFilled = requiredFields.every(field => {
                const element = document.querySelector(`[name="${field}"]`);
                return element && element.value;
            });
            
            submitButton.disabled = !allFieldsFilled;
            submitButton.style.opacity = allFieldsFilled ? '1' : '0.5';
        }

        // Add validation listeners to all form inputs
        document.querySelectorAll('.reservation-form select, .reservation-form input').forEach(input => {
            input.addEventListener('change', validateForm);
        });

        // Initial validation
        validateForm();
        
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

        // Add schedule functionality
        document.querySelector('select[name="laboratory"]').addEventListener('change', loadSchedule);
        document.querySelector('input[name="date"]').addEventListener('change', loadSchedule);

        function loadSchedule() {
            const lab = document.querySelector('select[name="laboratory"]').value;
            const date = document.querySelector('input[name="date"]').value;
            
            if (lab && date) {
                // Add AJAX call to fetch schedule
                fetch(`../controllers/get_schedule.php?lab=${lab}&date=${date}`)
                    .then(response => response.json())
                    .then(data => {
                        updateScheduleDisplay(data);
                    });
            }
        }

        function updateScheduleDisplay(scheduleData) {
            // Update display logic here
        }
    </script>
</body>
</html>