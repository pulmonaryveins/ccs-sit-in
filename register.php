<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Form</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    <script>
        function validateIDNo(input) {
            const pattern = /^\d{8}$/;
            if (!pattern.test(input.value)) {
                input.setCustomValidity('ID Number must be exactly 8 digits');
            } else {
                input.setCustomValidity('');
            }
        }
    </script>
</head>
<body class="auth-body">
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
        <div class="logo-container">
            <img src="logo/uc.png" alt="UC Logo">
            <img src="logo/ccs.png" alt="CCS Logo">
        </div>
        <h2>STUDENT REGISTRATION</h2>
        <form action="process_register.php" method="post">
            <div class="input-group">
                <input type="text" 
                       name="idno" 
                       placeholder="ID Number" 
                       pattern="\d{8}"
                       oninput="validateIDNo(this)"
                       required>
            </div>
            <div class="input-group">
                <input type="text" name="lastname" placeholder="Last Name" required>
            </div>
            <div class="input-group">
                <input type="text" name="firstname" placeholder="First Name" required>
            </div>
            <div class="input-group">
                <input type="text" name="middlename" placeholder="Middle Name">
            </div>
            <div class="input-group">
                <select name="course" required>
                    <option value="" disabled selected>Select Course / Department</option>
                    <option value="BS-Information Technology">BS-Information Technology</option>
                    <option value="BS-Computer Science">BS-Computer Science</option>
                    <option value="College of Engineering">College of Engineering</option>
                    <option value="College of Arts and Sciences">College of Arts and Sciences</option>
                    <option value="College of Hospitality Management">College of Hospitality Management</option>
                    <option value="College of Education">College of Education</option>
                    <option value="College of Customes Administration">College of Customes Administration</option>
                    <option value="College of Business and Accountancy">College of Business and Accountancy</option>
                    <option value="College of Criminal Justice">College of Criminal Justice</option>
                    <option value="College of Nursing">College of Nursing</option>
                </select>
            </div>
            <div class="input-group">
                <select name="year" required>
                    <option value="" disabled selected>Select Year Level</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
            </div>
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <input type="submit" value="Register">
        </form>
        <div class="register-text">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>
