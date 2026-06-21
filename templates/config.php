<?php
$host = "localhost";
$user = "root";
$password = "Password1";
$database = "grade"; 

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

define('DEBUG_MODE', false); 

define('BASE_URL', '/Grade/');
