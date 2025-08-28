<?php
$host = "localhost";
$user = "root";   // default in XAMPP
$pass = "";       // default empty in XAMPP
$db   = "mdm_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
