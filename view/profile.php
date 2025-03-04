<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db_connect.php';

// Fetch user details
$username = $_SESSION['username'];
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Student Information</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <a href="history.php" class="nav-link">
                    <i class="ri-history-line"></i>
                    <span>History</span>
                </a>
                <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === '../profile/profile.php' ? 'active' : ''; ?>">
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

    <!-- Add these elements -->
    <div class="backdrop" id="backdrop"></div>

    <div class="profile-panel" id="profile-panel">
        <div class="profile-content">
            <div class="profile-header">
                <h3>STUDENT INFORMATION</h3>
            </div>
            <div class="profile-body">
                <div class="profile-image-container">
                    <div class="profile-image">
                        <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : '../assets/images/logo/AVATAR.png'; ?>" 
                             alt="Profile Picture">
                    </div>
                    <div class="profile-name">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($user['lastname'] . ', ' . $user['firstname']); ?></h3>
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
                        <div class="info-icon"><i class="ri-time-fill"></i></div>
                        <div class="info-content">
                            <div class="detail-label">Session</div>
                            <div class="detail-value">30</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-page-container">
        <div class="profile-card">
            <div class="profile-header">
                <h2>Edit Profile</h2>
                <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <div class="profile-content">
                <div class="profile-image-section">
                    <div class="profile-image">
                        <img src="<?php echo htmlspecialchars($user['profile_image'] ?? '../assets/images/logo/AVATAR.png'); ?>" 
                             alt="Profile Picture" 
                             id="profile-preview">
                        <form id="image-upload-form" class="upload-form">
                            <input type="file" id="profile-upload" name="profile_image" accept="image/*" class="hidden">
                            <button type="button" class="change-photo-btn" onclick="document.getElementById('profile-upload').click()">
                                <i class="fas fa-camera"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <form id="profile-form" class="profile-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" name="idno" value="<?php echo htmlspecialchars($user['idno']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['lastname'] . ', ' . $user['firstname']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter your email">
                        </div>

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Course/Department</label>
                            <select name="course">
                                <?php
                                $courses = [
                                    "BS-Information Technology",
                                    "BS-Computer Science",
                                    "COE",
                                    "CAS",
                                    "SJH",
                                    "CTE",
                                    "CCA",
                                    "CBA",
                                    "CCJ",
                                    "CON"
                                ];
                                
                                foreach ($courses as $courseOption) {
                                    $selected = ($user['course'] === $courseOption) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($courseOption) . "\" $selected>" . 
                                         htmlspecialchars($courseOption) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Year Level</label>
                            <select name="year">
                                <?php
                                for ($i = 1; $i <= 4; $i++) {
                                    $selected = ($user['year'] == $i) ? 'selected' : '';
                                    echo "<option value=\"$i\" $selected>{$i}" . 
                                        ($i == 1 ? 'st' : ($i == 2 ? 'nd' : ($i == 3 ? 'rd' : 'th'))) . 
                                        " Year</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label>Home Address</label>
                            <textarea name="address" rows="3" placeholder="Enter your complete address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Replace the existing DOMContentLoaded event handler with this:
        document.addEventListener('DOMContentLoaded', function() {
            const profilePanel = document.getElementById('profile-panel');
            const backdrop = document.getElementById('backdrop');
            const profileTrigger = document.getElementById('profile-trigger');

            // Function to toggle profile panel
            function toggleProfile(show) {
                profilePanel.classList.toggle('active', show);
                backdrop.classList.toggle('active', show);
                document.body.style.overflow = show ? 'hidden' : '';
            }

            // Auto open the panel
            setTimeout(() => toggleProfile(true), 100);

            // Close panel when clicking backdrop
            backdrop.addEventListener('click', () => toggleProfile(false));

            // Toggle panel when clicking profile trigger
            profileTrigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                toggleProfile(!profilePanel.classList.contains('active'));
            });

            // Close panel when clicking outside
            document.addEventListener('click', (e) => {
                if (profilePanel.classList.contains('active') && 
                    !profilePanel.contains(e.target) && 
                    !profileTrigger.contains(e.target)) {
                    toggleProfile(false);
                }
            });

            // Prevent panel close when clicking inside
            profilePanel.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });

        // Profile image upload handling
        const profileUpload = document.getElementById('profile-upload');
        const profilePreview = document.getElementById('profile-preview');

        profileUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('profile_image', file);

                profilePreview.style.opacity = '0.5';
                
                fetch('../profile/upload_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        profilePreview.src = data.image_path + '?t=' + new Date().getTime();
                    } else {
                        alert(data.message || 'Error uploading image');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error uploading image');
                })
                .finally(() => {
                    profilePreview.style.opacity = '1';
                });
            }
        });

        // Update form submission handling with SweetAlert2
        document.getElementById('profile-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../profile/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success message with SweetAlert2
                    await Swal.fire({
                        title: 'Success!',
                        text: 'Profile successfully updated',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#7556cc',
                        position: 'top-end', // Position in top-right corner
                        width: '300px', // Smaller width
                        showConfirmButton: false, // Remove confirm button
                        timer: 1000, // Auto close after 2 seconds
                        toast: true, // Enable toast mode
                        customClass: {
                            popup: 'small-toast' // Custom class for additional styling
                        }
                    });
                    // Refresh the page to show updated data
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    // Show error message with SweetAlert2
                    await Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Error updating profile',
                        icon: 'error',
                        position: 'top-end',
                        width: '300px',
                        showConfirmButton: false,
                        timer: 3000,
                        toast: true,
                        customClass: {
                            popup: 'small-toast'
                        }
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                // Show error message with SweetAlert2
                await Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred',
                    icon: 'error',
                    position: 'top-end',
                    width: '300px',
                    showConfirmButton: false,
                    timer: 3000,
                    toast: true,
                    customClass: {
                        popup: 'small-toast'
                    }
                });
            }
        });
    </script>
</body>
</html>
