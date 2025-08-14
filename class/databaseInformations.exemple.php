<?php
$host = 'IP_DE_VOTRE_BDD';
$dbname = 'tournament';
$username = 'Login';
$password = 'YourPass';



$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


?>
