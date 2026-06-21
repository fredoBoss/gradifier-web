<?php
$host = 'localhost';       // usually 'localhost'
$dbname = 'grade'; // replace with your database name
$username = 'root';   // replace with your database username
$password = '';   // replace with your database password

// Create connection using MySQLi
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully!";
}
