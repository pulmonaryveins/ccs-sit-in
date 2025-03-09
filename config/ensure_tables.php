<?php
require_once 'db_connect.php';

// Check if sit_ins table exists and create if it doesn't
$tableCheckQuery = "SHOW TABLES LIKE 'sit_ins'";
$result = $conn->query($tableCheckQuery);

if ($result->num_rows == 0) {
    // Table doesn't exist, create it
    $createTableQuery = "
    CREATE TABLE `sit_ins` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `idno` varchar(20) NOT NULL,
      `fullname` varchar(100) NOT NULL,
      `purpose` varchar(50) NOT NULL,
      `laboratory` varchar(10) NOT NULL,
      `pc_number` int(11) NOT NULL,
      `time_in` time NOT NULL,
      `time_out` time DEFAULT NULL,
      `date` date NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `status` varchar(20) NOT NULL DEFAULT 'active',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    if ($conn->query($createTableQuery) !== TRUE) {
        error_log("Error creating sit_ins table: " . $conn->error);
    }
}

?>
