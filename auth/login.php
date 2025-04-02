<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if it's admin login
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = 'admin';
        header('Location: ../view/admin_dashboard.php');
        exit();
    } 

    // If not admin, check student credentials
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $row['id'];
            header("Location: ../view/dashboard.php");
            exit();
        }
    }
    
    $error = "Invalid username or password";
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CCS Sit-In System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <style>
        :root {
            --primary-color: #7556cc;
            --secondary-color: #9556cc;
            --accent-color: #6200ea;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --text-color: #334155;
            --light-text: #64748b;
            --bg-color: #f1f5f9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            padding: 40px 0;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: -1;
            opacity: 0; /* Slightly adjust opacity to make the blur more visible */
        }

        body::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: -1;
            opacity: 0.5; /* Slightly adjust opacity to make the blur more visible */
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            z-index: 1;
            animation: cardAppear 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes cardAppear {
            0% {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
        }

        .home-link {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
            font-weight: 600;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 30px;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            z-index: 10;
            animation: fadeIn 0.5s ease 0.3s forwards;
            opacity: 0;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        .home-link i {
            margin-right: 6px;
        }

        .home-link:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .logos-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            margin-bottom: 30px;
        }

        .logos-container img {
            width: 70px;
            height: auto;
        }

        h2 {
            text-align: center;
            color: var(--dark-color);
            font-size: 1.3rem;
            margin-bottom: 30px;
            font-weight: 700;
        }

        .success-message, .error-message {
            background-color: #10b981;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-message::before {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 10px;
        }

        .error-message {
            background-color: #ef4444;
        }

        .error-message::before {
            content: '\f071';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 10px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            font-size: 1rem;
            background: #f8fafc;
            color: var(--text-color);
            transition: all 0.3s ease;
            padding-left: 50px;
        }

        .input-group input:focus {
            border-color: #7556cc;
            box-shadow: 0 0 0 3px rgba(117, 86, 204, 0.15);
            outline: none;
            background: white;
        }

        .input-group::before {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: small;
            width: 25px;  /* reduced from 40px */
            height: 25px;  /* reduced from 40px */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;  /* reduced from 10px */
            background: #e9e9ff;
        }

        .input-group:nth-child(1)::before {
            content: "\f007"; /* User icon */
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            font-size: 0.8rem;
        }

        .input-group:nth-child(2)::before {
            content: "\f023"; /* Lock icon */
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            font-size: 0.8rem;
        }

        input[type="submit"] {
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(117, 86, 204, 0.25);
        }

        input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(117, 86, 204, 0.35);
        }

        .register-text {
            text-align: center;
            margin-top: 25px;
            color: var(--light-text);
            font-size: 0.95rem;
        }

        .register-text a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        /* Modal styles for notifications */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 100;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            position: relative;
        }

        .modal-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }

        .modal-icon.success {
            color: #10b981;
        }

        .modal-icon.error {
            color: #ef4444;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        .modal-text {
            color: var(--light-text);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .modal-button {
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(117, 86, 204, 0.3);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #64748b;
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 0 15px;
            }

            .logos-container img {
                width: 60px;
            }

            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <a href="../index.php" class="home-link">
        <i class="ri-home-4-line"></i> Home
    </a>

    <div class="login-container">
        <?php
        if (isset($_GET['success']) && $_GET['success'] == 'registered') {
            echo '<div class="success-message">Registration successful! Please login.</div>';
        }
        if (isset($_GET['error']) && $_GET['error'] == 'invalid') {
            echo '<div class="error-message">Invalid username or password!</div>';
        }
        if (isset($error)) {
            echo '<div class="error-message">' . $error . '</div>';
        }
        ?>
        <div class="logos-container">
            <img src="../assets/images/logo/uc.png" alt="UC Logo">
            <img src="../assets/images/logo/ccs.png" alt="CCS Logo">
        </div>
        <h2>CCS SIT-IN MONITORING SYSTEM</h2>
        
        <form method="POST" action="">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <input type="submit" value="Sign In">
        </form>
        <div class="register-text">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('successModal')">&times;</button>
            <div class="modal-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="modal-title">Success!</h3>
            <p class="modal-text" id="successText">Operation completed successfully.</p>
            <button class="modal-button" onclick="closeModal('successModal')">Continue</button>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('errorModal')">&times;</button>
            <div class="modal-icon error">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3 class="modal-title">Error</h3>
            <p class="modal-text" id="errorText">An error occurred. Please try again.</p>
            <button class="modal-button" onclick="closeModal('errorModal')">Try Again</button>
        </div>
    </div>

    <script>
        // Modal functionality
        function showModal(modalId, message) {
            const modal = document.getElementById(modalId);
            if (modalId === 'successModal') {
                document.getElementById('successText').textContent = message;
            } else if (modalId === 'errorModal') {
                document.getElementById('errorText').textContent = message;
            }
            modal.style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Check for URL parameters to show modals
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === 'registered') {
                showModal('successModal', 'Registration successful! You can now login with your credentials.');
            }
            if (urlParams.get('error') === 'invalid') {
                showModal('errorModal', 'Invalid username or password. Please try again.');
            }
        });
    </script>
</body>
</html>