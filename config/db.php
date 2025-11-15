<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'dbbigode';

$conn = new mysqli($host, $user, $pass, $dbname);

if($conn->connect_error){
    die('Error na conexâo: '. $conn->connect_error);
}

// Configurar charset para UTF-8
$conn->set_charset('utf8mb4');
mysqli_query($conn, "SET NAMES 'utf8mb4'");
mysqli_query($conn, "SET CHARACTER SET utf8mb4");
mysqli_query($conn, "SET character_set_connection=utf8mb4");

