<?php

$servername = "localhost";
$username = "root";
$password = ""; // Your XAMPP password, usually empty by default
$dbname = "librarry_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}