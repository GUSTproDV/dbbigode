<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'dbbigode';

$conn = new mysqli($host, $user, $pass, $dbname);

if($conn->connect_error){
    die('Error na conexâo: '. $conn->connect_error);
}

$conn->set_charset('utf8');

