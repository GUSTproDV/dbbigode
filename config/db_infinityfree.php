<?php
// Configuração do banco de dados para InfinityFree
$servername = "sql100.infinityfree.com";
$username = "if0_40469152";
$password = "15092431"; // Substitua pela senha real
$dbname = "if0_40469152_dbbigode";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Configurar charset para UTF-8
$conn->set_charset('utf8mb4');
$conn->query("SET NAMES 'utf8mb4'");
$conn->query("SET CHARACTER SET utf8mb4");
$conn->query("SET character_set_connection=utf8mb4");

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
?>
