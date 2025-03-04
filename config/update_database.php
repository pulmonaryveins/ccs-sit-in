<?php
require_once '../config/db_connect.php';

$sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image varchar(255) DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Profile image column added successfully";
} else {
    echo "Error adding profile image column: " . $conn->error;
}

$conn->close();
