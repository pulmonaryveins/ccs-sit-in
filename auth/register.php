<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | CCS Sit-In System</title>
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
            opacity: 0;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            opacity: 0.5;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: -1;
            opacity: 0.5;
        }
        
        .login-container, .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .register-container {
            max-width: 550px;
            animation: cardAppear 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        .register-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
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

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
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
            font-size: 1.75rem;
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

        .form-row {
            display: flex;
            grid-template-columns: repeat(1, 1fr);
            gap: 15px;
        }

        .input-group {
            position: relative;
        }

        .input-group input, .input-group select {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            font-size: 1rem;
            background: #f8fafc;
            color: var(--text-color);
            transition: all 0.3s ease;
            padding-left: 45px;
        }

        .input-group input:focus, .input-group select:focus {
            border-color: #7556cc;
            box-shadow: 0 0 0 3px rgba(117, 86, 204, 0.15);
            outline: none;
            background: white;
        }

        .input-group select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 40px;
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
        }

        .input-group select option {
            padding: 12px;
            background-color: white;
            color: var(--text-color);
        }

        .input-group::before {
            position: absolute;
            left: 15px;
            top: 50%;
            font-size: small;
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
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
            background: linear-gradient(135deg, rgba(117,86,204,0.1), rgba(213,105,167,0.1));
        }

        .input-group.id-input::before {
            content: "\f2c2";
        }

        .input-group.lastname-input::before {
            content: "\f007";
        }

        .input-group.firstname-input::before {
            content: "\f007";
        }

        .input-group.middlename-input::before {
            content: "\f007";
        }

        .input-group.course-input::before {
            content: "\f19d";
        }

        .input-group.year-input::before {
            content: "\f073";
        }

        .input-group.username-input::before {
            content: "\f2bd";
        }

        .input-group.password-input::before {
            content: "\f023";
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

        @media (max-width: 576px) {
            .login-container, .register-container {
                padding: 30px 20px;
                margin: 0 15px;
            }

            .logos-container img {
                width: 60px;
            }

            h2 {
                font-size: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

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
    </style>
    <script>
        function validateIDNo(input) {
            const pattern = /^\d{8}$/;
            if (!pattern.test(input.value)) {
                input.setCustomValidity('ID Number must be exactly 8 digits');
            } else {
                input.setCustomValidity('');
            }
        }

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
    </script>
</head>
<body>
    <a href="../index.php" class="home-link">
        <i class="ri-home-4-line"></i> Home
    </a>

    <div class="login-container register-container">
        <?php
        if (isset($_GET['error'])) {
            echo '<div class="error-message">';
            switch($_GET['error']) {
                case 'id_exists':
                    echo "ID Number is already registered!";
                    break;
                case 'username_exists':
                    echo "Username is already taken!";
                    break;
                case 'database':
                    echo "Registration failed.";
                    break;
            }
            echo '</div>';
        }
        ?>
        <div class="logos-container">
            <img src="../assets/images/logo/uc.png" alt="UC Logo">
            <img src="../assets/images/logo/ccs.png" alt="CCS Logo">
        </div>
        <h2>STUDENT REGISTRATION</h2>
        <form action="process_register.php" method="post">
            <div class="input-group id-input">
                <input type="text" 
                       name="idno" 
                       placeholder="ID Number" 
                       pattern="\d{8}"
                       oninput="validateIDNo(this)"
                       required>
            </div>
            <div class="form-row">
                <div class="input-group lastname-input">
                    <input type="text" name="lastname" placeholder="Last Name" required>
                </div>
                <div class="input-group firstname-input">
                    <input type="text" name="firstname" placeholder="First Name" required>
                </div>
            </div>
            <div class="input-group middlename-input">
                <input type="text" name="middlename" placeholder="Middle Name">
            </div>
            <div class="form-row">
                <div class="input-group course-input">
                    <select name="course" required>
                        <option value="" disabled selected>Select Course / Department</option>
                        <option value="BS-Information Technology">BS-Information Technology</option>
                        <option value="BS-Computer Science">BS-Computer Science</option>
                        <option value="COE">COE</option>
                        <option value="CAS">CAS</option>
                        <option value="CHM">CHM</option>
                        <option value="CTE">CTE</option>
                        <option value="CCA">CCA</option>
                        <option value="CBA">CBA</option>
                        <option value="CCJ">CCJ</option>
                        <option value="CON">CON</option>
                    </select>
                </div>
                <div class="input-group year-input">
                    <select name="year" required>
                        <option value="" disabled selected>Select Year Level</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
            </div>
            <div class="input-group username-input">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-group password-input">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <input type="submit" value="Register">
        </form>
        <div class="register-text">
            Already have an account? <a href="../auth/login.php">Login here</a>
        </div>
    </div>

    <div id="successModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('successModal')">&times;</button>
            <div class="modal-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="modal-title">Success!</h3>
            <p class="modal-text" id="successText">Registration successful!</p>
            <button class="modal-button" onclick="closeModal('successModal')">Continue</button>
        </div>
    </div>

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
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('error')) {
                let errorMessage = "An error occurred during registration.";
                switch(urlParams.get('error')) {
                    case 'id_exists':
                        errorMessage = "ID Number is already registered!";
                        break;
                    case 'username_exists':
                        errorMessage = "Username is already taken!";
                        break;
                    case 'database':
                        errorMessage = "Registration failed due to database error.";
                        break;
                }
                showModal('errorModal', errorMessage);
            }
        });
    </script>
</body>
</html>
