<?php
// db.php - database connection (included in both pages)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "coop_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
