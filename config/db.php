
<?php

// This was changed in the hosting web application (InfinityFree)
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "SK_Management";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

date_default_timezone_set("Asia/Manila");
?>
