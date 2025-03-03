<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if it's admin login
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = 'admin';
        header('Location: admin_dashboard.php');
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
            header("Location: dashboard.php");
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
    <title>Login Form</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body class="auth-body">
    <div class="login-container">
        <?php
        if (isset($_GET['success']) && $_GET['success'] == 'registered') {
            echo '<div class="success-message">Registration successful! Please login.</div>';
        }
        if (isset($_GET['error']) && $_GET['error'] == 'invalid') {
            echo '<div class="error-message">Invalid username or password!</div>';
        }
        ?>
        <div class="logo-container">
            <img src="logo/uc.png" alt="UC Logo">
            <img src="logo/ccs.png" alt="CCS Logo">
        </div>
        <h2>CCS SIT-IN MONITORING SYSTEM</h2>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
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
</body>
</html>