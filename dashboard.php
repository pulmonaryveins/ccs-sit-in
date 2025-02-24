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
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="reservation.php" class="nav-link">
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
                        <div class="info-icon"><i class="ri-time-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Session</div>
                            <div class="detail-value">30</div>
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

    <!-- Dashboard Grid Container -->
    <div class="dashboard-grid" style="margin-top: 80px;">
        <!-- Left Column - Announcements -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header centered-header">
                    <h2>CCS Announcements</h2>
                </div>
                <div class="profile-content">
                    <div class="announcement-list">
                        <div class="announcement-item">
                            <div class="announcement-title">
                            <i class="ri-notification-3-fill"></i>
                                <h3>CCS Admin</h3>
                            </div>
                            <div class="announcement-details">
                                <p>Extended laboratory hours will be available during Midterm examination week. The labs will be open until 8:00 PM.</p>
                                <span class="timestamp">Posted: February 20, 2025</span>
                            </div>
                        </div>

                        <div class="announcement-item">
                            <div class="announcement-title">
                                <i class="ri-notification-3-fill"></i>
                                <h3>CCS Admin</h3>
                            </div>
                            <div class="announcement-details">
                                <p>Extended laboratory hours will be available during Prelim examination week. The labs will be open until 8:00 PM.</p>
                                <span class="timestamp">Posted: February 19, 2025</span>
                            </div>
                        </div>
                        <div class="announcement-item">
                            <div class="announcement-title">
                                <i class="ri-notification-3-fill"></i>
                                <h3>CCS Admin</h3>
                            </div>
                            <div class="announcement-details">
                                <p>The Computer Laboratory will be closed for maintenance from December 20-22, 2023. All sit-in sessions during these dates will be rescheduled.</p>
                                <span class="timestamp">Posted: February 15, 2025</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Rules -->
        <div class="dashboard-column">
            <div class="profile-card">
                <div class="profile-header centered-header">
                    <h2>Laboratory Rules and Regulations</h2>
                </div>
                <div class="profile-content">
                    <div class="rules-container">
                        <div class="rules-header">
                            <h3>UNIVERSITY OF CEBU</h3>
                            <h4>COLLEGE OF INFORMATION & COMPUTER STUDIES</h4>
                        </div>

                        <div class="rules-content">
                            <p class="rules-intro">To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                            
                            <ol class="rules-list">
                                <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.</li>
                                <li>Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.</li>
                                <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</li>
                                <li>Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</li>
                                <li>Deleting computer files and changing the set-up of the computer is a major offense.</li>
                                <li>Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".</li>
                                <li>Observe proper decorum while inside the laboratory.
                                    <ul>
                                        <li>Do not get inside the lab unless the instructor is present.</li>
                                        <li>All bags, knapsacks, and the likes must be deposited at the counter.</li>
                                        <li>Follow the seating arrangement of your instructor.</li>
                                        <li>At the end of class, all software programs must be closed.</li>
                                        <li>Return all chairs to their proper places after using.</li>
                                    </ul>
                                </li>
                                <li>Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.</li>
                                <li>Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the members of the community, including public display of physical intimacy, are not tolerated.</li>
                                <li>Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.</li>
                                <li>For serious offense, the lab personnel may call the Civil Security Office (CSU) for assistance.</li>
                                <li>Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant or instructor immediately.</li>
                            </ol>

                            <div class="disciplinary-section">
                                <h4>DISCIPLINARY ACTION</h4>
                                <ul>
                                    <li><strong>First Offense</strong> - The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.</li>
                                    <li><strong>Second and Subsequent Offenses</strong> - A recommendation for a heavier sanction will be endorsed to the Guidance Center.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        /* Update card height */
        .dashboard-column .profile-card {
            height: 750px; /* Increased from 700px */
        }

        .announcement-container {
            height: 750px; /* Match container height */
            overflow-y: auto;
            padding-right: 10px;
        }

        /* Ensure consistent scrolling behavior */
        .rules-container {
            height: 700px;
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
                const response = await fetch('get_profile_data.php');
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
    </script>
</body>
</html>