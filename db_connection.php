<?php

$host = "localhost:3307";
$username = "root";      
$password = "";
$database = "r_force_admin";    

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>