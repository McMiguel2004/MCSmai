<?php
$servername = "localhost";
$username = "usuario";
$password = "usuario";
$dbname = "smai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>