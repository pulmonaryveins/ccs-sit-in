<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Include the ensure_tables script to make sure sit_ins table exists
require_once '../config/ensure_tables.php';
require_once '../config/db_connect.php';

// Fetch current sit-in students from the sit_ins table
$current_students = [];
$query = "SELECT s.*, u.firstname, u.lastname 
          FROM sit_ins s
          LEFT JOIN users u ON s.idno = u.idno
          WHERE s.time_out IS NULL
          AND s.status = 'active'
          ORDER BY s.time_in DESC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $current_students[] = $row;
    }
}

// For debugging
echo "<!-- Found " . count($current_students) . " current students -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Add base styles first -->
    <style>
        :root {
            --nav-height: 60px;
            --primary-color: #7556cc;
            --secondary-color: #d569a7;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/sit-in.css">
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

    <div class="content-wrapper">
        <div class="table-wrapper">
            <div class="table-header">
                <h2>Laboratory Management</h2>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="ri-search-line"></i>
                        <input type="text" id="searchInput" placeholder="Search...">
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab active" data-target="current-students">Current Students in Laboratory</div>
                <div class="filter-tab" data-target="add-sitin">Add Sit-in</div>
            </div>
            
            <!-- Current Students Container -->
            <div id="current-students" class="view-container active">
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Full Name</th>
                                <th>Purpose</th>
                                <th>Laboratory</th>
                                <!-- Removed PC Number column -->
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($current_students)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state"> <!-- Adjusted colspan from 8 to 7 -->
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
                                        <td>
                                            <?php 
                                            if (!empty($student['firstname']) && !empty($student['lastname'])) {
                                                echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']);
                                            } else {
                                                echo htmlspecialchars($student['fullname']);
                                            } 
                                            ?>
                                        </td>
                                        <td><span class="purpose-badge"><?php echo htmlspecialchars($student['purpose']); ?></span></td>
                                        <td>Laboratory <?php echo htmlspecialchars($student['laboratory']); ?></td>
                                        <!-- Removed PC Number column -->
                                        <td><?php echo date('h:i A', strtotime($student['time_in'])); ?></td>
                                        <td><span class="status-badge active">Active</span></td>
                                        <td>
                                            <button class="action-button danger" onclick="markTimeOut('<?php echo $student['id']; ?>')">
                                                <i class="ri-logout-box-line"></i> Time Out
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Add Sit-in Container -->
            <div id="add-sitin" class="view-container">
                <div class="dashboard-column" style="max-width: 1000px; margin: 0 auto;">
                    <div class="profile-card">
                        <div class="profile-header">
                            <h3>Add Student Sit-in</h3>
                        </div>
                        <form id="addSitInForm" class="reservation-form">
                            <!-- Student ID Search Field - Enhanced UI -->
                            <div class="search-container">
                                <div class="search-field">
                                    <input type="text" id="student_idno" name="idno" placeholder="Enter student ID number..." autocomplete="off">
                                    <button type="button" id="searchStudentBtn">
                                        <i class="ri-search-line"></i> Search
                                    </button>
                                </div>
                            
                                <!-- Student info with redesigned layout -->
                                <div id="studentInfo" class="student-info-grid" style="display: none;">
                                    <!-- Left Column - Profile and Sessions -->
                                    <div class="student-info-left">
                                        <!-- Student Profile -->
                                        <div class="student-profile-container">
                                            <div class="student-profile-image">
                                                <img src="../assets/images/logo/AVATAR.png" 
                                                     alt="Student Profile" 
                                                     id="display_profile_image"
                                                     onerror="this.src='../assets/images/logo/AVATAR.png'">
                                            </div>
                                            <div class="student-profile-name" id="display_student_name">Student Name</div>
                                            <div class="course-badge" id="display_course"></div>
                                        </div>
                                        
                                        <!-- Sessions Display -->
                                        <div class="sessions-container">
                                            <div class="sessions-number" id="remainingSessions">30</div>
                                            <div class="sessions-label">Remaining Sessions</div>
                                            <div class="sessions-progress">
                                                <div class="bg-gradient-to-r from-violet-500 to-fuchsia-500 h-full rounded-full transition-all duration-500" 
                                                     id="sessionsProgress" 
                                                     style="width: 100%">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Right Column - Student Info Cards -->
                                    <div class="student-info-right">
                                        <!-- ID Number -->
                                        <div class="info-card">
                                            <div class="info-icon"><i class="ri-profile-fill"></i></div>
                                            <div class="info-content">
                                                <div class="detail-label">Student ID</div>
                                                <div class="detail-value">
                                                    <input type="text" id="display_idno" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Year Level -->
                                        <div class="info-card">
                                            <div class="info-icon"><i class="ri-expand-up-down-fill"></i></div>
                                            <div class="info-content">
                                                <div class="detail-label">Year Level</div>
                                                <div class="detail-value">
                                                    <input type="text" id="display_year" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Laboratory -->
                                        <div class="info-card">
                                            <div class="info-icon"><i class="ri-computer-fill"></i></div>
                                            <div class="info-content">
                                                <div class="detail-label">Laboratory</div>
                                                <div class="detail-value">
                                                    <select id="laboratory" name="laboratory" required>
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

                                        <!-- Purpose -->
                                        <div class="info-card">
                                            <div class="info-icon"><i class="ri-code-box-fill"></i></div>
                                            <div class="info-content">
                                                <div class="detail-label">Purpose</div>
                                                <div class="detail-value">
                                                    <select id="purpose" name="purpose" required>
                                                        <option value="">Select Purpose</option>
                                                        <option value="C Programming">C Programming</option>
                                                        <option value="Java Programming">Java Programming</option>
                                                        <option value="C#">C#</option>
                                                        <option value="PHP">PHP</option>
                                                        <option value="ASP.Net">ASP.Net</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Form Submit Button -->
                                        <div class="form-controls">
                                            <button type="button" class="edit-btn" id="submitSitinBtn" onclick="submitAddSitIn()">
                                                <i class="ri-check-line"></i>
                                                <span>Add Student Sit-in</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden fields for current date and time will be set via JavaScript -->
                            <input type="hidden" id="sit_in_date" name="date">
                            <input type="hidden" id="sit_in_time" name="time">
                            <!-- Add default PC number since we removed the selection -->
                            <input type="hidden" id="selected_pc" name="pc_number" value="1">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification System -->
    <div id="notification-container"></div>
    
    <!-- Confirmation Modal -->
    <div class="confirm-modal-backdrop" id="confirmModal">
        <div class="confirm-modal">
            <div class="confirm-modal-header">
                <h3 class="confirm-modal-title" id="confirm-title">Confirm Action</h3>
                <button class="notification-close" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div class="confirm-modal-body">
                <p id="confirm-message">Are you sure you want to proceed with this action?</p>
            </div>
            <div class="confirm-modal-footer">
                <button class="confirm-btn confirm-btn-cancel" onclick="closeConfirmModal()">Cancel</button>
                <button class="confirm-btn confirm-btn-confirm" id="confirm-yes">Yes, Continue</button>
            </div>
        </div>
    </div>

    <script>
    // Tab switching functionality
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all view containers
            document.querySelectorAll('.view-container').forEach(container => {
                container.classList.remove('active');
            });

            // Show the target container
            const targetId = this.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
            
            // Clear search input when switching tabs
            document.getElementById('searchInput').value = '';
        });
    });
    
    // Search functionality
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        let searchText = this.value.toLowerCase();
        let activeView = document.querySelector('.view-container.active');
        
        if (activeView.id === 'current-students') {
            let tableRows = activeView.querySelectorAll('.modern-table tbody tr');
            tableRows.forEach(row => {
                if (!row.querySelector('.empty-state')) {
                    let text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchText) ? '' : 'none';
                }
            });
        }
    });
    
    // Notification System Functions
    function showNotification(title, message, type = 'info', duration = 5000) {
        const notificationContainer = document.getElementById('notification-container');
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        // Set icon based on type
        let icon = 'information-line';
        if (type === 'success') icon = 'check-line';
        if (type === 'error') icon = 'error-warning-line';
        if (type === 'warning') icon = 'alert-line';
        
        // Create notification content
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="ri-${icon}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">${title}</div>
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close" onclick="closeNotification(this)">&times;</button>
        `;
        
        notificationContainer.appendChild(notification);
        
        // Force reflow before adding the 'show' class for proper animation
        notification.getBoundingClientRect();
        
        // Show notification with animation
        notification.classList.add('show');
        
        // Auto dismiss after duration (if specified)
        if (duration > 0) {
            setTimeout(() => closeNotification(notification), duration);
        }
        
        return notification;
    }
    
    function closeNotification(notification) {
        if (!notification) return;
        
        // Trigger hide animation
        notification.classList.remove('show');
        
        // Remove from DOM after animation
        setTimeout(() => {
            if (notification.parentElement) {
                notification.parentElement.removeChild(notification);
            }
        }, 300);
    }
    
    // Confirmation Modal Functions
    function showConfirmModal(message, title = 'Confirm Action', callback) {
        document.getElementById('confirm-title').textContent = title;
        document.getElementById('confirm-message').textContent = message;
        
        const confirmBtn = document.getElementById('confirm-yes');
        
        // Remove previous event listener
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        // Add new event listener
        newConfirmBtn.addEventListener('click', () => {
            closeConfirmModal();
            callback(true);
        });
        
        document.getElementById('confirmModal').classList.add('show');
        return false; // Prevent default behavior
    }
    
    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.remove('show');
    }

    function markTimeOut(sitInId) {
        showConfirmModal("Are you sure you want to mark this student as timed out?", "Confirm Time Out", (confirmed) => {
            if (confirmed) {
                console.log("Timing out sit-in ID: " + sitInId); // Debug log
                
                // Create form data to send
                const formData = new FormData();
                formData.append('sit_in_id', sitInId);
                
                // Explicitly add the current time in Manila/GMT+8 timezone
                const now = new Date();
                const manilaTime = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                    timeZone: 'Asia/Manila'
                });
                
                // Send the Manila timezone with the request
                formData.append('time_out', manilaTime);
                formData.append('timezone', 'Asia/Manila');
                
                // Send AJAX request to process time out
                fetch('../controller/time_out.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Response data:", data);
                    if (data.success) {
                        // Show success message with remaining sessions
                        const message = `Student has been marked as timed out successfully. \nRemaining sessions: ${data.remaining_sessions}`;
                        showNotification("Success", message, 'success');
                        
                        // Reload the page to reflect changes after a short delay
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showNotification("Error", 'Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification("Error", 'An error occurred. Please try again.', 'error');
                });
            }
        });
    }

    // Student search by ID - enhanced to fetch remaining sessions
    document.getElementById('student_idno')?.addEventListener('input', function() {
        let idno = this.value.trim();
        if (idno.length >= 5) {
            // Search for student with this ID
            fetch('../controller/search_student.php?idno=' + idno)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Display student information
                        document.getElementById('display_idno').value = data.student.idno || '';
                        document.getElementById('display_course').textContent = data.student.course || 'Not specified';
                        document.getElementById('display_year').value = data.student.year_level_display || 'Not specified';
                        updateSessionsDisplay(data.student.remaining_sessions || 30);
                        
                        // Display student profile image and name
                        const profileImage = document.getElementById('display_profile_image');
                        profileImage.src = data.student.profile_image || '../assets/images/logo/AVATAR.png';
                        profileImage.onerror = function() {
                            this.src = '../assets/images/logo/AVATAR.png';
                        };
                        document.getElementById('display_student_name').textContent = 
                            data.student.firstname + ' ' + data.student.lastname;
                        
                        // Store the student ID for form submission
                        const hiddenIdField = document.createElement('input');
                        hiddenIdField.type = 'hidden';
                        hiddenIdField.name = 'student_id';
                        hiddenIdField.value = data.student.id;
                        
                        // Remove any existing hidden field before adding a new one
                        const existingField = document.querySelector('input[name="student_id"]');
                        if (existingField) existingField.remove();
                        document.getElementById('addSitInForm').appendChild(hiddenIdField);
                        
                        // Show the student info section
                        document.getElementById('studentInfo').style.display = 'grid';
                        validateForm();
                    } else {
                        document.getElementById('studentInfo').style.display = 'none';
                        showNotification("Not Found", 'Student not found. Please check the ID number.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('studentInfo').style.display = 'none';
                    
                    // Provide more helpful error message
                    if (error.message && error.message.includes('Network response was not ok')) {
                        showNotification("Server Error", 'Server error: ' + error.message, 'error');
                    } else {
                        showNotification("Error", 'Error searching for student. Please try again.', 'error');
                    }
                });
        } else {
            document.getElementById('studentInfo').style.display = 'none';
        }
    });

    // Load PC availability when laboratory is selected - remove this function
    document.getElementById('laboratory')?.addEventListener('change', function() {
        validateForm(); // Only validate form, no PC loading
    });

    function validateForm() {
        const requiredFields = [
            { id: 'student_idno', check: () => document.getElementById('studentInfo').style.display === 'grid' },
            { id: 'purpose', check: () => document.getElementById('purpose').value !== '' },
            { id: 'laboratory', check: () => document.getElementById('laboratory').value !== '' },
        ];

        const isValid = requiredFields.every(field => field.check());
        const submitBtn = document.getElementById('submitSitinBtn');
        if (isValid) {
            submitBtn.classList.add('active');
            submitBtn.disabled = false;
        } else {
            submitBtn.classList.remove('active');
            submitBtn.disabled = true;
        }
        return isValid;
    }

    // Add event listeners for form fields
    document.getElementById('purpose')?.addEventListener('change', validateForm);

    function submitAddSitIn() {
        if (!validateForm()) {
            showNotification("Form Incomplete", 'Please fill out all required fields.', 'warning');
            return;
        }
        
        // Set current date and time
        const now = new Date();
        
        // Format date as YYYY-MM-DD
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const dateString = `${year}-${month}-${day}`;
        
        // Format time as HH:MM:SS
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const timeString = `${hours}:${minutes}:${seconds}`;
        
        // Set hidden form fields
        document.getElementById('sit_in_date').value = dateString;
        document.getElementById('sit_in_time').value = timeString;
        
        // Ensure the pc_number field has a default value
        if (!document.getElementById('selected_pc').value) {
            document.getElementById('selected_pc').value = '1';
        }
        
        const formData = new FormData(document.getElementById('addSitInForm'));
        
        // For debugging - log form data
        console.log("Submitting form with data:");
        for (const [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        fetch('../controller/add_sitin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification("Success", 'Student added to sit-in successfully', 'success');
                
                // Reset the form
                document.getElementById('addSitInForm').reset();
                document.getElementById('studentInfo').style.display = 'none';
                
                // Switch back to the current students tab and reload after a short delay
                setTimeout(() => {
                    document.querySelector('.filter-tab[data-target="current-students"]').click();
                    location.reload();
                }, 1500);
            } else {
                showNotification("Error", 'Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification("Error", 'An error occurred while adding the student: ' + error.message, 'error');
        });
    }

    // Add search button click handler
    document.getElementById('searchStudentBtn')?.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent form submission
        searchStudent();
    });

    // Add enter key support for search
    document.getElementById('student_idno')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent form submission
            searchStudent();
        }
    });

    // Separate search functionality into its own function
    function searchStudent() {
        let idno = document.getElementById('student_idno').value.trim();
        const studentInfo = document.getElementById('studentInfo');
        
        if (idno.length < 5) {
            showNotification("Warning", 'Please enter at least 5 characters of the student ID', 'warning');
            return;
        }
        
        // Show loading indicator
        document.getElementById('searchStudentBtn').innerHTML = '<i class="ri-loader-4-line"></i> Searching...';
        document.getElementById('searchStudentBtn').disabled = true;
        
        // Clear the previous search result
        studentInfo.style.display = 'none';
        
        // Search for student with this ID
        fetch('../controller/search_student.php?idno=' + idno)
            .then(response => {
                // Check if the response is ok (status in the range 200-299)
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Search response:', data); // Debug log
                if (data.success) {
                    // Display complete student information
                    const student = data.student;
                    document.getElementById('display_idno').value = student.idno || '';
                    document.getElementById('display_course').textContent = student.course || 'Not specified';
                    document.getElementById('display_year').value = student.year_level_display || 'Not specified';
                    updateSessionsDisplay(student.remaining_sessions || 30);
                    
                    // Display student profile image and name
                    const profileImage = document.getElementById('display_profile_image');
                    profileImage.src = student.profile_image || '../assets/images/logo/AVATAR.png';
                    profileImage.onerror = function() {
                        this.src = '../assets/images/logo/AVATAR.png';
                    };
                    document.getElementById('display_student_name').textContent = 
                        student.firstname + ' ' + student.lastname;
                    
                    // Store the student ID for form submission
                    const hiddenIdField = document.createElement('input');
                    hiddenIdField.type = 'hidden';
                    hiddenIdField.name = 'student_id';
                    hiddenIdField.value = student.id;
                    
                    // Remove any existing hidden field before adding a new one
                    const existingField = document.querySelector('input[name="student_id"]');
                    if (existingField) existingField.remove();
                    document.getElementById('addSitInForm').appendChild(hiddenIdField);
                    
                    // Show the student info section
                    studentInfo.style.display = 'grid';
                    validateForm();
                } else {
                    studentInfo.style.display = 'none';
                    showNotification("Not Found", 'Student not found. Please check the ID number.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                studentInfo.style.display = 'none';
                
                // Provide more helpful error message
                if (error.message && error.message.includes('Network response was not ok')) {
                    showNotification("Server Error", 'Server error: ' + error.message, 'error');
                } else {
                    showNotification("Error", 'Error searching for student. Please try again.', 'error');
                }
            })
            .finally(() => {
                // Reset button state
                document.getElementById('searchStudentBtn').innerHTML = '<i class="ri-search-line"></i> Search';
                document.getElementById('searchStudentBtn').disabled = false;
            });
    }

    // Update the JavaScript section that handles updating sessions
    function updateSessionsDisplay(sessions) {
        const maxSessions = 30; // Maximum number of sessions
        const remainingElement = document.getElementById('remainingSessions');
        const progressBar = document.getElementById('sessionsProgress');
        
        // Update the number
        remainingElement.textContent = sessions;
        
        // Calculate percentage
        const percentage = (sessions / maxSessions) * 100;
        
        // Update progress bar width
        progressBar.style.width = `${percentage}%`;
        
        // Update colors based on remaining sessions
        if (sessions <= 5) {
            progressBar.className = 'bg-gradient-to-r from-red-500 to-red-400 h-full rounded-full transition-all duration-500';
            remainingElement.style.color = '#ef4444';
            remainingElement.style.background = 'linear-gradient(135deg, rgba(239,68,68,0.08), rgba(239,68,68,0.08))';
            remainingElement.style.borderColor = 'rgba(239,68,68,0.15)';
        } else if (sessions <= 10) {
            progressBar.className = 'bg-gradient-to-r from-yellow-500 to-yellow-400 h-full rounded-full transition-all duration-500';
            remainingElement.style.color = '#d97706';
            remainingElement.style.background = 'linear-gradient(135deg, rgba(217,119,6,0.08), rgba(217,119,6,0.08))';
            remainingElement.style.borderColor = 'rgba(217,119,6,0.15)';
        } else {
            progressBar.className = 'bg-gradient-to-r from-violet-500 to-fuchsia-500 h-full rounded-full transition-all duration-500';
            remainingElement.style.color = '#7556cc';
            remainingElement.style.background = 'linear-gradient(135deg, rgba(117,86,204,0.08), rgba(213,105,167,0.08))';
            remainingElement.style.borderColor = 'rgba(117,86,204,0.15)';
        }
    }

    // Update the student search function to use the new sessions display
    document.getElementById('student_idno')?.addEventListener('input', function() {
        let idno = this.value.trim();
        if (idno.length >= 5) {
            // Search for student with this ID
            fetch('../controller/search_student.php?idno=' + idno)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Display student information
                        document.getElementById('display_idno').value = data.student.idno || '';
                        document.getElementById('display_course').textContent = data.student.course || 'Not specified';
                        document.getElementById('display_year').value = data.student.year_level_display || 'Not specified';
                        updateSessionsDisplay(data.student.remaining_sessions || 30);
                        
                        // Display student profile image and name
                        const profileImage = document.getElementById('display_profile_image');
                        profileImage.src = data.student.profile_image || '../assets/images/logo/AVATAR.png';
                        profileImage.onerror = function() {
                            this.src = '../assets/images/logo/AVATAR.png';
                        };
                        document.getElementById('display_student_name').textContent = 
                            data.student.firstname + ' ' + data.student.lastname;
                        
                        // Store the student ID for form submission
                        const hiddenIdField = document.createElement('input');
                        hiddenIdField.type = 'hidden';
                        hiddenIdField.name = 'student_id';
                        hiddenIdField.value = data.student.id;
                        
                        // Remove any existing hidden field before adding a new one
                        const existingField = document.querySelector('input[name="student_id"]');
                        if (existingField) existingField.remove();
                        document.getElementById('addSitInForm').appendChild(hiddenIdField);
                        
                        // Show the student info section
                        document.getElementById('studentInfo').style.display = 'grid';
                        validateForm();
                    } else {
                        document.getElementById('studentInfo').style.display = 'none';
                        showNotification("Not Found", 'Student not found. Please check the ID number.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('studentInfo').style.display = 'none';
                    
                    // Provide more helpful error message
                    if (error.message && error.message.includes('Network response was not ok')) {
                        showNotification("Server Error", 'Server error: ' + error.message, 'error');
                    } else {
                        showNotification("Error", 'Error searching for student. Please try again.', 'error');
                    }
                });
        } else {
            document.getElementById('studentInfo').style.display = 'none';
        }
    });

    // Also update the searchStudent function
    function searchStudent() {
        let idno = document.getElementById('student_idno').value.trim();
        const studentInfo = document.getElementById('studentInfo');
        
        if (idno.length < 5) {
            showNotification("Warning", 'Please enter at least 5 characters of the student ID', 'warning');
            return;
        }
        
        // Show loading indicator
        document.getElementById('searchStudentBtn').innerHTML = '<i class="ri-loader-4-line"></i> Searching...';
        document.getElementById('searchStudentBtn').disabled = true;
        
        // Clear the previous search result
        studentInfo.style.display = 'none';
        
        // Search for student with this ID
        fetch('../controller/search_student.php?idno=' + idno)
            .then(response => {
                // Check if the response is ok (status in the range 200-299)
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Search response:', data); // Debug log
                if (data.success) {
                    // Display complete student information
                    const student = data.student;
                    document.getElementById('display_idno').value = student.idno || '';
                    document.getElementById('display_course').textContent = student.course || 'Not specified';
                    document.getElementById('display_year').value = student.year_level_display || 'Not specified';
                    updateSessionsDisplay(student.remaining_sessions || 30);
                    
                    // Display student profile image and name
                    const profileImage = document.getElementById('display_profile_image');
                    profileImage.src = student.profile_image || '../assets/images/logo/AVATAR.png';
                    profileImage.onerror = function() {
                        this.src = '../assets/images/logo/AVATAR.png';
                    };
                    document.getElementById('display_student_name').textContent = 
                        student.firstname + ' ' + student.lastname;
                    
                    // Store the student ID for form submission
                    const hiddenIdField = document.createElement('input');
                    hiddenIdField.type = 'hidden';
                    hiddenIdField.name = 'student_id';
                    hiddenIdField.value = student.id;
                    
                    // Remove any existing hidden field before adding a new one
                    const existingField = document.querySelector('input[name="student_id"]');
                    if (existingField) existingField.remove();
                    document.getElementById('addSitInForm').appendChild(hiddenIdField);
                    
                    // Show the student info section
                    studentInfo.style.display = 'grid';
                    validateForm();
                } else {
                    studentInfo.style.display = 'none';
                    showNotification("Not Found", 'Student not found. Please check the ID number.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                studentInfo.style.display = 'none';
                
                // Provide more helpful error message
                if (error.message && error.message.includes('Network response was not ok')) {
                    showNotification("Server Error", 'Server error: ' + error.message, 'error');
                } else {
                    showNotification("Error", 'Error searching for student. Please try again.', 'error');
                }
            })
            .finally(() => {
                // Reset button state
                document.getElementById('searchStudentBtn').innerHTML = '<i class="ri-search-line"></i> Search';
                document.getElementById('searchStudentBtn').disabled = false;
            });
    }

    // Fix for toggling display of student info - simplified & consistent
    function showStudentInfo(show) {
        const studentInfo = document.getElementById('studentInfo');
        studentInfo.style.display = show ? 'flex' : 'none';
    }
    
    // Update existing functions that toggle studentInfo visibility
    document.getElementById('searchStudentBtn')?.addEventListener('click', function(e) {
        e.preventDefault();
        searchStudent();
    });
    
    // Modify search functions to use the new display toggle
    function searchStudent() {
        let idno = document.getElementById('student_idno').value.trim();
        
        if (idno.length < 5) {
            showNotification("Warning", 'Please enter at least 5 characters of the student ID', 'warning');
            return;
        }
        
        // Show loading indicator
        document.getElementById('searchStudentBtn').innerHTML = '<i class="ri-loader-4-line"></i> Searching...';
        document.getElementById('searchStudentBtn').disabled = true;
        
        // Clear the previous search result
        showStudentInfo(false);
        
        // Search for student with this ID
        fetch('../controller/search_student.php?idno=' + idno)
            .then(response => {
                // Check if the response is ok (status in the range 200-299)
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Search response:', data); // Debug log
                if (data.success) {
                    // Display complete student information
                    const student = data.student;
                    document.getElementById('display_idno').value = student.idno || '';
                    document.getElementById('display_course').textContent = student.course || 'Not specified';
                    document.getElementById('display_year').value = student.year_level_display || 'Not specified';
                    updateSessionsDisplay(student.remaining_sessions || 30);
                    
                    // Display student profile image and name
                    const profileImage = document.getElementById('display_profile_image');
                    profileImage.src = student.profile_image || '../assets/images/logo/AVATAR.png';
                    profileImage.onerror = function() {
                        this.src = '../assets/images/logo/AVATAR.png';
                    };
                    document.getElementById('display_student_name').textContent = 
                        student.firstname + ' ' + student.lastname;
                    
                    // Store the student ID for form submission
                    const hiddenIdField = document.createElement('input');
                    hiddenIdField.type = 'hidden';
                    hiddenIdField.name = 'student_id';
                    hiddenIdField.value = student.id;
                    
                    // Remove any existing hidden field before adding a new one
                    const existingField = document.querySelector('input[name="student_id"]');
                    if (existingField) existingField.remove();
                    document.getElementById('addSitInForm').appendChild(hiddenIdField);
                    
                    // Show the student info section with proper display
                    showStudentInfo(true);
                    validateForm();
                } else {
                    showStudentInfo(false);
                    showNotification("Not Found", 'Student not found. Please check the ID number.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showStudentInfo(false);
                
                // Provide more helpful error message
                if (error.message && error.message.includes('Network response was not ok')) {
                    showNotification("Server Error", 'Server error: ' + error.message, 'error');
                } else {
                    showNotification("Error", 'Error searching for student. Please try again.', 'error');
                }
            })
            .finally(() => {
                // Reset button state
                document.getElementById('searchStudentBtn').innerHTML = '<i class="ri-search-line"></i> Search';
                document.getElementById('searchStudentBtn').disabled = false;
            });
    }
    </script>
</body>
</html>