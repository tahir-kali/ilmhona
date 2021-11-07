<?php
$servername = "localhost";
$database = "moodle";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);
mysqli_select_db($conn,$database);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

?>