<?php
$host = "localhost";
$user = "root"; // default in XAMPP
$pass = "";     // default in XAMPP (no password)
$db   = "crm_system";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
