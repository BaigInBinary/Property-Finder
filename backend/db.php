<?php
$host = 'localhost';        // or your server IP
$dbname = 'propfind';  // change to your database name
$username = 'root';         // your DB username
$password = '';             // your DB password (often empty for localhost)

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);
?>
