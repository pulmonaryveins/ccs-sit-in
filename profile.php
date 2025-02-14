<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-calendar"></i>
                    <span>Reservation</span>
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>History</span>
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i>
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
    <div class="profile-page-container">
        <div class="profile-card">
            <div class="profile-header">
                <h2>Edit Profile</h2>
                <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <div class="profile-content">
                <div class="profile-image-section">
                    <div class="profile-image">
                        <img src="<?php echo htmlspecialchars($user['profile_image'] ?? 'default-avatar.png'); ?>" 
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
                            <label>Course</label>
                            <select name="course">
                                <?php
                                $courses = [
                                    "BS-Information Technology",
                                    "BS-Computer Science",
                                    "College of Engineering",
                                    "College of Arts and Sciences",
                                    "College of Hospitality Management",
                                    "College of Education",
                                    "College of Customes Administration",
                                    "College of Business and Accountancy",
                                    "College of Criminal Justice",
                                    "College of Nursing"
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
        // Profile image upload handling
        const profileUpload = document.getElementById('profile-upload');
        const profilePreview = document.getElementById('profile-preview');

        profileUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('profile_image', file);

                profilePreview.style.opacity = '0.5';
                
                fetch('upload_image.php', {
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

        // Update form submission handling
        document.getElementById('profile-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Profile updated successfully!');
                    // Refresh the page to show updated data
                    window.location.reload();
                } else {
                    alert(data.message || 'Error updating profile');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating profile');
            }
        });
    </script>
</body>
</html>
