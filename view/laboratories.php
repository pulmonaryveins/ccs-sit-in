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

// Fetch laboratory schedules
$selected_lab = isset($_GET['lab']) ? $_GET['lab'] : 'Laboratory 517';
$selected_day = isset($_GET['day']) ? $_GET['day'] : 'Monday';

$lab_schedules = [];
$sql = "SELECT * FROM lab_schedules WHERE laboratory = ? AND day = ? ORDER BY time_start";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $selected_lab, $selected_day);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lab_schedules[] = $row;
    }
}

// Handle schedule CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new schedule
        if ($_POST['action'] === 'add_schedule') {
            $day = $_POST['day'];
            $laboratory = $_POST['laboratory'];
            $time_start = $_POST['time_start'];
            $time_end = $_POST['time_end'];
            $subject = $_POST['subject'];
            $professor = $_POST['professor'];
            
            $sql = "INSERT INTO lab_schedules (day, laboratory, time_start, time_end, subject, professor) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $day, $laboratory, $time_start, $time_end, $subject, $professor);
            
            if ($stmt->execute()) {
                header("Location: laboratories.php?lab=$laboratory&day=$day&success=Schedule added successfully");
                exit();
            } else {
                $error_message = "Error adding schedule: " . $conn->error;
            }
        }
        
        // Update schedule
        if ($_POST['action'] === 'update_schedule') {
            $id = $_POST['schedule_id'];
            $day = $_POST['day'];
            $laboratory = $_POST['laboratory'];
            $time_start = $_POST['time_start'];
            $time_end = $_POST['time_end'];
            $subject = $_POST['subject'];
            $professor = $_POST['professor'];
            
            $sql = "UPDATE lab_schedules SET day = ?, laboratory = ?, time_start = ?, 
                    time_end = ?, subject = ?, professor = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $day, $laboratory, $time_start, $time_end, $subject, $professor, $id);
            
            if ($stmt->execute()) {
                header("Location: laboratories.php?lab=$laboratory&day=$day&success=Schedule updated successfully");
                exit();
            } else {
                $error_message = "Error updating schedule: " . $conn->error;
            }
        }
        
        // Delete schedule
        if ($_POST['action'] === 'delete_schedule') {
            $id = $_POST['schedule_id'];
            $day = $_POST['day'];
            $laboratory = $_POST['laboratory'];
            
            $sql = "DELETE FROM lab_schedules WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                header("Location: laboratories.php?lab=$laboratory&day=$day&success=Schedule deleted successfully");
                exit();
            } else {
                $error_message = "Error deleting schedule: " . $conn->error;
            }
        }
    }
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
$sql = "SELECT *, TIME_FORMAT(time_in, '%h:%i %p') as formatted_time FROM reservations WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($sql);
$pending_reservations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_reservations[] = $row;
    }
}

// Get approved reservations
$sql = "SELECT *, TIME_FORMAT(time_in, '%h:%i %p') as formatted_time FROM reservations WHERE status = 'approved' ORDER BY created_at DESC LIMIT 20";
$result = $conn->query($sql);
$approved_reservations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $approved_reservations[] = $row;
    }
}

// Get rejected reservations
$sql = "SELECT *, TIME_FORMAT(time_in, '%h:%i %p') as formatted_time FROM reservations WHERE status = 'rejected' ORDER BY created_at DESC LIMIT 20";
$result = $conn->query($sql);
$rejected_reservations = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rejected_reservations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Management</title>
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
                <a href="leaderboard.php" class="nav-link">
                    <i class="ri-trophy-line"></i>
                    <span>Leaderboard</span>
                </a>
                <a href="laboratories.php" class="nav-link active">
                    <i class="ri-computer-line active"></i>
                    <span>Laboratory</span>
                </a>
                <a href="request.php" class="nav-link">
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
    <div class="container mx-auto px-4 pt-20 pb-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Laboratory Management</h1>
            <p class="text-gray-600">Manage laboratory schedules and availability</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
            <p><?php echo htmlspecialchars($_GET['success']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
        <?php endif; ?>

        <!-- Laboratory Schedules Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Laboratory Schedules</h2>
                <button id="addScheduleBtn" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition">
                    <i class="fas fa-plus mr-2"></i> Add Schedule
                </button>
            </div>

            <!-- Laboratory and Day Selection -->
            <div class="flex flex-wrap gap-4 mb-6">
                <div class="filter-section">
                    <label for="lab-filter" class="block text-sm font-medium text-gray-700 mb-1">Laboratory:</label>
                    <select id="lab-filter" name="lab" class="lab-select bg-gray-50" onchange="this.form.submit()">
                        <option value="Laboratory 517" <?php echo $selected_lab == 'Laboratory 517' ? 'selected' : ''; ?>>Laboratory 517</option>
                        <option value="Laboratory 524" <?php echo $selected_lab == 'Laboratory 524' ? 'selected' : ''; ?>>Laboratory 524</option>
                        <option value="Laboratory 526" <?php echo $selected_lab == 'Laboratory 526' ? 'selected' : ''; ?>>Laboratory 526</option>
                        <option value="Laboratory 528" <?php echo $selected_lab == 'Laboratory 528' ? 'selected' : ''; ?>>Laboratory 528</option>
                        <option value="Laboratory 530" <?php echo $selected_lab == 'Laboratory 530' ? 'selected' : ''; ?>>Laboratory 530</option>
                        <option value="Laboratory 542" <?php echo $selected_lab == 'Laboratory 542' ? 'selected' : ''; ?>>Laboratory 542</option>
                    </select>
                </div>

                <div class="filter-section">
                    <label for="day-filter" class="block text-sm font-medium text-gray-700 mb-1">Day:</label>
                    <select id="day-filter" name="day" class="lab-select bg-gray-50" onchange="this.form.submit()">
                        <option value="Monday" <?php echo $selected_day == 'Monday' ? 'selected' : ''; ?>>Monday</option>
                        <option value="Tuesday" <?php echo $selected_day == 'Tuesday' ? 'selected' : ''; ?>>Tuesday</option>
                        <option value="Wednesday" <?php echo $selected_day == 'Wednesday' ? 'selected' : ''; ?>>Wednesday</option>
                        <option value="Thursday" <?php echo $selected_day == 'Thursday' ? 'selected' : ''; ?>>Thursday</option>
                        <option value="Friday" <?php echo $selected_day == 'Friday' ? 'selected' : ''; ?>>Friday</option>
                        <option value="Saturday" <?php echo $selected_day == 'Saturday' ? 'selected' : ''; ?>>Saturday</option>
                    </select>
                </div>
            </div>

            <!-- Schedule Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-md">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-3 px-4 text-left border-b">Time</th>
                            <th class="py-3 px-4 text-left border-b">Subject</th>
                            <th class="py-3 px-4 text-left border-b">Professor</th>
                            <th class="py-3 px-4 text-left border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lab_schedules)): ?>
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">No schedules found for <?php echo htmlspecialchars($selected_lab); ?> on <?php echo htmlspecialchars($selected_day); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($lab_schedules as $schedule): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 px-4 border-b">
                                    <?php 
                                        $time_start = new DateTime($schedule['time_start']);
                                        $time_end = new DateTime($schedule['time_end']);
                                        echo $time_start->format('g:i A') . ' - ' . $time_end->format('g:i A'); 
                                    ?>
                                </td>
                                <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($schedule['professor']); ?></td>
                                <td class="py-3 px-4 border-b">
                                    <button class="edit-btn text-blue-500 hover:text-blue-700 mr-3" 
                                            data-id="<?php echo $schedule['id']; ?>"
                                            data-day="<?php echo htmlspecialchars($schedule['day']); ?>"
                                            data-lab="<?php echo htmlspecialchars($schedule['laboratory']); ?>"
                                            data-timestart="<?php echo htmlspecialchars($schedule['time_start']); ?>"
                                            data-timeend="<?php echo htmlspecialchars($schedule['time_end']); ?>"
                                            data-subject="<?php echo htmlspecialchars($schedule['subject']); ?>"
                                            data-professor="<?php echo htmlspecialchars($schedule['professor']); ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="delete-btn text-red-500 hover:text-red-700" 
                                            data-id="<?php echo $schedule['id']; ?>"
                                            data-day="<?php echo htmlspecialchars($schedule['day']); ?>"
                                            data-lab="<?php echo htmlspecialchars($schedule['laboratory']); ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div id="addScheduleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="border-b px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Add Laboratory Schedule</h3>
                <button id="closeAddModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add_schedule">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="day" class="block text-sm font-medium text-gray-700 mb-1">Day</label>
                        <select name="day" id="day" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="laboratory" class="block text-sm font-medium text-gray-700 mb-1">Laboratory</label>
                        <select name="laboratory" id="laboratory" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                            <option value="Laboratory 517">Laboratory 517</option>
                            <option value="Laboratory 524">Laboratory 524</option>
                            <option value="Laboratory 526">Laboratory 526</option>
                            <option value="Laboratory 528">Laboratory 528</option>
                            <option value="Laboratory 530">Laboratory 530</option>
                            <option value="Laboratory 542">Laboratory 542</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="time_start" class="block text-sm font-medium text-gray-700 mb-1">Time Start</label>
                        <input type="time" name="time_start" id="time_start" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                    </div>
                    
                    <div>
                        <label for="time_end" class="block text-sm font-medium text-gray-700 mb-1">Time End</label>
                        <input type="time" name="time_end" id="time_end" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                    </div>
                </div>
                
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" name="subject" id="subject" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                </div>
                
                <div>
                    <label for="professor" class="block text-sm font-medium text-gray-700 mb-1">Professor</label>
                    <input type="text" name="professor" id="professor" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="button" id="cancelAddBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md mr-2 hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition">
                        <i class="fas fa-plus mr-2"></i> Add Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div id="editScheduleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="border-b px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Edit Laboratory Schedule</h3>
                <button id="closeEditModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="update_schedule">
                <input type="hidden" name="schedule_id" id="edit_schedule_id">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_day" class="block text-sm font-medium text-gray-700 mb-1">Day</label>
                        <select name="day" id="edit_day" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="edit_laboratory" class="block text-sm font-medium text-gray-700 mb-1">Laboratory</label>
                        <select name="laboratory" id="edit_laboratory" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                            <option value="Laboratory 517">Laboratory 517</option>
                            <option value="Laboratory 524">Laboratory 524</option>
                            <option value="Laboratory 526">Laboratory 526</option>
                            <option value="Laboratory 528">Laboratory 528</option>
                            <option value="Laboratory 530">Laboratory 530</option>
                            <option value="Laboratory 542">Laboratory 542</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_time_start" class="block text-sm font-medium text-gray-700 mb-1">Time Start</label>
                        <input type="time" name="time_start" id="edit_time_start" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                    </div>
                    
                    <div>
                        <label for="edit_time_end" class="block text-sm font-medium text-gray-700 mb-1">Time End</label>
                        <input type="time" name="time_end" id="edit_time_end" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                    </div>
                </div>
                
                <div>
                    <label for="edit_subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" name="subject" id="edit_subject" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                </div>
                
                <div>
                    <label for="edit_professor" class="block text-sm font-medium text-gray-700 mb-1">Professor</label>
                    <input type="text" name="professor" id="edit_professor" class="w-full rounded-md border-gray-300 shadow-sm py-2 px-3" required>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="button" id="cancelEditBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md mr-2 hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Confirm Deletion</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this schedule? This action cannot be undone.</p>
            
            <form action="" method="POST" class="flex justify-end">
                <input type="hidden" name="action" value="delete_schedule">
                <input type="hidden" name="schedule_id" id="delete_schedule_id">
                <input type="hidden" name="day" id="delete_day">
                <input type="hidden" name="laboratory" id="delete_laboratory">
                
                <button type="button" id="cancelDeleteBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md mr-2 hover:bg-gray-400 transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                    <i class="fas fa-trash mr-2"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <style>
        .nav-container {
            margin: 0 auto;
            width: 100%;
            position: fixed;
            top: 0;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            z-index: 1000;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1),
                        0 8px 30px -5px rgba(0, 0, 0, 0.1);
        }
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
            font-weight: 500;
            color: #2d3748;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>

    <script>
        // Modal handling for Add Schedule
        const addScheduleBtn = document.getElementById('addScheduleBtn');
        const addScheduleModal = document.getElementById('addScheduleModal');
        const closeAddModal = document.getElementById('closeAddModal');
        const cancelAddBtn = document.getElementById('cancelAddBtn');

        addScheduleBtn.addEventListener('click', () => {
            addScheduleModal.classList.remove('hidden');
            
            // Pre-populate with current filter selections
            const labFilter = document.getElementById('lab-filter');
            const dayFilter = document.getElementById('day-filter');
            
            if(labFilter && dayFilter) {
                document.getElementById('laboratory').value = labFilter.value;
                document.getElementById('day').value = dayFilter.value;
            }
        });

        closeAddModal.addEventListener('click', () => {
            addScheduleModal.classList.add('hidden');
        });

        cancelAddBtn.addEventListener('click', () => {
            addScheduleModal.classList.add('hidden');
        });

        // Modal handling for Edit Schedule
        const editScheduleModal = document.getElementById('editScheduleModal');
        const closeEditModal = document.getElementById('closeEditModal');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const day = button.getAttribute('data-day');
                const lab = button.getAttribute('data-lab');
                const timeStart = button.getAttribute('data-timestart');
                const timeEnd = button.getAttribute('data-timeend');
                const subject = button.getAttribute('data-subject');
                const professor = button.getAttribute('data-professor');
                
                document.getElementById('edit_schedule_id').value = id;
                document.getElementById('edit_day').value = day;
                document.getElementById('edit_laboratory').value = lab;
                document.getElementById('edit_time_start').value = timeStart.substr(0, 5);
                document.getElementById('edit_time_end').value = timeEnd.substr(0, 5);
                document.getElementById('edit_subject').value = subject;
                document.getElementById('edit_professor').value = professor;
                
                editScheduleModal.classList.remove('hidden');
            });
        });

        closeEditModal.addEventListener('click', () => {
            editScheduleModal.classList.add('hidden');
        });

        cancelEditBtn.addEventListener('click', () => {
            editScheduleModal.classList.add('hidden');
        });

        // Modal handling for Delete Schedule
        const deleteModal = document.getElementById('deleteModal');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const day = button.getAttribute('data-day');
                const lab = button.getAttribute('data-lab');
                
                document.getElementById('delete_schedule_id').value = id;
                document.getElementById('delete_day').value = day;
                document.getElementById('delete_laboratory').value = lab;
                
                deleteModal.classList.remove('hidden');
            });
        });

        cancelDeleteBtn.addEventListener('click', () => {
            deleteModal.classList.add('hidden');
        });

        // Handle filter changes
        document.getElementById('lab-filter').addEventListener('change', function() {
            window.location.href = 'laboratories.php?lab=' + this.value + '&day=' + document.getElementById('day-filter').value;
        });

        document.getElementById('day-filter').addEventListener('change', function() {
            window.location.href = 'laboratories.php?lab=' + document.getElementById('lab-filter').value + '&day=' + this.value;
        });
    </script>
</body>
</html>