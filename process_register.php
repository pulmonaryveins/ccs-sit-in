<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate ID number format
        $idno = $_POST['idno'];
        if (!preg_match('/^\d{8}$/', $idno)) {
            header("Location: register.php?error=invalid_id");
            exit();
        }

        // Simple sanitization of inputs
        $username = $_POST['username'];

        // Check if ID number already exists
        $stmt = $pdo->prepare("SELECT idno FROM users WHERE idno = ?");
        $stmt->execute([$idno]);
        if ($stmt->rowCount() > 0) {
            header("Location: register.php?error=id_exists");
            exit();
        }

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            header("Location: register.php?error=username_exists");
            exit();
        }

        // If no existing user found, proceed with registration
        $lastname = $_POST['lastname'];
        $firstname = $_POST['firstname'];
        $middlename = $_POST['middlename'];
        $course = $_POST['course'];
        $year = $_POST['year'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (idno, lastname, firstname, middlename, course, year, username, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idno, $lastname, $firstname, $middlename, $course, $year, $username, $password]);

        header("Location: login.php?success=registered");
        exit();
    } catch(PDOException $e) {
        header("Location: register.php?error=database");
        exit();
    }
}
?>
